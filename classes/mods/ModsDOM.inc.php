<?php
/**
 * @file plugins/importexport/rosetta/classes/mods/ModsDOM.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ModsDOM
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief A class for creating and managing METS MODS XML documents.
 */

namespace TIBHannover\Rosetta\Mods;

use Author;
use Context;
use DOMDocument;
use DOMElement;
use Publication;

define('MODS_NS', 'http://www.loc.gov/mods/v3');

class ModsDOM extends DOMDocument
{
    /**
     * @var Context
     */
    public Context $context;

    /**
     * @var string
     */
    public string $locale;

    /**
     * @var DOMElement
     */
    public DOMElement $record;

    /**
     * @var array
     */
    public array $supportedFormLocales;

    /**
     * @var Publication
     */
    private Publication $publication;

    /**
     * Constructor
     *
     * @param Context $context The context associated with the MODS document.
     * @param Publication $publication The publication for which MODS data will be created.
     */
    public function __construct(Context $context, Publication $publication)
    {
        parent::__construct('1.0', 'UTF-8');
        $this->context = $context;
        $this->publication = $publication;
        $this->supportedFormLocales = $context->getSupportedFormLocales();
        $this->createPublication();
    }

    /**
     * @return void
     */
    private function createPublication(): void
    {
        $this->createRootElement();

        // titleInfo
        $this->createTitleInfo();

        // abstract
        $this->createAbstract();

        // authors
        $this->createName();

        //subjects
        $this->createDataElement('disciplines', $this->publication, $this->record,
            'subject', array('authority' => 'disciplines'));
        $this->createDataElement('keywords', $this->publication, $this->record,
            'subject', array('authority' => 'keywords'));
        $this->createDataElement('languages', $this->publication, $this->record,
            'subject', array('authority' => 'languages'));
        $this->createDataElement('subjects', $this->publication, $this->record,
            'subject', array('authority' => 'subjects'));
        $this->createDataElement('supportingAgencies', $this->publication, $this->record,
            'subject', array('authority' => 'supportingAgencies'));

        // coverage
        $this->createDataElement('coverage', $this->publication,
            $this->record, 'location', array('displayLabel' => 'coverage'));

        // rights
        $this->createDataElement('rights', $this->publication,
            $this->record, 'accessCondition', array('displayLabel' => 'rights'));

        // Source
        $recordInfo = $this->createElementNS(MODS_NS, 'mods:recordInfo');
        $this->createDataElement('source', $this->publication, $recordInfo, 'recordContentSource');

        // doi
        $this->createDataElement('pub-id::doi', $this->publication,
            $this->record, 'identifier', array('type' => 'doi'));

        $languageOfCataloging = $this->createElementNS(MODS_NS, 'mods:languageOfCataloging');
        #$languageTerm = $this->createDataElement('locale',$this->publication,$languageOfCataloging,'languageTerm');
        #$recordInfo->appendChild($languageTerm);
        $this->record->appendChild($recordInfo);

        // publisher
        $originInfo = $this->createElementNS(MODS_NS, 'mods:originInfo');
        $this->createDataElement('copyrightHolder', $this->publication, $originInfo, 'publisher');
        $this->record->appendChild($originInfo);
        $this->createDataElement('type', $this->publication, $this->record, 'genre');

        // Add  Context Info

        $this->createContext($this->context);
    }

    /**
     * @return void
     */
    private function createRootElement(): void
    {
        $this->record = $this->createElementNS(MODS_NS, 'mods:mods');
        $this->record->setAttributeNS('http://www.w3.org/2000/xmlns/',
            'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->record->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance',
            'schemaLocation', 'http://www.loc.gov/standards/mods/v3/mods-3-7.xsd');
        $this->record->setAttribute('version', '3.7');
        $this->appendChild($this->record);
    }

    /**
     * @return void
     */
    private function createTitleInfo(): void
    {
        $titleInfo = $this->createElementNS(MODS_NS, 'titleInfo');
        $titles = $this->publication->getData('title');
        if ($titles) {
            foreach ($titles as $lang => $title) {
                $prefix = $this->publication->getData('prefix');
                if (!is_null($prefix) and array_key_exists($lang, $prefix)) {
                    $title = $prefix[$lang] . ' ' . $title;
                }
                $titleDom = $this->createLocalizedElement($title, 'title', $lang);
                $titleInfo->appendChild($titleDom);
                $this->record->appendChild($titleInfo);
            }
        }
        $subTitles = $this->publication->getData('subtitle');
        if ($subTitles) {
            foreach ($subTitles as $lang => $subTitle) {
                $subTitle = $this->createLocalizedElement($subTitle, 'subTitle', $lang);
                $titleInfo->appendChild($subTitle);
                $this->record->appendChild($titleInfo);
            }
        }
    }

