<?php


define('MODS_NS', "http://www.loc.gov/mods/v3");

class ModsDOM extends DOMDocument {

	var $context;
	var $locale;
	var $publication;

	var $record;
	var $supportedFormLocales;


	public function __construct($context, $submission, $publication) {
		parent::__construct('1.0', 'UTF-8');
		$this->context = $context;
		$this->publication = $publication;
		$this->submission = $submission;
		$this->supportedFormLocales = $context->getSupportedFormLocales();
		$this->createPublication();

	}

	private function createPublication(): void {

		$this->createRootElement();

		// titleInfo
		$this->createTitleInfo($this->publication);
		// abstract
		$this->createAbstract($this->publication);
		// authors
		$this->createName($this->publication);
		//subjects
		$this->createDataElement("disciplines", $this->publication, $this->record, "subject", array("authority" => "disciplines"));
		$this->createDataElement("keywords", $this->publication, $this->record, "subject", array("authority" => "keywords"));
		$this->createDataElement("languages", $this->publication, $this->record, "subject", array("authority" => "languages"));
		$this->createDataElement("subjects", $this->publication, $this->record, "subject", array("authority" => "subjects"));
		$this->createDataElement("supportingAgencies", $this->publication, $this->record, "subject", array("authority" => "supportingAgencies"));
		// coverage
		$this->createDataElement("coverage", $this->publication, $this->record, "location", array("displayLabel" => "coverage"));
		// rights
		$this->createDataElement("rights", $this->publication, $this->record, "accessCondition", array("displayLabel" => "rights"));
		// Source
		$recordInfo = $this->createElementNS(MODS_NS, "mods:recordInfo");
		$this->createDataElement("source", $this->publication, $recordInfo, "recordContentSource");
		// doi
		$this->createDataElement("pub-id::doi", $this->publication, $this->record, "identifier", array("type" => "doi"));


		$languageOfCataloging = $this->createElementNS(MODS_NS, "mods:languageOfCataloging");
		#$languageTerm = $this->createDataElement("locale",$this->publication,$languageOfCataloging,"languageTerm");
		#$recordInfo->appendChild($languageTerm);

		$this->record->appendChild($recordInfo);

		// publisher
		$originInfo = $this->createElementNS(MODS_NS, "mods:originInfo");
		$this->createDataElement("pub-id::publisher-id", $this->publication, $originInfo, "publisher");
		$this->record->appendChild($originInfo);
		$this->createDataElement("type", $this->publication, $this->record, "genre");
		// Add  Context Info



		$this->createContext($this->context);




	}

	private function createContext(Journal  $context) {

		$relatedItem = $this->createElementNS(MODS_NS, "relatedItem");
		$relatedItem->setAttribute("type","host");
		$relatedItem->setAttribute("displayLabel",$context->getData("acronym", "en_US"));

		$this->record->appendChild($relatedItem);
		$extension =  $this->createElement("extension");
		$relatedItem->appendChild($extension);
		$elementNames = array('abbreviation','acronym','authorInformation','clocksLicense','customHeaders','librarianInformation','lockssLicense','openAccessPolicy','privacyStatement','readerInformation','searchDescription','supportedLocales','supportedSubmissionLocales');
		foreach ($elementNames as $elementName) {
			foreach ($context->getData($elementName) as $lang => $value) {
				$elem = $this->createElement($elementName, $value);
				$elem->setAttribute("xml:lang", $lang);
				$extension->appendChild($elem);
			}
		}

		//TODO
		#$issueDao = DAORegistry::getDAO('IssueDAO'); /** @var $issueDao IssueDAO */
		#$issue = $issueDao->getById($this->publication->getData('issueId'), $this->context);


		}



	/**
	 * @return DOMElement|false
	 */
	private function createRootElement() {


		$this->record = $this->createElementNS(MODS_NS, "mods:mods");
		$this->record->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
		$this->record->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation', "http://www.loc.gov/standards/mods/v3/mods-3-7.xsd");
		$this->record->setAttribute("version", "3.7");
		$this->appendChild($this->record);
	}

	/**
	 */
	private function createTitleInfo(): void {
		$titleInfo = $this->createElementNS(MODS_NS, "titleInfo");
		$titles = $this->publication->getData("title");
		if ($titles) {
			foreach ($titles as $lang => $title) {
				$prefix = $this->publication->getData("prefix");
				if (is_null($prefix)==false and array_key_exists($lang, $prefix)) {
					$title = $prefix[$lang] . " " . $title;
				}
				$titleDom = $this->createLocalizedElement($title, "title", $lang);
				$titleInfo->appendChild($titleDom);
				$this->record->appendChild($titleInfo);
			}
		}
		$subTitles = $this->publication->getData("subtitle");
		if ($subTitles) {
			foreach ($subTitles as $lang => $subTitle) {
				$subTitle = $this->createLocalizedElement($subTitle, "subTitle", $lang);
				$titleInfo->appendChild($subTitle);
				$this->record->appendChild($titleInfo);
			}
		}
	}

	/**
	 * @param DomElement $mods
	 */
	private function createAbstract(): void {
		$abstracts = $this->publication->getData("abstract");
		if ($abstracts) {
			foreach ($abstracts as $lang => $abstract) {
				$abstractDom = $this->createLocalizedElement($abstract, "abstract", $lang);
				$this->record->appendChild($abstractDom);
			}
		}
	}

