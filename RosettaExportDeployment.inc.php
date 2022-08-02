<?php
import('classes.core.Services');
import('plugins.importexport.rosetta.classes.dc.RosettaDCDom');
import('plugins.importexport.rosetta.classes.mets.RosettaMETSDom');
import('plugins.importexport.rosetta.classes.files.RosettaFileService');
# import('lib.pkp.classes.xml.XMLCustomWriter');

class RosettaExportDeployment
{
	/** @var Context The current import/export context */
	var $_context;
	/** @var Plugin The current import/export plugin */
	var $_plugin;

	/**
	 * Constructor
	 * @param $context Context
	 * @param $plugin DOIPubIdExportPlugin
	 */
	function __construct($context, $plugin)
	{
		$this->setContext($context);
		$this->setPlugin($plugin);
	}

	// Getter/setters

	/**
	 * Return true if the zip extension is loaded.
	 * @return boolean
	 */
	static function isZipFunctioanl(): bool
	{
		return (extension_loaded('zip'));
	}


	function getSubmissions(bool $isTest = false)
	{
		$context = $this->getContext();
		$settings = $this->getPlugin()->getPluginSettings()[$this->getContext()->getLocalizedAcronym()];
		// Load DOI settings
		$submissions = Services::get('submission')->getMany([
			'contextId' => $this->_context->getId(),
			'orderBy' => 'seq',
			'orderDirection' => 'ASC',
			'status' => STATUS_PUBLISHED,
		]);
		foreach ($submissions as $submission) {
			if (is_a($submission, 'Submission')) {
				$publications = $submission->getData('publications');
				foreach ($publications as $publication) {
					if ($settings == null) {
						$this->depositSubmission($context, $submission, $publication, $isTest);
					} else {
						$issue = \Services::get('issue')->get($publication->getData('issueId'));
						foreach ($settings as $setting) {
							if (($issue->getData('number') == $setting['number'] && $issue->getData('volume') == $setting['volume'] && $issue->getData('year') == $setting['year']) || $issue == null) {
								$this->depositSubmission($context, $submission, $publication, $isTest);
							}
						}
					}

				}
			}
		}
		return $submissions;
	}

	/**op
	 * Get the import/export context.
	 * @return Context
	 */
	function getContext()
	{
		return $this->_context;
	}

	/**
	 * Set the import/export context.
	 * @param $context Context
	 */
	function setContext($context)
	{
		$this->_context = $context;
	}

	/**
	 * Get the import/export plugin.
	 * @return ImportExportPlugin
	 */
	function getPlugin(): ImportExportPlugin
	{
		return $this->_plugin;
	}

	/**
	 * Set the import/export plugin.
	 * @param $plugin ImportExportPlugin
	 */
	function setPlugin($plugin): void
	{
		$this->_plugin = $plugin;
	}

	/**
	 * @param Context $context
	 * @param Submission $submission
	 * @param Publication $publication
	 * @return void
	 */
	public function depositSubmission(Context $context, Submission $submission, Publication $publication, bool $isTest): void
	{

		$RosettaSubDirectory = $this->getPlugin()->getSetting($context->getId(), 'subDirectoryName');
		$oldMask = umask(0);

		if (is_dir($RosettaSubDirectory)) {

			$galleyFiles = RosettaFileService::getGalleyFiles($publication);
			if(count($galleyFiles) > 0 ) {

				list($INGEST_PATH, $SIP_PATH, $PUB_CONTENT_PATH, $STREAM_PATH) = $this->getSipContentPaths($context, $submission, $publication, $RosettaSubDirectory);

				if (!is_dir($SIP_PATH)) {
					if (is_dir($SIP_PATH) == false) {
						mkdir($SIP_PATH, 0777);
					}
					mkdir($PUB_CONTENT_PATH, 0777);
					mkdir($STREAM_PATH, 0777);
					$masterPath = join(DIRECTORY_SEPARATOR, array($STREAM_PATH, MASTER_PATH));

					mkdir($masterPath, 0777);
					$DC_PATH = $SIP_PATH . DIRECTORY_SEPARATOR . 'dc.xml';
					$IE_PATH = join(DIRECTORY_SEPARATOR, array($PUB_CONTENT_PATH, "ie1.xml"));

					$metsDom = new RosettaMETSDom($context, $submission, $publication, $this->getPlugin());
					file_put_contents($IE_PATH, $metsDom->saveXML(), FILE_APPEND | LOCK_EX);

					$dcDom = new RosettaDCDom($context, $publication, false);
					file_put_contents($DC_PATH, $dcDom->saveXML(), FILE_APPEND | LOCK_EX);


					list($xmlExport, $tmpExportFile) = $metsDom->appendImportExportFile();
					shell_exec('php' . " " . $_SERVER['argv'][0] . "  NativeImportExportPlugin export " . $xmlExport . " " . $_SERVER['argv'][2] . " article " . $submission->getData('id'));

					if (file_exists($xmlExport)) {
						array_push($galleyFiles, $tmpExportFile);
					}

					foreach ($galleyFiles as $file) {

						copy($file["fullFilePath"], join(DIRECTORY_SEPARATOR, array($STREAM_PATH, $file["path"], basename($file["fullFilePath"]))));
						foreach ($file["dependentFiles"] as $dependentFile) {
							copy($dependentFile["fullFilePath"], join(DIRECTORY_SEPARATOR, array($STREAM_PATH, $file["path"], basename($dependentFile["fullFilePath"]))));
						}
					}

					exec('java -jar ' . $this->getPlugin()->getPluginPath() . '/bin/xsd11-validator.jar -if ' . $IE_PATH . ' -sf ' . $this->getPlugin()->getPluginPath() . '/schema/mets_rosetta.xsd ', $validationOutPut, $validationStatus);
					if (!$isTest and $validationStatus == 0) {
						$this->doDeposit($context, $INGEST_PATH, $SIP_PATH, $submission);
						unlink($xmlExport);
					}


				}
			} else {
				var_dump("Submission ".$submission->getId()." publication object ".$publication->getId()." does not contain any galleys");
			}
		}


		umask($oldMask);
	}

