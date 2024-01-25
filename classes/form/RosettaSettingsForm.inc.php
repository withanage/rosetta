<?php

namespace TIBHannover\Rosetta\Form;

use Form;
use FormValidator;
use RosettaExportPlugin;

import('lib.pkp.classes.form.Form');

class RosettaSettingsForm extends Form
{
	private int $journalId;
	private RosettaExportPlugin $plugin;
	private array $fields = ['rosettaHost', 'subDirectoryName', 'rosettaUsername', 'rosettaPassword',
		'rosettaProducerId', 'rosettaMaterialFlowId', 'journals'];

		public function __construct(RosettaExportPlugin $plugin, $journalId)
	{
		$this->journalId = $journalId;
		$this->plugin = $plugin;
		parent::__construct($this->plugin->getTemplateResource('settingsForm.tpl'));
		foreach ($this->fields as $name) {
			$this->addCheck(new FormValidator($this, $name, 'required',
				'plugins.importexport.rosetta.manager.settings.' . $name . 'Required'));
		}
	}

		public function initData(): void
	{
		foreach ($this->fields as $name) {
			$this->setData($name, $this->plugin->getSetting($this->journalId, $name));
		}
	}

		public function readInputData(): void
	{
		$this->readUserVars($this->fields);
	}

		public function execute(...$functionArgs): void
	{
		foreach ($this->fields as $name) {
			$this->plugin->updateSetting($this->journalId, $name, $this->getData($name));
		}
	}

		function getFormFields(): array
	{
		return $this->fields;
	}

		function isOptional(string $settingName): bool
	{
		return in_array($settingName, $this->fields);
	}
}
