<?php
/**
 * @file plugins/importexport/rosetta/classes/files/RosettaFileService.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RosettaFileService
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief The RosettaFileService class provides methods for handling files related to Rosetta export.
 */

namespace TIBHannover\Rosetta\Files;

use Publication;
use Services;

class RosettaFileService
{
    /**
     * Get a list of galley files associated with a publication.
     *
     * @param Publication $publication
     *
     * @return array
     */
    public static function getGalleyFiles(Publication $publication): array
    {
        // get all galleys

        $files = array();

        $galleysIterator = Services::get('galley')->getMany(['publicationIds' => $publication->getId()]);
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

    /**
     * Get a list of dependent file paths associated with a submission file.
     *
     * @param int $submissionId
     * @param int $fileId
     * @param string $path
     *
     * @return array
     */
    public static function getDependentFilePaths(int $submissionId, int $fileId, string $path): array
    {
        $submissionFile = Services::get('submissionFile')->get($fileId);
        $dependentFilesIterator = Services::get('submissionFile')->getMany([
            'includeDependentFiles' => true,
            'fileStages' => [SUBMISSION_FILE_DEPENDENT],
            'assocTypes' => [ASSOC_TYPE_SUBMISSION_FILE],
            'assocIds' => [$submissionFile->getId()],
        ]);

        $assetsFilePaths = array();
        foreach ($dependentFilesIterator as $dependentFile) {
            $originalFileName = $submissionFile->getLocalizedData('name');

            $assetsFilePaths[$originalFileName] = array(
                "fullFilePath" => $dependentFile->getData('path'),
                "path" => $path,
                "originalFileName" => $originalFileName
            );
        }
        return $assetsFilePaths;
    }
}
