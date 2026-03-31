<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Validation/XsltValidationTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class XsltValidationTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Validates SIP XML files using Saxon HE XSLT processor.
 *        Saxon is used instead of PHP's built-in XSLTProcessor to ensure
 *        full XSLT 2.0/3.0 support and consistency with the XSD 1.1
 *        validator already used in the project.
 *
 *        Single Responsibility: only tests XSLT validation results.
 *        Open/Closed: new rules can be added to the XSLT without changing tests.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Validation;

use DOMDocument;
use PHPUnit\Framework\TestCase;

class XsltValidationTest extends TestCase
{
	private static string $pluginPath;
	private static string $saxonJar;
	private static string $dcXslt;
	private static string $metsXslt;
	private static bool $javaAvailable = false;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		self::$pluginPath = dirname(__DIR__, 3);
		self::$saxonJar = self::$pluginPath . '/bin/saxon-he-10.6.jar';
		self::$dcXslt = self::$pluginPath . '/schema/xslt/validate-dc.xsl';
		self::$metsXslt = self::$pluginPath . '/schema/xslt/validate-mets.xsl';

		exec('java -version 2>&1', $output, $status);
		self::$javaAvailable = ($status === 0);
	}

	/**
	 * Run Saxon XSLT transform on a file path.
	 *
	 * @return array{errors: string[], errorCount: int, xml: string}
	 */
	private function validateWithSaxon(string $xmlFile, string $xsltFile): array
	{
		$this->ensureSaxonAvailable();
		$this->assertFileExists($xmlFile, "XML file not found: {$xmlFile}");
		$this->assertFileExists($xsltFile, "XSLT file not found: {$xsltFile}");

		$command = sprintf(
			'java -cp %s net.sf.saxon.Transform -s:%s -xsl:%s 2>&1',
			escapeshellarg(self::$saxonJar),
			escapeshellarg($xmlFile),
			escapeshellarg($xsltFile)
		);

		exec($command, $output, $exitCode);
		$xmlOutput = implode("\n", $output);

		return $this->parseValidationReport($xmlOutput);
	}

	/**
	 * Run Saxon XSLT transform on an inline XML string.
	 *
	 * @return array{errors: string[], errorCount: int, xml: string}
	 */
	private function validateXmlStringWithSaxon(string $xmlString, string $xsltFile): array
	{
		$this->ensureSaxonAvailable();
		$this->assertFileExists($xsltFile, "XSLT file not found: {$xsltFile}");

		$tmpFile = tempnam(sys_get_temp_dir(), 'rosetta_xslt_') . '.xml';
		file_put_contents($tmpFile, $xmlString);

		try {
			return $this->validateWithSaxon($tmpFile, $xsltFile);
		} finally {
			@unlink($tmpFile);
		}
	}

	private function ensureSaxonAvailable(): void
	{
		if (!self::$javaAvailable) {
			$this->markTestSkipped('Java is not available');
		}
		if (!file_exists(self::$saxonJar)) {
			$this->markTestSkipped('Saxon HE jar not found at ' . self::$saxonJar);
		}
	}

	/**
	 * Parse <validation-report> XML output from the XSLT into an array.
	 */
	private function parseValidationReport(string $xmlOutput): array
	{
		$errors = [];
		$dom = new DOMDocument();

		if (!empty($xmlOutput) && @$dom->loadXML($xmlOutput)) {
			$errorNodes = $dom->getElementsByTagName('error');
			for ($i = 0; $i < $errorNodes->length; $i++) {
				$node = $errorNodes->item($i);
				$rule = $node->getAttribute('rule');
				$message = trim($node->textContent);
				$errors[] = "[{$rule}] {$message}";
			}
		}

		return [
			'errors' => $errors,
			'errorCount' => count($errors),
			'xml' => $xmlOutput,
		];
	}

	private function assertNoValidationErrors(array $result, string $context = ''): void
	{
		$this->assertSame(
			0,
			$result['errorCount'],
			($context ? "{$context}: " : '') .
			"Expected zero validation errors, got {$result['errorCount']}:\n" .
			implode("\n", $result['errors'])
		);
	}

	private function assertHasValidationError(array $result, string $ruleId, string $message = ''): void
	{
		$found = false;
		foreach ($result['errors'] as $error) {
			if (str_contains($error, "[{$ruleId}]")) {
				$found = true;
				break;
			}
		}
		$this->assertTrue(
			$found,
			$message ?: "Expected validation error {$ruleId} not found in: " . implode(', ', $result['errors'])
		);
	}

	// ---------------------------------------------------------------
	// Prerequisites
	// ---------------------------------------------------------------

	public function testSaxonJarExists(): void
	{
		$this->assertFileExists(self::$saxonJar, 'Saxon HE jar is required in bin/');
	}

	public function testDcXsltExists(): void
	{
		$this->assertFileExists(self::$dcXslt);
	}

	public function testMetsXsltExists(): void
	{
		$this->assertFileExists(self::$metsXslt);
	}

	public function testDcXsltIsValidXml(): void
	{
		$dom = new DOMDocument();
		$this->assertTrue(@$dom->load(self::$dcXslt), 'validate-dc.xsl is not valid XML');
	}

	public function testMetsXsltIsValidXml(): void
	{
		$dom = new DOMDocument();
		$this->assertTrue(@$dom->load(self::$metsXslt), 'validate-mets.xsl is not valid XML');
	}

	// ---------------------------------------------------------------
	// DC validation: valid files pass (Saxon)
	// ---------------------------------------------------------------

	public function testSipDcXmlPassesSaxonValidation(): void
	{
		$result = $this->validateWithSaxon(
			self::$pluginPath . '/tests/sip/dc.xml',
			self::$dcXslt
		);
		$this->assertNoValidationErrors($result, 'sip/dc.xml');
	}

	public function testDataDcXmlPassesSaxonValidation(): void
	{
		$result = $this->validateWithSaxon(
			self::$pluginPath . '/tests/data/dc.xml',
			self::$dcXslt
		);
		$this->assertNoValidationErrors($result, 'data/dc.xml');
	}

	// ---------------------------------------------------------------
	// DC validation: missing required elements (Saxon)
	// ---------------------------------------------------------------

	public function testDcMissingTitleProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:creator>Author</dc:creator>
			<dc:date>2024-01-01</dc:date>
			<dc:type>status-type:publishedVersion</dc:type>
			<dc:type>doc-type:article</dc:type>
			<dcterms:license>License</dcterms:license>
			<dc:publisher>Publisher</dc:publisher>
			<dc:language>en-US</dc:language>
		</dc:record>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$dcXslt);
		$this->assertHasValidationError($result, 'DC-010');
	}

	public function testDcMissingCreatorProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title>Title</dc:title>
			<dc:date>2024-01-01</dc:date>
			<dc:type>status-type:publishedVersion</dc:type>
			<dc:type>doc-type:article</dc:type>
			<dcterms:license>License</dcterms:license>
			<dc:publisher>Publisher</dc:publisher>
			<dc:language>en-US</dc:language>
		</dc:record>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$dcXslt);
		$this->assertHasValidationError($result, 'DC-020');
	}

	public function testDcMissingDateProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title>Title</dc:title>
			<dc:creator>Author</dc:creator>
			<dc:type>status-type:publishedVersion</dc:type>
			<dc:type>doc-type:article</dc:type>
			<dcterms:license>License</dcterms:license>
			<dc:publisher>Publisher</dc:publisher>
			<dc:language>en-US</dc:language>
		</dc:record>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$dcXslt);
		$this->assertHasValidationError($result, 'DC-030');
	}

	public function testDcMissingPublisherProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title>Title</dc:title>
			<dc:creator>Author</dc:creator>
			<dc:date>2024-01-01</dc:date>
			<dc:type>status-type:publishedVersion</dc:type>
			<dc:type>doc-type:article</dc:type>
			<dcterms:license>License</dcterms:license>
			<dc:language>en-US</dc:language>
		</dc:record>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$dcXslt);
		$this->assertHasValidationError($result, 'DC-060');
	}

	public function testDcMissingTypeProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title>Title</dc:title>
			<dc:creator>Author</dc:creator>
			<dc:date>2024-01-01</dc:date>
			<dcterms:license>License</dcterms:license>
			<dc:publisher>Publisher</dc:publisher>
			<dc:language>en-US</dc:language>
		</dc:record>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$dcXslt);
		$this->assertHasValidationError($result, 'DC-040');
	}

	public function testDcMissingPublishedVersionTypeProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title>Title</dc:title>
			<dc:creator>Author</dc:creator>
			<dc:date>2024-01-01</dc:date>
			<dc:type>doc-type:article</dc:type>
			<dcterms:license>License</dcterms:license>
			<dc:publisher>Publisher</dc:publisher>
			<dc:language>en-US</dc:language>
		</dc:record>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$dcXslt);
		$this->assertHasValidationError($result, 'DC-041');
	}

	public function testDcUnderscoreLocaleProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title>Title</dc:title>
			<dc:creator>Author</dc:creator>
			<dc:date>2024-01-01</dc:date>
			<dc:type>status-type:publishedVersion</dc:type>
			<dc:type>doc-type:article</dc:type>
			<dcterms:license>License</dcterms:license>
			<dc:publisher>Publisher</dc:publisher>
			<dc:language>en_US</dc:language>
		</dc:record>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$dcXslt);
		$this->assertHasValidationError($result, 'DC-071');
	}

	public function testDcEmptyTitleProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title></dc:title>
			<dc:creator>Author</dc:creator>
			<dc:date>2024-01-01</dc:date>
			<dc:type>status-type:publishedVersion</dc:type>
			<dc:type>doc-type:article</dc:type>
			<dcterms:license>License</dcterms:license>
			<dc:publisher>Publisher</dc:publisher>
			<dc:language>en-US</dc:language>
		</dc:record>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$dcXslt);
		$this->assertHasValidationError($result, 'DC-011');
	}

	// ---------------------------------------------------------------
	// METS validation: valid files pass (Saxon)
	// ---------------------------------------------------------------

	public function testSipIe1XmlPassesMetsValidation(): void
	{
		$result = $this->validateWithSaxon(
			self::$pluginPath . '/tests/sip/content/ie1.xml',
			self::$metsXslt
		);
		$this->assertNoValidationErrors($result, 'sip/content/ie1.xml');
	}

	public function testDataIe1XmlPassesMetsValidation(): void
	{
		$result = $this->validateWithSaxon(
			self::$pluginPath . '/tests/data/ie1.xml',
			self::$metsXslt
		);
		$this->assertNoValidationErrors($result, 'data/ie1.xml');
	}

	// ---------------------------------------------------------------
	// METS validation: missing structural sections (Saxon)
	// ---------------------------------------------------------------

	public function testMetsMissingDmdSecProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<mets:mets xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets">
			<mets:amdSec ID="ie-amd">
				<mets:techMD ID="ie-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalIECharacteristics"><record>
								<key id="status">ACTIVE</key>
								<key id="IEEntityType">Article</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:fileSec><mets:fileGrp ID="rep1" ADMID="rep1-amd"/></mets:fileSec>
			<mets:structMap TYPE="PHYSICAL"><mets:div LABEL="x"/></mets:structMap>
		</mets:mets>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$metsXslt);
		$this->assertHasValidationError($result, 'METS-010');
	}

	public function testMetsMissingAmdSecProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<mets:mets xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets"
		           xmlns:dc="http://purl.org/dc/elements/1.1/">
			<mets:dmdSec ID="ie-dmd">
				<mets:mdWrap MDTYPE="DC"><mets:xmlData>
					<dc:record><dc:title>T</dc:title></dc:record>
				</mets:xmlData></mets:mdWrap>
			</mets:dmdSec>
			<mets:fileSec><mets:fileGrp ID="rep1" ADMID="rep1-amd"/></mets:fileSec>
			<mets:structMap TYPE="PHYSICAL"><mets:div LABEL="x"/></mets:structMap>
		</mets:mets>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$metsXslt);
		$this->assertHasValidationError($result, 'METS-020');
	}

	public function testMetsMissingFileSecProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<mets:mets xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets"
		           xmlns:dc="http://purl.org/dc/elements/1.1/">
			<mets:dmdSec ID="ie-dmd">
				<mets:mdWrap MDTYPE="DC"><mets:xmlData>
					<dc:record><dc:title>T</dc:title><dc:creator>A</dc:creator><dc:date>2024</dc:date></dc:record>
				</mets:xmlData></mets:mdWrap>
			</mets:dmdSec>
			<mets:amdSec ID="ie-amd">
				<mets:techMD ID="ie-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalIECharacteristics"><record>
								<key id="status">ACTIVE</key><key id="IEEntityType">Article</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:structMap TYPE="PHYSICAL"><mets:div LABEL="x"/></mets:structMap>
		</mets:mets>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$metsXslt);
		$this->assertHasValidationError($result, 'METS-030');
	}

	public function testMetsMissingStructMapProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<mets:mets xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets"
		           xmlns:dc="http://purl.org/dc/elements/1.1/">
			<mets:dmdSec ID="ie-dmd">
				<mets:mdWrap MDTYPE="DC"><mets:xmlData>
					<dc:record><dc:title>T</dc:title><dc:creator>A</dc:creator><dc:date>2024</dc:date></dc:record>
				</mets:xmlData></mets:mdWrap>
			</mets:dmdSec>
			<mets:amdSec ID="ie-amd">
				<mets:techMD ID="ie-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalIECharacteristics"><record>
								<key id="status">ACTIVE</key><key id="IEEntityType">Article</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:fileSec><mets:fileGrp ID="rep1" ADMID="rep1-amd"/></mets:fileSec>
		</mets:mets>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$metsXslt);
		$this->assertHasValidationError($result, 'METS-040');
	}

	// ---------------------------------------------------------------
	// METS validation: cross-reference integrity (Saxon)
	// ---------------------------------------------------------------

	public function testMetsBrokenFptrReferenceProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<mets:mets xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets"
		           xmlns:dc="http://purl.org/dc/elements/1.1/"
		           xmlns:xlink="http://www.w3.org/1999/xlink">
			<mets:dmdSec ID="ie-dmd">
				<mets:mdWrap MDTYPE="DC"><mets:xmlData>
					<dc:record><dc:title>T</dc:title><dc:creator>A</dc:creator><dc:date>2024</dc:date></dc:record>
				</mets:xmlData></mets:mdWrap>
			</mets:dmdSec>
			<mets:amdSec ID="ie-amd">
				<mets:techMD ID="ie-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalIECharacteristics"><record>
								<key id="status">ACTIVE</key><key id="IEEntityType">Article</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:amdSec ID="rep1-amd">
				<mets:techMD ID="rep1-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalRepCharacteristics"><record>
								<key id="preservationType">PRESERVATION_MASTER</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:fileSec>
				<mets:fileGrp ID="rep1" ADMID="rep1-amd">
					<mets:file ID="fid1-1" ADMID="fid1-1-amd">
						<mets:FLocat LOCTYPE="URL" xlink:href="file://MASTER/test.pdf"/>
					</mets:file>
				</mets:fileGrp>
			</mets:fileSec>
			<mets:structMap ID="rep1-1" TYPE="PHYSICAL">
				<mets:div LABEL="Preservation Master">
					<mets:div LABEL="rep1">
						<mets:div LABEL="" TYPE="FILE">
							<mets:fptr FILEID="fid-NONEXISTENT"/>
						</mets:div>
					</mets:div>
				</mets:div>
			</mets:structMap>
		</mets:mets>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$metsXslt);
		$this->assertHasValidationError($result, 'METS-110');
	}

	// ---------------------------------------------------------------
	// METS validation: missing embedded DC content (Saxon)
	// ---------------------------------------------------------------

	public function testMetsMissingDcTitleInDmdSecProducesError(): void
	{
		$xml = '<?xml version="1.0"?>
		<mets:mets xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets"
		           xmlns:dc="http://purl.org/dc/elements/1.1/">
			<mets:dmdSec ID="ie-dmd">
				<mets:mdWrap MDTYPE="DC"><mets:xmlData>
					<dc:record>
						<dc:creator>Author</dc:creator>
						<dc:date>2024</dc:date>
					</dc:record>
				</mets:xmlData></mets:mdWrap>
			</mets:dmdSec>
			<mets:amdSec ID="ie-amd">
				<mets:techMD ID="ie-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalIECharacteristics"><record>
								<key id="status">ACTIVE</key><key id="IEEntityType">Article</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:amdSec ID="rep1-amd">
				<mets:techMD ID="rep1-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalRepCharacteristics"><record>
								<key id="preservationType">PRESERVATION_MASTER</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:fileSec><mets:fileGrp ID="rep1" ADMID="rep1-amd"/></mets:fileSec>
			<mets:structMap TYPE="PHYSICAL"><mets:div LABEL="x"/></mets:structMap>
		</mets:mets>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$metsXslt);
		$this->assertHasValidationError($result, 'METS-051');
	}

	// ---------------------------------------------------------------
	// Minimal valid METS passes (Saxon)
	// ---------------------------------------------------------------

	public function testMinimalValidMetsPassesValidation(): void
	{
		$xml = '<?xml version="1.0"?>
		<mets:mets xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets"
		           xmlns:dc="http://purl.org/dc/elements/1.1/"
		           xmlns:mods="http://www.loc.gov/mods/v3"
		           xmlns:xlink="http://www.w3.org/1999/xlink">
			<mets:dmdSec ID="ie-dmd">
				<mets:mdWrap MDTYPE="DC"><mets:xmlData>
					<dc:record>
						<dc:title>Test</dc:title>
						<dc:creator>Author</dc:creator>
						<dc:date>2024-01-01</dc:date>
					</dc:record>
				</mets:xmlData></mets:mdWrap>
			</mets:dmdSec>
			<mets:amdSec ID="ie-amd">
				<mets:techMD ID="ie-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalIECharacteristics"><record>
								<key id="status">ACTIVE</key>
								<key id="IEEntityType">Article</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
				<sourceMD ID="ie-amd-source-1">
					<mets:mdWrap MDTYPE="MODS"><mets:xmlData>
						<mods:mods version="3.6">
							<mods:titleInfo><mods:title>Test</mods:title></mods:titleInfo>
							<mods:name type="personal"><mods:namePart>Author</mods:namePart></mods:name>
						</mods:mods>
					</mets:xmlData></mets:mdWrap>
				</sourceMD>
			</mets:amdSec>
			<mets:amdSec ID="rep1-amd">
				<mets:techMD ID="rep1-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalRepCharacteristics"><record>
								<key id="preservationType">PRESERVATION_MASTER</key>
								<key id="usageType">VIEW</key>
								<key id="RevisionNumber">0</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:amdSec ID="fid1-1-amd">
				<mets:techMD ID="fid1-1-amd-tech">
					<mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="dnx"><mets:xmlData>
						<dnx xmlns="http://www.exlibrisgroup.com/dps/dnx">
							<section id="generalFileCharacteristics"><record>
								<key id="fileOriginalPath">/test/file.pdf</key>
							</record></section>
							<section id="fileFixity"><record>
								<key id="fixityType">MD5</key>
								<key id="fixityValue">abc123</key>
							</record></section>
						</dnx>
					</mets:xmlData></mets:mdWrap>
				</mets:techMD>
			</mets:amdSec>
			<mets:fileSec>
				<mets:fileGrp ID="rep1" ADMID="rep1-amd">
					<mets:file ID="fid1-1" ADMID="fid1-1-amd">
						<mets:FLocat LOCTYPE="URL" xlink:href="file://MASTER/file.pdf"/>
					</mets:file>
				</mets:fileGrp>
			</mets:fileSec>
			<mets:structMap ID="rep1-1" TYPE="PHYSICAL">
				<mets:div LABEL="Preservation Master">
					<mets:div LABEL="rep1">
						<mets:div LABEL="" TYPE="FILE">
							<mets:fptr FILEID="fid1-1"/>
						</mets:div>
					</mets:div>
				</mets:div>
			</mets:structMap>
		</mets:mets>';

		$result = $this->validateXmlStringWithSaxon($xml, self::$metsXslt);
		$this->assertNoValidationErrors($result, 'minimal valid METS');
	}
}
