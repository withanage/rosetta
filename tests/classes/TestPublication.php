<?php
import('classes.publication.Publication');
import('classes.article.Author');

class TestPublication extends Publication
{
	public function __construct(Submission $submission)
	{
		$this->initialize($submission);
	}

	public function initialize(Submission $submission)
	{
		$this->setData('submissionId', $submission->getId());
			$this->stampModified();

		$this->setData('id', 1);
		$this->setData('locale', $submission->getLocale());
		$this->setData('version', 1);
		$this->setData('seq', 'seq');
		$this->setData('accessStatus', 'access_status');
		$this->setData('status', 'status');
		$this->setData('primaryContactId', 1);
		$this->setData('urlPath', 'url_path');
		$this->setData('urlPath', 'url_path');
		$this->setData('issueId', '1');
		$this->setData('version', '1');
		$this->setData('title', 'article-title-en', $submission->getLocale());
		$this->setData('authors', $this->createAuthors());
		$this->setData('datePublished', '2023-12-25');
		$this->setData('keywords', ['keyword1','keyword2'],'en_US');
		return $this;

	}

	public function createAuthors(): array
	{
		$authors = [];
		$author = new Author();
		$primaryLocale = 'en_US';
		$author->setGivenName('author-firstname', $primaryLocale);
		$author->setFamilyName('author-lastname', $primaryLocale);
		$author->setAffiliation('author-affiliation', $primaryLocale);
		$author->setEmail('someone@example.com');
		$author->setSubmissionId($this->getId());
		$authors[] = $author;
		return $authors;
	}




}
