<?php

/**
 * @file plugins/importexport/rosetta/classes/files/RosettaFileService.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaFileService
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes\files;

use APP\core\Application;
use APP\facades\Repo;
use APP\publication\Publication;
use PKP\submissionFile\SubmissionFile;

if (!defined('MASTER_PATH')) {
	define('MASTER_PATH', 'MASTER');
}

class RosettaFileService
{
	public static function getGalleyFiles(Publication $publication): array
	{
		$files = array();

		$galleysIterator = Repo::galley()->getCollector()
			->filterByPublicationIds([$publication->getId()])
			->getMany();
		foreach ($galleysIterator as $galley) {
			$fileId = $galley->getData('submissionFileId');
			$galleyFile = $galley->getFile();
			if (!is_null($galleyFile)) {
				$galleyFilePath = $galleyFile->getData('path');
				$dependentFilePaths = RosettaFileService::getDependentFilePaths(
					$publication->getData('submissionId'), $fileId, MASTER_PATH);
				$files[] = array(
					"label" => $galley->getLocalizedName(),
					"revision" => $publication->getData("version"),
					"fullFilePath" => $galleyFilePath,
					"dependentFiles" => $dependentFilePaths,
					"path" => MASTER_PATH);
			}
		}

		return $files;
	}

	public static function getDependentFilePaths(int $submissionId, int $fileId, string $path): array
	{
		$submissionFile = Repo::submissionFile()->get($fileId);
		$dependentFilesIterator = Repo::submissionFile()->getCollector()
			->includeDependentFiles(true)
			->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
			->filterByAssoc(Application::ASSOC_TYPE_SUBMISSION_FILE, [$submissionFile->getId()])
			->getMany();

		$dependentFilePaths = array();
		foreach ($dependentFilesIterator as $dependentFile) {
			$dependentFilePaths[] = array(
				"fullFilePath" => $dependentFile->getData('path'),
				"path" => $path
			);
		}
		return $dependentFilePaths;
	}
}
