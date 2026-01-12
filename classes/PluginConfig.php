<?php

/**
 * @file plugins/importexport/rosetta/classes/PluginConfig.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PluginConfig
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes;

use APP\notification\NotificationManager;
use APP\plugins\importexport\rosetta\classes\form\RosettaSettingsForm;
use APP\plugins\importexport\rosetta\RosettaExportPlugin;
use PKP\core\JSONMessage;

class PluginConfig
{
	public RosettaExportPlugin $plugin;

	public function __construct(RosettaExportPlugin $plugin)
	{
		$this->plugin = $plugin;
	}

	public function manage($args, $request, $parentManage): JSONMessage
	{
		if ($request->getUserVar('verb') == 'settings') {
			$user = $request->getUser();
			$this->plugin->addLocaleData();
			$form = new RosettaSettingsForm($this->plugin, $request->getContext()->getId());
			if ($request->getUserVar('save')) {
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();
					$notificationManager = new NotificationManager();
					$notificationManager->createTrivialNotification($user->getId());
					return new JSONMessage();
				}
			} else {
				$form->initData();
			}
			return new JSONMessage(true, $form->fetch($request));
		}

		return $parentManage;
	}
}
