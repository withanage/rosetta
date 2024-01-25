<?php

import('plugins.importexport.rosetta.tests.functional.xml.utils.General');

class Mets
{

	public function testMets(RosettaFunctionsTest $rosettaFunctionsTest): void

	{
		$nodeNames = ['dcterms:modified', 'dcterms:isPartOf'];

		$rosettaFunctionsTest->createRouter();
		$testSubmission = new TestSubmission();
		$testJournal = new TestJournal();

		$metsDom = new \TIBHannover\Rosetta\Mets\RosettaMETSDom($testJournal, $testSubmission, $testSubmission->getLatestPublication(), $rosettaFunctionsTest->getPlugin(), true);
		General::removeNodesListFromDom($metsDom, $nodeNames);

		$metsFile = join(DIRECTORY_SEPARATOR, array(getcwd(), $rosettaFunctionsTest->getPlugin()->getPluginPath(), 'tests', 'data', 'ie1.xml'));

		$rosettaFunctionsTest->assertXmlStringEqualsXmlFile($metsFile, $metsDom->saveXML());


	}
}
