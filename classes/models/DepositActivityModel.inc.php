<?php
/**
 * @file plugins/importexport/rosetta/classes/models/DepositActivityModel.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositActivityModel
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief Deposit Activity Model
 *
 * The `DepositActivityModel` class represents deposit activity data related to Rosetta imports and exports.
 * It is used to encapsulate and manage information about individual deposit activities within the system.
 * Deposit activities are associated with the submission, depositors, material flows, and other relevant details.
 *
 * @property string $subdirectory The subdirectory of the load directory where the deposit activity is located.
 * @property string $id The unique identifier for the deposit activity.
 * @property string $creation_date The date and time when the deposit activity was created.
 * @property string $submission_date The date and time when the deposit activity was submitted.
 * @property string $update_date The date and time when the deposit activity was last updated.
 * @property string $status The status of the deposit activity, which can be one of the following: inprocess, rejected, draft, approved, declined, error, finished.
 * @property string $title The title associated with the deposit activity.
 * @property array $producer_agent An associative array containing information about the producer agent of the depositor, with 'value' and 'desc' properties.
 * @property array $producer An associative array containing information about the producer of the deposit, with 'value' and 'desc' properties.
 * @property array $material_flow An associative array containing information about the Material Flow used, with 'value' and 'desc' properties.
 * @property string $sip_id The SIP (Submission Information Package) ID assigned to the deposit activity.
 * @property string $sip_reason The reason for which the SIP was rejected or declined, if applicable.
 *
 * @note This class is typically used in conjunction with Rosetta import and export operations to manage and represent deposit activity data.
 *
 * @see DepositActivityModel::__construct()
 * @see DepositActivityModel::assignValues()
 */

namespace TIBHannover\Rosetta\Models;

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

    /**
     * Constructor
     *
     * @param array|null $data An associative array containing deposit activity data to initialize the object.
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