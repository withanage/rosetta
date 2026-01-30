<?php

/**
 * @file plugins/importexport/rosetta/tests/classes/TestJournal.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class TestJournal
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\classes;

use APP\journal\Journal;
use APP\oai\ojs\OAIDAO;
use PKP\db\DAORegistry;

class TestJournal extends Journal
{
	protected string $primaryLocale = 'en_US';
	private int $journalId = 10000;

	public function __construct()
	{
		parent::__construct();

		$this->initialize();

		// $this->createOAI($this->getSection(), $this->getIssue());
	}

	public function initialize(): self
	{
		$this->setPrimaryLocale($this->primaryLocale);
		$this->setData('acronym', 'TestJournal', $this->primaryLocale);
		$this->setData('supportedFormLocales', ['en_US']);

		$journalSettings = array(
			'id' => $this->journalId,
			'urlPath' => 'journal-path',
			'publisherInstitution' => 'Publisher',
			'name' => 'Test Journal',
			'onlineIssn' => '2747-9986'
		);
		foreach ($journalSettings as $key => $value) {
			$this->setData($key, $value);
		}
		return $this;
	}

	public function getPrimaryLocale(): string
	{
		return $this->primaryLocale;
	}

	public function getJournalId(): int
	{
		return $this->journalId;
	}

	public function setJournalId(int $journalId): void
	{
		$this->journalId = $journalId;
	}

	public function createOAI(): void
	{
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
}
