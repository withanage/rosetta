<?php

/**
 * @file plugins/importexport/rosetta/tests/functional/xml/mets/Mets.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class Mets
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\functional\xml\mets;

use APP\plugins\importexport\rosetta\classes\xml\mets\RosettaMetsDom;
use APP\plugins\importexport\rosetta\tests\classes\TestJournal;
use APP\plugins\importexport\rosetta\tests\classes\TestSubmission;
use APP\plugins\importexport\rosetta\tests\functional\RosettaFunctionsTest;
use APP\plugins\importexport\rosetta\tests\functional\xml\utils\General;

class Mets
{
    public function testMets(RosettaFunctionsTest $rosettaFunctionsTest): void
    {
        $nodeNames = ['dcterms:modified', 'dcterms:isPartOf'];

        $rosettaFunctionsTest->createRouter();
        $testSubmission = new TestSubmission();
        $testJournal = new TestJournal();

        $metsDom = new RosettaMETSDom($testJournal, $testSubmission, $testSubmission->getLatestPublication(), $rosettaFunctionsTest->getPlugin(), true);
        General::removeNodesListFromDom($metsDom, $nodeNames);

        $metsFile = join(DIRECTORY_SEPARATOR, array(getcwd(), $rosettaFunctionsTest->getPlugin()->getPluginPath(), 'tests', 'data', 'ie1.xml'));

        $rosettaFunctionsTest->assertXmlStringEqualsXmlFile($metsFile, $metsDom->saveXML());
    }
}
