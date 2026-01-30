<?php

/**
 * @file plugins/importexport/rosetta/classes/xml/XmlUtils.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class XmlUtils
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes\xml;

use APP\plugins\importexport\rosetta\classes\xml\mets\RosettaMetsDom;
use DOMElement;

class XmlUtils
{
	public static function createIEAmdSections(RosettaMetsDom $document, array $sectionsArray, string $name,
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
