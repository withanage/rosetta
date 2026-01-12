<?php

/**
 * @file plugins/importexport/rosetta/tests/functional/RosettaFunctionsTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaFunctionsTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\functional;

use APP\core\Application;
use APP\core\PageRouter;
use APP\plugins\importexport\rosetta\RosettaExportPlugin;
use APP\plugins\importexport\rosetta\tests\functional\xml\dublincore\DublinCore;
use APP\plugins\importexport\rosetta\tests\functional\xml\mets\Mets;
use PKP\core\PKPRouter;
use PKP\plugins\PluginRegistry;
use PKP\tests\plugins\PluginTestCase;

class RosettaFunctionsTest extends PluginTestCase
{
	public function getPlugin(): RosettaExportPlugin
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
			$router = new PageRouter();
			$request->setRouter($router);
		} else {
			$router = $request->getRouter();
		}

		return $router;
	}

	protected function getMockedDAOs(): array
	{
		return array('AuthorDAO', 'OAIDAO', 'ArticleGalleyDAO', 'IssueDAO');
	}

	protected function getMockedRegistryKeys(): array
	{
		return array('request');
	}
}