    /**
     * @param string $value
     * @param string $qualifiedName
     * @param string $locale
     *
     * @return DOMElement
     */
    private function createLocalizedElement(string $value, string $qualifiedName, string $locale = ''): DOMElement
    {
        $node = $this->createElementNS(MODS_NS, $qualifiedName);

        if (!empty($value)) {
            $node->nodeValue = htmlspecialchars($value, ENT_XHTML, 'UTF-8');
        }

        if (strlen($locale) > 0) {
            $langAttr = $this->createAttribute('xml:lang');
            $langAttr->value = $locale;
            $node->appendChild($langAttr);
        }

        return $node;
    }

    /**
     * @return void
     */
    private function createAbstract(): void
    {
        $abstracts = $this->publication->getData('abstract');
        if ($abstracts) {
            foreach ($abstracts as $lang => $abstract) {
                $abstractDom = $this->createLocalizedElement($abstract, 'abstract', $lang);
                $this->record->appendChild($abstractDom);
            }
        }
    }

    /**
     * @return void
     */
    private function createName(): void
    {
        $authors = $this->publication->getData('authors');
        foreach ($authors as $author) {
            $nameDom = $this->createElementNS(MODS_NS, 'mods:name');
            foreach ($this->supportedFormLocales as $locale) {
                // namePart
                $authorGivenNameEmpty = !array_filter(array_values($author->getData('givenName')));
                $authorType = ($authorGivenNameEmpty) ? 'corporate' : 'personal';
                $nameDom->setAttribute('type', $authorType);
                if (array_key_exists($locale, $author->getData('familyName'))) {
                    $familyNamePartDom = $this->createElementNS(MODS_NS, 'namePart',
                        $author->getData('familyName')[$locale]);
                    $familyNamePartDom->setAttribute('xml:lang', $locale);
                    $familyNamePartDom->setAttribute('type', 'family');
                    $nameDom->appendChild($familyNamePartDom);
                }
                if (array_key_exists($locale, $author->getData('givenName'))) {
                    $givenNamePartDom = $this->createElementNS(MODS_NS, 'namePart',
                        $author->getData('givenName')[$locale]);
                    $givenNamePartDom->setAttribute('xml:lang', $locale);
                    $givenNamePartDom->setAttribute('type', 'given');
                    $nameDom->appendChild($givenNamePartDom);
                }
                // Create user properties
                $properties = array(
                    'affiliation' => 'affiliation',
                    'biography' => 'description',
                    'preferredPublicName' => 'displayForm'
                );
                foreach ($properties as $key => $value) {
                    $this->createDataElement($key, $author, $nameDom, $value);
                }
                $this->createNameRoles($author, $locale, $nameDom);

            }
            $this->createNameOrcid($author, $nameDom);
            $this->createNameURL($author, $nameDom);
            $this->createNameCountry($author, $nameDom);

            $this->record->appendChild($nameDom);

        }
    }

    /**
     * @param string $orig
     * @param mixed $dataProvider
     * @param DOMElement $parent
     * @param string $new
     * @param array $attrs
     *
     * @return void
     */
    public function createDataElement(string $orig, mixed $dataProvider, DOMElement $parent,
                                      string $new = '', array $attrs = []): void
    {
        $newElement = null;
        $data = $dataProvider->getData($orig);
        if (gettype($data) == 'array') {
            foreach ($data as $locale => $entry) {
                $elemName = (strlen($new) > 0) ? $new : $orig;
                if (gettype($entry) == 'string') {
                    $newElement = $this->createElementNS(MODS_NS, $elemName,
                        htmlspecialchars($entry, ENT_XHTML, 'UTF-8'));
                    $newElement->setAttribute('xml:lang', $locale);
                    $this->setAllAttributes($attrs, $newElement, $parent);
                }
                if (gettype($entry) == 'array') {
                    foreach ($entry as $part) {
                        $newElement = $this->createElementNS(MODS_NS, $elemName,
                            htmlspecialchars($part, ENT_XHTML, 'UTF-8'));
                        $newElement->setAttribute('xml:lang', $locale);
                        $this->setAllAttributes($attrs, $newElement, $parent);
                    }
                }
            }
        } elseif (gettype('data') == 'string') {
            $newElement = $this->createLocalizedElement($data, $new);
            $this->setAllAttributes($attrs, $newElement, $parent);
        }
    }

