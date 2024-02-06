<?php


use PHPUnit\Framework\MockObject\MockObject;

import('classes.article.ArticleGalley');
import('classes.article.ArticleGalleyDAO');
import('classes.journal.Journal');


class TestJournal extends Journal
{
	protected string $primaryLocale = 'en_US';
	private int $journalId = 10000;



	public function __construct()
	{
		$this->initialize($this->getPrimaryLocale());
		//$this->createIssue();
		//$this->createOAI($this->getSection(), $this->getIssue());
	}

		public function initialize()
	{
		$this->setPrimaryLocale($this->primaryLocale);
		$this->setData('acronym', 'Testjournal', $this->primaryLocale);
		$this->setData('supportedFormLocales', ['en_US']);

		$journalSettings = array(
			'id' => $this->journalId,
			'urlPath' => 'journal-path',
			'publisherInstitution' => 'Publisher',
			'name' => 'Test Journal',
			'onlineIssn'=> '2747-9986'
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


}
