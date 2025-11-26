<?php

/**
 * @file plugins/importexport/rosetta/classes/models/DepositActivityModel.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class DepositActivityModel
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes\models;

class DepositActivityModel
{
    public string $subdirectory = '';
    public string $id = '';
    public string $creation_date = '';
    public string $submission_date = '';
    public string $update_date = '';
    public string $status = '';
    public string $title = '';
    public array $producer_agent = ['value' => null, 'desc' => null];
    public array $producer = ['value' => null, 'desc' => null];
    public array $material_flow = ['value' => null, 'desc' => null];
    public string $sip_id = '';
    public string $sip_reason = '';

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
