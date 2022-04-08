<?php


require_mock_env('env2');

import('plugins.importexport.rosetta.tests.data.JournalTest');
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
	protected JournalTest $journalTest;
	private int $journalId = 10000;
	protected string $primaryLocale = 'en_US';

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->journalTest = new JournalTest($this);
	}


	/**
	 * @covers OAIMetadataFormat_DC
	 * @covers Dc11SchemaArticleAdapter
	 */
	public function testToXml()
	{

		$request = Application::get()->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		}
		//
		// Create test data.
		//

		$context =$this->getJournalTest()->createContext($this->getPrimaryLocale(), $this->getJournalId());
		$issue = $this->getJournalTest()->createIssue($context);
		$section = $this->getJournalTest()->createSection($context);
		$this->getJournalTest()->createOAI($context, $section, $issue);

		$submission = $this->getJournalTest()->createSubmission($context, $section);
		$this->getJournalTest()->createAuthors($submission);
		$this->getJournalTest()->createGalleys($submission);
		// Article


		// Router
		import('lib.pkp.classes.core.PKPRouter');
		$router = $this->getMockBuilder(PKPRouter::class)
			->setMethods(array('url'))
			->getMock();
		$application = Application::get();
		$router->setApplication($application);
		$router->expects($this->any())
			->method('url')
			->will($this->returnCallback(array($this, 'routerUrl')));

		// Request
		import('classes.core.Request');
		$request = $this->getMockBuilder(Request::class)
			->setMethods(array('getRouter'))
			->getMock();
		$request->expects($this->any())
			->method('getRouter')
			->will($this->returnValue($router));
		Registry::set('request', $request);



		$dcDom = new RosettaDCDom($context, $submission->getLatestPublication(), false);
		$nodeModified = $dcDom->getElementsByTagName('dcterms:modified')->item(0);
		$nodeModified->parentNode->removeChild($nodeModified);
		$dcXml = join(DIRECTORY_SEPARATOR, array(getcwd(), $this->getPlugin()->getPluginPath(), 'tests','data','dc.xml'));
		$this->assertXmlStringEqualsXmlFile($dcXml,$dcDom->saveXML());

		//check mets
		$metsDom = new RosettaMETSDom($context, $submission, $submission->getLatestPublication(), $this->getPlugin());
		$nodeModified1 = $metsDom->getElementsByTagName('dcterms:modified')->item(0);
		$nodeModified1->parentNode->removeChild($nodeModified1);

		$saveXML = $metsDom->saveXML();
		$c2 = preg_split('/\r\n|\r|\n/', $saveXML);
		$ie1Xml = join(DIRECTORY_SEPARATOR, array(getcwd(), $this->getPlugin()->getPluginPath(), 'tests','data','ie1.xml'));
		$doc = new DOMDocument();
		$doc->loadXML(file_get_contents($ie1Xml));

		$nodeModified2=$doc->getElementsByTagNameNS('http://purl.org/dc/terms/','modified')->item(0);//all namespaces, all local names

		$nodeModified2->parentNode->removeChild($nodeModified2);
		#$this->assertEquals(preg_split('/\r\n|\r|\n/', $metsDom->saveXML()), preg_split('/\r\n|\r|\n/', ));
		$c1 = preg_split('/\r\n|\r|\n/', $doc->saveXML());
		$this->assertEquals(array_filter($c2), array_filter($c1));



		$x= 1;
	}

	/**
	 * @return JournalTest
	 */
	public function getJournalTest(): JournalTest
	{
		return $this->getJournalTest();
	}

	/**
	 * @param JournalTest $journalTest
	 */
	public function setJournalTest(JournalTest $journalTest): void
	{
		$this->getJournalTest = $journalTest;
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

	function routerUrl($request, $newContext = null, $handler = null, $op = null, $path = null)
	{
		return $handler . '-' . $op . '-' . implode('-', $path);
	}

	public function getPlugin()
	{
		$importExportPlugins = PluginRegistry::loadCategory('importexport');
		return $importExportPlugins['RosettaExportPlugin'];
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
