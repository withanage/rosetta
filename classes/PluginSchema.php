<?php

/**
 * @file plugins/importexport/rosetta/classes/PluginSchema.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PluginSchema
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes;

use APP\plugins\importexport\rosetta\RosettaExportPlugin;

class PluginSchema
{
	public RosettaExportPlugin $plugin;

	public function __construct(RosettaExportPlugin $plugin)
	{
		$this->plugin = $plugin;
	}

	public function addToPublication($hookName, $params): void
	{
		// Get the schema object from the hook parameters
		$schema = $params[0];

		// Add the 'depositStatus' property to the schema
		$schema->properties->{Constants::DEPOSIT_STATUS_SETTING_NAME} = (object)[
			'type' => 'string',
			'multilingual' => false,
			'writeOnly' => true,
			'validation' => ['nullable'],
		];

		// Add the 'depositActivity' property to the schema
		$schema->properties->{Constants::DEPOSIT_ACTIVITY_SETTING_NAME} = (object)[
			'type' => 'string',
			'multilingual' => false,
			'writeOnly' => true,
			'validation' => ['nullable'],
		];
	}
}
