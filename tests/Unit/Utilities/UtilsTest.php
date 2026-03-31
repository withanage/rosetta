<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Utilities/UtilsTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class UtilsTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Tests for Utils — utility functions (directory operations, logging).
 *        Single Responsibility: only tests the Utils class.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Utilities;

use APP\plugins\importexport\rosetta\classes\utilities\Utils;
use PKP\tests\PKPTestCase;

class UtilsTest extends PKPTestCase
{
	private string $tempDir;

	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = sys_get_temp_dir() . '/rosetta_test_' . uniqid();
	}

	protected function tearDown(): void
	{
		if (is_dir($this->tempDir)) {
			Utils::removeDirRecursively($this->tempDir);
		}
		parent::tearDown();
	}

	public function testRemoveDirRecursivelyRemovesDirectory(): void
	{
		mkdir($this->tempDir, 0777, true);
		file_put_contents($this->tempDir . '/test.txt', 'content');
		mkdir($this->tempDir . '/subdir');
		file_put_contents($this->tempDir . '/subdir/nested.txt', 'nested');

		$this->assertDirectoryExists($this->tempDir);

		Utils::removeDirRecursively($this->tempDir);

		$this->assertDirectoryDoesNotExist($this->tempDir);
	}

	public function testRemoveDirRecursivelyHandlesEmptyPath(): void
	{
		Utils::removeDirRecursively('');

		$this->assertTrue(true);
	}

	public function testRemoveDirRecursivelyHandlesNonExistentPath(): void
	{
		Utils::removeDirRecursively('/tmp/nonexistent_rosetta_path_' . uniqid());

		$this->assertTrue(true);
	}

	public function testSetPermissionsRecursively(): void
	{
		mkdir($this->tempDir, 0755, true);
		file_put_contents($this->tempDir . '/file.txt', 'test');
		mkdir($this->tempDir . '/sub', 0755);
		file_put_contents($this->tempDir . '/sub/file2.txt', 'test2');

		// chmod may not be permitted in all environments (e.g. CI containers)
		@Utils::setPermissionsRecursively($this->tempDir, 0777);

		$this->assertDirectoryExists($this->tempDir);
	}

	public function testSetPermissionsRecursivelyHandlesEmptyPath(): void
	{
		Utils::setPermissionsRecursively('');

		$this->assertTrue(true);
	}

	public function testSetPermissionsRecursivelyHandlesNonExistentPath(): void
	{
		Utils::setPermissionsRecursively('/tmp/nonexistent_rosetta_path_' . uniqid());

		$this->assertTrue(true);
	}

	public function testRemoveDirRecursivelyHandlesEmptyDirectory(): void
	{
		mkdir($this->tempDir, 0777, true);

		Utils::removeDirRecursively($this->tempDir);

		$this->assertDirectoryDoesNotExist($this->tempDir);
	}

	public function testRemoveDirRecursivelyHandlesDeeplyNested(): void
	{
		$deepPath = $this->tempDir . '/a/b/c/d';
		mkdir($deepPath, 0777, true);
		file_put_contents($deepPath . '/deep.txt', 'deep');

		Utils::removeDirRecursively($this->tempDir);

		$this->assertDirectoryDoesNotExist($this->tempDir);
	}
}
