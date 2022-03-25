<?php
import('plugins.importexport.rosetta.classes.XMLUtils');
import('plugins.importexport.rosetta.classes.dc.RosettaDCDom');
define('MASTER_PATH', 'MASTER');

/**
 * Class RosettaMETSDom
 */
class RosettaMETSDom extends DOMDocument
{
	var $context;
	var $domSettings;
	var $metsNS = "http://www.loc.gov/METS/";
	var $plugin;
	var $xpathSettings;
	var $publication;
	var $record;
	var $submission;

	/***
	 * RosettaMETSDom constructor.
	 * @param Context $context
	 * @param Submission $submission
	 * @param Publication $publication
	 * @param Plugin $plugin
	 */
	public function __construct(Context $context, Submission $submission, Publication $publication, Plugin $plugin)
	{
		parent::__construct('1.0', 'UTF-8');
		$settingsDom = new DOMDocument();
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		$settingsPath = Core::getBaseDir() . DIRECTORY_SEPARATOR . $plugin->getContextSpecificPluginSettingsFile();
		$this->context = $context;
		$this->domSettings = $settingsDom->load($settingsPath);
		$this->publication = $publication;
		$this->submission = $submission;
		$this->xpathSettings = new DOMXPath($settingsDom);
		$this->createInstance();
	}

	public function createInstance(): void
	{
		$repId = "1";
		$repIdSuffix = "1";
		$this->createRootElement();
		// create dmdSec
		$dcDom = new RosettaDCDom($this->context, $this->publication, false);
		$dc = $this->importNode($dcDom->getRecord(), true);
		// Dublin core Metadata
		$dmdSec = $this->createMetsDCElement("ie-dmd", "mets:dmdSec", "DC", $dc);
		$this->record->appendChild($dmdSec);

		$ieAmd = "ie-amd";
		$adminSec = $this->createElementNS($this->metsNS, "mets:amdSec");
		$adminSec->setAttribute("ID", $ieAmd);
		// <mets:techMD ID="ie-amd-tech">
		// <section id="CMS"><record><key id="system">TIB</key><key id="recordId">publicknowledge-2-v2</key></record></section>
		//TODO clarify:recordId
		$recordId = (string)$this->context->getData('urlPath') . '-' . (string)$this->submission->getData('id') . '-v' . (string)$this->publication->getData('version');
		// <section id="generalIECharacteristics"><record><key id="status">active</key><key id="IEEntityType">Article</key></record></section>
		// active= frei verfügbar or suppressed for embargo
		//TODO conference type
		XMLUtils::createIEAmdSections($this, array(array("id" => "generalIECharacteristics", "records" => array(
				["id" => "status", "value" => "ACTIVE"],
				["id" => "IEEntityType", "value" => "Article"],
				["id" => "UserDefinedA", "value" => "OJS_born-digital"],
			)))
			, "techMD", "tech", $ieAmd, $adminSec);
		$this->createAmdSecMods($adminSec);

		// get Galley files
		$galleyFiles = $this->getGalleyFiles();
		// TODO append import export file
		list($xmlExport, $exportFile) = $this->appendImportExportFile();
		if (file_exists($xmlExport)) {
			array_push($galleyFiles, $exportFile);
		}
		// mets:fileSec
		$fileSec = $this->createElementNS($this->metsNS, "mets:fileSec");

		$fileGrpNode = $this->createElementNS($this->metsNS, "mets:fileGrp");
		$fileGrpNode->setAttribute("ID", "rep" . $repId);
		$fileGrpNode->setAttribute("ADMID", "rep" . $repId . "-amd");
		$fileSec->appendChild($fileGrpNode);
		//mets structMap
		$divNode = $this->createElementNS($this->metsNS, "mets:div");
		$divNode->setAttribute("LABEL", "Preservation Master");
		$structMapDiv = $this->createStructDiv($repId, $repIdSuffix);


		$galleyFilesCount = count($galleyFiles) + 1;
		foreach ($galleyFiles as $index => $file) {
			$this->createFileCharacteristics($index + 1, $repIdSuffix, $recordId, $file);
			$fileNode = $this->createMetsFileSecChildElements($repId, strval($index + 1), $file);
			$fileGrpNode->appendChild($fileNode);
			$structMap = $this->createMetsStructSecElement($repId, strval($index + 1), $file);
			$structMapDiv->appendChild($structMap);
			foreach ($file["dependentFiles"] as $dependentFile) {
				$this->createFileCharacteristics($galleyFilesCount, $repIdSuffix, $recordId, $dependentFile);
				$fileNode = $this->createMetsFileSecChildElements($repId, strval($galleyFilesCount), $dependentFile);
				$fileGrpNode->appendChild($fileNode);
				$structMap = $this->createMetsStructSecElement($repId, strval($galleyFilesCount), $dependentFile);
				$structMapDiv->appendChild($structMap);
				$galleyFilesCount += 1;
			}

		}
		$structMapNode = $this->createElementNS($this->metsNS, "mets:structMap");
		$structMapNode->setAttribute("ID", "rep" . $repId . "-" . $repIdSuffix);
		$structMapNode->setAttribute("TYPE", "PHYSICAL");
		$divNode->appendChild($structMapDiv);
		$structMapNode->appendChild($divNode);

		$this->record->appendChild($fileSec);
		$this->record->appendChild($structMapNode);

	}

