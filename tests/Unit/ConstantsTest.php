<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/ConstantsTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ConstantsTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Tests for Constants — verifies constant values and settings loading.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit;

use APP\plugins\importexport\rosetta\classes\Constants;
use PHPUnit\Framework\TestCase;

class ConstantsTest extends TestCase
{
	public function testUserAgentConstant(): void
	{
		$this->assertSame('OJSRosettaExportPlugin', Constants::USER_AGENT);
	}

	public function testDepositStatusSettingName(): void
	{
		$this->assertSame('rosetta::deposit_status', Constants::DEPOSIT_STATUS_SETTING_NAME);
	}

	public function testDepositActivitySettingName(): void
	{
		$this->assertSame('rosetta::deposit_activity_object', Constants::DEPOSIT_ACTIVITY_SETTING_NAME);
	}

	public function testDepositHistoryInDays(): void
	{
		$this->assertSame(730, Constants::DEPOSIT_HISTORY_IN_DAYS);
	}

	public function testRegisteredDoiSettingName(): void
	{
		$this->assertSame('crossref::registeredDoi', Constants::REGISTERED_DOI_SETTING_NAME);
	}

	public function testGetContextSettingsReturnsArray(): void
	{
		$settings = Constants::getContextSettings();

		$this->assertIsArray($settings);
	}

	public function testGetContextSettingsLoadsFromSettingsJson(): void
	{
		$settings = Constants::getContextSettings();
		$settingsFile = dirname(__DIR__, 2) . '/settings.json';
		$expected = json_decode(file_get_contents($settingsFile), true);

		$this->assertSame($expected, $settings);
	}
}
