<?php

class JournalTest
{
    private FunctionalRosettaExportTest $functionalRosettaExportTest;

    public function __construct(FunctionalRosettaExportTest $functionalRosettaExportTest)
    {
        $this->functionalRosettaExportTest = $functionalRosettaExportTest;
    }

    /**
     * @param string $primaryLocale
     * @return Section
     */
    public function createSections(string $primaryLocale): Section
    {
        import('classes.journal.Section');
        $section = new Section();
        $section->setIdentifyType('section-identify-type', $primaryLocale);
        return $section;
    }

    /**
     * @param int $journalId
     * @return Issue|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    public function createIssues(int $journalId)
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
        $issue->setJournalId($journalId);
        return $issue;
    }

    /**
     * @return ArticleGalley[]
     */
    public function setGalleys(): array
    {
        import('classes.article.ArticleGalley');
        $galley = new ArticleGalley();
        $galley->setId(98);
        $galley->setStoredPubId('doi', 'galley-doi');
        $galleys = array($galley);
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
     * @param array $galleys
     */
    public function createGalleys(array $galleys): void
    {
// Create a mocked ArticleGalleyDAO that returns our test data.
        import('classes.article.ArticleGalleyDAO');
        $articleGalleyDao = $this->functionalRosettaExportTest->getMockBuilder(ArticleGalleyDAO::class)
            ->setMethods(array('getBySubmissionId'))
            ->getMock();
        $articleGalleyDao->expects($this->functionalRosettaExportTest->any())
            ->method('getBySubmissionId')
            ->will($this->functionalRosettaExportTest->returnValue($galleys));
        DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);
        // FIXME: ArticleGalleyDAO::getBySubmissionId returns iterator; array expected here. Fix expectations.
    }

    /**
     * @param Author $author
     */
    public function createAuthors(Author $author): void
    {
        import('classes.article.AuthorDAO');
        $authorDao = $this->functionalRosettaExportTest->getMockBuilder(AuthorDAO::class)
            ->setMethods(array('getBySubmissionId'))
            ->getMock();
        $authorDao->expects($this->functionalRosettaExportTest->any())
            ->method('getBySubmissionId')
            ->will($this->functionalRosettaExportTest->returnValue(array($author)));
        DAORegistry::registerDAO('AuthorDAO', $authorDao);
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
}
