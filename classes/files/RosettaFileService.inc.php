<?php

class RosettaFileService

{
	public static function getGalleyFiles($publication): array
	{
		// get all galleys

		$files = array();

		$galleysIterator = Services::get('galley')->getMany(['publicationIds' => $publication->getId()]);
		foreach ($galleysIterator as $galley) {
			$fileId = $galley->getData('fileId');
			$galleyFile = $galley->getFile();
			if (is_null($galleyFile) == false) {
				$galleyFilePath = $galleyFile->getFilePath();
				$dependentFilePaths = RosettaFileService::getDependentFilePaths($publication->getData('submissionId'), $fileId, MASTER_PATH);
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

	public static function getDependentFilePaths($submissionId, $fileId, string $path): array
	{
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile'); // Constants
		$dependentFiles = $submissionFileDao->getLatestRevisionsByAssocId(
			ASSOC_TYPE_SUBMISSION_FILE,
			$fileId,
			$submissionId,
			SUBMISSION_FILE_DEPENDENT
		);
		$assetsFilePaths = array();
		foreach ($dependentFiles as $dFile) {
			$assetsFilePaths[$dFile->getOriginalFileName()] = array(
				"fullFilePath" => $dFile->getFilePath(),
				"path" => $path,
				"originalFileName" => $dFile->getOriginalFileName()
			);
		}
		return $assetsFilePaths;
	}

}
