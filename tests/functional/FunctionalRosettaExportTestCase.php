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
	private Journal $journal;

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->journal = new Journal($this);
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
		$journalId = 10000;


		// Author
		import('classes.article.Author');
		$author = new Author();
		$primaryLocale = 'en_US';
		$author->setGivenName('author-firstname', $primaryLocale);
		$author->setFamilyName('author-lastname', $primaryLocale);
		$author->setAffiliation('author-affiliation', $primaryLocale);
		$author->setEmail('someone@example.com');

		// Article
		import('classes.submission.Submission');
		$article = $this->getMockBuilder(Submission::class)
			->setMethods(array('getBestId'))
			->getMock();
		$article->expects($this->any())
			->method('getBestId')
			->will($this->returnValue(9));
		$article->setId(9);
		$article->setJournalId($journalId);
		$author->setSubmissionId($article->getId());
		$article->setPages(15);
		$article->setType('art-type', $primaryLocale);
		$article->setTitle('article-title-en', $primaryLocale);
		$article->setTitle('article-title-de', 'de_DE');
		$article->setDiscipline('article-discipline', $primaryLocale);
		$article->setSubject('article-subject', $primaryLocale);
		$article->setAbstract('article-abstract', $primaryLocale);
		$article->setSponsor('article-sponsor', $primaryLocale);
		$article->setStoredPubId('doi', 'article-doi');
		$article->setLanguage($primaryLocale);

		// Galleys
		$galleys = $this->setGalleys();

		$context = $this->createContext($primaryLocale, $journalId);

		$section = $this->createSections($primaryLocale);

		$issue = $this->createIssues($journalId);

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



		$this->createAuthors($author);
$this->createOAI($context, $section, $issue);
		$this->createGalleys($galleys);

		$importExportPlugins = PluginRegistry::loadCategory('importexport');
		$rosettaExportPlugin = $importExportPlugins['RosettaExportPlugin'];

		$deployment = new RosettaExportDeployment($context, $rosettaExportPlugin, 1);
		$submissions = $deployment->getSubmissions(true);

		$x = 1;


	}

	function routerUrl($request, $newContext = null, $handler = null, $op = null, $path = null)
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

	/**
	 * @return ArticleGalley[]
	 */
	private function setGalleys(): array
	{
		return $this->journal->setGalleys();
	}

	/**
	 * @param $context
	 * @param Section $section
	 * @param $issue
	 */
	private function createOAI($context, Section $section, $issue): void
	{
		$this->journal->createOAI($context, $section, $issue);
	}

	/**
	 * @param Author $author
	 */
	private function createAuthors(Author $author): void
	{
		$this->journal->createAuthors($author);
	}

	/**
	 * @param array $galleys
	 */
	private function createGalleys(array $galleys): void
	{
		$this->journal->createGalleys($galleys);
	}

	/**
	 * @param int $journalId
	 * @return Issue|mixed|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function createIssues(int $journalId)
	{
		return $this->journal->createIssues($journalId);
	}

	/**
	 * @param string $primaryLocale
	 * @return Section
	 */
	private function createSections(string $primaryLocale): Section
	{
		return $this->journal->createSections($primaryLocale);
	}

	/**
	 * @param string $primaryLocale
	 * @param int $journalId
	 * @return Journal|mixed|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function createContext(string $primaryLocale, int $journalId)
	{
		return $this->journal->createContext($primaryLocale, $journalId);
	}


}
