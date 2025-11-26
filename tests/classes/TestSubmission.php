<?php
/**
 * @file plugins/importexport/rosetta/tests/classes/TestIssue.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class TestIssue
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\classes;

use APP\submission\Submission;
use PKP\db\DAORegistry;

class TestSubmission extends Submission
{
    public string $locale;

    public function __construct()
    {
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->locale = 'en_US';
        $this->inititalize();
        //$section = new TestSection($this->getSubmissionLocale());

        $issue = new TestIssue();
    }

    public function createGalleys(): array
    {
        $galleys = [];
        $articleGalleyDao = $this->currentTest
            ->getMockBuilder(ArticleGalleyDAO::class)
            ->setMethods(array('getBySubmissionId'))
            ->getMock();
        DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);
        $galley = new ArticleGalley();
        $galley->setId(98);
        $galley->setData('publicationId', $this->getLatestPublication()->getId());
        $galley->setStoredPubId('doi', 'galley-doi');
        $galleys[] = $galley;
        return $galleys;
    }

    public function getSubmissionLocale()
    {
        return $this->locale;
    }

    public function inititalize()
    {
        $this->setId(9);
        $this->setJournalId(1);
        $this->setPages(15);
        $this->setData('type', 'art-type', $this->getSubmissionLocale());
        $this->setData('discipline', 'article-discipline', $this->getSubmissionLocale());
        $this->setSubject('article-subject', $this->getSubmissionLocale());
        $this->setData('abstract', 'article-abstract', $this->getSubmissionLocale());
        $this->setData('sponsor', 'article-sponsor', $this->getSubmissionLocale());
        $this->setData('pub-id::doi', 'article-doi');
        $this->setData('locale', 'en_US');

        $this->setSectionId(1);
        //$this->createGalleys();

        $publication = new TestPublication($this);
        $this->setData('publications', [$publication]);
    }
}
