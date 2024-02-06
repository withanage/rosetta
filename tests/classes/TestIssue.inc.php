<?php

import('classes.issue.Issue');



class TestIssue extends Issue

{
	public $issue;

	function __construct(Issue $issue)
	{
		$this->issue =$issue;
		$this->initialize();
	}

	public function initialize(): void
	{

		$this->issue->setId(96);
		$this->issue->setDatePublished('2010-11-05');
		$this->issue->setStoredPubId('doi', 'issue-doi');
		$this->issue->setJournalId($this->getData('id'));


	}


}
