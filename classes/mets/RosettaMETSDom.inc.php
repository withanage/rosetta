<?php
/**
 * @file plugins/importexport/rosetta/classes/mets/RosettaMETSDom.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RosettaMETSDom
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief This class represents the METS XML document used in the Rosetta export process.
 *
 * @property Context $context The OJS Context associated with the publication.
 * @property string $metsNS The XML namespace for METS elements.
 * @property Plugin $plugin The OJS plugin instance.
 * @property Publication $publication The OJS publication being exported.
 * @property DOMElement $record The root element of the METS document.
 * @property Submission $submission The OJS submission associated with the publication.
 */

namespace TIBHannover\Rosetta\Mets;

import('plugins.importexport.rosetta.classes.mods.ModsDOM');
import('plugins.importexport.rosetta.classes.xml.XMLUtils');
import('plugins.importexport.rosetta.classes.dc.RosettaDCDom');
import('plugins.importexport.rosetta.classes.files.RosettaFileService');

define('MASTER_PATH', 'MASTER');

use Context;
use DOMDocument;
use DOMElement;
use PKPString;
use Plugin;
use Publication;
use Submission;
use TIBHannover\Rosetta\Dc\RosettaDCDom;
use TIBHannover\Rosetta\Files\RosettaFileService;
use TIBHannover\Rosetta\Mods\ModsDOM;
use TIBHannover\Rosetta\Xml\XMLUtils;

class RosettaMETSDom extends DOMDocument
{
	public Context $context;
	public string $metsNS = 'http://www.exlibrisgroup.com/xsd/dps/rosettaMets';
	public Plugin $plugin;
	public Publication $publication;
	public DOMElement $record;
	public Submission $submission;

	/***
	 * Constructor
	 *
	 * @param Context $context The OJS Context.
	 * @param Submission $submission The OJS submission.
	 * @param Publication $publication The OJS publication.
	 * @param Plugin $plugin The OJS plugin instance.
	 * @param bool $isTest Set to true for testing, false for production.
	 */
	public function __construct(Context $context, Submission $submission, Publication $publication, Plugin $plugin, bool $isTest = false)
	{
		parent::__construct('1.0', 'UTF-8');
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		$this->context = $context;
		$this->plugin = $plugin;
		$this->publication = $publication;
		$this->submission = $submission;

		// Create the METS XML structure.
		$this->createInstance($isTest);
	}

	/**
	 * Create the METS XML structure.
	 *
	 * @param bool $isTest Set to true for testing, false for production.
	 *
	 * @return void
	 */
	public function createInstance(bool $isTest): void
	{
		$repId = '1';
		$repIdSuffix = '1';

		// Create the root element.
		$this->createRootElement();

		// create dmdSec
		$dcDom = new RosettaDCDom($this->context, $this->publication, $this->submission, false);
		$dc = $this->importNode($dcDom->getRecord(), true);

		// Dublin core Metadata
		$dmdSec = $this->createMetsDCElement('ie-dmd', 'mets:dmdSec', 'DC', $dc);
		$this->record->appendChild($dmdSec);

		$ieAmd = 'ie-amd';
		$adminSec = $this->createElementNS($this->metsNS, 'mets:amdSec');
		$adminSec->setAttribute('ID', $ieAmd);
		// <mets:techMD ID='ie-amd-tech'>
		// <section id='CMS'><record><key id='system'>TIB</key><key id='recordId'>publicknowledge-2-v2</key></record></section>
		//TODO clarify:recordId
		$recordId = (string)$this->context->getData('urlPath') . '-' . (string)$this->submission->getData('id') . '-v' . (string)$this->publication->getData('version');
		// <section id='generalIECharacteristics'><record><key id='status'>active</key><key id='IEEntityType'>Article</key></record></section>
		// active= frei verfügbar or suppressed for embargo
		//TODO conference type
		XMLUtils::createIEAmdSections($this, array(array('id' => 'generalIECharacteristics', 'records' => array(
				['id' => 'status', 'value' => 'ACTIVE'],
				['id' => 'IEEntityType', 'value' => 'Article'],
				['id' => 'UserDefinedA', 'value' => 'OJS_born-digital'],
			)))
			, 'techMD', 'tech', $ieAmd, $adminSec);
		$this->createAmdSecMods($adminSec);

		// get Galley files
		$galleyFiles = RosettaFileService::getGalleyFiles($this->getPublication());
		// TODO append import export file
		/**
		list($xmlExport, $exportFile) = $this->appendImportExportFile();
		if (file_exists($xmlExport)) {
			$galleyFiles[] = $exportFile;
		}*/

		// mets:fileSec
		$fileSec = $this->createElementNS($this->metsNS, 'mets:fileSec');

		$fileGrpNode = $this->createElementNS($this->metsNS, 'mets:fileGrp');
		$fileGrpNode->setAttribute('ID', 'rep' . $repId);
		$fileGrpNode->setAttribute('ADMID', 'rep' . $repId . '-amd');
		$fileSec->appendChild($fileGrpNode);

		//mets structMap
		$divNode = $this->createElementNS($this->metsNS, 'mets:div');
		$divNode->setAttribute('LABEL', 'Preservation Master');
		$structMapDiv = $this->createStructDiv($repId, $repIdSuffix);

		if (!$isTest) {
			$galleyFilesCount = count($galleyFiles) + 1;
			foreach ($galleyFiles as $index => $file) {
				$this->createFileCharacteristics($index + 1, $repIdSuffix, $recordId, $file);
				$fileNode = $this->createMetsFileSecChildElements($repId, strval($index + 1), $file);
				$fileGrpNode->appendChild($fileNode);
				$structMap = $this->createMetsStructSecElement($repId, strval($index + 1), $file);
				$structMapDiv->appendChild($structMap);
				$dependentFiles = $file['dependentFiles'];
				foreach ($dependentFiles as $dependentFile) {
					$this->createFileCharacteristics($galleyFilesCount, $repIdSuffix, $recordId, $dependentFile);
					$fileNode = $this->createMetsFileSecChildElements($repId, strval($galleyFilesCount), $dependentFile);
					$fileGrpNode->appendChild($fileNode);
					$structMap = $this->createMetsStructSecElement($repId, strval($galleyFilesCount), $dependentFile);
					$structMapDiv->appendChild($structMap);
					$galleyFilesCount += 1;
				}


			}
			$structMapNode = $this->createElementNS($this->metsNS, 'mets:structMap');
			$structMapNode->setAttribute('ID', 'rep' . $repId . '-' . $repIdSuffix);
			$structMapNode->setAttribute('TYPE', 'PHYSICAL');
			$divNode->appendChild($structMapDiv);
			$structMapNode->appendChild($divNode);
			if(count($galleyFiles)>0) {
				$this->record->appendChild($fileSec);
			}

			$this->record->appendChild($structMapNode);
		}
	}

