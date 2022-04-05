<?php

class JournalTest
{
    private FunctionalRosettaExportTest $functionalRosettaExportTest;

    public function __construct(FunctionalRosettaExportTest $functionalRosettaExportTest)
    {
        $this->functionalRosettaExportTest = $functionalRosettaExportTest;
    }


	public function  createPublication(Journal $context, Submission $submission) {
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$publication = $publicationDao->newDataObject(); /** @var $publication PKPPublication */

		$publication->setData('submissionId', $submission->getId());

		$publication->stampModified();

		if (empty($publicationLocale))
			$publicationLocale = $context->getPrimaryLocale();

		$publication->setData('id', 1);
		$publication->setData('locale', $publicationLocale);
		$publication->setData('version', 'version');
		$publication->setData('seq', 'seq');
		$publication->setData('accessStatus', 'access_status');
		$publication->setData('status', 'status');
		$publication->setData('primaryContactId', 1);
		$publication->setData('urlPath', 'url_path');
		return $publication;

	}

    /**
     * @param string $primaryLocale
     * @return Section
     */
    public function createSection(Journal $context): Section
    {
        import('classes.journal.Section');
        $section = new Section();
        $section->setIdentifyType('section-identify-type', $context->getPrimaryLocale());
		return $section;
    }


    public function createIssue(Journal $context) :Issue
    {
        import('classes.issue.Issue');
        $issue = $this->functionalRosettaExportTest->getMockBuilder(Issue::class)
            ->setMethods(array('getIssueIdentification'))
            ->getMock();
        $issue->expects($this->functionalRosettaExportTest->any())
            ->method('getIssueIdentification')
            ->will($this->functionalRosettaExportTest->returnValue('issue-identification'));
        $issue->setId(96);
        $issue->setDatePublished('2010-11-05');
        $issue->setStoredPubId('doi', 'issue-doi');
        $issue->setJournalId($context->getId());
        return $issue;
    }

    /**
     * @return ArticleGalley[]
     */
    public function createGalleys(Submission  $submission): array
    {
		$galleys = [] ;
		import('classes.article.ArticleGalleyDAO');
		$articleGalleyDao = $this->functionalRosettaExportTest->getMockBuilder(ArticleGalleyDAO::class)
			->setMethods(array('getBySubmissionId'))
			->getMock();
		DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);
		import('classes.article.ArticleGalley');
		$galley = new ArticleGalley();
		$galley->setId(98);
		$galley->setData('publicationId',$submission->getLatestPublication()->getId());
		$galley->setStoredPubId('doi', 'galley-doi');
		$galleys[] = $galley;
        return $galleys;
    }

    /**
     * @param string $primaryLocale
     * @param int $journalId
     * @return Journal|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    public function createContext(string $primaryLocale, int $journalId)
    {
        import('classes.journal.Journal');
        $context = $this->functionalRosettaExportTest->getMockBuilder(Journal::class)
            ->setMethods(array('getSetting'))
            ->getMock();
        $context->expects($this->functionalRosettaExportTest->any())
            ->method('getSetting') // includes getTitle()
            ->will($this->functionalRosettaExportTest->returnCallback(array($this, 'getJournalSetting')));
        $context->setPrimaryLocale($primaryLocale);
        $context->setData('acronym', 'Testjournal', $primaryLocale);

        $journalSettings = array(
            'id' => $journalId,
            'urlPath' => 'journal-path',

            'name' => 'Test Journal'
        );
        foreach ($journalSettings as $key => $value) {
            $context->setData($key, $value);
        }
        return $context;
    }



    /**
     * @param Author $author
     */
    public function createAuthors(Submission  $submission): array
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

    /**
     * @param $context
     * @param Section $section
     * @param $issue
     */
    public function createOAI($context, Section $section, $issue): void
    {
        import('classes.oai.ojs.OAIDAO');
        $oaiDao = $this->functionalRosettaExportTest->getMockBuilder(OAIDAO::class)
            ->setMethods(array('getJournal', 'getSection', 'getIssue'))
            ->getMock();
        $oaiDao->expects($this->functionalRosettaExportTest->any())
            ->method('getJournal')
            ->will($this->functionalRosettaExportTest->returnValue($context));
        $oaiDao->expects($this->functionalRosettaExportTest->any())
            ->method('getSection')
            ->will($this->functionalRosettaExportTest->returnValue($section));
        $oaiDao->expects($this->functionalRosettaExportTest->any())
            ->method('getIssue')
            ->will($this->functionalRosettaExportTest->returnValue($issue));
        DAORegistry::registerDAO('OAIDAO', $oaiDao);
    }
	/**
	 * @param int $journalId
	 * @param string $context->getPrimaryLocale()
	 */
	public function createSubmission(Journal $context , Section  $section):  Submission
	{
		import('classes.submission.Submission');
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
		$publication = $this->createPublication($context,$submission);
		$submission->setData('publications', [$publication]);
		return $submission;
	}

}
