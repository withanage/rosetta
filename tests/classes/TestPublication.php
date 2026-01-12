<?php

/**
 * @file plugins/importexport/rosetta/tests/classes/TestPublication.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class TestPublication
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\classes;

use APP\author\Author;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\affiliation\Affiliation;

class TestPublication extends Publication
{
	public function __construct(Submission $submission)
	{
		parent::__construct();

		$this->initialize($submission);
	}

	public function initialize(Submission $submission): self
	{
		$this->setData('submissionId', $submission->getId());
		$this->stampModified();

		$this->setData('id', 1);
		$this->setData('locale', $submission->getDefaultLocale());
		$this->setData('version', 1);
		$this->setData('seq', 'seq');
		$this->setData('accessStatus', 'access_status');
		$this->setData('status', 'status');
		$this->setData('primaryContactId', 1);
		$this->setData('urlPath', 'url_path');
		$this->setData('urlPath', 'url_path');
		$this->setData('issueId', '1');
		$this->setData('version', '1');
		$this->setData('title', 'article-title-en', $submission->getDefaultLocale());
		$this->setData('authors', $this->createAuthors());
		$this->setData('datePublished', '2023-12-25');
		$this->setData('keywords', ['keyword1', 'keyword2'], 'en_US');
		return $this;
	}

	public function createAuthors(): array
	{
		$authors = [];
		$author = new Author();
		$primaryLocale = 'en_US';
		$author->setGivenName('author-firstname', $primaryLocale);
		$author->setFamilyName('author-lastname', $primaryLocale);
		$affiliation = new Affiliation();
		$affiliation->setName(['author-affiliation', $primaryLocale]);
		$author->setAffiliations([$affiliation]);
		$author->setEmail('someone@example.com');
		$author->setSubmissionId($this->getId());
		$authors[] = $author;
		return $authors;
	}
}
