<?php

namespace TIBHannover\Rosetta\Dc;

use Context;
use DAORegistry;
use DOMDocument;
use DOMElement;
use DOMException;
use Publication;
use Submission;

class RosettaDCDom extends DOMDocument
{

	public string $XML_NS = 'http://www.w3.org/2000/xmlns/';
	public DOMElement $record;
	public Publication $publication;
	public Context $context;
	public string $locale;
	public array $supportedFormLocales;
	public Submission $submission;

	public function __construct(Context $context, Publication $publication, Submission $submission, bool $isMultilingual = true)
	{
		parent::__construct('1.0', 'UTF-8');

		$this->context = $context;
		$this->publication = $publication;
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		$this->locale = $publication->getData('locale');
		$this->supportedFormLocales = $context->getSupportedFormLocales();
		$this->submission = $submission;

		$this->createInstance($isMultilingual);
	}

	public function createInstance(bool $isMultilingual): void
	{
		$acronym = $this->context->getData('acronym', 'en_US');

		$this->createDCElement();

		$this->createQualifiedElement('dc:type', 'status-type:publishedVersion');

		$this->createQualifiedElement('dc:type', 'doc-type:article');

		$this->createQualifiedElement('dcterms:license', 'TIB_OJS_Lizenzvereinbarung');


		// title
		if ($isMultilingual) {
			$titles = $this->publication->getData('title');
			foreach ($titles as $language => $title) {
				$this->createQualifiedElement('dc:title', $title, $language);
			}
		} else {
			$node = $this->createElement('dc:title', $this->publication->getLocalizedTitle());
			$this->record->appendChild($node);
		}


		$this->createAuthors();

		$this->createPublishedDate();

		$this->createIssue($acronym);

		$this->createAbstracts();

		$this->createCopyrightYear();

		try {
			$this->createIdentifier();
		} catch (DOMException $e) {

		}

		$this->createlastModifiedDate();


		$this->createPublisherInstitution();

		$this->createLanguage();

		$this->createLicenseURL();

		$this->createCopyrightHolderOther();
	}

	private function createDCElement(): void
	{


		$this->record = $this->createElementNS('http://purl.org/dc/elements/1.1/',
			'dc:record');

		$this->record->setAttributeNS($this->XML_NS, 'xmlns:dcterms',
			'http://purl.org/dc/terms/');
		$this->record->setAttributeNS($this->XML_NS, 'xmlns:xsi',
			'http://www.w3.org/2001/XMLSchema-instance');
		$this->record->setAttributeNS($this->XML_NS, 'xmlns:dc',
			'http://purl.org/dc/elements/1.1/');
		$this->appendChild($this->record);
	}


	private function createQualifiedElement(string $qName, string $value, string $locale = ''): void
	{
		if (!empty($value)) {
			$node = $this->createElement($qName, $value);
			if (strlen($locale) > 0) {
				$langAttr = $this->createAttribute('xml:lang');
				$langAttr->value = $locale;
				$node->appendChild($langAttr);
			}
			$this->record->appendChild($node);
		}
	}

	public function createAuthors(): void
	{
		$authors = $this->publication->getData('authors');
		foreach ($authors as $author) {
			{
				$this->createQualifiedElement('dc:creator', $author->getFullName());
			}
		}
	}

	/**
	 * @return void
	 */
	public function createPublishedDate(): void
	{
		$datePublished = $this->publication->getData('datePublished');
		$this->createQualifiedElement('dc:date', $datePublished);
	}

	/**
	 * @param mixed $acronym
	 * @return void
	 */
	public function createIssue(mixed $acronym): void
	{
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getById($this->publication->getId(), $this->context->getId());
		$rosettaIssue = '';
		if ($issue) {
			$rosettaIssue = 'Open Access E-Journals/TIB OP/' . $acronym . '/' . $issue->getData('year') . '/' .
				$issue->getData('volume') . '/' . $issue->getData('id') . '/';
		}
		$this->createQualifiedElement('dcterms:isPartOf', $rosettaIssue);
	}

	/**
	 * @return void
	 */
	public function createAbstracts(): void
	{
		$abstracts = $this->publication->getData('abstract');
		if ($abstracts) {
			foreach ($abstracts as $language => $abstract) {
				$this->createQualifiedElement('dcterms:abstract',
					str_replace('&nbsp;', ' ', strip_tags($abstract)), $language);
			}
		}
	}

	/**
	 * @return void
	 */
	public function createCopyrightYear(): void
	{
		$copyrightYear = $this->publication->getData('copyrightYear');
		if ($copyrightYear) {
			$this->createQualifiedElement('dcterms:issued', $copyrightYear);
		}
	}

	/**
	 * @return void
	 * @throws DOMException
	 */
	public function createIdentifier(): void
	{
		$node = $this->createElement('dc:identifier', htmlspecialchars(
			'DOI:' . $this->publication->getStoredPubId('doi'), ENT_COMPAT, 'UTF-8'));

		$this->record->appendChild($node);
	}

	/**
	 * @return void
	 */
	public function createlastModifiedDate(): void
	{
		$dateModified = $this->publication->getData('lastModified');
		$this->createQualifiedElement('dcterms:modified', $dateModified);
	}

	/**
	 * @return void
	 */
	public function createPublisherInstitution(): void
	{
		$publisher = $this->context->getData('publisherInstitution');
		$this->createQualifiedElement('dc:publisher', $publisher);
	}

	/**
	 * @return void
	 */
	public function createLanguage(): void
	{
		$this->createQualifiedElement('dc:language',
			str_replace('_', '-', $this->publication->getData('locale')));
	}

	public function createLicenseURL(): void
	{
		if ($this->context->getData('licenseUrl')) {
			$this->createQualifiedElement('dc:rights', $this->context->getData('licenseUrl'));
		}
	}

	public function createCopyrightHolderOther(): void
	{
		if ($this->context->getData('copyrightHolderOther')) {
			foreach ($this->context->getData('copyrightHolderOther') as $locale => $copyrightHolderOther) {
				$this->createQualifiedElement('dc:rights',
					$this->context->getData('copyrightHolderOther')[$locale]);
			}
		}
	}

	public function getRecord(): DOMElement
	{
		return $this->record;
	}


}
