<?php
/**
 * @file plugins/importexport/rosetta/RosettaExportDeployment.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RosettaExportDeployment
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief Class responsible for depositing publications to Rosetta.
 *
 * The `RosettaExportDeployment` class is responsible for automating the deposit of publications into the Rosetta system.
 * It processes and deposits publications that meet specific criteria, such as approved status and age.
 *
 * @property bool $isTest A flag indicating whether the system is in test mode.
 * @property Context $context The journal context for which publications are being deposited.
 * @property RosettaExportPlugin $plugin The instance of the Rosetta export plugin.
 * @property object $client A Guzzle HTTP client object used for making HTTP requests to Rosetta.
 * @property array|string[] $depositAcceptedStatuses An array of deposit status values that are considered accepted.
 * @property array|string[] $depositRejectedStatuses An array of deposit status values that are considered rejected.
 * @property string $username The username for Rosetta API authentication.
 * @property string $password The password for Rosetta API authentication.
 * @property string $institutionCode The institution code for Rosetta API authentication.
 * @property string $producerId The producer ID for Rosetta API authentication.
 * @property string $materialFlowId The material flow ID for Rosetta API authentication.
 * @property string $subDirectory The mounted directory to the Rosetta server.
 * @property string $host The host address of the Rosetta server.
 *
 * @note This class automates the deposit of publications to the Rosetta system based on specific criteria.
 *
 * @see RosettaExportDeployment::__construct()
 * @see RosettaExportDeployment::process()
 * @see RosettaExportDeployment::depositPublication()
 */

namespace TIBHannover\Rosetta;

import('classes.core.Services');
import('plugins.importexport.rosetta.classes.dc.RosettaDCDom');
import('plugins.importexport.rosetta.classes.mets.RosettaMETSDom');
import('plugins.importexport.rosetta.classes.files.RosettaFileService');
import('plugins.importexport.rosetta.classes.models.DepositActivityModel');
import('plugins.importexport.rosetta.classes.models.DepositStatusModel');

use Context;
use Core;
use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use PKPString;
use Publication;
use PublicationDAO;
use TIBHannover\Rosetta\Dc\RosettaDCDom;
use RosettaExportPlugin;
use TIBHannover\Rosetta\Files\RosettaFileService;
use TIBHannover\Rosetta\Mets\RosettaMETSDom;
use Services;
use Submission;
use SubmissionDAO;
use TIBHannover\Rosetta\Models\DepositActivityModel;
use TIBHannover\Rosetta\Models\DepositStatusModel;

class RosettaExportDeployment
{
	protected bool $isTest = false;
	protected Context $context;
	protected RosettaExportPlugin $plugin;
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

