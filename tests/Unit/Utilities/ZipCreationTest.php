<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Utilities/ZipCreationTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ZipCreationTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Tests SIP zip creation for journal deposits.
 *        Simulates config.inc.php subDirectoryName as a temp folder,
 *        creates a SIP structure for journal jpkjpk, and verifies
 *        the zip is created with the correct contents.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Utilities;

use APP\plugins\importexport\rosetta\classes\utilities\Utils;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ZipCreationTest extends TestCase
{
	private string $subDirectoryName;
	private string $sipPath;

	protected function setUp(): void
	{
		parent::setUp();

		// Simulate config.inc.php subDirectoryName as a temp folder
		$this->subDirectoryName = sys_get_temp_dir() . '/rosetta_zip_test_' . uniqid();
		mkdir($this->subDirectoryName, 0777, true);

		// Create a SIP structure for journal jpkjpk, submission 42, version 1
		$this->sipPath = $this->subDirectoryName . '/jpkjpk-42-v1';
		$contentPath = $this->sipPath . '/content';
		$streamsPath = $contentPath . '/streams';
		$masterPath = $streamsPath . '/MASTER';

		mkdir($masterPath, 0777, true);

		// Create dc.xml
		file_put_contents($this->sipPath . '/dc.xml', $this->getSampleDcXml());

		// Create ie1.xml
		file_put_contents($contentPath . '/ie1.xml', $this->getSampleIe1Xml());

		// Create sample galley files
		file_put_contents($masterPath . '/article.pdf', '%PDF-1.4 sample content');
		file_put_contents($masterPath . '/figure1.png', 'PNG sample content');
	}

	protected function tearDown(): void
	{
		if (is_dir($this->subDirectoryName)) {
			Utils::removeDirRecursively($this->subDirectoryName);
		}
		parent::tearDown();
	}

	public function testSipDirectoryExistsBeforeZip(): void
	{
		$this->assertDirectoryExists($this->sipPath);
		$this->assertFileExists($this->sipPath . '/dc.xml');
		$this->assertFileExists($this->sipPath . '/content/ie1.xml');
		$this->assertFileExists($this->sipPath . '/content/streams/MASTER/article.pdf');
		$this->assertFileExists($this->sipPath . '/content/streams/MASTER/figure1.png');
	}

	public function testCreateZipFromSipFolder(): void
	{
		$zipPath = $this->subDirectoryName . '/jpkjpk-42-v1.zip';

		$result = Utils::createZip($this->sipPath, $zipPath);

		$this->assertTrue($result, 'createZip should return true');
		$this->assertFileExists($zipPath, 'Zip file should exist');
	}

	public function testZipContainsAllSipFiles(): void
	{
		$zipPath = $this->subDirectoryName . '/jpkjpk-42-v1.zip';
		Utils::createZip($this->sipPath, $zipPath);

		$zip = new ZipArchive();
		$zip->open($zipPath);

		$expectedFiles = [
			'dc.xml',
			'content/ie1.xml',
			'content/streams/MASTER/article.pdf',
			'content/streams/MASTER/figure1.png',
		];

		foreach ($expectedFiles as $expectedFile) {
			$this->assertNotFalse(
				$zip->locateName($expectedFile),
				"Zip should contain {$expectedFile}"
			);
		}

		$this->assertSame(count($expectedFiles), $zip->numFiles, 'Zip should contain exactly 4 files');

		$zip->close();
	}

	public function testZipFileContentMatchesOriginal(): void
	{
		$zipPath = $this->subDirectoryName . '/jpkjpk-42-v1.zip';
		Utils::createZip($this->sipPath, $zipPath);

		$zip = new ZipArchive();
		$zip->open($zipPath);

		$dcContent = $zip->getFromName('dc.xml');
		$this->assertSame(
			file_get_contents($this->sipPath . '/dc.xml'),
			$dcContent,
			'dc.xml content in zip should match original'
		);

		$pdfContent = $zip->getFromName('content/streams/MASTER/article.pdf');
		$this->assertSame(
			'%PDF-1.4 sample content',
			$pdfContent,
			'article.pdf content in zip should match original'
		);

		$zip->close();
	}

	public function testCreateZipWithEmptySourceReturnsfalse(): void
	{
		$zipPath = $this->subDirectoryName . '/empty.zip';

		$result = Utils::createZip('', $zipPath);

		$this->assertFalse($result);
	}

	public function testCreateZipWithNonExistentSourceReturnsFalse(): void
	{
		$zipPath = $this->subDirectoryName . '/nonexistent.zip';

		$result = Utils::createZip('/tmp/nonexistent_rosetta_' . uniqid(), $zipPath);

		$this->assertFalse($result);
	}

	public function testCreateZipOverwritesExistingZip(): void
	{
		$zipPath = $this->subDirectoryName . '/jpkjpk-42-v1.zip';

		// Create zip twice
		Utils::createZip($this->sipPath, $zipPath);
		$firstSize = filesize($zipPath);

		// Add another file and recreate
		file_put_contents($this->sipPath . '/content/streams/MASTER/extra.txt', 'extra');
		Utils::createZip($this->sipPath, $zipPath);
		clearstatcache();
		$secondSize = filesize($zipPath);

		$this->assertGreaterThan($firstSize, $secondSize, 'Recreated zip with extra file should be larger');
	}

	public function testSipFolderCanBeRemovedAfterZip(): void
	{
		$zipPath = $this->subDirectoryName . '/jpkjpk-42-v1.zip';
		Utils::createZip($this->sipPath, $zipPath);

		$this->assertFileExists($zipPath);

		// Simulate what depositPublication does after deposit: remove SIP folder
		Utils::removeDirRecursively($this->sipPath);

		$this->assertDirectoryDoesNotExist($this->sipPath, 'SIP folder should be removed after zip');
		$this->assertFileExists($zipPath, 'Zip should persist after SIP folder removal');
	}

	private function getSampleDcXml(): string
	{
		return '<?xml version="1.0" encoding="UTF-8"?>' .
			'<dc:record xmlns:dc="http://purl.org/dc/elements/1.1/" ' .
			'xmlns:dcterms="http://purl.org/dc/terms/" ' .
			'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
			'<dc:title>Test Article for JPKJPK</dc:title>' .
			'<dc:creator>Test Author</dc:creator>' .
			'<dc:date>2024-01-15</dc:date>' .
			'<dc:type>article</dc:type>' .
			'<dc:identifier>10.12345/test.jpkjpk.42</dc:identifier>' .
			'</dc:record>';
	}

	private function getSampleIe1Xml(): string
	{
		return '<?xml version="1.0" encoding="UTF-8"?>' .
			'<mets:mets xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets" ' .
			'xmlns:xlink="http://www.w3.org/1999/xlink">' .
			'<mets:dmdSec ID="dmd-1"><mets:mdWrap MDTYPE="DC">' .
			'<mets:xmlData><dc:record xmlns:dc="http://purl.org/dc/elements/1.1/">' .
			'<dc:title>Test Article for JPKJPK</dc:title>' .
			'</dc:record></mets:xmlData></mets:mdWrap></mets:dmdSec>' .
			'</mets:mets>';
	}
}
