<?php
import('plugins.importexport.rosetta.RosettaExportDeployment');

class RosettaDCDom extends DOMDocument {
	/* @var $record DOMElement */
	var $record;
	/* @var $publication Publication */
	var $publication;
	/* @var $context Context */
	var $context;
	/* @var $locale string */
	var $locale;

	var $supportedFormLocales;

	/**
	 * RosettaDCDom constructor.
	 * @param $context Context
	 * @param Publication $publication
	 */
	public function __construct(Context $context, Publication $publication) {
		$this->context = $context;
		$this->publication = $publication;
		parent::__construct('1.0', 'UTF-8');
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		$this->locale = $publication->getData("locale");
		$this->supportedFormLocales = $context->getSupportedFormLocales();
		$this->createInstance();

	}

	public function createInstance(): void {
		$this->createRootElement();

		// title
		$disciplines = $this->publication->getData("title");
		foreach ($disciplines as $language => $title) {
			$this->createQualifiedElement("dc:title", $title, $language);
		}

		// authors
		$authors = $this->publication->getData("authors");
		foreach ($authors as $author) {
			if ($author->getPrimaryContact()) {
				$this->createElementDCTerms("dc:creator", $author->getFullName());
			}
			{
				$this->createQualifiedElement("dc:creator", $author->getFullName());
			}

		}

		// date published
		$copyrightYear = $this->publication->getData("datePublished");
		$this->createElementDCTerms("dc:date", $copyrightYear);

		// context
		$acronym = $this->getContext()->getData("acronym", "en_US");
		$issueDao = DAORegistry::getDAO('IssueDAO'); /** @var $issueDao IssueDAO */
		$issue = $issueDao->getById($this->publication,$this->getContext());
		$rosettaIssue = 'Open Access E-Journals/TIB OP/'. $acronym.'/'.$issue->getData('year').'/'.$issue->getData('volume').'/'.$issue->getData('id').'/';
		$this->createElementDCTerms("dcterms:isPartOf", $rosettaIssue);

		// abstract
		$abstracts = $this->publication->getData("abstract");
		foreach ($abstracts as $language => $abstract) {
			$this->createQualifiedElement("dcterms:abstract", str_replace('&nbsp;', ' ', strip_tags($abstract)), $language);
		}


		//  categories

		// Copyright year
		$copyrightYear = $this->publication->getData("copyrightYear");
		$this->createElementDCTerms("dcterms:issued", $copyrightYear);


		// Issue
		$issueId = $this->publication->getData('issueId');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		/* @var $issueDao IssueDAO */
		$rosettalIssue = $issueDao->getById($issueId, $this->context->getId());

		//TODO add eindeutige id, doi usw,

		// identifiers
		$doi = $this->publication->getData("pub-id::doi");
		if ($doi !== null) {
			$node = $this->createElement("dc:identifier", htmlspecialchars('DOI:'.$doi, ENT_COMPAT, 'UTF-8'));
			$xsiType = $this->createAttribute("xsi:type");
			$xsiType->value = "dcterms:URI";
			$node->appendChild($xsiType);
			$this->record->appendChild($node);
		}

		// last modified
		$dateModified = $this->publication->getData("lastModified");
		$this->createElementDCTerms("dcterms:modified", $dateModified);


		// publisher
		$publisher = $this->context->getData("publisherInstitution");
		$this->createQualifiedElement("dc:publisher", $publisher);


		//type
		$this->createQualifiedElement("dc:type", "status-type:publishedVersion");
		$this->createQualifiedElement("dc:type", ", doc-type:article");
		$this->createElementDCTerms("dcterms:license", "TIB_OJS_Lizenzvereinbarung");

		//language
		$this->createQualifiedElement("dc:language", str_replace('_','-',$this->publication->getData('locale')));

		//license URL

		if ($this->context->getData('licenseUrl')) {
			$this->createQualifiedElement("dc:rights", $this->context->getData('licenseUrl'));
		}

		if ($this->context->getData('copyrightHolderOther')) {
			foreach ($this->context->getData('copyrightHolderOther') as $locale => $copyrightHolderOther) {
				$this->createQualifiedElement("dc:rights", $this->context->getData('copyrightHolderOther')[$locale]);
			}
		}

	}

	private function createRootElement(): void {
		$this->record = $this->createElementNS("http://purl.org/dc/elements/1.1/", "dc:record");
		$this->record->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:dcterms", "http://purl.org/dc/terms/");
		$this->record->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
		$this->record->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:dc", "http://purl.org/dc/elements/1.1/");
		$this->appendChild($this->record);
	}

	/**
	 * @param $qName
	 * @param $value
	 * @param string $locale
	 */
	private function createQualifiedElement($qName, $value, $locale = ""): void {
		if (empty($value) == false) {
			$node = $this->createElement($qName, $value);
			if (strlen($locale) > 0) {
				$langAttr = $this->createAttribute("xml:lang");
				$langAttr->value = $locale;
				$node->appendChild($langAttr);
			}
			$this->record->appendChild($node);
		}
	}

	/* @param $value
	 * @param $errorMessage
	 * @param $qualifiedName
	 */
	private function createElementDCTerms($qualifiedName, $value, $locale = ""): void {
		if (empty($value) == false) {
			$node = $this->createElement($qualifiedName, $value);
			if (strlen($locale) > 0) {
				$langAttr = $this->createAttribute("xml:lang");
				$langAttr->value = $locale;
				$node->appendChild($langAttr);
			}
			$this->record->appendChild($node);
		}
	}

	/**
	 * @return string|null
	 */
	public function getContext(): Context {
		return $this->context;
	}

	/**
	 * @return Publication
	 */
	public function getPublication(): Publication {
		return $this->publication;
	}

	/**
	 * @return DOMElement
	 */
	public function getRecord(): DOMElement {
		return $this->record;
	}

	/**
	 * @return string
	 */
	function getPubIdType(): string {
		return 'doi';
	}

	/**
	 * @return Context
	 */
	private
	function getContextName(): string {
		return $this->context->getLocalizedName();
	}

	/**
	 * @return string|null
	 */
	private
	function getPublicationTitle(): string {
		return $this->publication->getLocalizedTitle();
	}
}
