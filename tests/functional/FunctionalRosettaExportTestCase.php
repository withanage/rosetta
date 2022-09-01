<?php


require_mock_env('env2');

import('plugins.importexport.rosetta.tests.functional.TestJournal');
import('plugins.importexport.rosetta.RosettaExportPlugin');
import('plugins.importexport.rosetta.RosettaExportDeployment');
import('lib.pkp.tests.plugins.PluginTestCase');


import('lib.pkp.tests.PKPTestCase');

import('lib.pkp.classes.oai.OAIStruct');
import('lib.pkp.classes.oai.OAIUtils');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormat_DC');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormatPlugin_DC');
import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.classes.services.PKPSchemaService');


class FunctionalRosettaExportTest extends PluginTestCase
{
	var bool $isTest = true;
	protected TestJournal $testJournal;

	/**
	 * @param null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->testJournal = new TestJournal($this);


	}

	/**
	 * @covers OAIMetadataFormat_DC
	 * @covers Dc11SchemaArticleAdapter
	 */
	public function testSipContent()
	{

		$request = Application::get()->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		}
		$router = $this->getRouter();
		$this->getRequest($router);

		$this->validateDublinCore();
		$this->validateMets($this->getIsTest());

	}

	/**
	 * @return mixed|\PHPUnit\Framework\MockObject\MockObject|PKPRouter
	 */
	public function getRouter()
	{
		import('lib.pkp.classes.core.PKPRouter');
		$router = $this->getMockBuilder(PKPRouter::class)
			->setMethods(array('url'))
			->getMock();
		$application = Application::get();
		$router->setApplication($application);
		$router->expects($this->any())
			->method('url')
			->will($this->returnCallback(array($this, 'getRouterUrl')));
		return $router;
	}

	/**
	 * @param $router
	 * @return mixed|\PHPUnit\Framework\MockObject\MockObject|Request
	 */
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

	/**
	 * validate dublinCore
	 */
	public function validateDublinCore(): void
	{
		$dcDom = new RosettaDCDom($this->getTestJournal()->getContext(), $this->getTestJournal()->getSubmission()->getLatestPublication(), false);

		$this->removeCustomNodes($dcDom);


		$dcXml = join(DIRECTORY_SEPARATOR, array(getcwd(), $this->getPlugin()->getPluginPath(), 'tests', 'data', 'dc.xml'));

		$this->assertXmlStringEqualsXmlFile($dcXml, $dcDom->saveXML());
	}

	/**
	 * @return TestJournal
	 */
	public function getTestJournal(): TestJournal
	{
		return $this->testJournal;
	}

	/**
	 * @param TestJournal $TestJournal
	 */
	public function setTestJournal(TestJournal $TestJournal): void
	{
		$this->testJournal = $TestJournal;
	}

	/**
	 * @param $dcDom
	 */
	public function removeCustomNodes($dcDom): void
	{
		$nodeModified = $dcDom->getElementsByTagName('dcterms:modified')->item(0);
		if ($nodeModified) $nodeModified->parentNode->removeChild($nodeModified);

		$nodePartOf = $dcDom->getElementsByTagName('dcterms:isPartOf')->item(0);
		if ($nodePartOf) $nodePartOf->parentNode->removeChild($nodePartOf);
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin
	{
		$importExportPlugins = PluginRegistry::loadCategory('importexport');
		return $importExportPlugins['RosettaExportPlugin'];
	}

	/**
	 * @param bool $isTest
	 */
	public function validateMets(bool $isTest): void
	{

		$metsDom = new RosettaMETSDom($this->getTestJournal()->getContext(), $this->getTestJournal()->getSubmission(), $this->getTestJournal()->getSubmission()->getLatestPublication(), $this->getPlugin(), $isTest);
		$metsDom->preserveWhiteSpace = false;
		$metsDom->formatOutput = true;
		$this->removeCustomNodes($metsDom);

		$expectedDom = new DOMDocument();
		$expectedDom->preserveWhiteSpace = false;
		$expectedDom->formatOutput = true;
		$expectedDom->loadXML(file_get_contents(join(DIRECTORY_SEPARATOR, array(getcwd(), $this->getPlugin()->getPluginPath(), 'tests', 'data', 'ie1.xml'))));

		$regExLineBreaks = '/\r\n|\r|\n|\t/';
		$generatedXML = $metsDom->saveXML();
		$expectedXML = $expectedDom->saveXML();
		//$this->assertEqualsCanonicalizing(array_filter(preg_split($regExLineBreaks, $generatedXML)), array_filter(preg_split($regExLineBreaks, $expectedXML)));


	}

	/**
	 * @return bool
	 */
	public function getIsTest(): bool
	{
		return $this->isTest;
	}

	/**
	 * @param bool $isTest
	 */
	public function setIsTest(bool $isTest): void
	{
		$this->isTest = $isTest;
	}

	/**
	 * @return Publication|null
	 */
	public function getLatestPublication(): ?Publication
	{
		return $this->getSubmission()->getLatestPublication();
	}

	function getRouterUrl($request, $newContext = null, $handler = null, $op = null, $path = null)
	{
		return $handler . '-' . $op . '-' . implode('-', $path);
	}

	/**
	 * @see PKPTestCase::getMockedDAOs()
	 */
	protected function getMockedDAOs()
	{
		return array('AuthorDAO', 'OAIDAO', 'ArticleGalleyDAO');
	}

	/**
	 * @see PKPTestCase::getMockedRegistryKeys()
	 */
	protected function getMockedRegistryKeys()
	{
		return array('request');
	}


}
