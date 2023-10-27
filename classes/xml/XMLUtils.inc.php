<?php
/**
 * @file plugins/importexport/rosetta/classes/xml/XMLUtils.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class XMLUtils
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief Utility class for creating and managing METS XML sections for Rosetta digital preservation.
 */

namespace TIBHannover\Rosetta\Xml;

use DOMElement;
use TIBHannover\Rosetta\Mets\RosettaMETSDom;

class XMLUtils
{
    /**
     * Create METS XML sections for the Intellectual Entity (IE) Administrative Metadata (IE Amd).
     *
     * This method creates METS XML sections for IE Amd and appends them to the provided METS document.
     * Each section is defined by a name, type, IE Amd identifier, and associated administrative section.
     *
     * @param RosettaMETSDom $document The METS XML document to which sections will be added.
     * @param array $sectionsArray An array containing metadata sections and their records.
     * @param string $name The name of the METS section.
     * @param string $type The type of metadata.
     * @param string $ieAmd The identifier for IE Amd.
     * @param DOMElement $adminSec The administrative section to which sections will be appended.
     * 
     * @return void
     */
    public static function createIEAmdSections(RosettaMETSDom $document, array $sectionsArray, string $name,
                                               string $type, string $ieAmd, DOMElement $adminSec): void
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
