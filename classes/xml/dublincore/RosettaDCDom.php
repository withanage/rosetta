<?php

/**
 * @file plugins/importexport/rosetta/classes/xml/dublincore/RosettaDCDom.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaDCDom
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes\xml\dublincore;

use APP\publication\Publication;
use APP\submission\Submission;
use DOMDocument;
use DOMElement;
use PKP\context\Context;
use PKP\db\DAORegistry;

class RosettaDCDom extends DOMDocument
{

    public string $XML_NS = 'http://www.w3.org/2000/xmlns/';
    public DOMElement $record;
    public Publication $publication;
    public Context $context;
    public string $locale;
    public bool $isMultilingual = true;
    public array $supportedFormLocales;
    public Submission $submission;

    public function __construct(Context $context, Publication $publication, Submission $submission)
    {
        parent::__construct('1.0', 'UTF-8');

        $this->context = $context;
        $this->publication = $publication;
        $this->preserveWhiteSpace = false;
        $this->formatOutput = true;
        $this->locale = $publication->getData('locale');
        $this->supportedFormLocales = $context->getSupportedFormLocales();
        $this->submission = $submission;
        $this->createInstance();
    }

    public function createInstance(): void
    {
        $acronym = $this->context->getData('acronym', 'en_US');

        $this->createDCElement();
        $this->createQualifiedElement('dc:type', 'status-type:publishedVersion');
        $this->createQualifiedElement('dc:type', 'doc-type:article');
        $this->createQualifiedElement('dcterms:license', 'TIB_OJS_Lizenzvereinbarung');
        $this->createTitle();
        $this->createAuthors();
        $this->createPublishedDate();
        $this->createIssue();
        $this->createAbstracts();
        $this->createCopyrightYear();
        $this->createIdentifier();
        $this->createLastModifiedDate();
        $this->createPublisherInstitution();
        $this->createLanguage();
        $this->createLicenseURL();
        $this->createCopyrightHolderOther();
        $this->createISSN();
        $this->createQualifiedElement('dcterms:hasVersion', 'Version ' . $this->publication->getData('version'));
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

    public function createPublishedDate(): void
    {
        $datePublished = $this->publication->getData('datePublished');
        $this->createQualifiedElement('dc:date', $datePublished);
    }

    public function createIssue(): void
    {
        $issn = $this->context->getData('onlineIssn');
        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issue = $issueDao->getById($this->publication->getData('issueId'));
        if ($issue) {
            $rosettaIssue = 'Open Access E-Journals/TIB OP/' . $issn . '/' . $issue->getData('year') . '/' . $issue->getData('volume');
            $this->createQualifiedElement('dcterms:isPartOf', $rosettaIssue);
        } else {
            error_log('Issue id ' . $this->publication->getId() . ' not found\n', 3, Utils::logFilePath());
        }
    }

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

    public function createCopyrightYear(): void
    {
        $copyrightYear = $this->publication->getData('copyrightYear');
        if ($copyrightYear) {
            $this->createQualifiedElement('dcterms:issued', $copyrightYear);
        }
    }

    public function createIdentifier(): void
    {
        $node = $this->createElement('dc:identifier', htmlspecialchars(
            'DOI:' . $this->publication->getStoredPubId('doi'), ENT_COMPAT, 'UTF-8'));
        $typeAttribute = $this->createAttribute('xsi:type');
        $typeAttribute->value = 'dcterms:URI';
        $node->appendChild($typeAttribute);

        $this->record->appendChild($node);
    }

    public function createLastModifiedDate(): void
    {
        $dateModified = $this->publication->getData('lastModified');
        $this->createQualifiedElement('dcterms:modified', $dateModified);
    }

    public function createPublisherInstitution(): void
    {
        $publisher = $this->context->getData('publisherInstitution');
        $this->createQualifiedElement('dc:publisher', $publisher);
    }

    public function createLanguage(): void
    {
        $this->createQualifiedElement('dc:language',
            str_replace('_', '-', $this->publication->getData('locale')));
    }

    public function createLicenseURL(): void
    {
        if ($this->publication->getData('licenseUrl')) {
            $this->createQualifiedElement('dc:rights', $this->publication->getData('licenseUrl'));
        } elseif ($this->context->getData('licenseUrl')) {
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

    public function createISSN(): void
    {
        $issn = $this->context->getData('onlineIssn');

        if ($issn) {
            $node = $this->createElement('dc:identifier', $issn);
            $typeAttribute = $this->createAttribute('xsi:type');
            $typeAttribute->value = 'dcterms:ISSN';
            $node->appendChild($typeAttribute);
            $this->record->appendChild($node);
        }
    }

    public function getRecord(): DOMElement
    {
        return $this->record;
    }

    public function createTitle(): void
    {
        $node = $this->createElement('dc:title', $this->publication->getLocalizedTitle());
        $this->record->appendChild($node);
    }
}