	/**
	 * Create the root element of the METS document.
	 *
	 * @return void
	 */
	function createRootElement(): void
	{
		$this->record = $this->createElementNS($this->metsNS, 'mets:mets');
		$this->record->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$this->appendChild($this->record);
	}

	/**
	 * Create a METS DC element.
	 *
	 * @param string $id The ID for the element.
	 * @param string $sec The section name.
	 * @param string $mdType The metadata type.
	 * @param DOMElement $child The child element to attach.
	 *
	 * @return DOMElement
	 */
	function createMetsDCElement(string $id, string $sec, string $mdType, DOMElement $child): DOMElement
	{
		$dmdSec = $this->createElementNS($this->metsNS, $sec);
		$dmdSec->setAttribute('ID', $id);
		$mdWrap = $this->createElementNS($this->metsNS, 'mets:mdWrap');
		$mdWrap->setAttribute('MDTYPE', $mdType);
		$dmdSec->appendChild($mdWrap);
		$xmlData = $this->createElementNS($this->metsNS, 'mets:xmlData');
		$mdWrap->appendChild($xmlData);
		$xmlData->appendChild($child);
		return $dmdSec;
	}

	/**
	 * Create the administrative section for MODS (Metadata Object Description Schema) metadata.
	 * @param DOMElement $adminSec
	 *
	 * @return void
	 */
	private function createAmdSecMods(DomElement $adminSec): void
	{
		$mods = new ModsDOM($this->context, $this->publication);
		$sourceMD = $this->createElementNS($this->metsNS, 'sourceMD');
		$sourceMD->setAttribute('ID', 'ie-amd-source-1');
		$mdWrap = $this->createElementNS($this->metsNS, 'mets:mdWrap');
		$mdWrap->setAttribute('MDTYPE', 'MODS');
		$sourceMD->appendChild($mdWrap);
		$xmlData = $this->createElementNS($this->metsNS, 'mets:xmlData');
		$mdWrap->appendChild($xmlData);
		$xmlData->appendChild($this->importNode($mods->getRecord(), true));
		$adminSec->appendChild($sourceMD);
		$this->record->appendChild($adminSec);

		//<mets:amdSec ID='rep1-amd'>
		$adminSecRep = $this->createElementNS($this->metsNS, 'mets:amdSec');
		$adminSecRep->setAttribute('ID', 'rep1-amd');
		XMLUtils::createIEAmdSections($this,
			array(
				array('id' => 'generalRepCharacteristics', 'records' => array(
					['id' => 'preservationType', 'value' => 'PRESERVATION_MASTER'],
					['id' => 'usageType', 'value' => 'VIEW'],
					['id' => 'RevisionNumber', 'value' => '0'],
				))), 'techMD', 'tech', 'rep1-amd', $adminSecRep);
		$this->record->appendChild($adminSecRep);
	}

