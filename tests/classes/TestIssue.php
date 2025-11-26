<?php

/**
 * @file plugins/importexport/rosetta/tests/classes/TestIssue.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class TestIssue
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\classes;

use APP\issue\Issue;

class TestIssue extends Issue
{
    function __construct()
    {
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->setId(1);
        $this->setJournalId(10000);
        $this->setVolume(1);
        $this->setYear(2024);
        $this->setNumber(1);
        $this->setDatePublished('2010-11-05');
        $this->setStoredPubId('doi', '10.1234/jpkjpk.v1i2');
    }
}