	/**
	 * @param DomElement $mods
	 */
	private function createName(): void {
		$authors = $this->publication->getData("authors");
		foreach ($authors as $author) {
			$nameDom = $this->createElementNS(MODS_NS, "mods:name");
			foreach ($this->supportedFormLocales as $locale) {
				// namePart
				$authorGivenNameEmpty = !array_filter(array_values($author->getData("givenName")));
				$authorType = ($authorGivenNameEmpty) ? "corporate" : "personal";
				$nameDom->setAttribute("type", $authorType);

				if (array_key_exists($locale, $author->getData('familyName'))) {

					$familyNamePartDom = $this->createElementNS(MODS_NS, "namePart", $author->getData('familyName')[$locale]);
					$familyNamePartDom->setAttribute("xml:lang", $locale);
					$familyNamePartDom->setAttribute("type", "family");
					$nameDom->appendChild($familyNamePartDom);
				}
				if (array_key_exists($locale, $author->getData('givenName'))) {
					$givenNamePartDom = $this->createElementNS(MODS_NS, "namePart", $author->getData('givenName')[$locale]);
					$givenNamePartDom->setAttribute("xml:lang", $locale);
					$givenNamePartDom->setAttribute("type", "given");
					$nameDom->appendChild($givenNamePartDom);
				}

				// Create user properties
				$properties = array(
					"affiliation" => "affiliation",
					"biography" => "description",
					"preferredPublicName" => "displayForm"
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
	 * @param DOMDocument $dataProvider
	 * @param DOMElement $parent
	 * @param string $new
	 * @param array $attrs
	 */
	public function createDataElement(string $orig, $dataProvider, DOMElement $parent, string $new = "", array $attrs = []): void {
		$newElement = null;
		$data = $dataProvider->getData($orig);
		if (gettype($data) == "array") {
			foreach ($data as $locale => $entry) {
				$elemName = (strlen($new) > 0) ? $new : $orig;
				if (gettype($entry) == "string") {
					$newElement = $this->createElementNS(MODS_NS, $elemName, htmlspecialchars($entry, ENT_XHTML, 'UTF-8'));
					$newElement->setAttribute("xml:lang", $locale);
					$this->setAllAttributes($attrs, $newElement, $parent);
				}
				if (gettype($entry) == "array") {
					foreach ($entry as $part) {
						$newElement = $this->createElementNS(MODS_NS, $elemName, htmlspecialchars($part, ENT_XHTML, 'UTF-8'));
						$newElement->setAttribute("xml:lang", $locale);
						$this->setAllAttributes($attrs, $newElement, $parent);
					}
				}

			}
		} elseif (gettype("data") == "string") {
			$newElement = $this->createLocalizedElement($data, $new);
			$this->setAllAttributes($attrs, $newElement, $parent);

		}

	}

	private function createLocalizedElement($value, $qualifiedName, $locale = ""): DOMElement {
		$node = $this->createElementNS(MODS_NS, $qualifiedName);
		if (empty($value) == false) {
			$node->nodeValue = htmlspecialchars($value, ENT_XHTML, 'UTF-8');
		}

		if (strlen($locale) > 0) {
			$langAttr = $this->createAttribute("xml:lang");
			$langAttr->value = $locale;
			$node->appendChild($langAttr);
		}
		return $node;

	}

	/**
	 * @param $author
	 * @param $locale
	 * @param  $nameDom
	 */
	private function createNameRoles($author, $locale, $nameDom): void {

		$userGroup = $author->getUserGroup();

		$role = $this->createElementNS(MODS_NS, "mods:role");

		$roleTerm = $this->createElementNS(MODS_NS, "mods:roleTerm", $userGroup->getName($locale));
		$roleTerm->setAttribute("xml:lang", $locale);
		$roleTerm->setAttribute("type", "text");
		$role->appendChild($roleTerm);
		$roleTerm = $this->createElementNS(MODS_NS, "mods:roleTerm", $userGroup->getAbbrev($locale));
		$roleTerm->setAttribute("xml:lang", $locale);
		$roleTerm->setAttribute("type", "code");
		$role->appendChild($roleTerm);

		$nameDom->appendChild($role);
	}

	/**
	 * @param $author
	 * @param  $nameDom
	 * @return array
	 */
	private function createNameOrcid($author, $nameDom): void {
		$orcidValue = $author->getData("orcid");
		if (strlen($orcidValue) > 0) {
			$orcid = $this->createElementNS(MODS_NS, "mods:affiliation", $orcidValue);
			$orcid->setAttribute("script", "orcid");
			$nameDom->appendChild($orcid);
		}

	}

	/**
	 * @param $author
	 * @param  $nameDom
	 * @return array
	 */
	private function createNameURL($author, $nameDom): void {
		$orcidValue = $author->getData("url");
		if (strlen($orcidValue) > 0) {
			$orcid = $this->createElementNS(MODS_NS, "mods:affiliation", $orcidValue);
			$orcid->setAttribute("script", "url");
			$nameDom->appendChild($orcid);
		}

	}

	/**
	 * @param $author
	 * @param  $nameDom
	 * @return array
	 */
	private function createNameCountry($author, $nameDom): void {
		$orcidValue = $author->getData("country");
		if (strlen($orcidValue) > 0) {
			$orcid = $this->createElementNS(MODS_NS, "mods:affiliation", $orcidValue);
			$orcid->setAttribute("script", "country");
			$nameDom->appendChild($orcid);
		}

	}

	/**
	 * @param array $attrs
	 * @param DOMElement $newElement
	 * @param DOMElement $parent
	 */
	private function setAllAttributes(array $attrs, DOMElement $newElement, DOMElement $parent): void {
		foreach ($attrs as $key => $attr) {
			$newElement->setAttribute($key, $attr);
		}
		$parent->appendChild($newElement);
	}

	public function getRecord(): DOMElement {
		//$s = $this->saveXML();
		//file_put_contents("/tmp/".$this->submission->getData('id')."mods.xml", $s);
		return $this->record;
	}


}
