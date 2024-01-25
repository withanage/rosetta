<?php

namespace TIBHannover\Rosetta\Mets;

import('plugins.importexport.rosetta.classes.xml.mods.ModsDOM');
import('plugins.importexport.rosetta.classes.xml.XMLUtils');
import('plugins.importexport.rosetta.classes.xml.dublincore.RosettaDCDom');
import('plugins.importexport.rosetta.classes.files.RosettaFileService');

define('MASTER_PATH', 'MASTER');

use Context;
use DOMDocument;
use DOMElement;
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

	public function createInstance(bool $isTest): void
	{

		// Create the root element.
		$this->createMetsElement();

		// create dmdSec
		$dcDom = new RosettaDCDom($this->context, $this->publication, $this->submission, false);
		$dc = $this->importNode($dcDom->getRecord(), true);

		// Dublin core Metadata
		$dmdSec = $this->createMetsDCElement('ie-dmd', 'mets:dmdSec', 'DC', $dc);
		$this->record->appendChild($dmdSec);

		$ieAmd = 'ie-amd';
		$adminSec = $this->createElementNS($this->metsNS, 'mets:amdSec');
		$adminSec->setAttribute('ID', $ieAmd);


		XMLUtils::createIEAmdSections($this, array(array('id' => 'generalIECharacteristics', 'records' => array(
				['id' => 'status', 'value' => 'ACTIVE'],
				['id' => 'IEEntityType', 'value' => 'Article'],
				['id' => 'UserDefinedA', 'value' => 'OJS_born-digital'],
			)))
			, 'techMD', 'tech', $ieAmd, $adminSec);

		$this->createAmdSecMods($adminSec);


		$repId = '1';

		$fileGrpNode = $this->createElementNS($this->metsNS, 'mets:fileGrp');
		$fileGrpNode->setAttribute('ID', 'rep' . $repId);
		$fileGrpNode->setAttribute('ADMID', 'rep' . $repId . '-amd');

		$fileSec = $this->createElementNS($this->metsNS, 'mets:fileSec');
		$fileSec->appendChild($fileGrpNode);

		//mets:structMap
		$divNode = $this->createElementNS($this->metsNS, 'mets:div');
		$divNode->setAttribute('LABEL', 'Preservation Master');

		$repIdSuffix = '1';
		$recordId = (string)$this->context->getData('urlPath') . '-' . (string)$this->submission->getData('id') . '-v' . (string)$this->publication->getData('version');
		$structMapDiv = $this->createStructDiv($repId, $repIdSuffix);

		$galleyFiles = RosettaFileService::getGalleyFiles($this->publication);
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
		$this->record->appendChild($fileSec);
		$this->record->appendChild($structMapNode);


	}

	function createMetsElement(): void
	{
		$this->record = $this->createElementNS($this->metsNS, 'mets:mets');
		$this->record->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$this->appendChild($this->record);
	}

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


	private function createStructDiv(string $repId, string $repIdSuffix): bool|DOMElement
	{
		$divDivNode = $this->createElementNS($this->metsNS, 'mets:div');
		$divDivNode->setAttribute('LABEL', 'rep' . $repId);

		return $divDivNode;
	}


	private function createFileCharacteristics(int $index, string $repIdSuffix, string $recordId, $file): void
	{
		$filePath = $this->plugin->getBasePath() . DIRECTORY_SEPARATOR . $file['fullFilePath'];
		$generalFileChars = $this->createElementNS($this->metsNS, 'mets:amdSec');
		$generalFileChars->setAttribute('ID', 'fid' . strval($index) . '-' . $repIdSuffix . '-amd');

		$md5_file = md5_file($filePath);

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


	public function getContext(): Context
	{
		return $this->context;
	}

	public function setContext(Context $context): void
	{
		$this->context = $context;
	}
}



