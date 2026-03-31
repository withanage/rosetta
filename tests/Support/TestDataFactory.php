<?php

/**
 * @file plugins/importexport/rosetta/tests/Support/TestDataFactory.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class TestDataFactory
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Factory for creating test data objects with sensible defaults.
 *        Each method returns a real PKP/OJS object populated with test data,
 *        avoiding tight coupling to the test framework.
 */

namespace APP\plugins\importexport\rosetta\tests\Support;

use APP\author\Author;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\LazyCollection;
use PKP\affiliation\Affiliation;

class TestDataFactory
{
	public static function createJournal(array $overrides = []): Journal
	{
		$defaults = [
			'id' => 10000,
			'urlPath' => 'journal-path',
			'publisherInstitution' => 'Publisher',
			'name' => 'Test Journal',
			'onlineIssn' => '2747-9986',
			'primaryLocale' => 'en_US',
			'acronym' => ['en_US' => 'TestJournal'],
			'supportedFormLocales' => ['en_US'],
		];

		$data = array_merge($defaults, $overrides);

		$journal = new Journal();
		$journal->setPrimaryLocale($data['primaryLocale']);

		foreach ($data as $key => $value) {
			if ($key === 'primaryLocale') continue;
			if ($key === 'acronym' && is_array($value)) {
				foreach ($value as $locale => $acronym) {
					$journal->setData('acronym', $acronym, $locale);
				}
				continue;
			}
			$journal->setData($key, $value);
		}

		return $journal;
	}

	public static function createSubmission(array $overrides = []): Submission
	{
		$defaults = [
			'id' => 9,
			'contextId' => 1,
			'pages' => 15,
			'locale' => 'en_US',
			'sectionId' => 1,
		];

		$data = array_merge($defaults, $overrides);

		$submission = new Submission();
		foreach ($data as $key => $value) {
			$submission->setData($key, $value);
		}

		return $submission;
	}

	public static function createPublication(array $overrides = [], ?Submission $submission = null): Publication
	{
		$submission = $submission ?? self::createSubmission();

		$defaults = [
			'id' => 1,
			'submissionId' => $submission->getId(),
			'locale' => $submission->getData('locale') ?? 'en_US',
			'version' => 1,
			'seq' => 'seq',
			'accessStatus' => 'access_status',
			'status' => 'status',
			'primaryContactId' => 1,
			'urlPath' => 'url_path',
			'issueId' => 1,
			'title' => ['en_US' => 'article-title-en'],
			'datePublished' => '2023-12-25',
			'keywords' => ['en_US' => ['keyword1', 'keyword2']],
		];

		$data = array_merge($defaults, $overrides);

		$publication = new Publication();
		$publication->stampModified();

		foreach ($data as $key => $value) {
			if ($key === 'title' && is_array($value)) {
				foreach ($value as $locale => $title) {
					$publication->setData('title', $title, $locale);
				}
				continue;
			}
			if ($key === 'keywords' && is_array($value)) {
				foreach ($value as $locale => $keywords) {
					$publication->setData('keywords', $keywords, $locale);
				}
				continue;
			}
			$publication->setData($key, $value);
		}

		if (!isset($overrides['authors'])) {
			$publication->setData('authors', self::createAuthors());
		}

		return $publication;
	}

	public static function createAuthors(array $authorsData = []): array
	{
		if (empty($authorsData)) {
			$authorsData = [
				[
					'givenName' => 'author-firstname',
					'familyName' => 'author-lastname',
					'affiliation' => 'author-affiliation',
					'email' => 'someone@example.com',
				],
			];
		}

		$authors = [];
		foreach ($authorsData as $authorData) {
			$locale = $authorData['locale'] ?? 'en_US';
			$author = new Author();
			$author->setGivenName($authorData['givenName'], $locale);
			$author->setFamilyName($authorData['familyName'], $locale);

			if (isset($authorData['affiliation'])) {
				$affiliation = new Affiliation();
				$affiliation->setName([$authorData['affiliation'], $locale]);
				$author->setAffiliations([$affiliation]);
			}

			if (isset($authorData['email'])) {
				$author->setEmail($authorData['email']);
			}

			$author->setSubmissionId($authorData['submissionId'] ?? 1);
			$authors[] = $author;
		}

		return $authors;
	}

	public static function createIssue(array $overrides = []): Issue
	{
		$defaults = [
			'id' => 1,
			'journalId' => 10000,
			'volume' => 1,
			'year' => 2024,
			'number' => 1,
			'datePublished' => '2010-11-05',
		];

		$data = array_merge($defaults, $overrides);

		$issue = new Issue();
		foreach ($data as $key => $value) {
			if ($key === 'journalId') {
				$issue->setJournalId($value);
				continue;
			}
			$issue->setData($key, $value);
		}

		if (isset($overrides['doi'])) {
			$issue->setStoredPubId('doi', $overrides['doi']);
		}

		return $issue;
	}

	public static function createSubmissionWithPublication(array $submissionOverrides = [], array $publicationOverrides = []): Submission
	{
		$submission = self::createSubmission($submissionOverrides);
		$publication = self::createPublication($publicationOverrides, $submission);
		$submission->setData('publications', LazyCollection::make([$publication]));

		return $submission;
	}
}
