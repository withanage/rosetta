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
	private int $journalId = 10000;
	private FunctionalRosettaExportTest $functionalRosettaExportTest;

	private Submission $submission ;
	private Section $section;
	private Issue  $issue;


	public function __construct(FunctionalRosettaExportTest $functionalRosettaExportTest)
	{
		$this->functionalRosettaExportTest = $functionalRosettaExportTest;
		$this->setContext($this->getPrimaryLocale(), $this->getJournalId());
		$this->createIssue();
		$this->createSection();
		$this->createSubmission($this->getSection());
		$this->createAuthors($this->getSubmission());
		$this->setGalleys($this->getSubmission());
		$this->createOAI($this->getSection(), $this->getIssue());
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

	public function createOAI( ): void
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
			->will($this->functionalRosettaExportTest->returnValue($this->getSection()));
		$oaiDao->expects($this->functionalRosettaExportTest->any())
			->method('getIssue')
			->will($this->functionalRosettaExportTest->returnValue($this->getIssue()));
		DAORegistry::registerDAO('OAIDAO', $oaiDao);
	}


	/**
	 * @param string $primaryLocale
	 * @return Section
	 */
	public function createSection(): Section
	{

		$section = new Section();
		$section->setIdentifyType('section-identify-type', $this->getPrimaryLocale());
		$this->setSection($section);
		return $section;
	}

	public function getContext()
	{
		return $this->setContext($this->getPrimaryLocale(), $this->getJournalId());
	}


	public function createIssue(): Issue
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
		$this->issue = $issue;
		return $issue;
	}

	/**
	 * @param Author $author
	 */
	public function createAuthors(): array
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
		$author->setSubmissionId($this->getSubmission()->getId());
		$authors[] = $author;
		return $authors;
	}



	public function createSubmission(): Submission
	{

		$submission = $this->functionalRosettaExportTest->getMockBuilder(Submission::class)
			->setMethods(array('getBestId'))
			->getMock();
		$submission->expects($this->functionalRosettaExportTest->any())
			->method('getBestId')
			->will($this->functionalRosettaExportTest->returnValue(9));
		$submission->setId(9);
		$submission->setJournalId($this->getId());
		$submission->setPages(15);
		$submission->setData('type','art-type', $this->getPrimaryLocale());
		$submission->setData('title','article-title-en', $this->getPrimaryLocale());
		$submission->setData('title','article-title-de', 'de_DE');
		$submission->setData('discipline','article-discipline', $this->getPrimaryLocale());
		$submission->setSubject('article-subject', $this->getPrimaryLocale());
		$submission->setData('abstract','article-abstract', $this->getPrimaryLocale());
		$submission->setData('sponsor','article-sponsor', $this->getPrimaryLocale());
		$submission->setData('pub-id::doi', 'article-doi');
		$submission->setLanguage($this->getPrimaryLocale());
		$submission->setSectionId($this->getSection()->getId());
		$submission->setData('issueId',$this->getIssue()->getData('id'));



		$publication = $this->createPublication($submission);
		$submission->setData('publications', [$publication]);

		$this->setSubmission($submission);
		return $submission;
	}

	public function createPublication(Submission $submission)
	{
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$publication = $publicationDao->newDataObject();
		/** @var $publication PKPPublication */

		$publication->setData('submissionId', $submission->getId());

		$publication->stampModified();

		if (empty($publicationLocale))
			$publicationLocale = $this->getPrimaryLocale();

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

	/**
	 * @return Submission
	 */
	public function getSubmission(): Submission
	{
		return $this->submission;
	}

	/**
	 * @param Submission $submission
	 */
	public function setSubmission(Submission $submission): void
	{
		$this->submission = $submission;
	}

	/**
	 * @return Section
	 */
	public function getSection(): Section
	{
		return $this->section;
	}

	/**
	 * @param Section $section
	 */
	public function setSection(Section $section): void
	{
		$this->section = $section;
	}

	/**
	 * @return Issue
	 */
	public function getIssue(): Issue
	{
		return $this->issue;
	}

	public function setIssue(Issue  $issue): void
	{
		$this->issue = $issue;
	}


}
