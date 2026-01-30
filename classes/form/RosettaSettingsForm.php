<?php

/**
 * @file plugins/importexport/rosetta/classes/form/RosettaSettingsForm.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaSettingsForm
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\classes\form;

use APP\plugins\importexport\rosetta\RosettaExportPlugin;
use PKP\form\Form;
use PKP\form\validation\FormValidator;

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

	public function getFormFields(): array
	{
		return $this->fields;
	}

	public function isOptional(string $settingName): bool
	{
		return in_array($settingName, $this->fields);
	}
}