	/**
	 * Constructor
	 *
	 * @param Context $context The journal context.
	 * @param RosettaExportPlugin $plugin The Rosetta export plugin instance.
	 */
	function __construct(Context $context, RosettaExportPlugin $plugin)
	{
		$this->context = $context;
		$this->plugin = $plugin;

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
			'headers' => ['User-Agent' => $this->plugin->userAgent],
			'verify' => false
		]);
	}

	/**
	 * Process submissions and deposit publications based on specified conditions.
	 *
	 * Steps:
	 *  1. Check if the specified subdirectory exists using `is_dir`.
	 *     - If not, log an error and exit the process.
	 *  2. Update the database with the latest data from the Rosetta server.
	 *  3. Retrieve plugin settings for the current context.
	 *  4. Retrieve submissions based on specified criteria .
	 *  5. Iterate through the retrieved submissions.:
	 *     a. Log information about the submission being processed.
	 *     b. Skip processing if it's a test and submission DOI is empty.
	 *     c. Retrieve the publications associated with the submission.
	 *     d. Iterate over each publication:
	 *        - Skip processing if it's a test and stored DOI is empty.
	 *        - Check depositStatus and depositActivity:
	 *          - If deposit status is true and the date is later than the last modified date of the publication:
	 *            - Log information about the publication change after deposit.
	 *          - Skip iteration if deposit activity status is not empty and not in the list of rejected statuses.
	 *        - Deposit the publication based on settings or all if settings are null.
	 *
	 * @return void
	 */
	public function process(): void
	{
		// Check if the folder is mounted and return if not.
		if (!is_dir($this->subDirectory)) {
			$this->plugin->logError('The Rosetta drive ' . $this->subDirectory . ' is not mounted');
			return;
		}

		// Update the database with the latest data from the Rosetta server.
		$this->updateIsDeposited();

		// Retrieve plugin settings for the current context.
		$settings = $this->plugin->pluginSettings[$this->context->getLocalizedAcronym()];

		// Retrieve published submissions based on specific criteria.
		$submissions = Services::get('submission')->getMany([
			'contextId' => $this->context->getId(),
			'orderBy' => 'seq',
			'orderDirection' => 'ASC',
			'status' => STATUS_PUBLISHED,
		]);

		// Iterate through the retrieved submissions.
		foreach ($submissions as $submission) {
			if (is_a($submission, 'Submission')) {

				// Log information about the submission being processed.
				$this->plugin->logInfo('Submission being processed: ' . $submission->getData('id'));

				// Skip if production and there is no DOI for the submission.
				//if (!$this->isTest && empty($submission->getData($this->plugin->registeredDoiSettingName))) {
				//    continue;
				//}

				// Retrieve publications for this submission
				$publications = $submission->getData('publications');
				var_dump($submission->getData('id'));

				// Iterate through associated publications.
				foreach ($publications as $publication) {
					var_dump($publication->getData('id'));
					$galleyFiles = RosettaFileService::getGalleyFiles($publication);

					$galleyFileMissing = false;
					foreach ($galleyFiles as $galleyFile) {
						$fileFullPath = $this->getPlugin()->getBasePath() . DIRECTORY_SEPARATOR . $galleyFile['fullFilePath'];
						if (!file_exists($fileFullPath)) {
							$galleyFileMissing = true;
						} else {
							var_dump('File ' . $fileFullPath . ' does not exist');
						}
					}

					// Skip if test and publication DOI is empty and no gallery files
					if ($publication->getStoredPubId('doi') == null && count($galleyFiles) == 0 && $galleyFileMissing) {

						continue;
					}

					// Skip based on deposit status and on deposit activity status.
					$depositStatus = new DepositStatusModel(
						json_decode($publication->getData($this->plugin->depositStatusSettingName), true));
					$depositActivity = new DepositActivityModel(
						json_decode($publication->getData($this->plugin->depositActivitySettingName), true));

					// Decide if this publication should be skipped or deposited
					if ($depositStatus->status) {
						// Log if publication modified date is before deposit date.
						if ($depositStatus->date >= $publication->getData('lastModified')) {
							$this->plugin->logInfo('Publication has changed after deposit > ' .
								'submission:' . $submission->getId() . '|publication:' . $publication->getId());
						}

						// Skip if deposit activity status is not empty and is not declined nor deleted.
						if (!empty($depositActivity->status) &&
							!in_array(
								strtolower($depositActivity->status),
								$this->depositRejectedStatuses, true)) {
							continue;
						}
					}


					// Deposit the publication to Rosetta based on specified settings.
					if ($settings == null) {
						$this->depositPublication($submission, $publication, $galleyFiles);
					} else {
						$issue = Services::get('issue')->get($publication->getData('issueId'));
						foreach ($settings as $setting) {
							if (($issue->getData('number') == $setting['number'] &&
								$issue->getData('volume') == $setting['volume'] &&
								$issue->getData('year') == $setting['year']) /* || $issue == null */) {
								$this->depositPublication($submission, $publication, $galleyFiles);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Update the deposited statuses of publications based on Rosetta API data.
	 *
	 * This method retrieves deposit activity information from the Rosetta API and updates the deposit status
	 * of corresponding publications in the system. It associates the deposit activity data with the respective
	 * publications and updates their records in the database.
	 *
	 * @return void
	 */
	private function updateIsDeposited(): void
	{
		foreach ($this->getDepositsFromRosettaApi() as $key => $value) {
			// Create a DepositActivityModel instance from the Rosetta API data.
			$row = new DepositActivityModel($value);

			// Extract submission ID from the subdirectory field.
			$subdirectory = explode('-', $row->subdirectory);
			$submissionId = $subdirectory[count($subdirectory) - 2];

			// Retrieve the submission object based on the extracted submission ID.
			$submissionDao = new SubmissionDAO();
			$submission = $submissionDao->getById($submissionId);

			if (is_a($submission, 'Submission')) {
				// Get the list of publications associated with the submission.
				$publications = $submission->getData('publications');

				foreach ($publications as $publication) {
					// Check if the subdirectory matches the publication's version.
					if ($subdirectory[count($subdirectory) - 1] === 'v' . $publication->getData('version')) {
						// Update the publication's deposit activity data and save it to the database.
						$publication->setData($this->plugin->depositActivitySettingName, json_encode($row));
						$publicationDao = new PublicationDAO();
						$publicationDao->updateObject($publication);
					}
				}
			}
		}
	}

	/**
	 * Retrieve deposit activity data from the Rosetta API.
	 *
	 * This method makes HTTP requests to the Rosetta API to fetch deposit activity data based on specified parameters.
	 * It supports pagination by using an offset and retrieves a batch of deposit records at a time.
	 *
	 * @param int $offset The offset for pagination e.g. 0 (default), 1, 2, ...
	 *
	 * @return array An array containing deposit activity data obtained from the Rosetta API.
	 */
	private function getDepositsFromRosettaApi(int $offset = 0): array
	{
		$deposits = []; // Array to store deposit activity data

		// Get the deposit endpoint
		$params = [
			'producer' => $this->producerId,
			'material_flow' => $this->materialFlowId,
			'creation_date_from' => date('d/m/Y', strtotime('-' . $this->plugin->depositHistoryInDays . ' days')),
			'creation_date_to' => date('d/m/Y'),
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
			$response = $this->client->get($endpoint, ['headers' => $headers]);

			if ($response->getStatusCode() === 200) {
				$body = json_decode($response->getBody(), true);

				// Check if there are more records to fetch (pagination).
				if ($body['total_record_count'] >= 100) {
					$deposits = array_merge($deposits, $this->getDepositsFromRosettaApi($offset + 1));
				}

				// Merge the fetched deposit records into the result array.
				if (!empty($body['deposit'])) {
					$deposits = array_merge($deposits, $body['deposit']);
				}
			}
		} catch (Exception $e) {
			$this->plugin->logError($e->getMessage());
		}

		// If offset is 0, sort and log the total record count.
		if ($offset === 0) {
			$local = [];
			foreach ($deposits as $row) {
				$local[$row['id']] = $row;
			}
			ksort($local);
			$deposits = $local;

			var_dump('total_record_count:' . count($deposits));
		}

		return $deposits;
	}

	/**
	 * Generate the endpoint URL for Rosetta deposits based on the API type.
	 *
	 * This method constructs the endpoint URL for making deposits to Rosetta. It supports both SOAP and REST APIs
	 * by specifying the API type. The constructed URL is based on the provided host and protocol.
	 *
	 * @param string $apiType The type of the Rosetta API, either soap or rest (default).
	 *
	 * @return string The generated endpoint URL for Rosetta deposits.
	 */
	private function getDepositEndpoint(string $apiType = ''): string
	{
		$protocol = explode(':', $this->host)[0]; // Extract the protocol (e.g., 'https')

		// Extract the host parts after removing the protocol.
		$hostParts = explode('/',
			str_replace($protocol . '://', '', $this->host));

		return match ($apiType) {
			'soap' => $protocol . '://' . $hostParts[0] . '/dpsws/deposit/DepositWebServices?wsdl',
			default => $protocol . '://' . $hostParts[0] . '/rest/v0/deposits',
		};
	}

	/**
	 * Generate Base64-encoded credentials for Rosetta API authentication.
	 *
	 * This method constructs a Base64-encoded string containing the username, institution code,
	 * and password for authenticating with the Rosetta API.
	 *
	 * @return string The Base64-encoded credentials string.
	 */
	private function getBase64Credentials(): string
	{
		return base64_encode($this->username . '-institutionCode-' . $this->institutionCode . ':' . $this->password);
	}

	/**
	 * Deposit a publication to Rosetta.
	 *
	 * This method deposits a single publication to Rosetta.
	 *
	 * @param Submission $submission The submission object.
	 * @param Publication $publication The publication object.
	 *
	 * @return void
	 */
	private function depositPublication(Submission $submission, Publication $publication, array $galleyFiles): void
	{
		$oldMask = umask(0);


		$INGEST_PATH = PKPString::strtolower(
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

		$metsDom = new RosettaMETSDom($this->context, $submission, $publication, $this->plugin);
		file_put_contents($IE_PATH, $metsDom->saveXML(), LOCK_EX);

		$dcDom = new RosettaDCDom($this->context, $publication, $submission, false);
		file_put_contents($DC_PATH, $dcDom->saveXML(), LOCK_EX);
		//TODO remove this
		/**
		list($xmlExport, $tmpExportFile) = $metsDom->appendImportExportFile();
		shell_exec('php' . ' ' . $_SERVER['argv'][0] . '  NativeImportExportPlugin export ' .
			$xmlExport . ' ' . $_SERVER['argv'][2] . ' article ' . $submission->getData('id'));
		if (file_exists($xmlExport)) $galleyFiles[] = $tmpExportFile;
		*/

		$failedFiles = [];

		foreach ($galleyFiles as $file) {
			$copySuccess = copy(
				$this->plugin->getBasePath() . DIRECTORY_SEPARATOR . $file['fullFilePath'],
				join(DIRECTORY_SEPARATOR,
					array($STREAM_PATH, $file['path'], basename($file['fullFilePath']))));

			if (!$copySuccess)
				$failedFiles [] = $file['fullFilePath'];

			foreach ($file['dependentFiles'] as $dependentFile) {
				$copySuccess = copy(
					$this->plugin->getBasePath() . DIRECTORY_SEPARATOR . $dependentFile['fullFilePath'],
					join(DIRECTORY_SEPARATOR,
						array($STREAM_PATH, $file['path'], basename($dependentFile['fullFilePath']))));

				if (!$copySuccess)
					$failedFiles [] = $this->plugin->getBasePath() . DIRECTORY_SEPARATOR . $dependentFile['fullFilePath'];
			}
		}

		// change permissions of stream path recursively
		$this->plugin->setPermissionsRecursively($STREAM_PATH, 0777);

		if (count($failedFiles) > 0) {
			foreach ($failedFiles as $failedFile) {
				var_dump('Copy of file failed for ' . $failedFile);
			}
		}

		// Run validation
		exec('java -jar ' . $this->plugin->getPluginPath() . '/bin/xsd11-validator.jar ' .
			'-if ' . $IE_PATH . ' ' .
			'-sf ' . $this->plugin->getPluginPath() . '/schema/mets_rosetta.xsd ',
			$validationOutPut,
			$validationStatus);

		// if not testMode and validated
		if (!$this->isTest and $validationStatus == 0 && count($failedFiles) == 0) {
			$this->doDeposit($INGEST_PATH, $publication);
			//TODO unlink($xmlExport);
			$this->plugin->removeDirRecursively($SIP_PATH);
		}


		umask($oldMask);
	}

	/**
	 * Deposit a publication to Rosetta.
	 *
	 * This method deposits a single publication to Rosetta.
	 *
	 * @param string $ingestPath The ingest path for the publication.
	 * @param Publication $publication The publication object.
	 *
	 * @return void
	 */
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
				$this->plugin->logError($errorMessage);
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
				sleep(30);

				// Log deposit information
				$this->plugin->logInfo($this->context->getData('id') . '-' . $publication->getData('id'));

				var_dump($this->context->getData('id') . '-' . $publication->getData('id'));
			} else {
				// Handle deposit failure
				$depositStatus->id = '';
				$depositStatus->status = false;
				$depositStatus->date = '';
				$depositStatus->doi = '';

				// Log the response in case of an error
				$this->plugin->logError($responseBody);
			}

			// Update the publication object with deposit status
			$publicationDao = new PublicationDAO();
			$publication->setData($this->plugin->depositStatusSettingName, json_encode($depositStatus));
			$publicationDao->updateObject($publication);

		} catch (Exception $e) {
			$this->plugin->logError($e->getMessage());
		}
	}

	/**
	 * Generate SOAP payload for depositing a publication to Rosetta.
	 *
	 * This method constructs a SOAP XML payload with the necessary information for depositing a publication to Rosetta.
	 *
	 * @param string $materialFlowId The material flow ID associated with the publication.
	 * @param string $ingestPath The ingest path for the publication.
	 * @param string $producerId The producer ID for the publication.
	 *
	 * @return string The generated SOAP payload as a string.
	 */
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

	/**
	 * Creates and returns a DOMXPath object for querying the XML response obtained from a SOAP request.
	 *
	 * This method loads the provided XML response into a DOMDocument and creates a DOMXPath object
	 * for performing XPath queries on the XML data. It also registers a namespace 'ser' for use in XPath queries.
	 *
	 * @param string $response The SOAP response as a string.
	 *
	 * @return DOMXPath The DOMXPath object containing the parsed SOAP response.
	 */
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
	public function getPlugin(): RosettaExportPlugin
	{
		return $this->plugin;
	}
}
