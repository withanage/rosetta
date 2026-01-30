<?php

/**
 * @file plugins/importexport/rosetta/RosettaInfoSender.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaInfoSender
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta;

use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;
use PKP\scheduledTask\ScheduledTaskHelper;

class RosettaInfoSender extends ScheduledTask
{
	protected RosettaExportPlugin $plugin;

	public function __construct($args)
	{
		PluginRegistry::loadCategory('importexport');
		$plugin = PluginRegistry::getPlugin('importexport', 'RosettaExportPlugin');
		/* @var $plugin RosettaExportPlugin */
		$this->plugin = $plugin;
		if (is_a($plugin, 'RosettaExportPlugin')) {
			$plugin->addLocaleData();
		}

		parent::__construct($args);
	}

	public function getPlugin(): RosettaExportPlugin
	{
		return $this->plugin;
	}

	public function getName(): string
	{
		return __('plugins.importexport.rosetta.senderTask.name');
	}

	public function _addLogEntry($result): void
	{
		if (is_array($result)) {
			foreach ($result as $error) {
				assert(is_array($error) && count($error) >= 1);
				$this->addExecutionLogEntry(
					__($error[0], ['param' => ($error[1] ?? null)]),
					ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_WARNING
				);
			}
		} else {
			$this->addExecutionLogEntry(
				__('plugins.importexport.common.register.error.mdsError', ['param' => ' - ']),
				ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_WARNING
			);
		}
	}

	protected function executeActions(): bool
	{
		if (!$this->getPlugin()) {
			return false;
		}

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();
		foreach ($journals as $journal) {
			$unregisteredArticles = $this->getPlugin()->getUnregisteredArticles($journal);
			if (count($unregisteredArticles)) {
				$this->_registerObjects($unregisteredArticles, 'article=>rosetta-xml', $journal, 'articles');
			}
		}

		return true;
	}
}
