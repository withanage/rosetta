<?php
/**
 * @file plugins/importexport/rosetta/classes/models/DepositStatusModel.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositStatusModel
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief Deposit Status Model
 *
 * The `DepositStatusModel` class represents the status of a deposit within the Rosetta system.
 * It encapsulates information about the current status, SIP ID, date, and associated DOI.
 *
 * @property string $sip_id The SIP (Submission Information Package) ID associated with the deposit.
 * @property bool $status The current status of the deposit, which can be `true` (success) or `false` (failure/error).
 * @property string $date The date and time when the deposit status was recorded in the format "YYYY-MM-DD HH:MM:SS".
 * @property string $doi The DOI (Digital Object Identifier) associated with the deposit, if applicable.
 *
 * @note This class is typically used to track the status of deposits made to the Rosetta system.
 *
 * @see DepositStatusModel::__construct()
 * @see DepositStatusModel::assignValues()
 */

namespace TIBHannover\Rosetta\Models;

class DepositStatusModel
{
    public string $id = '';
    public bool $status = false;
    public string $date = '';
    public string $doi = '';

    /**
     * Constructor
     *
     * @param array|null $data An associative array containing deposit status data to initialize the object.
     */
    function __construct(?array $data = [])
    {
        if (!empty($data)) $this->assignValues($data);
    }

    /**
     * Assign values to class properties from the given data array.
     *
     * This method is used to populate the class properties with data from an associative array.
     *
     * @param array $data An associative array containing data to be assigned to class properties.
     */
    private function assignValues(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                if (!empty($value)) $this->$key = $value;
            }
        }
    }
}