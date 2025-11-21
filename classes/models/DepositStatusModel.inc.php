<?php

/**
 * @file plugins/importexport/rosetta/RosettaExportPlugin.php
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

namespace APP\plugins\importexport\rosetta\classes\Models;

class DepositStatusModel
{
    public string $id = '';
    public bool $status = false;
    public string $date = '';
    public string $doi = '';

    function __construct(?array $data = [])
    {
        if (!empty($data)) $this->assignValues($data);
    }

    private function assignValues(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                if (!empty($value)) $this->$key = $value;
            }
        }
    }
}