	/**
	 * @return Publication
	 */
	public function getPublication(): Publication
	{
		return $this->publication;
	}

	/**
	 * Append the OJS export file to the METS document.
	 *
	 * @return array An array containing the XML export file and its information.
	 */
	public function appendImportExportFile(): array
	{
		$xmlExport = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
			PKPString::strtolower($this->context->getLocalizedAcronym()) . '-' . $this->submission->getId() .
			'-v' . $this->publication->getData('version') . '-nativeExport.xml';
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
	 * Create the structMap div element.
	 *
	 * @param string $repId The representation ID.
	 * @param string $repIdSuffix The representation ID suffix.
	 *
	 * @return bool|DOMElement
	 */
	private function createStructDiv(string $repId, string $repIdSuffix): bool|DOMElement
	{
		$divDivNode = $this->createElementNS($this->metsNS, 'mets:div');
		$divDivNode->setAttribute('LABEL', 'rep' . $repId);

		return $divDivNode;
	}

	/**
	 * Create file characteristics for a file.
	 *
	 * @param int $index The index of the file.
	 * @param string $repIdSuffix The representation ID suffix.
	 * @param string $recordId The record ID.
	 * @param array $file The galley file information.
	 *
	 * @return void
	 */
	private function createFileCharacteristics(int $index, string $repIdSuffix, string $recordId, $file): void
	{

		$generalFileChars = $this->createElementNS($this->metsNS, 'mets:amdSec');
		$generalFileChars->setAttribute('ID', 'fid' . strval($index) . '-' . $repIdSuffix . '-amd');
		$md5_file = md5_file($this->getPlugin()->getBasePath() . DIRECTORY_SEPARATOR . $file['fullFilePath']);
		XMLUtils::createIEAmdSections($this, array(
				array('id' => 'generalFileCharacteristics', 'records' => array(
					['id' => 'fileOriginalPath', 'value' => '/' . $recordId . '/content/streams/' . $file['path'] . '/' . basename($file['fullFilePath'])],
				)),
				array('id' => 'fileFixity', 'records' => array(
					['id' => 'fixityType', 'value' => 'MD5'],
					['id' => 'fixityValue', 'value' => $md5_file],
				))
			)
			, 'techMD', 'tech', 'fid' . strval($index) . '-' . $repIdSuffix . '-amd', $generalFileChars);
		$this->record->appendChild($generalFileChars);
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin(): Plugin
	{
		return $this->plugin;
	}

	/**
	 * @param Plugin $plugin
	 */
	public function setPlugin(Plugin $plugin): void
	{
		$this->plugin = $plugin;
	}

	/**
	 * Create METS fileSec child elements.
	 *
	 * @param string $fid The file ID.
	 * @param string $id The ID of the file.
	 * @param array $file The galley file information.
	 *
	 * @return DOMElement
	 */
	function createMetsFileSecChildElements(string $fid, string $id, array $file): DOMElement
	{
		$fileNode = $this->createElementNS($this->metsNS, 'mets:file');
		$fileNode->setAttribute('ID', 'fid' . $id . '-' . $fid);
		$fileNode->setAttribute('ADMID', 'fid' . $id . '-' . $fid . '-amd');
		$fileLocNode = $this->createElementNS($this->metsNS, 'mets:FLocat');
		$fileLocNode->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$fileLocNode->setAttribute('LOCTYPE', 'URL');
		$fileLocNode->setAttribute('xlink:href', 'file://' . $file['path'] . '/' . basename($file['fullFilePath']));
		$fileNode->appendChild($fileLocNode);

		return $fileNode;
	}

	/**
	 * Create METS structMap elements.
	 *
	 * @param string $fid The file ID.
	 * @param string $id The ID of the file.
	 * @param array $file The galley file information.
	 *
	 * @return DOMElement
	 */
	function createMetsStructSecElement(string $fid, string $id, array $file): DOMElement
	{
		$divDivDivNode = $this->createElementNS($this->metsNS, 'mets:div');
		$divDivDivNode->setAttribute('LABEL', '');
		$divDivDivNode->setAttribute('TYPE', 'FILE');
		$fptrNode = $this->createElementNS($this->metsNS, 'mets:fptr');
		$fptrNode->setAttribute('FILEID', 'fid' . $id . '-' . $fid);
		$divDivDivNode->appendChild($fptrNode);
		return $divDivDivNode;
	}

	/**
	 * @return Context
	 */
	public function getContext(): Context
	{
		return $this->context;
	}

	/**
	 * @param Context $context
	 */
	public function setContext(Context $context): void
	{
		$this->context = $context;
	}
}



