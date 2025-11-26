<?php

/**
 * @file plugins/importexport/rosetta/tests/functional/xml/utils/General.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class General
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\functional\xml\utils;

class General
{
    public static function removeNodesListFromDom($dcDom, $nodeNames): void
    {
        foreach ($nodeNames as $nodeName) {
            self::removeNodeFromDom($dcDom, $nodeName);
        }
    }

    public static function removeNodeFromDom($dcDom, string $nodeName): void
    {
        $nodeModified = $dcDom->getElementsByTagName($nodeName)->item(0);
        if ($nodeModified) {
            $nodeModified->parentNode->removeChild($nodeModified);
        }
    }
}
