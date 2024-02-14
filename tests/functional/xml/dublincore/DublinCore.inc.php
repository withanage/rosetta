<?php

use TIBHannover\Rosetta\Dc\RosettaDCDom;
import('plugins.importexport.rosetta.tests.functional.xml.utils.General');

class DublinCore
{
	public function testDublincore(RosettaFunctionsTest $rosettaFunctionsTest): void
	{
		$nodeNames = ['dcterms:modified', 'dcterms:isPartOf'];

		$rosettaFunctionsTest->createRouter();
		$testJournal = new TestJournal();
		$testSubmission = new TestSubmission();
		$testIssue = new TestIssue();
		$testSubmission->setData('issueId',$testIssue->getData('id'));
		$latestPublication = $testSubmission->getLatestPublication();
		$dublinCoreFile = join(DIRECTORY_SEPARATOR, array(getcwd(), $rosettaFunctionsTest->getPlugin()->getPluginPath(), 'tests', 'data', 'dc.xml'));

		$dcDom = new RosettaDCDom($testJournal, $latestPublication, $testSubmission, false);
		General::removeNodesListFromDom($dcDom, $nodeNames);
		$rosettaFunctionsTest->assertXmlStringEqualsXmlFile($dublinCoreFile, $dcDom->saveXML());
	}
}
