<?php
declare(strict_types=1);



use PHPUnit\Framework\MockObject\MockObject;
use TIBHannover\Rosetta\Dc\RosettaDCDom as RosettaDCDom;
use TIBHannover\Rosetta\Mets\RosettaMETSDom as RosettaMETSDom;

require_mock_env('env2');


import('plugins.importexport.rosetta.tests.classes.TestSubmission');
import('plugins.importexport.rosetta.tests.classes.TestJournal');

import('plugins.importexport.rosetta.RosettaExportPlugin');
import('plugins.importexport.rosetta.RosettaExportDeployment');
import('lib.pkp.tests.plugins.PluginTestCase');
import('lib.pkp.tests.plugins.metadata.MetadataPluginTestCase');

import('lib.pkp.tests.PKPTestCase');

import('lib.pkp.classes.oai.OAIStruct');
import('lib.pkp.classes.oai.OAIUtils');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormat_DC');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormatPlugin_DC');
import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.classes.services.PKPSchemaService');


class RosettaFunctionalTest extends PluginTestCase
{



		public function getRequest($router)
	{
		import('classes.core.Request');
		$request = $this->getMockBuilder(Request::class)
			->setMethods(array('getRouter'))
			->getMock();
		$request->expects($this->any())
			->method('getRouter')
			->will($this->returnValue($router));
		Registry::set('request', $request);
		return $request;
	}

		public function getRouter()
	{
		$application = Application::get();

		import('lib.pkp.classes.core.PKPRouter');
		$router = $this->getMockBuilder(PKPRouter::class)
			->setMethods(array('url'))
			->getMock();
		$router->setApplication($application);
		$router->expects($this->any())
			->method('url')
			->will($this->returnCallback(array($this, 'getRouterUrl')));

		return $router;
	}

		public function testDublinCore(): void
	{

		$this->createRouter();
		$testJournal = new TestJournal();
		$testSubmission = new TestSubmission();
		$latestPublication = $testSubmission->getLatestPublication();

		$dublinCoreFile = join(DIRECTORY_SEPARATOR, array(getcwd(), $this->getPlugin()->getPluginPath(), 'tests', 'data', 'dc.xml'));

		$dcDom = new RosettaDCDom($testJournal, $latestPublication, $testSubmission, false);
		$this->removeUnnecessaryNodes($dcDom);

		$this->assertXmlStringEqualsXmlFile($dublinCoreFile, $dcDom->saveXML());
	}



		public function removeUnnecessaryNodes($dcDom): void
	{
		$nodeModified = $dcDom->getElementsByTagName('dcterms:modified')->item(0);
		if ($nodeModified) $nodeModified->parentNode->removeChild($nodeModified);

		$nodePartOf = $dcDom->getElementsByTagName('dcterms:isPartOf')->item(0);
		if ($nodePartOf) $nodePartOf->parentNode->removeChild($nodePartOf);
	}

		public function getPlugin(): Plugin
	{
		$importExportPlugins = PluginRegistry::loadCategory('importexport');
		return $importExportPlugins['RosettaExportPlugin'];
	}

		public function testMets(): void
	{
		$regExLineBreaks = '/\r\n|\r|\n|\t/';

		$testSubmission = new TestSubmission();
		$testJournal = new TestJournal();

		$metsDom = new RosettaMETSDom($testJournal,$testSubmission,$testSubmission->getLatestPublication(), $this->getPlugin(), true);
		$metsDom->preserveWhiteSpace = false;
		$metsDom->formatOutput = true;
		$this->removeUnnecessaryNodes($metsDom);
		$ieFile = join(DIRECTORY_SEPARATOR, array(getcwd(), $this->getPlugin()->getPluginPath(), 'tests', 'data', 'ie1.xml'));

		$this->assertXmlStringEqualsXmlFile($ieFile, $metsDom->saveXML());


	}


	function getRouterUrl($request, $newContext = null, $handler = null, $op = null, $path = null)
	{
		return $handler . '-' . $op . '-' . implode('-', $path);
	}


	/**
	 * @return PKPRouter
	 */
	public function createRouter(): PKPRouter
	{
		$request = Application::get()->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		}
		return $router;
	}

	protected function getMockedDAOs()
	{
		return array('AuthorDAO', 'OAIDAO', 'ArticleGalleyDAO');
	}

		protected function getMockedRegistryKeys()
	{
		return array('request');
	}


}
