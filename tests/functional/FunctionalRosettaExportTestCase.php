<?php


require_mock_env('env2');

import('plugins.importexport.rosetta.tests.functional.TestJournal');
import('plugins.importexport.rosetta.RosettaExportPlugin');
import('plugins.importexport.rosetta.RosettaExportDeployment');
import('lib.pkp.tests.plugins.PluginTestCase');

require_mock_env('env2');

import('lib.pkp.tests.PKPTestCase');

import('lib.pkp.classes.oai.OAIStruct');
import('lib.pkp.classes.oai.OAIUtils');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormat_DC');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormatPlugin_DC');
import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.classes.services.PKPSchemaService'); // Constants


class FunctionalRosettaExportTest extends PluginTestCase
{
	protected TestJournal $TestJournal;
	protected string $primaryLocale = 'en_US';
	private int $journalId = 10000;

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->TestJournal = new TestJournal($this);
		$this->getTestJournal()->createOAI($this->getContext(), $this->getSection(), $this->getIssue());
		$this->getTestJournal()->createAuthors($this->getSubmission());
		$this->getTestJournal()->setGalleys($this->getSubmission());
		$this->getTestJournal()->setIssue($this->getTestJournal());

	}

	/**
	 * @return TestJournal
	 */
	public function getTestJournal(): TestJournal
	{
		return $this->TestJournal;
	}

	/**
	 * @param TestJournal $TestJournal
	 */
	public function setTestJournal(TestJournal $TestJournal): void
	{
		$this->TestJournal = $TestJournal;
	}

	public function getContext()
	{
		return $this->getTestJournal()->setContext($this->getPrimaryLocale(), $this->getJournalId());
	}

	/**
	 * @return string
	 */
	public function getPrimaryLocale(): string
	{
		return $this->primaryLocale;
	}

	/**
	 * @param string $primaryLocale
	 */
	public function setPrimaryLocale(string $primaryLocale): void
	{
		$this->primaryLocale = $primaryLocale;
	}

	/**
	 * @return int
	 */
	public function getJournalId(): int
	{
		return $this->journalId;
	}

	/**
	 * @param int $journalId
	 */
	public function setJournalId(int $journalId): void
	{
		$this->journalId = $journalId;
	}

	public function getSection(): Section
	{
		return $this->getTestJournal()->createSection($this->getContext());
	}

	public function getIssue(): Issue
	{
		return $this->getTestJournal()->setIssue($this->getContext());
	}

	public function getSubmission(): Submission
	{
		return $this->getTestJournal()->createSubmission($this->getContext(), $this->getSection());
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

		$this->testDublinCore();
		$this->testMets();

	}

	public function testDublinCore(): void
	{
		$dcDom = new RosettaDCDom($this->getContext(), $this->getLatestPublication(), false);
		$nodeModified = $dcDom->getElementsByTagName('dcterms:modified')->item(0);
		$nodeModified->parentNode->removeChild($nodeModified);

		$dcXml = join(DIRECTORY_SEPARATOR, array(getcwd(), $this->getPlugin()->getPluginPath(), 'tests', 'data', 'dc.xml'));

		$this->assertXmlStringEqualsXmlFile($dcXml, $dcDom->saveXML());
	}

	public function getLatestPublication(): ?Publication
	{
		return $this->getSubmission()->getLatestPublication();
	}

	public function getPlugin()
	{
		$importExportPlugins = PluginRegistry::loadCategory('importexport');
		return $importExportPlugins['RosettaExportPlugin'];
	}

	public function testMets(): void
	{

		$metsDom = new RosettaMETSDom($this->getContext(), $this->getSubmission(), $this->getLatestPublication(), $this->getPlugin());
		$nodeModified = $metsDom->getElementsByTagName('dcterms:modified')->item(0);
		$nodeModified->parentNode->removeChild($nodeModified);

		$doc = new DOMDocument();
		$doc->loadXML(file_get_contents(join(DIRECTORY_SEPARATOR, array(getcwd(), $this->getPlugin()->getPluginPath(), 'tests', 'data', 'ie1.xml'))));
		$nodeModified = $doc->getElementsByTagNameNS('http://purl.org/dc/terms/', 'modified')->item(0);//all namespaces, all local names
		$nodeModified->parentNode->removeChild($nodeModified);

		$this->assertEquals(array_filter(preg_split('/\r\n|\r|\n/', $metsDom->saveXML())), array_filter(preg_split('/\r\n|\r|\n/', $doc->saveXML())));

	}

	function getRouterUrl($request, $newContext = null, $handler = null, $op = null, $path = null)
	{
		return $handler . '-' . $op . '-' . implode('-', $path);
	}

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
