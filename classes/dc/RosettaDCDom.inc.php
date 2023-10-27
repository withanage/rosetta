<?php
/**
 * @file plugins/importexport/rosetta/classes/dc/RosettaDCDom.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RosettaDCDom
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief Represents a DOM structure for creating Dublin Core metadata for Rosetta export.
 *
 * @property DOMElement $record The root element of the Dublin Core record.
 * @property Publication $publication The publication for which Dublin Core metadata is being generated.
 * @property Context $context The context associated with the publication.
 * @property string $locale The locale associated with the publication.
 * @property array $supportedFormLocales An array of supported form locales within the context.
 * @property Submission $submission The submission associated with the publication.
 */

namespace TIBHannover\Rosetta\Dc;

use Context;
use DAORegistry;
use DOMDocument;
use DOMElement;
use IssueDAO;
use Publication;
use Submission;

class RosettaDCDom extends DOMDocument
{
	public DOMElement $record;
	public Publication $publication;
	public Context $context;
	public string $locale;
	public array $supportedFormLocales;
	public Submission $submission;

	/**
	 * Constructor
	 *
	 * @param Context $context The context associated with the publication.
	 * @param Publication $publication The publication for which Dublin Core metadata is being generated.
	 * @param Submission $submission The submission associated with the publication.
	 * @param bool $isMultilingual Flag indicating if the metadata should be multilingual.
	 */
	public function __construct(Context $context, Publication $publication, Submission $submission, bool $isMultilingual = true)
	{
		$this->context = $context;
		$this->publication = $publication;
		parent::__construct('1.0', 'UTF-8');
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		$this->locale = $publication->getData('locale');
		$this->supportedFormLocales = $context->getSupportedFormLocales();
		$this->submission = $submission;

		$this->createInstance($isMultilingual);
	}

	/**
	 * @param bool $isMultilingual
	 *
	 * @return void
	 */
	public function createInstance(bool $isMultilingual): void
	{
		$this->createRootElement();

		// title
		if ($isMultilingual) {
			$titles = $this->getPublication()->getData('title');
			foreach ($titles as $language => $title) {
				$this->createQualifiedElement('dc:title', $title, $language);
			}
		} else {
			$node = $this->createElement('dc:title', $this->getPublication()->getLocalizedTitle());
			$this->record->appendChild($node);
		}

		// authors
		$authors = $this->getPublication()->getData('authors');
		foreach ($authors as $author) {
			if ($author->getPrimaryContact()) {
				$this->createElementDCTerms('dc:creator', $author->getFullName());
			}
			{
				$this->createQualifiedElement('dc:creator', $author->getFullName());
			}
		}

		// date published
		$copyrightYear = $this->getPublication()->getData('datePublished');
		$this->createElementDCTerms('dc:date', $copyrightYear);

		// context
		$acronym = $this->getContext()->getData('acronym', 'en_US');

		// issue
		$issueDao = DAORegistry::getDAO('IssueDAO');
		/* @var $issueDao IssueDao */

		$issue = $issueDao->getById($this->publication->getId(), $this->getContext());
		$rosettaIssue = '';
		if ($issue) {
			$rosettaIssue = 'Open Access E-Journals/TIB OP/' . $acronym . '/' . $issue->getData('year') . '/' .
				$issue->getData('volume') . '/' . $issue->getData('id') . '/';
		}
		$this->createElementDCTerms('dcterms:isPartOf', $rosettaIssue);

		// abstract
		$abstracts = $this->getPublication()->getData('abstract');
		if ($abstracts) {
			foreach ($abstracts as $language => $abstract) {
				$this->createQualifiedElement('dcterms:abstract',
					str_replace('&nbsp;', ' ', strip_tags($abstract)), $language);
			}
		}

		// categories
		// Copyright year

		$copyrightYear = $this->getPublication()->getData('copyrightYear');
		if ($copyrightYear) {
			$this->createElementDCTerms('dcterms:issued', $copyrightYear);
		}
		// identifiers
		$node = $this->createElement('dc:identifier', htmlspecialchars(
			'DOI:' . $this->getPublication()->getStoredPubId('doi'), ENT_COMPAT, 'UTF-8'));
		$xsiType = $this->createAttribute('xsi:type');
		$xsiType->value = 'dcterms:URI';
		$node->appendChild($xsiType);
		$this->record->appendChild($node);

		// last modified
		$dateModified = $this->getPublication()->getData('lastModified');
		$this->createElementDCTerms('dcterms:modified', $dateModified);

		// publisher
		$publisher = $this->context->getData('publisherInstitution');
		$this->createQualifiedElement('dc:publisher', $publisher);

		//type
		$this->createQualifiedElement('dc:type', 'status-type:publishedVersion');
		$this->createQualifiedElement('dc:type', 'doc-type:article');
		$this->createElementDCTerms('dcterms:license', 'TIB_OJS_Lizenzvereinbarung');

		//language
		$this->createQualifiedElement('dc:language',
			str_replace('_', '-', $this->getPublication()->getData('locale')));

		//license URL
		if ($this->context->getData('licenseUrl')) {
			$this->createQualifiedElement('dc:rights', $this->context->getData('licenseUrl'));
		}
		if ($this->context->getData('copyrightHolderOther')) {
			foreach ($this->context->getData('copyrightHolderOther') as $locale => $copyrightHolderOther) {
				$this->createQualifiedElement('dc:rights',
					$this->context->getData('copyrightHolderOther')[$locale]);
			}
		}
	}

	/**
	 * @return void
	 */
	private function createRootElement(): void
	{
		$this->record = $this->createElementNS('http://purl.org/dc/elements/1.1/',
			'dc:record');
		$this->record->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dcterms',
			'http://purl.org/dc/terms/');
		$this->record->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi',
			'http://www.w3.org/2001/XMLSchema-instance');
		$this->record->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc',
			'http://purl.org/dc/elements/1.1/');
		$this->appendChild($this->record);
	}

	/**
	 * @return Publication
	 */
	public function getPublication(): Publication
	{
		return $this->publication;
	}

	/**
	 * @param string $qName
	 * @param string $value
	 * @param string $locale
	 */
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

	/**
	 * @param string $qualifiedName
	 * @param string $value
	 * @param string $locale
	 *
	 * @return void
	 */
	private function createElementDCTerms(string $qualifiedName, string $value, string $locale = ''): void
	{
		if (!empty($value)) {
			$node = $this->createElement($qualifiedName, $value);
			if (strlen($locale) > 0) {
				$langAttr = $this->createAttribute('xml:lang');
				$langAttr->value = $locale;
				$node->appendChild($langAttr);
			}
			$this->record->appendChild($node);
		}
	}

	/**
	 * @return Context
	 */
	public function getContext(): Context
	{
		return $this->context;
	}

	/**
	 * @return Submission
	 */
	public function getSubmission(): Submission
	{
		return $this->submission;
	}

	/**
	 * @return DOMElement
	 */
	public function getRecord(): DOMElement
	{
		return $this->record;
	}

	/**
	 * @return string
	 */
	function getPubIdType(): string
	{
		return 'doi';
	}

	/**
	 * @return string
	 */
	private function getContextName(): string
	{
		return $this->context->getLocalizedName();
	}

	/**
	 * @return string
	 */
	private
	function getPublicationTitle(): string
	{
		return $this->getPublication()->getLocalizedTitle();
	}
}
