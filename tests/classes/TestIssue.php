<?php

import('classes.issue.Issue');



class TestIssue extends Issue

{

	function __construct()
	{

		$this->initialize();
	}

	public function initialize(): void
	{

		$this->setId(1);
		$this->setJournalId(10000);
		$this->setVolume(1);
		$this->setYear(2024);
		$this->setNumber(1);
		$this->setDatePublished('2010-11-05');
		$this->setStoredPubId('doi', '10.1234/jpkjpk.v1i2');


	}


}
