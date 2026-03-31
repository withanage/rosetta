<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Xml/TestDataFactoryTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class TestDataFactoryTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Tests for TestDataFactory — verifies the factory creates valid test objects.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Xml;

use APP\issue\Issue;
use APP\journal\Journal;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\plugins\importexport\rosetta\tests\Support\TestDataFactory;
use PHPUnit\Framework\TestCase;

class TestDataFactoryTest extends TestCase
{
	public function testCreateJournalWithDefaults(): void
	{
		$journal = TestDataFactory::createJournal();

		$this->assertInstanceOf(Journal::class, $journal);
		$this->assertSame(10000, $journal->getData('id'));
		$this->assertSame('en_US', $journal->getPrimaryLocale());
		$this->assertSame('2747-9986', $journal->getData('onlineIssn'));
	}

	public function testCreateJournalWithOverrides(): void
	{
		$journal = TestDataFactory::createJournal(['id' => 99, 'onlineIssn' => '0000-0001']);

		$this->assertSame(99, $journal->getData('id'));
		$this->assertSame('0000-0001', $journal->getData('onlineIssn'));
	}

	public function testCreateSubmissionWithDefaults(): void
	{
		$submission = TestDataFactory::createSubmission();

		$this->assertInstanceOf(Submission::class, $submission);
		$this->assertSame(9, $submission->getId());
		$this->assertSame('en_US', $submission->getData('locale'));
	}

	public function testCreatePublicationWithDefaults(): void
	{
		$publication = TestDataFactory::createPublication();

		$this->assertInstanceOf(Publication::class, $publication);
		$this->assertSame(1, $publication->getData('id'));
		$this->assertSame('2023-12-25', $publication->getData('datePublished'));
		$this->assertNotEmpty($publication->getData('authors'));
	}

	public function testCreateIssueWithDefaults(): void
	{
		$issue = TestDataFactory::createIssue();

		$this->assertInstanceOf(Issue::class, $issue);
		$this->assertSame(1, $issue->getData('volume'));
		$this->assertSame(2024, $issue->getData('year'));
	}

	public function testCreateSubmissionWithPublicationLinksCorrectly(): void
	{
		$submission = TestDataFactory::createSubmissionWithPublication();

		$this->assertNotNull($submission->getLatestPublication());
		$this->assertSame($submission->getId(), $submission->getLatestPublication()->getData('submissionId'));
	}

	public function testCreateAuthorsReturnsArray(): void
	{
		$authors = TestDataFactory::createAuthors();

		$this->assertIsArray($authors);
		$this->assertCount(1, $authors);
	}

	public function testCreateAuthorsWithCustomData(): void
	{
		$authors = TestDataFactory::createAuthors([
			['givenName' => 'Jane', 'familyName' => 'Doe', 'email' => 'jane@example.com'],
			['givenName' => 'John', 'familyName' => 'Smith', 'email' => 'john@example.com'],
		]);

		$this->assertCount(2, $authors);
	}
}
