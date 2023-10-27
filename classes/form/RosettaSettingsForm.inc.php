<?php
/**
 * @file plugins/importexport/rosetta/classes/form/RosettaSettingsForm.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RosettaSettingsForm
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief A class for managing settings related to the Rosetta export plugin.
 *
 * @property int $journalId The ID of the journal associated with the form.
 * @property RosettaExportPlugin $plugin The instance of the Rosetta export plugin.
 * @property array $fields An array of field names used in the form.
 */

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

	/**
	 * Constructor
	 *
	 * @param RosettaExportPlugin $plugin
	 * @param int $journalId
	 */
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

	/**
	 * @copydoc Form::initData()
	 */
	public function initData(): void
	{
		foreach ($this->fields as $name) {
			$this->setData($name, $this->plugin->getSetting($this->journalId, $name));
		}
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	public function readInputData(): void
	{
		$this->readUserVars($this->fields);
	}

	/**
	 * @copydoc Form::execute()
	 */
	public function execute(...$functionArgs): void
	{
		foreach ($this->fields as $name) {
			$this->plugin->updateSetting($this->journalId, $name, $this->getData($name));
		}
	}

	/**
	 * Get an array of form fields.
	 *
	 * @return array An array of field names.
	 */
	function getFormFields(): array
	{
		return $this->fields;
	}

	/**
	 * Check if a specific setting is optional or not.
	 *
	 * @param string $settingName The name of the setting.
	 *
	 * @return bool true if the setting is optional, false otherwise.
	 */
	function isOptional(string $settingName): bool
	{
		return in_array($settingName, $this->fields);
	}
}
