<?php
declare(strict_types=1);


use PHPUnit\Framework\MockObject\MockObject;

require_mock_env('env2');

import('plugins.importexport.rosetta.tests.functional.xml.mets.Mets');
import('plugins.importexport.rosetta.tests.functional.xml.dublincore.DublinCore');
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


class RosettaFunctionsTest extends PluginTestCase
{

	public function getPlugin(): Plugin
	{
		$importExportPlugins = PluginRegistry::loadCategory('importexport');
		return $importExportPlugins['RosettaExportPlugin'];
	}

	public function testDublincore()
	{
		$dublinCore = new DublinCore();
		$dublinCore->testDublincore($this);
	}

	public function testMets()
	{
		$mets = new Mets();
		$mets->testMets($this);
	}

	public function createRouter(): PKPRouter
	{
		$request = Application::get()->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		} else {
			$router = $request->getRouter();
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
