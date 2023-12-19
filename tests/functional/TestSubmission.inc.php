<?php


use PHPUnit\Framework\MockObject\MockObject;

class TestSubmission extends Submission
{

	public function createSubmission(): Submission
	{

		$submission = $this->functionalRosettaExportTest->getMockBuilder(Submission::class)
			->setMethods(array('getBestId'))
			->getMock();
		$submission->expects($this->functionalRosettaExportTest->any())
			->method('getBestId')
			->will($this->functionalRosettaExportTest->returnValue(9));
		$submission->setId(9);
		$submission->setJournalId($this->getId());
		$submission->setPages(15);
		$submission->setData('type', 'art-type', $this->getPrimaryLocale());
		$submission->setData('discipline', 'article-discipline', $this->getPrimaryLocale());
		$submission->setSubject('article-subject', $this->getPrimaryLocale());
		$submission->setData('abstract', 'article-abstract', $this->getPrimaryLocale());
		$submission->setData('sponsor', 'article-sponsor', $this->getPrimaryLocale());
		$submission->setData('pub-id::doi', 'article-doi');
		$submission->setLanguage($this->getPrimaryLocale());
		$submission->setSectionId($this->getSection()->getId());
		$submission->setData('issueId', $this->getIssue()->getData('id'));



		$publication = $this->createPublication($submission);

		$submission->setData('publications', [$publication]);

		$this->setSubmission($submission);
		return $submission;
	}
}
