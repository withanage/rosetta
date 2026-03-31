<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Validation/SipValidationTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class SipValidationTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Validates SIP XML files against Rosetta schemas.
 *        Uses Saxon HE with XSLT stylesheets to validate METS and Dublin Core XML
 *        against the official Rosetta schemas.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Validation;

use PHPUnit\Framework\TestCase;

class SipValidationTest extends TestCase
{
	private static string $pluginPath;
	private static string $saxonJar;
	private static string $metsXslt;
	private static bool $javaAvailable = false;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		self::$pluginPath = dirname(__DIR__, 3);
		self::$saxonJar = self::$pluginPath . '/bin/saxon-he-10.6.jar';
		self::$metsXslt = self::$pluginPath . '/schema/xslt/validate-mets.xsl';

		// Check Java availability
		exec('java -version 2>&1', $output, $status);
		self::$javaAvailable = ($status === 0);
	}

	private function runXsltValidation(string $xmlFile, string $xsltFile): array
	{
		if (!self::$javaAvailable) {
			$this->markTestSkipped('Java is not available');
		}

		if (!file_exists(self::$saxonJar)) {
			$this->markTestSkipped('saxon-he-10.6.jar not found at ' . self::$saxonJar);
		}

		if (!file_exists($xmlFile)) {
			$this->markTestSkipped('XML file not found: ' . $xmlFile);
		}

		if (!file_exists($xsltFile)) {
			$this->markTestSkipped('XSLT file not found: ' . $xsltFile);
		}

		$command = sprintf(
			'java -cp %s net.sf.saxon.Transform -s:%s -xsl:%s 2>&1',
			escapeshellarg(self::$saxonJar),
			escapeshellarg($xmlFile),
			escapeshellarg($xsltFile)
		);

		exec($command, $output, $exitCode);

		$errors = array_filter($output, fn(string $line) =>
			str_starts_with($line, '[Error]') || str_contains($line, 'ERROR'));

		return [
			'exitCode' => $exitCode,
			'output' => $output,
			'errors' => $errors,
			'errorCount' => count($errors),
		];
	}

	// ---------------------------------------------------------------
	// SIP ie1.xml (real-world METS) validation
	// ---------------------------------------------------------------

	public function testSipIe1XmlExistsAndIsWellFormed(): void
	{
		$ie1Path = self::$pluginPath . '/tests/sip/content/ie1.xml';
		$this->assertFileExists($ie1Path);

		$dom = new \DOMDocument();
		$loaded = @$dom->load($ie1Path);
		$this->assertTrue($loaded, 'ie1.xml is not well-formed XML');
	}

	public function testSipIe1XmlHasMetsRootElement(): void
	{
		$ie1Path = self::$pluginPath . '/tests/sip/content/ie1.xml';
		$dom = new \DOMDocument();
		$dom->load($ie1Path);

		$this->assertSame('mets:mets', $dom->documentElement->tagName);
		$this->assertSame(
			'http://www.exlibrisgroup.com/xsd/dps/rosettaMets',
			$dom->documentElement->namespaceURI
		);
	}

	public function testSipIe1XmlContainsDmdSec(): void
	{
		$dom = $this->loadSipIe1();

		$dmdSecs = $dom->getElementsByTagNameNS(
			'http://www.exlibrisgroup.com/xsd/dps/rosettaMets', 'dmdSec'
		);
		$this->assertGreaterThan(0, $dmdSecs->length, 'ie1.xml must contain dmdSec');
	}

	public function testSipIe1XmlContainsAmdSec(): void
	{
		$dom = $this->loadSipIe1();

		$amdSecs = $dom->getElementsByTagNameNS(
			'http://www.exlibrisgroup.com/xsd/dps/rosettaMets', 'amdSec'
		);
		$this->assertGreaterThan(0, $amdSecs->length, 'ie1.xml must contain amdSec');
	}

	public function testSipIe1XmlContainsFileSec(): void
	{
		$dom = $this->loadSipIe1();

		$fileSecs = $dom->getElementsByTagNameNS(
			'http://www.exlibrisgroup.com/xsd/dps/rosettaMets', 'fileSec'
		);
		$this->assertGreaterThan(0, $fileSecs->length, 'ie1.xml must contain fileSec');
	}

	public function testSipIe1XmlContainsStructMap(): void
	{
		$dom = $this->loadSipIe1();

		$structMaps = $dom->getElementsByTagNameNS(
			'http://www.exlibrisgroup.com/xsd/dps/rosettaMets', 'structMap'
		);
		$this->assertGreaterThan(0, $structMaps->length, 'ie1.xml must contain structMap');
	}

	public function testSipIe1XmlContainsDublinCoreMetadata(): void
	{
		$dom = $this->loadSipIe1();

		$dcRecords = $dom->getElementsByTagNameNS(
			'http://purl.org/dc/elements/1.1/', 'record'
		);
		$this->assertGreaterThan(0, $dcRecords->length, 'ie1.xml must contain dc:record');
	}

	public function testSipIe1XmlContainsModsMetadata(): void
	{
		$dom = $this->loadSipIe1();

		$modsElements = $dom->getElementsByTagNameNS(
			'http://www.loc.gov/mods/v3', 'mods'
		);
		$this->assertGreaterThan(0, $modsElements->length, 'ie1.xml must contain mods:mods');
	}

	public function testSipIe1XmlContainsDnxSections(): void
	{
		$dom = $this->loadSipIe1();

		$dnxElements = $dom->getElementsByTagName('dnx');
		$this->assertGreaterThan(0, $dnxElements->length, 'ie1.xml must contain dnx sections');
	}

	public function testSipIe1XmlValidatesAgainstMetsSchema(): void
	{
		$ie1Path = self::$pluginPath . '/tests/sip/content/ie1.xml';
		$result = $this->runXsltValidation($ie1Path, self::$metsXslt);

		$this->assertEmpty(
			$result['errors'],
			"SIP ie1.xml has validation errors:\n" . implode("\n", $result['errors'])
		);
	}

	// ---------------------------------------------------------------
	// SIP dc.xml validation
	// ---------------------------------------------------------------

	public function testSipDcXmlExistsAndIsWellFormed(): void
	{
		$dcPath = self::$pluginPath . '/tests/sip/dc.xml';
		$this->assertFileExists($dcPath);

		$dom = new \DOMDocument();
		$loaded = @$dom->load($dcPath);
		$this->assertTrue($loaded, 'sip/dc.xml is not well-formed XML');
	}

	public function testSipDcXmlHasDcRecordRoot(): void
	{
		$dcPath = self::$pluginPath . '/tests/sip/dc.xml';
		$dom = new \DOMDocument();
		$dom->load($dcPath);

		$this->assertSame('dc:record', $dom->documentElement->tagName);
		$this->assertSame(
			'http://purl.org/dc/elements/1.1/',
			$dom->documentElement->namespaceURI
		);
	}

	public function testSipDcXmlContainsRequiredElements(): void
	{
		$dcPath = self::$pluginPath . '/tests/sip/dc.xml';
		$dom = new \DOMDocument();
		$dom->load($dcPath);

		$dcNs = 'http://purl.org/dc/elements/1.1/';
		$requiredElements = ['title', 'creator', 'date', 'type'];
		foreach ($requiredElements as $element) {
			$nodes = $dom->getElementsByTagNameNS($dcNs, $element);
			$this->assertGreaterThan(0, $nodes->length, "dc.xml must contain dc:{$element}");
		}
	}

	// ---------------------------------------------------------------
	// Test data ie1.xml validation
	// ---------------------------------------------------------------

	public function testDataIe1XmlExistsAndIsWellFormed(): void
	{
		$ie1Path = self::$pluginPath . '/tests/data/ie1.xml';
		$this->assertFileExists($ie1Path);

		$dom = new \DOMDocument();
		$loaded = @$dom->load($ie1Path);
		$this->assertTrue($loaded, 'data/ie1.xml is not well-formed XML');
	}

	public function testDataIe1XmlValidatesAgainstMetsSchema(): void
	{
		$ie1Path = self::$pluginPath . '/tests/data/ie1.xml';
		$result = $this->runXsltValidation($ie1Path, self::$metsXslt);

		$this->assertEmpty(
			$result['errors'],
			"data/ie1.xml has validation errors:\n" . implode("\n", $result['errors'])
		);
	}

	// ---------------------------------------------------------------
	// Test data dc.xml validation
	// ---------------------------------------------------------------

	public function testDataDcXmlExistsAndIsWellFormed(): void
	{
		$dcPath = self::$pluginPath . '/tests/data/dc.xml';
		$this->assertFileExists($dcPath);

		$dom = new \DOMDocument();
		$loaded = @$dom->load($dcPath);
		$this->assertTrue($loaded, 'data/dc.xml is not well-formed XML');
	}

	public function testDataDcXmlHasDcRecordRoot(): void
	{
		$dcPath = self::$pluginPath . '/tests/data/dc.xml';
		$dom = new \DOMDocument();
		$dom->load($dcPath);

		$this->assertSame('dc:record', $dom->documentElement->tagName);
	}

	public function testDataDcXmlContainsRequiredElements(): void
	{
		$dcPath = self::$pluginPath . '/tests/data/dc.xml';
		$dom = new \DOMDocument();
		$dom->load($dcPath);

		$dcNs = 'http://purl.org/dc/elements/1.1/';
		$this->assertGreaterThan(
			0,
			$dom->getElementsByTagNameNS($dcNs, 'title')->length,
			'dc.xml must contain dc:title'
		);
		$this->assertGreaterThan(
			0,
			$dom->getElementsByTagNameNS($dcNs, 'creator')->length,
			'dc.xml must contain dc:creator'
		);
	}

	// ---------------------------------------------------------------
	// Schema files existence
	// ---------------------------------------------------------------

	public function testSchemaFilesExist(): void
	{
		$schemaFiles = [
			'mets_rosetta.xsd',
			'dnx_sip.xsd',
			'dc.xsd',
			'dcterms.xsd',
			'dcmitype.xsd',
		];

		foreach ($schemaFiles as $schemaFile) {
			$this->assertFileExists(
				self::$pluginPath . '/schema/' . $schemaFile,
				"Schema file {$schemaFile} is missing"
			);
		}
	}

	public function testSaxonJarExists(): void
	{
		$this->assertFileExists(
			self::$saxonJar,
			'saxon-he-10.6.jar is missing from bin/'
		);
	}

	// ---------------------------------------------------------------
	// SIP directory structure validation
	// ---------------------------------------------------------------

	public function testSipDirectoryStructure(): void
	{
		$sipPath = self::$pluginPath . '/tests/sip';

		$this->assertFileExists($sipPath . '/dc.xml', 'SIP must contain dc.xml');
		$this->assertDirectoryExists($sipPath . '/content', 'SIP must contain content/');
		$this->assertFileExists($sipPath . '/content/ie1.xml', 'SIP must contain content/ie1.xml');
		$this->assertDirectoryExists($sipPath . '/content/streams', 'SIP must contain content/streams/');
		$this->assertDirectoryExists($sipPath . '/content/streams/MASTER', 'SIP must contain content/streams/MASTER/');
	}

	public function testSipMasterContainsFiles(): void
	{
		$masterPath = self::$pluginPath . '/tests/sip/content/streams/MASTER';
		$files = glob($masterPath . '/*');

		$this->assertNotEmpty($files, 'MASTER directory must contain at least one file');
	}

	// ---------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------

	private function loadSipIe1(): \DOMDocument
	{
		$ie1Path = self::$pluginPath . '/tests/sip/content/ie1.xml';
		$dom = new \DOMDocument();
		$dom->load($ie1Path);
		return $dom;
	}
}