	/**
	 *Create root element
	 */
	function createRootElement()
	{
		$this->record = $this->createElementNS($this->metsNS, "mets:mets");
		$this->record->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
		$this->appendChild($this->record);
	}

	/**
	 * @param string $id
	 * @param string $sec
	 * @param string $mdType
	 * @param DOMElement $child
	 * @return DOMElement
	 */
	function createMetsDCElement(string $id, string $sec, string $mdType, DOMElement $child): DOMElement
	{
		$dmdSec = $this->createElementNS($this->metsNS, $sec);
		$dmdSec->setAttribute("ID", $id);
		$mdWrap = $this->createElementNS($this->metsNS, "mets:mdWrap");
		$mdWrap->setAttribute("MDTYPE", $mdType);
		$dmdSec->appendChild($mdWrap);
		$xmlData = $this->createElementNS($this->metsNS, "mets:xmlData");
		$mdWrap->appendChild($xmlData);
		$xmlData->appendChild($child);
		return $dmdSec;
	}

	/**
	 * @param DomElement $adminSec
	 */
	private function createAmdSecMods(DomElement $adminSec): void
	{
		import('plugins.importexport.rosetta.classes.mods.ModsDOM');
		$mods = new ModsDOM($this->context, $this->submission, $this->publication);
		$sourceMD = $this->createElementNS($this->metsNS, "sourceMD");
		$sourceMD->setAttribute("ID", "ie-amd-source-1");
		$mdWrap = $this->createElementNS($this->metsNS, "mets:mdWrap");
		$mdWrap->setAttribute("MDTYPE", "MODS");
		$sourceMD->appendChild($mdWrap);
		$xmlData = $this->createElementNS($this->metsNS, "mets:xmlData");
		$mdWrap->appendChild($xmlData);
		$xmlData->appendChild($this->importNode($mods->getRecord(), true));
		$adminSec->appendChild($sourceMD);
		$this->record->appendChild($adminSec);

		//<mets:amdSec ID="rep1-amd">
		$adminSecRep = $this->createElementNS($this->metsNS, "mets:amdSec");
		$adminSecRep->setAttribute("ID", "rep1-amd");
		XMLUtils::createIEAmdSections($this,
			array(
				array("id" => "generalRepCharacteristics", "records" => array(
					["id" => "preservationType", "value" => "PRESERVATION_MASTER"],
					["id" => "usageType", "value" => "VIEW"],
					["id" => "RevisionNumber", "value" => "0"],
				))), "techMD", "tech", "rep1-amd", $adminSecRep);
		$this->record->appendChild($adminSecRep);
	}

	/**
	 * @return array
	 */
	public function getGalleyFiles(): array
	{
		// get all galleys

		$files = array();

		$galleysIterator = Services::get('galley')->getMany(['publicationIds' => $this->publication->getId()]);
		foreach ($galleysIterator as $galley) {
			$fileId = $galley->getData('fileId');
			$galleyFile = $galley->getFile();
			if (is_null($galleyFile) == false) {
				$galleyFilePath = $galleyFile->getFilePath();
				$dependentFilePaths = $this->getDependentFilePaths($this->publication->getData('submissionId'), $fileId, MASTER_PATH);
				$files[] = array(
					"label" => $galley->getLocalizedName(),
					"revision" => $this->publication->getData("version"),
					"fullFilePath" => $galleyFilePath,
					"dependentFiles" => $dependentFilePaths,
					"path" => MASTER_PATH);
			}
		}

		return $files;
	}

