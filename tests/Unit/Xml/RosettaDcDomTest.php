<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Xml/RosettaDcDomTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaDcDomTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Tests for RosettaDcDom — Dublin Core XML generation.
 *        Single Responsibility: only tests DC XML output.
 *        Dependency Inversion: depends on abstractions (Context interface) via test data factory.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Xml;

use APP\plugins\importexport\rosetta\classes\xml\dublincore\RosettaDcDom;
use APP\plugins\importexport\rosetta\tests\Support\TestDataFactory;
use APP\plugins\importexport\rosetta\tests\Support\XmlTestHelper;
use PKP\tests\PKPTestCase;

class RosettaDcDomTest extends PKPTestCase
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

	private function createDcDom(array $journalOverrides = [], array $publicationOverrides = [], array $submissionOverrides = []): RosettaDcDom
	{
		$journal = TestDataFactory::createJournal($journalOverrides);
		$submission = TestDataFactory::createSubmissionWithPublication($submissionOverrides, $publicationOverrides);
		$publication = $submission->getLatestPublication();

		return new RosettaDcDom($journal, $publication, $submission);
	}

	public function testGeneratesValidXml(): void
	{
		$dcDom = $this->createDcDom();
		$xml = $dcDom->saveXML();

		$this->assertNotEmpty($xml);
		$this->assertStringContainsString('<?xml', $xml);
	}

	public function testRootElementIsDcRecord(): void
	{
		$dcDom = $this->createDcDom();

		$this->assertSame('dc:record', $dcDom->documentElement->tagName);
	}

	public function testContainsDcNamespaces(): void
	{
		$dcDom = $this->createDcDom();
		$root = $dcDom->documentElement;

		$this->assertSame('http://purl.org/dc/elements/1.1/', $root->lookupNamespaceUri('dc'));
		$this->assertSame('http://purl.org/dc/terms/', $root->lookupNamespaceUri('dcterms'));
		$this->assertSame('http://www.w3.org/2001/XMLSchema-instance', $root->lookupNamespaceUri('xsi'));
	}

	public function testContainsStaticTypeElements(): void
	{
		$dcDom = $this->createDcDom();

		$this->assertXmlElementValue($dcDom, 'dc:type', 'status-type:publishedVersion');
		$this->assertXmlElementValue($dcDom, 'dc:type', 'doc-type:article');
	}

	public function testContainsLicenseElement(): void
	{
		$dcDom = $this->createDcDom();

		$this->assertXmlElementValue($dcDom, 'dcterms:license', 'TIB_OJS_Lizenzvereinbarung');
	}

	public function testContainsTitle(): void
	{
		$dcDom = $this->createDcDom([], ['title' => ['en_US' => 'My Test Article']]);

		$this->assertXmlElementValue($dcDom, 'dc:title', 'My Test Article');
	}

	public function testContainsAuthor(): void
	{
		$dcDom = $this->createDcDom();

		$this->assertXmlElementValue($dcDom, 'dc:creator', 'author-firstname author-lastname');
	}

	public function testContainsPublishedDate(): void
	{
		$dcDom = $this->createDcDom([], ['datePublished' => '2024-06-15']);

		$this->assertXmlElementValue($dcDom, 'dc:date', '2024-06-15');
	}

	public function testContainsPublisher(): void
	{
		$dcDom = $this->createDcDom(['publisherInstitution' => 'TIB']);

		$this->assertXmlElementValue($dcDom, 'dc:publisher', 'TIB');
	}

	public function testContainsLanguage(): void
	{
		$dcDom = $this->createDcDom();

		$this->assertXmlElementValue($dcDom, 'dc:language', 'en-US');
	}

	public function testContainsIssn(): void
	{
		$dcDom = $this->createDcDom(['onlineIssn' => '1234-5678']);
		$xml = $dcDom->saveXML();

		$this->assertStringContainsString('1234-5678', $xml);
	}

	public function testContainsVersion(): void
	{
		$dcDom = $this->createDcDom([], ['version' => 3]);

		$this->assertXmlElementValue($dcDom, 'dcterms:hasVersion', 'Version 3');
	}

	public function testContainsDoi(): void
	{
		$dcDom = $this->createDcDom();
		$xml = $dcDom->saveXML();

		$this->assertStringContainsString('dc:identifier', $xml);
		$this->assertStringContainsString('dcterms:URI', $xml);
	}

	public function testContainsLicenseUrlFromPublication(): void
	{
		$dcDom = $this->createDcDom([], ['licenseUrl' => 'https://creativecommons.org/licenses/by/4.0/']);

		$this->assertXmlElementValue($dcDom, 'dc:rights', 'https://creativecommons.org/licenses/by/4.0/');
	}

	public function testContainsCopyrightYear(): void
	{
		$dcDom = $this->createDcDom([], ['copyrightYear' => '2024']);

		$this->assertXmlElementValue($dcDom, 'dcterms:issued', '2024');
	}

	public function testMultipleAuthors(): void
	{
		$authors = TestDataFactory::createAuthors([
			['givenName' => 'Alice', 'familyName' => 'Smith', 'email' => 'alice@example.com'],
			['givenName' => 'Bob', 'familyName' => 'Jones', 'email' => 'bob@example.com'],
		]);

		$dcDom = $this->createDcDom([], ['authors' => $authors]);

		$this->assertXmlElementValue($dcDom, 'dc:creator', 'Alice Smith');
		$this->assertXmlElementValue($dcDom, 'dc:creator', 'Bob Jones');
	}

	public function testXmlMatchesExpectedFixture(): void
	{
		$dcDom = $this->createDcDom();
		$this->removeNodesFromDom($dcDom, ['dcterms:modified', 'dcterms:isPartOf']);

		$expectedFile = $this->getTestDataPath('dc.xml');
		if (file_exists($expectedFile)) {
			$this->assertXmlStringEqualsXmlFile($expectedFile, $dcDom->saveXML());
		} else {
			$this->markTestSkipped('Fixture file dc.xml not found');
		}
	}
}
