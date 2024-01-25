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

		$issue->setId(96);
		$issue->setDatePublished('2010-11-05');
		$issue->setStoredPubId('doi', 'issue-doi');
		$issue->setJournalId($this->getData('id'));
		$this->issue = $issue;

	}


}
