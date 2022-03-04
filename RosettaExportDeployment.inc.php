<?php
import('classes.core.Services');
import('plugins.importexport.rosetta.classes.RosettaDCDom');
import('plugins.importexport.rosetta.classes.RosettaMETSDom');
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

	private function depositSubmission(Context $context, Submission $submission, Publication $publication)
	{
		$subDirectoryName = $this->getPlugin()->getSetting($context->getId(), 'subDirectoryName');

		$oldmask = umask(0);
		if (is_dir($subDirectoryName)) {
			$ingestPath = PKPString::strtolower($context->getLocalizedAcronym()) . '-' . $submission->getId() . '-v' . $publication->getData('version');
			$pubPath = $subDirectoryName . '/' . $ingestPath;
			if (is_dir($pubPath) == false) {
				mkdir($pubPath, 0777);

				$dcDom = new RosettaDCDom($context, $publication);

				file_put_contents($pubPath . DIRECTORY_SEPARATOR . 'dc.xml', $dcDom->saveXML(), FILE_APPEND | LOCK_EX);

				$pubContentPath = join(DIRECTORY_SEPARATOR, array($pubPath, 'content'));

				if (is_dir($pubContentPath) == false) {
					mkdir($pubContentPath, 0777);


					$metsDom = new RosettaMETSDom($context, $submission, $publication, $this->getPlugin());
					file_put_contents(join(DIRECTORY_SEPARATOR, array($pubContentPath, "ie1.xml")), $metsDom->saveXML(), FILE_APPEND | LOCK_EX);

					// Add dependent files
					$streamsPath = join(DIRECTORY_SEPARATOR, array($pubContentPath, 'streams'));

					list($xmlExport, $exportFile) = $metsDom->appendImportExportFile();
					shell_exec('php' . " " . $_SERVER['argv'][0] . "  NativeImportExportPlugin export " . $xmlExport . " " . $_SERVER['argv'][2] . " article " . $submission->getData('id'));


					$galleyFiles = $metsDom->getGalleyFiles();
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

					$this->_doRequest($context, $ingestPath, $submission);
					unlink($xmlExport);

				}
			}
		}

		umask($oldmask);
	}

	/**
	 * Get the import/export plugin.
	 * @return ImportExportPlugin
	 */
	function getPlugin()
	{
		return $this->_plugin;
	}

	/**
	 * Set the import/export plugin.
	 * @param $plugin ImportExportPlugin
	 */
	function setPlugin($plugin)
	{
		$this->_plugin = $plugin;
	}



	/**
	 * @param Context $context
	 * @param string $ingestPath
	 * @param Submission $submission
	 */
	private function _doRequest(Context $context, string $ingestPath, Submission $submission): void
	{
		$endpoint = $this->getPlugin()->getSetting($context->getId(), 'rosettaHost') . 'deposit/DepositWebServices?wsdl';
		$username = $this->getPlugin()->getSetting($context->getId(), 'rosettaUsername');
		$password = $this->getPlugin()->getSetting($context->getId(), 'rosettaPassword');
		$institutionCode = $this->getPlugin()->getSetting($context->getId(), 'rosettaInstitutionCode');
		$materialFlowId = $this->getPlugin()->getSetting($context->getId(), 'rosettaMaterialFlowId');
		//$subDirectoryName = $this->getPlugin()->getSetting($context->getId(), 'rosettaSubDirectoryName');
		$producerId = $this->getPlugin()->getSetting($context->getId(), 'rosettaProducerId');

		$password = $username . '-institutionCode-' . $institutionCode . ':' . $password;
		$base64_credentials = base64_encode($password);


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


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);

		$headers = array();
		$headers[] = 'Content-Type: text/xml';
		$headers[] = 'SoapAction: ""';
		$headers[] = 'Authorization: local ' . $base64_credentials;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($response_code == 200) {
			$submissionDao = DAORegistry::getDAO('SubmissionDAO');
			$submission->setData('dateUpdated', Core::getCurrentDate());
			$date = new DateTime();
			$submission->setData($this->_plugin->getDepositStatusSettingName(), $date->getTimestamp());
			$submissionDao->updateObject($submission);

		} else {
			$this->getPlugin()->logError($result);
		}
		curl_close($ch);



	}

	/**
	 * @param Context $context
	 * @param Submission $submission
	 * @param Publication $publication
	 * @return string
	 */
	private function createSIPPath(Context $context, Submission $submission, Publication $publication): string
	{
		$subDirectoryName = $this->getPlugin()->getSetting($context->getId(), 'subDirectoryName');
		if (is_dir($subDirectoryName)) {
			return $subDirectoryName . '/' . PKPString::strtolower($context->getLocalizedAcronym()) . '-' . $submission->getId() . '-v' . $publication->getData('version') . '.zip';
		} else {
			var_dump("Exception:  subDirectoryName " . $subDirectoryName . " not available");
			return '';
		}
	}

	/**
	 * @param Context $context
	 * @param Submission $submission
	 * @param Publication $publication
	 * @param string $archivePath
	 */
	private function copyPublicationToShareZIPFile(Context $context, Submission $submission, Publication $publication, string $archivePath)
	{

		if (self::zipFunctional()) {
			if (file_exists($archivePath) == false) {
				$zip = new ZipArchive();
				if ($zip->open($archivePath, ZIPARCHIVE::CREATE) == true) {

					$dcDom = new RosettaDCDom($context, $publication);
					$dcDom->preserveWhiteSpace = false;
					$dcDom->formatOutput = true;
//$dcDom->createInstance();
					$zip->addFromString("dc.xml", $dcDom->saveXML());
					$zip->addEmptyDir("content");
					$metsDom = new RosettaMETSDom($context, $submission, $publication, $this->getPlugin());
					$metsDom->preserveWhiteSpace = false;
					$metsDom->formatOutput = true;
// create mets file
					$zip->addFromString("content/ie1.xml", $metsDom->saveXML());
// add files
					foreach ($metsDom->getGalleyFiles() as $file) {
						$fiePath = "content/streams/{$file["path"]}/";
						$zip->addFile($file["fullFilePath"], $fiePath . basename($file["fullFilePath"]));
						foreach ($file["dependentFiles"] as $dFile) {
							$dFilePath = $dFile["fullFilePath"];
							$zip->addFile($dFilePath, $fiePath . basename($dFilePath));
						}
					}

					$zip->close();
				}

			}
			$pdsHandle = null; //TODO retrieve
			/*	$soap = new SoapClient(
$endpoint,
array(

	"soap_version" => SOAP_1_1,
	"trace" => 1
)
				);
				#retval = dpws.submitDepositActivity(pdsHandle,materialFlowId, folder, producerId, depositSetId);
				$params = array("pdsHandle" => $pdsHandle,
"materialFlowId" => $materialFlowId,
"folder" => $subDirectoryName,
"producerId" => $producerId
				);

				$result = $soap->__doRequest();
				var_dump($result);*/

		} else {
			fatalError('ZIP not installed');
		}
	}

	/**
	 * Return true if the zip extension is loaded.
	 * @return boolean
	 */
	static function zipFunctional(): bool
	{
		return (extension_loaded('zip'));
	}


}