    /**
     * @param array $attrs
     * @param DOMElement $newElement
     * @param DOMElement $parent
     */
    private function setAllAttributes(array $attrs, DOMElement $newElement, DOMElement $parent): void
    {
        foreach ($attrs as $key => $attr) {
            $newElement->setAttribute($key, $attr);
        }
        $parent->appendChild($newElement);
    }

    /**
     * @param Author $author
     * @param string $locale
     * @param DOMElement|false $nameDom
     *
     * @return void
     */
    private function createNameRoles(Author $author, string $locale, DOMElement|false $nameDom): void
    {
        $userGroup = $author->getUserGroup();
        $role = $this->createElementNS(MODS_NS, 'mods:role');
        $roleTerm = $this->createElementNS(MODS_NS, 'mods:roleTerm', $userGroup->getName($locale));
        $roleTerm->setAttribute('xml:lang', $locale);
        $roleTerm->setAttribute('type', 'text');
        $role->appendChild($roleTerm);
        $roleTerm = $this->createElementNS(MODS_NS, 'mods:roleTerm', $userGroup->getAbbrev($locale));
        $roleTerm->setAttribute('xml:lang', $locale);
        $roleTerm->setAttribute('type', 'code');
        $role->appendChild($roleTerm);
        $nameDom->appendChild($role);
    }

    /**
     * @param Author $author
     * @param DOMElement|false $nameDom
     *
     * @return void
     */
    private function createNameOrcid(Author $author, DOMElement|false$nameDom): void
    {
        $orcidValue = $author->getData('orcid');
        if (strlen($orcidValue) > 0) {
            $orcid = $this->createElementNS(MODS_NS, 'mods:affiliation', $orcidValue);
            $orcid->setAttribute('script', 'orcid');
            $nameDom->appendChild($orcid);
        }
    }

    /**
     * @param Author $author
     * @param DOMElement|false $nameDom
     *
     * @return void
     */
    private function createNameURL(Author $author, DOMElement|false $nameDom): void
    {
        $orcidValue = $author->getData('url');
        if (strlen($orcidValue) > 0) {
            $orcid = $this->createElementNS(MODS_NS, 'mods:affiliation', $orcidValue);
            $orcid->setAttribute('script', 'url');
            $nameDom->appendChild($orcid);
        }
    }

    /**
     * @param Author $author
     * @param DOMElement|false $nameDom
     *
     * @return void
     */
    private function createNameCountry(Author $author, DOMElement|false$nameDom): void
    {
        $orcidValue = $author->getData('country');
        if (strlen($orcidValue) > 0) {
            $orcid = $this->createElementNS(MODS_NS, 'mods:affiliation', $orcidValue);
            $orcid->setAttribute('script', 'country');
            $nameDom->appendChild($orcid);
        }
    }

    /**
     * @param Context $context
     *
     * @return void
     */
    private function createContext(Context $context): void
    {
        $relatedItem = $this->createElementNS(MODS_NS, 'relatedItem');
        $relatedItem->setAttribute('type', 'host');
        $relatedItem->setAttribute('displayLabel', $context->getData('acronym', 'en_US'));
        $this->record->appendChild($relatedItem);
        $extension = $this->createElement('extension');
        $relatedItem->appendChild($extension);
        $elementNames = array('abbreviation', 'acronym', 'authorInformation', 'clocksLicense', 'customHeaders', 'librarianInformation', 'lockssLicense', 'openAccessPolicy', 'privacyStatement', 'readerInformation', 'searchDescription', 'supportedLocales', 'supportedSubmissionLocales');
        foreach ($elementNames as $elementName) {
            foreach ($context->getData($elementName) as $lang => $value) {
                $elem = $this->createElement($elementName, $value);
                $elem->setAttribute('xml:lang', $lang);
                $extension->appendChild($elem);
            }
        }
        //TODO
        #$issueDao = DAORegistry::getDAO('IssueDAO'); /** @var $issueDao IssueDAO */
        #$issue = $issueDao->getById($this->publication->getData('issueId'), $this->context);

    }

    /**
     * @return DOMElement
     */
    public function getRecord(): DOMElement
    {
        return $this->record;
    }

    /**
     * @return Publication
     */
    public function getPublication(): Publication
    {
        return $this->publication;
    }

    /**
     * @param Publication $publication
     *
     * @return void
     */
    public function setPublication(Publication $publication): void
    {
        $this->publication = $publication;
    }
}
