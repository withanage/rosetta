<?php


require_mock_env('env2');


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


	/**
	 * @covers OAIMetadataFormat_DC
	 * @covers Dc11SchemaArticleAdapter
	 */
	public function testToXml()
	{
		#$this->markTestSkipped('Skipped because of weird class interaction with ControlledVocabDAO.');

		$request = Application::get()->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		}
		//
		// Create test data.
		//
		$journalId = 1;


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
		import('classes.article.ArticleGalley');
		$galley = new ArticleGalley();
		$galley->setId(98);
		$galley->setStoredPubId('doi', 'galley-doi');
		$galleys = array($galley);

		// Journal
		import('classes.journal.Journal');
		$journal = $this->getMockBuilder(Journal::class)
			->setMethods(array('getSetting'))
			->getMock();
		$journal->expects($this->any())
			->method('getSetting') // includes getTitle()
			->will($this->returnCallback(array($this, 'getJournalSetting')));
		$journal->setPrimaryLocale($primaryLocale);
		$journal->setName('Test_Journal',$primaryLocale);
		$journalSettings = array(
			'id' => $journalId,
			'urlPath' => 'journal-path',

		);
		foreach ($journalSettings as $key => $value) {
			$journal->setData($key, $value);
		}

		// Section
		import('classes.journal.Section');
		$section = new Section();
		$section->setIdentifyType('section-identify-type', $primaryLocale);

		// Issue
		import('classes.issue.Issue');
		$issue = $this->getMockBuilder(Issue::class)
			->setMethods(array('getIssueIdentification'))
			->getMock();
		$issue->expects($this->any())
			->method('getIssueIdentification')
			->will($this->returnValue('issue-identification'));
		$issue->setId(96);
		$issue->setDatePublished('2010-11-05');
		$issue->setStoredPubId('doi', 'issue-doi');
		$issue->setJournalId($journalId);


		//
		// Create infrastructural support objects
		//

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


		//
		// Create mock DAOs
		//

		// Create a mocked AuthorDAO that returns our test author.
		import('classes.article.AuthorDAO');
		$authorDao = $this->getMockBuilder(AuthorDAO::class)
			->setMethods(array('getBySubmissionId'))
			->getMock();
		$authorDao->expects($this->any())
			->method('getBySubmissionId')
			->will($this->returnValue(array($author)));
		DAORegistry::registerDAO('AuthorDAO', $authorDao);

		// Create a mocked OAIDAO that returns our test data.
		import('classes.oai.ojs.OAIDAO');
		$oaiDao = $this->getMockBuilder(OAIDAO::class)
			->setMethods(array('getJournal', 'getSection', 'getIssue'))
			->getMock();
		$oaiDao->expects($this->any())
			->method('getJournal')
			->will($this->returnValue($journal));
		$oaiDao->expects($this->any())
			->method('getSection')
			->will($this->returnValue($section));
		$oaiDao->expects($this->any())
			->method('getIssue')
			->will($this->returnValue($issue));
		DAORegistry::registerDAO('OAIDAO', $oaiDao);

		// Create a mocked ArticleGalleyDAO that returns our test data.
		import('classes.article.ArticleGalleyDAO');
		$articleGalleyDao = $this->getMockBuilder(ArticleGalleyDAO::class)
			->setMethods(array('getBySubmissionId'))
			->getMock();
		$articleGalleyDao->expects($this->any())
			->method('getBySubmissionId')
			->will($this->returnValue($galleys));
		DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);
		// FIXME: ArticleGalleyDAO::getBySubmissionId returns iterator; array expected here. Fix expectations.
		$importExportPlugins = PluginRegistry::loadCategory('importexport');
		$rosettaExportPlugin = $importExportPlugins['RosettaExportPlugin'];

		$deployment = new RosettaExportDeployment($journal, $rosettaExportPlugin, 1);
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


}
