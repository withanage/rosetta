<?php

/**
 * @file plugins/importexport/rosetta/tests/functional/xml/dublincore/DublinCore.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class DublinCore
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\functional\xml\dublincore;

use APP\plugins\importexport\rosetta\classes\xml\dublincore\RosettaDcDom;
use APP\plugins\importexport\rosetta\tests\classes\TestIssue;
use APP\plugins\importexport\rosetta\tests\classes\TestJournal;
use APP\plugins\importexport\rosetta\tests\classes\TestSubmission;
use APP\plugins\importexport\rosetta\tests\functional\RosettaFunctionsTest;
use APP\plugins\importexport\rosetta\tests\functional\xml\utils\General;

class DublinCore
{
	public function testDublincore(RosettaFunctionsTest $rosettaFunctionsTest): void
	{
		$nodeNames = ['dcterms:modified'];

		$rosettaFunctionsTest->createRouter();
		$testJournal = new TestJournal();
		$testSubmission = new TestSubmission();
		$testIssue = new TestIssue();
		$testSubmission->setData('issueId', $testIssue->getData('id'));
		$latestPublication = $testSubmission->getLatestPublication();
		$dublinCoreFile = join(DIRECTORY_SEPARATOR, [getcwd(), $rosettaFunctionsTest->getPlugin()->getPluginPath(), 'tests', 'data', 'dc.xml']);

		$dcDom = new RosettaDcDom($testJournal, $latestPublication, $testSubmission, false);
		General::removeNodesListFromDom($dcDom, $nodeNames);
		$saveXML = $dcDom->saveXML();
		$rosettaFunctionsTest->assertXmlStringEqualsXmlFile($dublinCoreFile, $saveXML);
	}
}
