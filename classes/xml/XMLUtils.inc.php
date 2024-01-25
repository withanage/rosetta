<?php

namespace TIBHannover\Rosetta\Xml;

use DOMElement;
use TIBHannover\Rosetta\Mets\RosettaMETSDom;

class XMLUtils
{
		public static function createIEAmdSections(RosettaMETSDom $document, array $sectionsArray, string $name,
											   string         $type, string $ieAmd, DOMElement $adminSec): void
	{
		$mdType = $document->createElementNS($document->metsNS, $name);
		$mdType->setAttribute('ID', $ieAmd . '-' . $type);
		$adminSec->appendChild($mdType);
		$mdWrap = $document->createElementNS($document->metsNS, 'mets:mdWrap');
		$mdWrap->setAttribute('MDTYPE', 'OTHER');
		$mdWrap->setAttribute('OTHERMDTYPE', 'dnx');
		$mdType->appendChild($mdWrap);
		$xmlData = $document->createElementNS($document->metsNS, 'mets:xmlData');
		$mdWrap->appendChild($xmlData);
		$dnxNode = $document->createElement('dnx');
		$dnxNode->setAttribute('xmlns', 'http://www.exlibrisgroup.com/dps/dnx');
		foreach ($sectionsArray as $s) {
			$section = $document->createElement('section');
			$sId = $section->setAttribute('id', $s['id']);
			$section->appendChild($sId);
			$record = $document->createElement('record');
			$section->appendChild($record);
			foreach ($s['records'] as $r) {
				$key = $document->createElement('key', $r['value']);
				$key->setAttribute('id', $r['id']);
				$record->appendChild($key);
			}
			$dnxNode->appendChild($section);
		}

		$xmlData->appendChild($dnxNode);
	}
}
