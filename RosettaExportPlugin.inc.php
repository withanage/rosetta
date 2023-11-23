<?php
/**
 * @file plugins/importexport/rosetta/RosettaExportPlugin.inc.php
 *
 * Copyright (c) 2021+ TIB Hannover
 * Copyright (c) 2021+ Dulip Withanage, Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RosettaExportPlugin
 * @ingroup plugins_importexport_rosettaexportplugin
 *
 * @brief Plugin for depositing publications to Rosetta.
 *
 * @property array $pluginSettings Settings which are saved in settings.json
 * @property string $userAgent User agent name for identifying us
 * @property string $depositStatusSettingName Status of the deposit setting name
 * @property string $depositActivitySettingName Rosetta deposit object received from the REST Api setting name
 * @property int $depositHistoryInDays Delay in days before depositing again
 * @property string $registeredDoiSettingName Setting name of registered doi
 */

import('classes.plugins.PubObjectsExportPlugin');
import('plugins.importexport.rosetta.RosettaExportDeployment');

use TIBHannover\Rosetta\Form\RosettaSettingsForm;
use TIBHannover\Rosetta\RosettaExportDeployment;

class RosettaExportPlugin extends PubObjectsExportPlugin
{
	public array $pluginSettings;
	public string $userAgent = 'OJSRosettaExportPlugin';
	public string $depositStatusSettingName = 'rosetta::deposit_status';
	public string $depositActivitySettingName = 'rosetta::deposit_activity_object';
	public int $depositHistoryInDays = 730;
	public string $registeredDoiSettingName = 'crossref::registeredDoi';

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->pluginSettings = json_decode(file_get_contents(__DIR__ . '/settings.json'), true);
		parent::__construct();
	}

	/**
	 * Register the plugin with OJS.
	 *
	 * @param string $category The category to which the plugin belongs.
	 * @param string $path The file path of the plugin.
	 * @param int|null $mainContextId The ID of the main context, if applicable.
	 *
	 * @return bool Returns `true` if the plugin registration is successful; otherwise, it returns `false`.
	 */
	public function register($category, $path, $mainContextId = null): bool
	{
		// Add properties to the schema of a publication
		HookRegistry::register('Schema::get::publication', array($this, 'addToSchema'));

		// Call the parent class's register method to perform standard registration
		$success = parent::register($category, $path, $mainContextId);

		// Add locale data to the plugin
		$this->addLocaleData();

		// Return the success status of the registration
		return $success;
	}

	/**
	 * Add Rosetta-specific properties to the schema of a publication.
	 *
	 * @param string $hookName The name of the hook being executed.
	 * @param array $params An array of parameters passed to the hook. The first parameter ($params[0]) is the schema object.
	 *
	 * @return void
	 */
	function addToSchema($hookName, $params): void
	{
		// Get the schema object from the hook parameters
		$schema = $params[0];

		// Add the 'depositStatus' property to the schema
//        $schema->properties->{$this->depositStatusSettingName} = (object)[
//            'type' => 'string',
//            'multilingual' => false,
//            'writeOnly' => true,
//            'validation' => ['nullable'],
//        ];

		// Add the 'depositActivity' property to the schema
		$schema->properties->{$this->depositActivitySettingName} = (object)[
			'type' => 'string',
			'multilingual' => false,
			'writeOnly' => true,
			'validation' => ['nullable'],
		];
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName(): string
	{
		return __('plugins.importexport.rosetta.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription(): string
	{
		return __('plugins.importexport.rosetta.description');
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName(): string
	{
		return 'RosettaSettingsForm';
	}

	/**
	 * Get a list of unregistered articles with Rosetta
	 *
	 * @param Context $context The context for which to retrieve unregistered articles.
	 *
	 * @return array An array of unregistered articles within the context.
	 */
	function getUnregisteredArticles($context): array
	{
		// Retrieve all published submissions that have not yet been registered.
		$submissionDao = new SubmissionDAO();
		$articles = $submissionDao->getExportable(
			$context->getId(),
			null,
			null,
			null,
			null,
			$this->depositStatusSettingName,
			EXPORT_STATUS_NOT_DEPOSITED,
			null
		);

		return $articles->toArray();
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	public function display($args, $request): void
	{
		$templateManager = TemplateManager::getManager();
		$journal = $request->getContext();
		switch (array_shift($args)) {
			case 'index':
			case 'settings':
			case '':
				$templateManager->display($this->getTemplateResource('index.tpl'));
		}
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	public function manage($args, $request): JSONMessage
	{
		if ($request->getUserVar('verb') == 'settings') {
			$user = $request->getUser();
			$this->addLocaleData();
			AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_MANAGER);
			$this->import('classes.form.RosettaSettingsForm');
			$form = new RosettaSettingsForm($this, $request->getContext()->getId());
			if ($request->getUserVar('save')) {
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();
					$notificationManager = new NotificationManager();
					$notificationManager->createTrivialNotification($user->getId(),
						NOTIFICATION_TYPE_SUCCESS);
					return new JSONMessage();
				}
			} else {
				$form->initData();
			}
			return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * Get the path to the plugin settings file.
	 *
	 * @return string The file path to the context-specific plugin settings file in XML format.
	 */
	public function getContextSpecificPluginSettingsFile(): string
	{
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Execute the plugin's command-line interface (CLI) functionality.
	 *
	 * @param string $scriptName The name of the CLI script being executed.
	 * @param array $args An array of arguments passed to the CLI script.
	 *
	 * @return void
	 */
	public function executeCLI($scriptName, &$args): void
	{
		try {
			$journalPath = array_shift($args);
			$journalDao = DAORegistry::getDAO('JournalDAO');
			/* @var $journalDao JournalDAO */

			$journal = $journalDao->getByPath($journalPath);

			if (!$journal) {
				$contextDao = Application::getContextDAO();
				$journalFactory = $contextDao->getAll();

				while ($journal = $journalFactory->next()) {
					PluginRegistry::loadCategory('pubIds', true, $journal->getId()); // DO not remove

					if (key_exists(strtoupper($journal->getLocalizedAcronym()), array_change_key_case($this->pluginSettings, CASE_UPPER))) {
						$deployment = new RosettaExportDeployment($journal, $this);
						$deployment->process();
					}
				}
			} else {
				// Deploy submissions
				PluginRegistry::loadCategory('pubIds', true, $journal->getId());
				$deployment = new RosettaExportDeployment($journal, $this);
				$deployment->process();
			}
		} catch (Exception $exception) {
			$this->logError($exception);
		}
	}

	/**
	 * This method writes an error message to the log file with the 'ERROR' level.
	 *
	 * @param string $message The error message to be logged.
	 *
	 * @return void
	 */
	public function logError(string $message): void
	{
		self::writeLog($message, 'ERROR');
	}

	/**
	 * This method is responsible for writing log messages to a log file.
	 *
	 * @param string $message The log message to be written.
	 * @param string $level The log level (e.g., 'ERROR', 'INFO').
	 *
	 * @return void
	 */
	public static function writeLog(string $message, string $level): void
	{
		try {
			// Generate a timestamp with microsecond precision.
			$fineStamp = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);

			// Construct the log entry.
			$logEntry = "$fineStamp $level $message\n";

			// Write the log entry to the log file.
			error_log($logEntry, 3, self::logFilePath());
		} catch (Exception $e) {
			var_dump($e->getMessage());
		}
	}

	/**
	 * Get the path to the log file.
	 *
	 * @return string The absolute file path to the log file.
	 */
	public static function logFilePath(): string
	{
		return Config::getVar('rosetta', 'subDirectoryName') . '/rosetta.log';

	}

	/**
	 * Load a setting or load it from the config.inc.php if it is specified there.
	 *
	 * @param int $contextId The context or journal identifier.
	 * @param string $name The name of the setting.
	 *
	 * @return mixed|null|false The setting value or null if not found.
	 */
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

	/**
	 * Recursively remove a directory and its contents.
	 *
	 * @param string $dir The path to the directory to be removed.
	 *
	 * @return void
	 */
	public function removeDirRecursively(string $dir): void
	{
		if (empty($dir)) return;

		try {
			if (is_dir($dir)) {
				// iterate through all items in current directory
				$items = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
				foreach ($items as $item) {
					$pathName = $item->getPathname();
					if ($item->isDir() && !$item->isDot()) {
						// current item is a directory, call this method again
						$this->removeDirRecursively($pathName);
					} else {
						// current item is a file, remove file
						unlink($pathName);
					}
				}
				// Remove the directory itself after its contents are deleted.
				rmdir($dir);
			}
		} catch (Exception $e) {
			$this->logError($e->getMessage());
		}
	}

	/**
	 * Recursively change the permissions of directories and files.
	 *
	 * @param string $dir
	 * @param int $permissions
	 *
	 * @return void
	 */
	public function setPermissionsRecursively(string $dir, int $permissions = 0777): void
	{
		if (empty($dir)) return;

		try {
			if (is_dir($dir)) {
				//change permission of current directory
				chmod($dir, $permissions);

				// iterate through all items in current directory
				$items = new DirectoryIterator($dir);
				foreach ($items as $item) {
					if ($item->isDir() && !$item->isDot()) {
						// current item is a directory, call this method again
						$this->setPermissionsRecursively($item->getPathname(), $permissions);
					} else {
						// current item is a file, change permission
						chmod($item->getPathname(), $permissions);
					}
				}
			}
		} catch (Exception $e) {
			$this->logError($e->getMessage());
		}
	}

	/**
	 *  This method writes an informational message to the log file with the 'INFO' level.
	 *
	 * @param string $message The message to be logged.
	 */
	public function logInfo(string $message): void
	{
		self::writeLog($message, 'INFO');
	}

	/**
	 * Display the command-line usage information.
	 *
	 * @param string $scriptName The name of the script being executed.
	 */
	public function usage($scriptName): void
	{
		echo __('plugins.importexport.rosetta.cliUsage', array(
				'scriptName' => $scriptName,
				'pluginName' => $this->getName()
			)) . "\n";
	}

	/**
	 * @copydoc Plugin::getName()
	 */
	public function getName(): string
	{
		return __CLASS__;
	}

	/**
	 * Deposit XML document.
	 *
	 * @param mixed $objects Array of or single published submission, issue or galley
	 * @param Context $context The context in OJS
	 * @param string $filename The name of the XML file to create.
	 *
	 * @return bool Whether the XML document has been registered
	 */
	public function depositXML($objects, $context, $filename): bool
	{
		//todo: do something useful
		return true;
	}

	/**
	 * Return the name of the plugin's deployment class.
	 *
	 * @return string
	 *
	 * @inheritDoc
	 */
	public function getExportDeploymentClassName(): string
	{
		// todo: implement getExportDeploymentClassName() method.
		return '';
	}

	/**
	 * Get the base path for file storage, e.g. /var/www/html/ojs_files
	 *
	 * @return string The base path for file storage.
	 */
	public function getBasePath(): string
	{
		return Config::getVar('files', 'files_dir');
	}
}
