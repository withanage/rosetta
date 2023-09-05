<?php
/**
 * @file plugins/importexport/rosetta/classes/models/RosettaDepositModel.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RosettaDepositModel
 * @ingroup plugins_importexport_rosettaexportdeployment
 *
 * @brief Deposit activity object
 */

namespace TIBHannover\Rosetta\Models;

class RosettaDepositModel
{
    /**
     * Subdirectory of the load directory. Input parameter.
     * @var string
     */
    public string $subdirectory = '';

    /**
     * Deposit activity ID
     * @var string
     */
    public string $id = '';

    /**
     * Deposit activity creation date
     * @var string
     */
    public string $creation_date = '';

    /**
     * Deposit activity submission date
     * @var string
     */
    public string $submission_date = '';

    /**
     * Deposit activity last update date
     * @var string
     */
    public string $update_date = '';

    /**
     * Status of the deposit activity. e.g. Inprocess, Rejected, Draft, Approved, Declined.
     * @var string
     */
    public string $status = '';

    /**
     * Title of the deposit activity.
     * @var string
     */
    public string $title = '';

    /**
     * Producer Agent ID of depositor.     *
     * @var array
     */
    public array $producer_agent = ['value' => null, 'desc' => null];

    /**
     * Producer ID of deposit.
     * @var array
     */
    public array $producer = ['value' => null, 'desc' => null];

    /**
     * ID of the Material Flow used
     * @var array
     */
    public array $material_flow = ['value' => null, 'desc' => null];

    /**
     * SIP ID assigned to deposit activity.
     * @var string
     */
    public string $sip_id = '';

    /**
     * Reason SIP was rejected or declined.
     * @var string
     */
    public string $sip_reason = '';

    function __construct(array $data = [])
    {
        foreach($data as $key => $value) {
            if(property_exists(__CLASS__,$key)) {
                $this->$key = $value;
            }
        }
    }
}