<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Xml/ModsDomTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ModsDomTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Tests for ModsDom — MODS XML generation.
 *        Single Responsibility: only tests MODS XML output.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Xml;

use APP\plugins\importexport\rosetta\classes\xml\mods\ModsDom;
use APP\plugins\importexport\rosetta\tests\Support\TestDataFactory;
use APP\plugins\importexport\rosetta\tests\Support\XmlTestHelper;
use PKP\tests\PKPTestCase;

class ModsDomTest extends PKPTestCase
{
	use XmlTestHelper;

	protected function setUp(): void
	{
		parent::setUp();
		$this->mockRequest();
		$localeService = app(\PKP\i18n\interfaces\LocaleInterface::class);
		$reflection = new \ReflectionProperty($localeService, 'locale');
		$reflection->setAccessible(true);
		$reflection->setValue($localeService, 'en_US');
	}

	private function createModsDom(array $journalOverrides = [], array $publicationOverrides = []): ModsDom
	{
		$journal = TestDataFactory::createJournal($journalOverrides);
		$submission = TestDataFactory::createSubmissionWithPublication([], $publicationOverrides);
		$publication = $submission->getLatestPublication();

		return new ModsDom($journal, $publication);
	}

	public function testGeneratesValidXml(): void
	{
		$modsDom = $this->createModsDom();
		$xml = $modsDom->saveXML();

		$this->assertNotEmpty($xml);
		$this->assertStringContainsString('<?xml', $xml);
	}

	public function testRootElementIsModsMods(): void
	{
		$modsDom = $this->createModsDom();

		$this->assertSame('mods:mods', $modsDom->documentElement->tagName);
	}

	public function testContainsModsNamespace(): void
	{
		$modsDom = $this->createModsDom();

		$this->assertSame('http://www.loc.gov/mods/v3', $modsDom->documentElement->namespaceURI);
	}

	public function testContainsVersion(): void
	{
		$modsDom = $this->createModsDom();

		$this->assertSame('3.6', $modsDom->documentElement->getAttribute('version'));
	}

	public function testContainsTitle(): void
	{
		$modsDom = $this->createModsDom([], ['title' => ['en_US' => 'MODS Test Title']]);

		$this->assertXmlElementValue($modsDom, 'title', 'MODS Test Title');
	}

	public function testContainsTitleInfo(): void
	{
		$modsDom = $this->createModsDom();

		$this->assertXmlHasElement($modsDom, 'titleInfo');
	}

	public function testContainsAuthorName(): void
	{
		$modsDom = $this->createModsDom();

		$this->assertXmlHasElement($modsDom, 'name');
		$this->assertXmlElementValue($modsDom, 'namePart', 'author-lastname');
		$this->assertXmlElementValue($modsDom, 'namePart', 'author-firstname');
	}

	public function testAuthorNameTypeAttribute(): void
	{
		$modsDom = $this->createModsDom();
		$nameNodes = $modsDom->getElementsByTagNameNS('http://www.loc.gov/mods/v3', 'name');

		$this->assertGreaterThan(0, $nameNodes->length);
		$this->assertSame('personal', $nameNodes->item(0)->getAttribute('type'));
	}

	public function testContainsKeywords(): void
	{
		$modsDom = $this->createModsDom([], ['keywords' => ['en_US' => ['keyword1', 'keyword2']]]);

		$this->assertXmlElementValue($modsDom, 'topic', 'keyword1');
		$this->assertXmlElementValue($modsDom, 'topic', 'keyword2');
	}

	public function testContainsSubjectElements(): void
	{
		$modsDom = $this->createModsDom([], ['keywords' => ['en_US' => ['kw1']]]);

		$this->assertXmlHasElement($modsDom, 'subject');
	}

	public function testContainsOriginInfo(): void
	{
		$modsDom = $this->createModsDom();

		$this->assertXmlHasElement($modsDom, 'originInfo');
	}

	public function testContainsRelatedItem(): void
	{
		$modsDom = $this->createModsDom(['acronym' => ['en_US' => 'TJ']]);
		$relatedItems = $modsDom->getElementsByTagNameNS('http://www.loc.gov/mods/v3', 'relatedItem');

		$this->assertGreaterThan(0, $relatedItems->length);
		$this->assertSame('host', $relatedItems->item(0)->getAttribute('type'));
	}

	public function testGetRecordReturnsRootElement(): void
	{
		$modsDom = $this->createModsDom();

		$this->assertSame($modsDom->documentElement, $modsDom->getRecord());
	}

	public function testGetPublicationReturnsPublication(): void
	{
		$modsDom = $this->createModsDom();

		$this->assertNotNull($modsDom->getPublication());
		$this->assertInstanceOf(\APP\publication\Publication::class, $modsDom->getPublication());
	}
}
