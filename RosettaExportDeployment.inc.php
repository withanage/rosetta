<?php
import('classes.core.Services');
import('plugins.importexport.rosetta.classes.dc.RosettaDCDom');
import('plugins.importexport.rosetta.classes.RosettaMETSDom');
import('plugins.importexport.rosetta.classes.files.RosettaFileService');
import('lib.pkp.classes.xml.XMLCustomWriter');
const ROSETTA_STATUS_DEPOSITED = 'deposited';
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

	/**
	 * Deploy all articles
	 */
	function depositSubmissions()
	{
		$context = $this->getContext();
		// Load DOI settings
		PluginRegistry::loadCategory('pubIds', true, $context->getId());
		$notDepositedArticles = $this->_plugin->getUnregisteredArticles($context);
		foreach ($notDepositedArticles as $submission) {
			if (is_a($submission, 'Submission')) {
				foreach ($submission->getData('publications') as $publication) {
					$this->depositSubmission($context, $submission, $publication);
				}
			}
		}
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
	 * @param string $ingestPath
	 * @param string $sipPath
	 * @param Submission $submission
	 */
	function doRequest(Context $context, string $ingestPath, string $sipPath, Submission $submission): void
	{

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
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$sipIdNode = $this->getResponseQueryPath($ch, $response)->query("//ser:sip_id")[0];

		if ($response_code == 200 && !is_null($sipIdNode)) {

			$submissionDao = DAORegistry::getDAO('SubmissionDAO');
			$submission->setData('dateUpdated', Core::getCurrentDate());
			$submission->setData($this->_plugin->getDepositStatusSettingName(), $sipIdNode->nodeValue);
			$submissionDao->updateObject($submission);

			// Wait for network to finish ingestion
			sleep(30);

			$this->getPlugin()->rrmdir($sipPath);
			$this->getPlugin()->logInfo($context->getData('id') . "-" . $submission->getData('id'));

		} else $this->getPlugin()->logError($response);

		curl_close($ch);
	}

	/**
	 * @param $ch
	 * @param $response
	 * @return DOMElement
	 */
	protected function getResponseQueryPath($ch, $response): DOMXPath
	{
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$body = substr($response, $header_size);
		$doc = new DOMDocument();
		$doc->loadXML(html_entity_decode($body));
		$xpath = new DOMXpath($doc);
		$xpath->registerNamespace('ser', 'http://www.exlibrisgroup.com/xsd/dps/deposit/service');
		return $xpath;
	}

	/**
	 * @param Context $context
	 * @param Submission $submission
	 * @param Publication $publication
	 * @return void
	 */
	private function depositSubmission(Context $context, Submission $submission, Publication $publication): void
	{
		$subDirectoryName = $this->getPlugin()->getSetting($context->getId(), 'subDirectoryName');
		$oldmask = umask(0);
		if (is_dir($subDirectoryName)) {
			$ingestPath = PKPString::strtolower($context->getLocalizedAcronym()) . '-' . $submission->getId() . '-v' . $publication->getData('version');
			$sipPath = $subDirectoryName . '/' . $ingestPath;
			if (is_dir($sipPath) == false) {
				mkdir($sipPath, 0777);
				$dcDom = new RosettaDCDom($context, $publication, false);
				file_put_contents($sipPath . DIRECTORY_SEPARATOR . 'dc.xml', $dcDom->saveXML(), FILE_APPEND | LOCK_EX);
				$pubContentPath = join(DIRECTORY_SEPARATOR, array($sipPath, 'content'));
				if (is_dir($pubContentPath) == false) {
					mkdir($pubContentPath, 0777);

					$metsDom = new RosettaMETSDom($context, $submission, $publication, $this->getPlugin());
					file_put_contents(join(DIRECTORY_SEPARATOR, array($pubContentPath, "ie1.xml")), $metsDom->saveXML(), FILE_APPEND | LOCK_EX);
					// Add dependent files
					$streamsPath = join(DIRECTORY_SEPARATOR, array($pubContentPath, 'streams'));
					list($xmlExport, $exportFile) = $metsDom->appendImportExportFile();
					shell_exec('php' . " " . $_SERVER['argv'][0] . "  NativeImportExportPlugin export " . $xmlExport . " " . $_SERVER['argv'][2] . " article " . $submission->getData('id'));

					$galleyFiles = RosettaFileService::getGalleyFiles($publication);
					if (file_exists($xmlExport)) {
						array_push($galleyFiles, $exportFile);
					}

					foreach ($galleyFiles as $file) {
						if (is_dir($streamsPath) == false) {
							mkdir($streamsPath, 0777);
						}
						$masterPath = join(DIRECTORY_SEPARATOR, array($streamsPath, MASTER_PATH));
						if (is_dir($masterPath) == false) {
							mkdir($masterPath, 0777);
						}
						copy($file["fullFilePath"], join(DIRECTORY_SEPARATOR, array($streamsPath, $file["path"], basename($file["fullFilePath"]))));
						foreach ($file["dependentFiles"] as $dependentFile) {
							copy($dependentFile["fullFilePath"], join(DIRECTORY_SEPARATOR, array($streamsPath, $file["path"], basename($dependentFile["fullFilePath"]))));
						}
					}
					$this->doRequest($context, $ingestPath, $sipPath, $submission);
					unlink($xmlExport);
				}
			}
		}
		umask($oldmask);
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

}