	/**
	 * @param Context $context
	 * @param Submission $submission
	 * @param Publication $publication
	 * @param $RosettaSubDirectory
	 * @return array
	 */
	private function getSipContentPaths(Context $context, Submission $submission, Publication $publication, $RosettaSubDirectory): array
	{
		$ingestPath = PKPString::strtolower($context->getLocalizedAcronym()) . '-' . $submission->getId() . '-v' . $publication->getData('version');
		$sipPath = join(DIRECTORY_SEPARATOR, array($RosettaSubDirectory, $ingestPath));
		$pubContentPath = join(DIRECTORY_SEPARATOR, array($sipPath, 'content'));
		$streamsPath = join(DIRECTORY_SEPARATOR, array($pubContentPath, 'streams'));


		return array($ingestPath, $sipPath, $pubContentPath, $streamsPath);
	}

	/**
	 * @param Context $context
	 * @param string $ingestPath
	 * @param string $sipPath
	 * @param Submission $submission
	 */
	function doDeposit(Context $context, string $ingestPath, string $sipPath, Submission $submission): void
	{

		$submissionDao = DAORegistry::getDAO('SubmissionDAO');

		$endpoint = $this->getPlugin()->getSetting($context->getId(), 'rosettaHost') . 'deposit/DepositWebServices?wsdl';
		$producerId = $this->getPlugin()->getSetting($context->getId(), 'rosettaProducerId');
		$materialFlowId = $this->getPlugin()->getSetting($context->getId(), 'rosettaMaterialFlowId');
		$payload = $this->getSoapPayload($materialFlowId, $ingestPath, $producerId);


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$headers = array();
		$headers[] = 'Content-Type: text/xml';
		$headers[] = 'SoapAction: ""';
		$headers[] = 'Authorization: local ' . $this->getBase64Credentials($context);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);
		$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$sipIdNode = $this->getSoapResponeXpath($ch, $response)->query("//ser:sip_id")[0];
		$errorMessage = $this->getSoapResponeXpath($ch, $response)->query("//ser:message_code")[0];
		$sipStatus = json_decode($submission->getData($this->_plugin->getDepositStatusSettingName()), true);

		$isModifiedPublication = $sipStatus['date'] < $submission->getData('lastModified');
		$registeredDoi = $submission->getData('crossref::registeredDoi');

		if (($responseCode == 200 && !is_null($sipIdNode)) && (!isset($sipStatus) || $isModifiedPublication) && $registeredDoi) {
			$rosetta_status = array(
				'id' => $sipIdNode->nodeValue,
				'status' => true,
				'date' => Core::getCurrentDate(),
				'doi' => $registeredDoi
			);
			$submission->setData($this->_plugin->getDepositStatusSettingName(), json_encode($rosetta_status));
			$submissionDao->updateObject($submission);

			// Wait for network to finish ingestion
			sleep(30);

			$this->getPlugin()->rrmdir($sipPath);
			$this->getPlugin()->logInfo($context->getData('id') . "-" . $submission->getData('id'));

		} else if (($responseCode == 200) && !is_null($errorMessage)) {
			$rosetta_status = array(
				'id' => $sipIdNode->nodeValue,
				'status' => false,
				'date' => Core::getCurrentDate(),
				'doi' => $registeredDoi,
				'message_code' => $errorMessage->nodeValue
			);
			$submission->setData($this->_plugin->getDepositStatusSettingName(), json_encode($rosetta_status));
			$submissionDao->updateObject($submission);
		} else {
			$this->getPlugin()->logError($response);
		}


		curl_close($ch);
	}

	/**
	 * @param $materialFlowId
	 * @param string $ingestPath
	 * @param $producerId
	 * @return string
	 */
	private function getSoapPayload($materialFlowId, string $ingestPath, $producerId): string
	{
		$payload = '<?xml version="1.0" encoding="UTF-8"?>' .
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
		return $payload;
	}

	/**
	 * @param Context $context
	 * @return string
	 */
	private function getBase64Credentials(Context $context): string
	{
		$username = $this->getPlugin()->getSetting($context->getId(), 'rosettaUsername');
		$password = $this->getPlugin()->getSetting($context->getId(), 'rosettaPassword');
		$institutionCode = $this->getPlugin()->getSetting($context->getId(), 'rosettaInstitutionCode');

		$password = $username . '-institutionCode-' . $institutionCode . ':' . $password;
		$base64_credentials = base64_encode($password);
		return $base64_credentials;
	}

	/**
	 * @param $ch
	 * @param string $response
	 * @return DOMXPath
	 */
	protected function getSoapResponeXpath($ch, string $response): DOMXPath
	{
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$body = substr($response, $header_size);
		$doc = new DOMDocument();
		$doc->loadXML(html_entity_decode($body));
		$xpath = new DOMXpath($doc);
		$xpath->registerNamespace('ser', 'http://www.exlibrisgroup.com/xsd/dps/deposit/service');
		return $xpath;
	}

}
