<?php
/**
 * @file plugins/importexport/rosetta/tests/classes/TestSubmission.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class TestSubmission
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\classes;

use APP\submission\Submission;
use PKP\galley\Galley;

class TestSubmission extends Submission
{
	public string $locale;

	public function __construct()
	{
		parent::__construct();

		$this->locale = 'en_US';

		$this->initialize();

		//$section = new TestSection($this->getSubmissionLocale());

		$issue = new TestIssue();
	}

	public function initialize(): void
	{
		$this->setId(9);
		$this->setData('contextId', 1);
		$this->setData('pages', 15);
		$this->setData('type', 'art-type', $this->locale);
		$this->setData('discipline', 'article-discipline', $this->locale);
		$this->setData('subjects', 'article-subject', $this->locale);
		$this->setData('abstract', 'article-abstract', $this->locale);
		$this->setData('sponsor', 'article-sponsor', $this->locale);
		$this->setData('pub-id::doi', 'article-doi');
		$this->setData('locale', $this->locale);

		$this->setData('sectionId', 1);
		//$this->createGalleys();

		$publication = new TestPublication($this);
		$this->setData('publications', [$publication]);
	}

	public function createGalleys(): array
	{
		$galleys = [];
		$galley = new Galley();
		$galley->setId(98);
		$galley->setData('publicationId', $this->getLatestPublication()->getId());
		$galley->setStoredPubId('doi', 'galley-doi');
		$galleys[] = $galley;
		return $galleys;
	}

	public function getSubmissionLocale(): string
	{
		return $this->locale;
	}
}
