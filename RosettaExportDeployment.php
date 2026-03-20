<?php

/**
 * @file plugins/importexport/rosetta/RosettaExportDeployment.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaExportDeployment
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta;

use APP\facades\Repo;
use APP\plugins\importexport\rosetta\classes\Constants;
use APP\plugins\importexport\rosetta\classes\files\RosettaFileService;
use APP\plugins\importexport\rosetta\classes\models\DepositActivityModel;
use APP\plugins\importexport\rosetta\classes\models\DepositStatusModel;
use APP\plugins\importexport\rosetta\classes\utilities\Utils;
use APP\plugins\importexport\rosetta\classes\xml\dublincore\RosettaDcDom;
use APP\plugins\importexport\rosetta\classes\xml\mets\RosettaMetsDom;
use APP\publication\Publication;
use APP\submission\Submission;
use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use PKP\config\Config;
use PKP\context\Context;
use PKP\core\Core;
use Psr\Http\Message\ResponseInterface;

class RosettaExportDeployment
{
	protected bool $isTest = false;
	protected Context $context;
	protected RosettaExportPlugin $plugin;
	protected string $filesDirPath = '';
	protected object $client;
	protected array $depositAcceptedStatuses = ['approved', 'finished'];
	protected array $depositRejectedStatuses = ['declined', 'deleted'];
	private string $username;
	private string $password;
	private string $institutionCode;
	private string $producerId;
	private string $materialFlowId;
	private string $subDirectory;
	private string $host;

	public function __construct(RosettaExportPlugin $plugin, Context $context)
	{
		$this->context = $context;
		$this->plugin = $plugin;
		$this->filesDirPath = Config::getVar('files', 'files_dir');

		$this->username = $this->plugin->getSetting($this->context->getId(), 'rosettaUsername');
		$this->password = $this->plugin->getSetting($this->context->getId(), 'rosettaPassword');
		$this->institutionCode = $this->plugin->getSetting($this->context->getId(), 'rosettaInstitutionCode');
		$this->producerId = $this->plugin->getSetting($this->context->getId(), 'rosettaProducerId');
		$this->materialFlowId = $this->plugin->getSetting($this->context->getId(), 'rosettaMaterialFlowId');
		$this->host = $this->plugin->getSetting($this->context->getId(), 'rosettaHost');
		$this->subDirectory = $this->plugin->getSetting($this->context->getId(), 'subDirectoryName');

		$isTest = $this->plugin->getSetting($this->context->getId(), 'testMode');
		if (!empty($isTest)) $this->isTest = (bool)$isTest;

		$this->client = new Client([
			'headers' => ['User-Agent' => Constants::USER_AGENT],
			'verify' => false
		]);
	}

	public function process(): void
	{
		// Check if the folder is mounted and return if not.
		if (!is_dir($this->subDirectory)) {
			Utils::logError('The Rosetta drive ' . $this->subDirectory . ' is not mounted');
			return;
		}

		// Update the database with the latest data from the Rosetta server.
		$this->updateIsDeposited();

		$localizedAcronymLowerCase = strtolower($this->context->getLocalizedAcronym());
		$currentContextSettings = Constants::getContextSettings()[$localizedAcronymLowerCase];

		// Retrieve published submissions based on specific criteria.
		$submissions = $this->getPublishedSubmissions();

		// Iterate through the retrieved submissions.
		foreach ($submissions as $submission) {
			if (is_a($submission, 'Submission')) {

				Utils::logInfo('Submission being processed: ' . $submission->getData('id'));

				// Skip if production and there is no DOI for the submission.
				$publications = $submission->getData('publications');

				// Iterate through associated publications.
				foreach ($publications as $publication) {

					$galleyFiles = RosettaFileService::getGalleyFiles($publication);

					$galleyFileMissing = false;
					foreach ($galleyFiles as $galleyFile) {

						$fileFullPath = $this->filesDirPath . DIRECTORY_SEPARATOR . $galleyFile['fullFilePath'];
						if (!file_exists($fileFullPath)) {
							$galleyFileMissing = true;
						}
					}

					// Skip if test and publication DOI is empty and no gallery files
					if ($publication->getStoredPubId('doi') == null && count($galleyFiles) == 0 || $galleyFileMissing) {
						continue;
					}

					// Skip based on deposit status and on deposit activity status.
					$depositStatus = new DepositStatusModel(
						json_decode($publication->getData(Constants::DEPOSIT_STATUS_SETTING_NAME), true));
					$depositActivity = new DepositActivityModel(
						json_decode($publication->getData(Constants::DEPOSIT_ACTIVITY_SETTING_NAME), true));

					// Decide if this publication should be skipped or deposited
					if ($depositStatus->status) {
						// Log if publication modified date is before deposit date.
						if ($depositStatus->date >= $publication->getData('lastModified')) {
							Utils::logInfo('Publication has changed after deposit > ' .
								'submission:' . $submission->getId() . '|publication:' . $publication->getId());
						}

						// Skip if deposit activity status is not empty and is not declined nor deleted.
						if (!empty($depositActivity->status) &&
							!in_array(
								strtolower($depositActivity->status),
								$this->depositRejectedStatuses, true)) {
							continue;
						}
						// skip if deposit entry is present.
						continue;
					}

					// Deposit the publication to Rosetta based on specified settings.
					if ($currentContextSettings == null) {
						$this->depositPublication($submission, $publication, $galleyFiles);
					} else {
						$issue = Repo::issue()->get($publication->getData('issueId'));
						foreach ($currentContextSettings as $setting) {
							if ($issue->getData('volume') == $setting['volume'] && $issue->getData('year') == $setting['year']) {
								$this->depositPublication($submission, $publication, $galleyFiles);
							}
						}
					}
				}
			}
		}
	}

	private function updateIsDeposited(): void
	{
		$depositedArticles = $this->getDepositsFromRosettaApi();
		$results = $this->logDeposits($depositedArticles);
		error_log($results, 3, '/tmp/rosetta-api.csv');

		foreach ($depositedArticles as $key => $value) {
			// Create a DepositActivityModel instance from the Rosetta API data.
			$row = new DepositActivityModel($value);

			// Extract submission ID from the subdirectory field.
			$subdirectory = explode('-', $row->subdirectory);
			$submissionId = $subdirectory[count($subdirectory) - 2];

			// Retrieve the submission object based on the extracted submission ID.
			$submission = Repo::submission()->get($submissionId);

			if (is_a($submission, 'Submission')) {
				// Get the list of publications associated with the submission.
				$publications = $submission->getData('publications');

				foreach ($publications as $publication) {
					// Check if the subdirectory matches the publication's version.
					if ($subdirectory[count($subdirectory) - 1] === 'v' . $publication->getData('version')) {
						// Update the publication's deposit activity data and save it to the database.
						$publication->setData(Constants::DEPOSIT_ACTIVITY_SETTING_NAME, json_encode($row));
						Repo::publication()->edit($publication, []);
					}
				}
			}
		}
	}

	private function getDepositsFromRosettaApi(int $offset = 0): array
	{
		$deposits = []; // Array to store deposit activity data

		// Get the deposit endpoint
		$params = [
			'producer' => $this->producerId,
			'material_flow' => $this->materialFlowId,
			'creation_date_from' => date('d/m/Y', strtotime('-' . Constants::DEPOSIT_HISTORY_IN_DAYS . ' days')),
			'creation_date_to' => date('d/m/Y', strtotime('+1 days')),
			'offset' => $offset
		];
		$endpoint = $this->getDepositEndpoint('rest') . '?' . http_build_query($params);

		// Define the HTTP headers
		$headers = [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'Authorization' => 'local ' . $this->getBase64Credentials(),
			'accept-encoding' => 'gzip, deflate'
		];

		try {
			// Make a GET request to the Rosetta API with the specified parameters.
			$response = $this->apiRequest($endpoint, $headers);

			if ($response->getStatusCode() === 200) {
				$body = json_decode($response->getBody(), true);

				// Check if there are more records to fetch (pagination).
				if ($body['total_record_count'] > 0) {
					$deposits = array_merge($deposits, $this->getDepositsFromRosettaApi($offset + 1));
				}

				// Merge the fetched deposit records into the result array.
				if (!empty($body['deposit'])) {
					$deposits = array_merge($deposits, $body['deposit']);
				}
			}
		} catch (Exception $e) {
			Utils::logError($e->getMessage());
		}

		// If offset is 0, sort and log the total record count.
		if ($offset === 0) {
			$local = [];
			foreach ($deposits as $row) {
				$local[$row['id']] = $row;
			}
			ksort($local);
			$deposits = $local;
		}
		return $deposits;
	}

	private function getDepositEndpoint(string $apiType = ''): string
	{
		$protocol = explode(':', $this->host)[0]; // Extract the protocol (e.g., 'https')

		// Extract the host parts after removing the protocol.
		$hostParts = explode('/', str_replace($protocol . '://', '', $this->host));

		return match ($apiType) {
			'soap' => $protocol . '://' . $hostParts[0] . '/dpsws/deposit/DepositWebServices?wsdl',
			default => $protocol . '://' . $hostParts[0] . '/rest/v0/sips'
		};
	}

	private function getBase64Credentials(): string
	{
		return base64_encode($this->username . '-institutionCode-' . $this->institutionCode . ':' . $this->password);
	}

	public function getPlugin(): RosettaExportPlugin
	{
		return $this->plugin;
	}

	private function depositPublication(Submission $submission, Publication $publication, array $galleyFiles): void
	{
		$oldMask = umask(0);

		$INGEST_PATH = strtolower(
				$this->context->getLocalizedAcronym()) . '-' .
			$submission->getId() .
			'-v' . $publication->getData('version');
		$SIP_PATH = join(DIRECTORY_SEPARATOR, array($this->subDirectory, $INGEST_PATH));
		$PUB_CONTENT_PATH = join(DIRECTORY_SEPARATOR, array($SIP_PATH, 'content'));
		$STREAM_PATH = join(DIRECTORY_SEPARATOR, array($PUB_CONTENT_PATH, 'streams'));
		$IE_PATH = join(DIRECTORY_SEPARATOR, array($PUB_CONTENT_PATH, 'ie1.xml'));
		$DC_PATH = $SIP_PATH . DIRECTORY_SEPARATOR . 'dc.xml';
		$MASTER_PATH = join(DIRECTORY_SEPARATOR, array($STREAM_PATH, MASTER_PATH));

		if (!is_dir($SIP_PATH)) mkdir($SIP_PATH, 0777);
		if (!is_dir($PUB_CONTENT_PATH)) mkdir($PUB_CONTENT_PATH, 0777);
		if (!is_dir($STREAM_PATH)) mkdir($STREAM_PATH, 0777);
		if (!is_dir($MASTER_PATH)) mkdir($MASTER_PATH, 0777);

		$metsDom = new RosettaMetsDom($this->context, $submission, $publication, $this->plugin);
		file_put_contents($IE_PATH, $metsDom->saveXML(), LOCK_EX);

		$dcDom = new RosettaDcDom($this->context, $publication, $submission, false);
		file_put_contents($DC_PATH, $dcDom->saveXML(), LOCK_EX);
		$failedFiles = [];

		foreach ($galleyFiles as $file) {
			$sourceFilePath = $this->filesDirPath . DIRECTORY_SEPARATOR . $file['fullFilePath'];
			if (!file_exists($sourceFilePath)) {
				$failedFiles [] = $file['fullFilePath'];
				continue;
			}
			if (!copy(
				$sourceFilePath,
				join(DIRECTORY_SEPARATOR,
					array($STREAM_PATH, $file['path'], basename($file['fullFilePath']))))) {
				$failedFiles[] = $file['fullFilePath'];
			}

			foreach ($file['dependentFiles'] as $dependentFile) {
				if (!copy(
					$this->filesDirPath . DIRECTORY_SEPARATOR . $dependentFile['fullFilePath'],
					join(DIRECTORY_SEPARATOR,
						array($STREAM_PATH, $file['path'], basename($dependentFile['fullFilePath']))))) {
					$failedFiles[] = $dependentFile['fullFilePath'];
				}
			}
		}

		Utils::setPermissionsRecursively($STREAM_PATH, 0775);

		if (count($failedFiles) > 0) {
			foreach ($failedFiles as $failedFile) {
				Utils::logError('Copy of file failed for ' . $failedFile);
			}
		}

		exec('java -jar ' . escapeshellarg($this->plugin->getPluginPath() . '/bin/xsd11-validator.jar') . ' ' .
			'-if ' . escapeshellarg($IE_PATH) . ' ' .
			'-sf ' . escapeshellarg($this->plugin->getPluginPath() . '/schema/mets_rosetta.xsd'),
			$validationOutPut,
			$validationStatus);

		if ($validationStatus !== 0) {
			Utils::logError('XML validation failed for ' . $IE_PATH . ': ' . implode("\n", $validationOutPut));
		}

		if (!$this->isTest && $validationStatus === 0 && count($failedFiles) === 0) {
			$this->doDeposit($INGEST_PATH, $publication);
			Utils::removeDirRecursively($SIP_PATH);
		}

		umask($oldMask);
	}

	private function doDeposit(string $ingestPath, Publication $publication): void
	{
		// Get the deposit endpoint and SOAP payload
		$endpoint = $this->getDepositEndpoint('soap');
		$payload = $this->getSoapPayload($this->materialFlowId, $ingestPath, $this->producerId);

		// Define the HTTP headers
		$headers = [
			'Content-Type' => 'text/xml',
			'SoapAction' => '""',
			'Authorization' => 'local ' . $this->getBase64Credentials(),
		];

		try {
			// Send the HTTP POST request
			$response = $this->client->post($endpoint, ['headers' => $headers, 'body' => $payload,]);
			$responseCode = $response->getStatusCode();
			$responseBody = $response->getBody()->getContents();

			// Extract relevant data from the SOAP response
			$sipIdNode = $this->getSoapResponseXpath($responseBody)->query('//ser:sip_id')[0];
			$errorMessage = $this->getSoapResponseXpath($responseBody)->query('//ser:message_code')[0];

			// Handle error messages if present
			if (!empty($errorMessage)) {
				Utils::logError($errorMessage);
			}

			// Create a deposit status model
			$depositStatus = new DepositStatusModel();

			// Check if the deposit was successful
			if ($this->isTest || ($responseCode == 200 && !is_null($sipIdNode))) {
				$depositStatus->id = $sipIdNode->nodeValue;
				$depositStatus->status = true;
				$depositStatus->date = Core::getCurrentDate();
				if (!empty($publication->getStoredPubId('doi')))
					$depositStatus->doi = $publication->getStoredPubId('doi');

				// Wait for network to finish ingestion (adjust sleep time as needed)
				sleep(120);

				// Log deposit information
				Utils::logInfo($this->context->getData('id') . '-' . $publication->getData('id'));
			} else {
				// Handle deposit failure
				$depositStatus->id = '';
				$depositStatus->status = false;
				$depositStatus->date = '';
				$depositStatus->doi = '';

				// Log the response in case of an error
				Utils::logError($responseBody);
			}

			// Update the publication object with deposit status
			$publication->setData(Constants::DEPOSIT_STATUS_SETTING_NAME, json_encode($depositStatus));
			Repo::publication()->edit($publication, []);

		} catch (Exception $e) {
			Utils::logError($e->getMessage());
		}
	}

	private function getSoapPayload(string $materialFlowId, string $ingestPath, string $producerId): string
	{
		return
			'<?xml version="1.0" encoding="UTF-8"?>' .
			'<soap:Envelope' .
			'	soap:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"' .
			'	xmlns:dbs="http://dps.exlibris.com/"' .
			'	xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"' .
			'	xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"' .
			'	xmlns:xsd="http://www.w3.org/2001/XMLSchema"' .
			'	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
			'  <soap:Body>' .
			'	<dbs:submitDepositActivity>' .
			'	  <arg1>' . $materialFlowId . '</arg1>' .
			'	  <arg2>' . $ingestPath . '</arg2>' .
			'	  <arg3>' . $producerId . '</arg3>' .
			'	</dbs:submitDepositActivity>' .
			'  </soap:Body>' .
			'</soap:Envelope>';
	}

	protected function getSoapResponseXpath(string $response): DOMXPath
	{
		// Create a new DOMDocument and load the XML response.
		$doc = new DOMDocument();
		$doc->loadXML(html_entity_decode($response));

		// Create a DOMXPath object for querying the XML.
		$xpath = new DOMXpath($doc);

		// Register the 'ser' namespace for use in XPath queries.
		$xpath->registerNamespace('ser', 'http://www.exlibrisgroup.com/xsd/dps/deposit/service');

		return $xpath;
	}

	public function getPublishedSubmissions(): mixed
	{
		return Repo::submission()->getCollector()
			->filterByContextIds([$this->context->getId()])
			->filterByStatus([Submission::STATUS_PUBLISHED])
			->getMany();
	}

	public function apiRequest(string $endpoint, array $headers): ResponseInterface
	{
		return $this->client->get($endpoint, ['headers' => $headers]);
	}

	public function logDeposits(array $deposits): string
	{
		$result = '';
		$result_head_once = true;
		foreach ($deposits as $deposit) {
			foreach (array_values($deposit) as $value) {
				if (gettype($value) == 'array') {
					$result .= '|' . implode(' |', $value);
				} else {
					$result .= '|' . $value;
				}

			}
			$result .= PHP_EOL;
		}
		return $result;
	}
}
