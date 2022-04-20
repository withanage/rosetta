<?php

use PHPUnit\Framework\MockObject\MockObject;

import('classes.article.ArticleGalley');
import('classes.article.ArticleGalleyDAO');
import('classes.journal.Journal');
import('classes.submission.Submission');
import('classes.issue.Issue');
import('classes.journal.Section');


class TestJournal extends Journal
{
	protected string $primaryLocale = 'en_US';
	private FunctionalRosettaExportTest $functionalRosettaExportTest;
	private int $journalId = 10000;


	public function __construct(FunctionalRosettaExportTest $functionalRosettaExportTest)
	{
		$this->functionalRosettaExportTest = $functionalRosettaExportTest;
		$this->setContext($this->getPrimaryLocale(), $this->getJournalId());
		$this->createOAI($this->getSection(), $this->getIssue());
		$this->createAuthors($this->getSubmission());
		$this->setGalleys($this->getSubmission());
		$this->setIssue();
	}

	/**
	 * @param string $primaryLocale
	 * @param int $journalId
	 * @return Journal|mixed|MockObject
	 */
	public function setContext(string $primaryLocale, int $journalId)
	{
		$this->setPrimaryLocale($primaryLocale);
		$this->setData('acronym', 'Testjournal', $primaryLocale);

		$journalSettings = array(
			'id' => $journalId,
			'urlPath' => 'journal-path',

			'name' => 'Test Journal'
		);
		foreach ($journalSettings as $key => $value) {
			$this->setData($key, $value);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPrimaryLocale(): string
	{
		return $this->primaryLocale;
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
	 * @param $context
	 * @param Section $section
	 * @param $issue
	 */
	public function createOAI(Section $section, $issue): void
	{
		import('classes.oai.ojs.OAIDAO');
		$oaiDao = $this->functionalRosettaExportTest->getMockBuilder(OAIDAO::class)
			->setMethods(array('getJournal', 'getSection', 'getIssue'))
			->getMock();
		$oaiDao->expects($this->functionalRosettaExportTest->any())
			->method('getJournal')
			->will($this->functionalRosettaExportTest->returnValue($this));
		$oaiDao->expects($this->functionalRosettaExportTest->any())
			->method('getSection')
			->will($this->functionalRosettaExportTest->returnValue($section));
		$oaiDao->expects($this->functionalRosettaExportTest->any())
			->method('getIssue')
			->will($this->functionalRosettaExportTest->returnValue($issue));
		DAORegistry::registerDAO('OAIDAO', $oaiDao);
	}

	public function getSection(): Section
	{
		return $this->createSection($this->getContext());
	}

	/**
	 * @param string $primaryLocale
	 * @return Section
	 */
	public function createSection(Journal $context): Section
	{

		$section = new Section();
		$section->setIdentifyType('section-identify-type', $context->getPrimaryLocale());
		return $section;
	}

	public function getContext()
	{
		return $this->setContext($this->getPrimaryLocale(), $this->getJournalId());
	}

	public function getIssue(): Issue
	{
		return $this->setIssue($this->getContext());
	}

	public function setIssue(): Issue
	{
		$issue = $this->functionalRosettaExportTest->getMockBuilder(Issue::class)
			->setMethods(array('getIssueIdentification'))
			->getMock();
		$issue->expects($this->functionalRosettaExportTest->any())
			->method('getIssueIdentification')
			->will($this->functionalRosettaExportTest->returnValue('issue-identification'));
		$issue->setId(96);
		$issue->setDatePublished('2010-11-05');
		$issue->setStoredPubId('doi', 'issue-doi');
		$issue->setJournalId($this->getData('id'));
		return $issue;
	}

	/**
	 * @param Author $author
	 */
	public function createAuthors(Submission $submission): array
	{
		$authors = [];
		import('classes.article.AuthorDAO');
		$authorDao = $this->functionalRosettaExportTest->getMockBuilder(AuthorDAO::class)
			->setMethods(array('getBySubmissionId'))
			->getMock();
		DAORegistry::registerDAO('AuthorDAO', $authorDao);
		import('classes.article.Author');
		$author = new Author();
		$primaryLocale = 'en_US';
		$author->setGivenName('author-firstname', $primaryLocale);
		$author->setFamilyName('author-lastname', $primaryLocale);
		$author->setAffiliation('author-affiliation', $primaryLocale);
		$author->setEmail('someone@example.com');
		$author->setSubmissionId($submission->getId());
		$authors[] = $author;
		return $authors;
	}

	public function getSubmission(): Submission
	{
		return $this->createSubmission($this->getContext(), $this->getSection());
	}

	/**
	 * @param int $journalId
	 * @param string $context- >getPrimaryLocale()
	 */
	public function createSubmission(Journal $context, Section $section): Submission
	{

		$submission = $this->functionalRosettaExportTest->getMockBuilder(Submission::class)
			->setMethods(array('getBestId'))
			->getMock();
		$submission->expects($this->functionalRosettaExportTest->any())
			->method('getBestId')
			->will($this->functionalRosettaExportTest->returnValue(9));
		$submission->setId(9);
		$submission->setJournalId($context->getId());
		$submission->setPages(15);
		$submission->setType('art-type', $context->getPrimaryLocale());
		$submission->setTitle('article-title-en', $context->getPrimaryLocale());
		$submission->setTitle('article-title-de', 'de_DE');
		$submission->setDiscipline('article-discipline', $context->getPrimaryLocale());
		$submission->setSubject('article-subject', $context->getPrimaryLocale());
		$submission->setAbstract('article-abstract', $context->getPrimaryLocale());
		$submission->setSponsor('article-sponsor', $context->getPrimaryLocale());
		$submission->setStoredPubId('doi', 'article-doi');
		$submission->setLanguage($context->getPrimaryLocale());
		$submission->setSectionId($section->getId());
		$publication = $this->createPublication($context, $submission);
		$submission->setData('publications', [$publication]);
		return $submission;
	}

	public function createPublication(Journal $context, Submission $submission)
	{
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$publication = $publicationDao->newDataObject();
		/** @var $publication PKPPublication */

		$publication->setData('submissionId', $submission->getId());

		$publication->stampModified();

		if (empty($publicationLocale))
			$publicationLocale = $context->getPrimaryLocale();

		$publication->setData('id', 1);
		$publication->setData('locale', $publicationLocale);
		$publication->setData('version', 1);
		$publication->setData('seq', 'seq');
		$publication->setData('accessStatus', 'access_status');
		$publication->setData('status', 'status');
		$publication->setData('primaryContactId', 1);
		$publication->setData('urlPath', 'url_path');
		return $publication;

	}

	/**
	 * @return ArticleGalley[]
	 */
	public function setGalleys(Submission $submission): array
	{
		$galleys = [];
		$articleGalleyDao = $this->functionalRosettaExportTest->getMockBuilder(ArticleGalleyDAO::class)
			->setMethods(array('getBySubmissionId'))
			->getMock();
		DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);
		$galley = new ArticleGalley();
		$galley->setId(98);
		$galley->setData('publicationId', $submission->getLatestPublication()->getId());
		$galley->setStoredPubId('doi', 'galley-doi');
		$galleys[] = $galley;
		return $galleys;
	}

}
