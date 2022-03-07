<?php
import('lib.pkp.classes.form.Form');

class RosettaSettingsForm extends Form
{
	/** @var $journalId int */
	private $journalId;
	/** @var $plugin RosettaExportPlugin */
	private $plugin;
	/** @var $fields array */
	private $fields = ['rosettaHost', 'subDirectoryName', 'rosettaUsername', 'rosettaPassword', 'rosettaProducerId', 'rosettaMaterialFlowId', 'journals'];

	/**
	 * Constructor
	 * @param $plugin RosettaExportPlugin
	 * @param $journalId int
	 */
	public function __construct(RosettaExportPlugin $plugin, $journalId)
	{
		$this->journalId = $journalId;
		$this->plugin = $plugin;
		parent::__construct($this->plugin->getTemplateResource('settingsForm.tpl'));
		foreach ($this->fields as $name) {
			$this->addCheck(new FormValidator($this, $name, 'required', 'plugins.importexport.rosetta.manager.settings.' . $name . 'Required'));
		}
	}

	/**
	 * @copydoc Form::initData()
	 */
	public function initData()
	{
		foreach ($this->fields as $name) {
			$this->setData($name, $this->plugin->getSetting($this->journalId, $name));
		}
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	public function readInputData()
	{
		$this->readUserVars($this->fields);
	}

	function getFormFields()
	{
		return $this->fields;
	}

	function isOptional($settingName)
	{
		return in_array($settingName, $this->fields);
	}

	/**
	 * @copydoc Form::execute()
	 */
	public function execute()
	{
		foreach ($this->fields as $name) {
			$this->plugin->updateSetting($this->journalId, $name, $this->getData($name));
		}
	}
}
