<?php

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class RosettaInfoSender extends ScheduledTask
{
		var RosettaExportPlugin $plugin;

	function __construct($args)
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

		function getPlugin(): RosettaExportPlugin
	{
		return $this->plugin;
	}

		function getName()
	{
		return __('plugins.importexport.rosetta.senderTask.name');
	}

		function _addLogEntry($result)
	{
		if (is_array($result)) {
			foreach ($result as $error) {
				assert(is_array($error) && count($error) >= 1);
				$this->addExecutionLogEntry(
					__($error[0], array('param' => (isset($error[1]) ? $error[1] : null))),
					SCHEDULED_TASK_MESSAGE_TYPE_WARNING
				);
			}
		} else {
			$this->addExecutionLogEntry(
				__('plugins.importexport.common.register.error.mdsError', array('param' => ' - ')),
				SCHEDULED_TASK_MESSAGE_TYPE_WARNING
			);
		}
	}

		protected function executeActions()
	{
		if ($this->getPlugin() == false) return false;

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();
		foreach ($journals as $journal) {
			$unregisteredArticles = $this->getPlugin()->getUnregisteredArticles($journal);
			if (count($unregisteredArticles)) {
				$this->_registerObjects($unregisteredArticles, 'article=>rosetta-xml', $journal, 'articles');
			}
		}
	}
}