	/**
	 * @param $submissionId
	 * @param $fileId
	 * @param string $path
	 * @return array
	 */
	public function getDependentFilePaths($submissionId, $fileId, string $path): array
	{
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile'); // Constants
		$dependentFiles = $submissionFileDao->getLatestRevisionsByAssocId(
			ASSOC_TYPE_SUBMISSION_FILE,
			$fileId,
			$submissionId,
			SUBMISSION_FILE_DEPENDENT
		);
		$assetsFilePaths = array();
		foreach ($dependentFiles as $dFile) {
			$assetsFilePaths[$dFile->getOriginalFileName()] = array(
				"fullFilePath" => $dFile->getFilePath(),
				"path" => $path,
				"originalFileName" => $dFile->getOriginalFileName()
			);
		}
		return $assetsFilePaths;
	}

	/**
	 * @return array
	 */
	public function appendImportExportFile(): array
	{
		$xmlExport = sys_get_temp_dir() . DIRECTORY_SEPARATOR . PKPString::strtolower($this->context->getLocalizedAcronym()) . '-' . $this->submission->getId() . '-v' . $this->publication->getData('version') . '-nativeExport.xml';
		$exportFile = array(
			'dependentFiles' => array(),
			'fullFilePath' => $xmlExport,
			'label' => 'NativeImportExportXML',
			'path' => 'MASTER',
			'revision' => 1
		);
		return array($xmlExport, $exportFile);
	}

	/**
	 * @param string $repId
	 * @param string $repIdSuffix
	 * @return DOMElement|false
	 */
	private function createStructDiv(string $repId, string $repIdSuffix)
	{
		$divDivNode = $this->createElementNS($this->metsNS, "mets:div");
		$divDivNode->setAttribute("LABEL", "rep" . $repId);
		return $divDivNode;
	}

	/**
	 * @param int $index
	 * @param string $repIdSuffix
	 * @param string $recordId
	 * @param $file
	 */
	private function createFileCharacteristics(int $index, string $repIdSuffix, string $recordId, $file): void
	{
		$generalFileChars = $this->createElementNS($this->metsNS, "mets:amdSec");
		$generalFileChars->setAttribute("ID", "fid" . strval($index) . "-" . $repIdSuffix . "-amd");
		$md5_file = null;
		if (file_exists($file["fullFilePath"])) {
			$md5_file = md5_file($file["fullFilePath"]);
		} else {
			var_dump('File ' . $file["fullFilePath"] . ' does not exist');
		}
		XMLUtils::createIEAmdSections($this, array(
				array("id" => "generalFileCharacteristics", "records" => array(
					["id" => "fileOriginalPath", "value" => '/' . $recordId . "/content/streams/" . $file['path'] . "/" . basename($file['fullFilePath'])],
				)),
				array("id" => "fileFixity", "records" => array(
					["id" => "fixityType", "value" => "MD5"],
					["id" => "fixityValue", "value" => $md5_file],
				))
			)
			, "techMD", "tech", "fid" . strval($index) . '-' . $repIdSuffix . '-amd', $generalFileChars);
		$this->record->appendChild($generalFileChars);
	}

	/**
	 * @param string $fid
	 * @param string $id
	 * @param array $file
	 * @return DOMElement
	 */
	function createMetsFileSecChildElements(string $fid, string $id, array $file): DOMElement
	{
		$fileNode = $this->createElementNS($this->metsNS, "mets:file");
		$fileNode->setAttribute("ID", "fid" . $id . "-" . $fid);
		$fileNode->setAttribute("ADMID", "fid" . $id . "-" . $fid . "-amd");
		$fileLocNode = $this->createElementNS($this->metsNS, "mets:FLocat");
		$fileLocNode->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
		$fileLocNode->setAttribute("LOCTYPE", "URL");
		$fileLocNode->setAttribute("xlink:href", "file://" . $file['path'] . "/" . basename($file['fullFilePath']));
		$fileNode->appendChild($fileLocNode);

		return $fileNode;
	}

	/**
	 * @param string $fid
	 * @param string $id
	 * @param array $file
	 * @return DOMElement
	 */
	function createMetsStructSecElement(string $fid, string $id, array $file): DOMElement
	{

		$divDivDivNode = $this->createElementNS($this->metsNS, "mets:div");
		$divDivDivNode->setAttribute("LABEL", "");
		$divDivDivNode->setAttribute("TYPE", "FILE");
		$fptrNode = $this->createElementNS($this->metsNS, "mets:fptr");
		$fptrNode->setAttribute("FILEID", "fid" . $id . '-' . $fid);
		$divDivDivNode->appendChild($fptrNode);
		return $divDivDivNode;
	}
}



