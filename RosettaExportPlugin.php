<?php

/**
 * @file plugins/importexport/rosetta/RosettaExportPlugin.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class RosettaExportPlugin
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\JournalDAO;
use APP\plugins\importexport\rosetta\classes\Constants;
use APP\plugins\importexport\rosetta\classes\PluginConfig;
use APP\plugins\importexport\rosetta\classes\PluginSchema;
use APP\plugins\importexport\rosetta\classes\utilities\Utils;
use APP\plugins\PubObjectsExportPlugin;
use APP\template\TemplateManager;
use Exception;
use PKP\config\Config;
use PKP\context\Context;
use PKP\core\JSONMessage;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class RosettaExportPlugin extends PubObjectsExportPlugin
{
	public PluginConfig $pluginConfig;

	public function __construct()
	{
		parent::__construct();
		$this->pluginConfig = new PluginConfig($this);
	}

	public function register($category, $path, $mainContextId = null): bool
	{
		// Add properties to the schema of a publication
		$pluginSchema = new PluginSchema($this);
		Hook::add('Schema::get::publication', $pluginSchema->addToPublication(...));

		// Call the parent class's register method to perform standard registration
		$success = parent::register($category, $path, $mainContextId);

		// Add locale data to the plugin
		$this->addLocaleData();

		// Return the success status of the registration
		return $success;
	}

	public function getDisplayName(): string
	{
		return __('plugins.importexport.rosetta.displayName');
	}

	public function getDescription(): string
	{
		return __('plugins.importexport.rosetta.description');
	}

	public function getSettingsFormClassName(): string
	{
		return 'RosettaSettingsForm';
	}

	public function getUnregisteredArticles($context): array
	{
		// Retrieve all published submissions that have not yet been registered.
		$articles = Repo::submission()->dao->getExportable(
			$context->getId(),
			null,
			null,
			null,
			null,
			Constants::DEPOSIT_STATUS_SETTING_NAME,
			PubObjectsExportPlugin::EXPORT_STATUS_NOT_DEPOSITED,
			null
		);

		return $articles->toArray();
	}

	public function display($args, $request): void
	{
		$templateManager = TemplateManager::getManager();
		switch (array_shift($args)) {
			case 'index':
			case 'settings':
			case '':
				$templateManager->display($this->getTemplateResource('index.tpl'));
		}
	}

	public function manage($args, $request): JSONMessage
	{
		return $this->pluginConfig->manage($args, $request, parent::manage($args, $request));
	}

	public function getContextSpecificPluginSettingsFile(): string
	{
		return $this->getPluginPath() . '/settings.xml';
	}

	public function executeCLI($scriptName, &$args): void
	{
		/** @var JournalDAO $journalDao */
		try {
			$commandLineArgument = array_shift($args);
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getByPath($commandLineArgument);

			if (!$journal) {
				$contextDao = Application::getContextDAO();
				$journalFactory = $contextDao->getAll();

				/** @var Context $journal */
				while ($journal = $journalFactory->next()) {
					if (key_exists(strtoupper($journal->getLocalizedAcronym()), array_change_key_case(Constants::getContextSettings(), CASE_UPPER))) {
						PluginRegistry::loadCategory('pubIds', true, $journal->getId()); // Do not remove
						$deployment = new RosettaExportDeployment($this, $journal);
						$deployment->process();
					}
				}
			} else {
				// Deploy submissions
				PluginRegistry::loadCategory('pubIds', true, $journal->getId());
				$deployment = new RosettaExportDeployment($this, $journal);
				$deployment->process();
			}
		} catch (Exception $exception) {
			Utils::logError($exception);
		}
	}

	public function getSetting($contextId, $name): mixed
	{
		switch ($name) {
			case 'rosettaHost':
				$config_value = Config::getVar('rosetta', 'host');
				break;
			case 'rosettaInstitutionCode':
				$config_value = Config::getVar('rosetta', 'institution_code');
				break;
			case 'subDirectoryName':
				$config_value = Config::getVar('rosetta', 'subDirectoryName');
				break;
			case 'rosettaUsername':
				$config_value = Config::getVar('rosetta', 'username');
				break;
			case 'rosettaPassword':
				$config_value = Config::getVar('rosetta', 'password');
				break;
			case 'rosettaMaterialFlowId':
				$config_value = Config::getVar('rosetta', 'materialFlowId');
				break;
			case 'rosettaProducerId':
				$config_value = Config::getVar('rosetta', 'producerId');
				break;
			case 'testMode':
				$config_value = Config::getVar('rosetta', 'testMode');
				if (!empty($config_value) && (strtolower($config_value) === 'true' || (string)$config_value === '1')) {
					$config_value = true;
				} else if (!empty($config_value)) {
					$config_value = false;
				}
				break;
			default:
				return parent::getSetting($contextId, $name);
		}

		return $config_value ?: parent::getSetting($contextId, $name);
	}

	public function usage($scriptName): void
	{
		echo __('plugins.importexport.rosetta.cliUsage', [
				'scriptName' => $scriptName,
				'pluginName' => $this->getName()
			]) . "\n";
	}

	public function getName(): string
	{
		$classNameParts = explode('\\', get_class($this));
		return strtolower(end($classNameParts));
	}

	public function depositXML($objects, $context, $filename): bool
	{
		return true;
	}

	public function getExportDeploymentClassName(): string
	{
		return '';
	}
}
