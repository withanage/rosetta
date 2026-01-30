<?php

/**
 * @file plugins/importexport/rosetta/classes/Constants.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class Constants
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes;

class Constants
{
	public const USER_AGENT = 'OJSRosettaExportPlugin';

	public const DEPOSIT_STATUS_SETTING_NAME = 'rosetta::deposit_status';

	public const DEPOSIT_ACTIVITY_SETTING_NAME = 'rosetta::deposit_activity_object';

	public const DEPOSIT_HISTORY_IN_DAYS = 730;

	public const REGISTERED_DOI_SETTING_NAME = 'crossref::registeredDoi';

	public static function getContextSettings(): array
	{
		return json_decode(
			file_get_contents(__DIR__ . '/../settings.json'),
			true
		);
	}
}
