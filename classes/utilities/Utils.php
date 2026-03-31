<?php

/**
 * @file plugins/importexport/rosetta/classes/utilities/RosettaExportPlugin.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaExportPlugin
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes\utilities;

use DirectoryIterator;
use Exception;
use FilesystemIterator;
use PKP\config\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Utils
{
	public static function writeLog($message, string $level): void
	{
		try {
			// Generate a timestamp with microsecond precision.
			$timeStamp = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);

			// Construct the log entry.
			$logEntry = "$timeStamp $level $message\n";

			// Write the log entry to the log file.
			error_log($logEntry, 3, self::logFilePath());
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	public static function logFilePath(): string
	{
		return Config::getVar('rosetta', 'subDirectoryName') . '/rosetta.log';
	}

	public static function print_rr(mixed $input, int $level = 0): void
	{
		if ($level == 4) {
			return;
		}

		if (is_object($input)) {
			$vars = get_object_vars($input);

		}

		if (is_array($input)) {
			$vars = $input;
		}

		if (!$vars) {
			print " $input \n";
			return;
		}

		foreach ($vars as $k => $v) {
			if (is_object($v)) self::print_rr($v, $level++);
			if (is_array($v)) self::print_rr($v, $level++);
		}
	}

	public static function logInfo(string $message): void
	{
		self::writeLog($message, 'INFO');
	}

	public static function logError($message): void
	{
		self::writeLog($message, 'ERROR');
	}

	public static function removeDirRecursively(string $dir): void
	{
		if (empty($dir) || !realpath($dir)) {
			return;
		}

		try {
			if (is_dir($dir)) {
				// iterate through all items in current directory
				$items = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
				foreach ($items as $item) {
					$pathName = $item->getPathname();
					if ($item->isDir()) {
						// current item is a directory, call this method again
						self::removeDirRecursively($pathName);
					} else {
						// current item is a file, remove file
						unlink($pathName);
					}
				}
				// Remove the directory itself after its contents are deleted.
				rmdir($dir);
			}
		} catch (Exception $e) {
			self::logError($e->getMessage());
		}
	}

	public static function createZip(string $sourceDir, string $zipFilePath): bool
	{
		if (empty($sourceDir) || !is_dir($sourceDir)) {
			self::logError('createZip: source directory does not exist: ' . $sourceDir);
			return false;
		}

		$zip = new ZipArchive();
		if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			self::logError('createZip: could not create zip file: ' . $zipFilePath);
			return false;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($files as $file) {
			if ($file->isFile()) {
				$filePath = $file->getRealPath();
				$relativePath = substr($filePath, strlen(realpath($sourceDir)) + 1);
				$zip->addFile($filePath, $relativePath);
			}
		}

		$zip->close();

		return file_exists($zipFilePath);
	}

	public static function setPermissionsRecursively(string $dir, int $permissions = 0777): void
	{
		if (empty($dir) || !realpath($dir)) {
			return;
		}

		try {
			if (is_dir($dir)) {
				//change permission of current directory
				chmod($dir, $permissions);

				// iterate through all items in current directory
				$items = new DirectoryIterator($dir);
				foreach ($items as $item) {
					if ($item->isDir() && !$item->isDot()) {
						// current item is a directory, call this method again
						self::setPermissionsRecursively($item->getPathname(), $permissions);
					} else {
						// current item is a file, change permission
						chmod($item->getPathname(), $permissions);
					}
				}
			}
		} catch (Exception $e) {
			self::logError($e->getMessage());
		}
	}
}
