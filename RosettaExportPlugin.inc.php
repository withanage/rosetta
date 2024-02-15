<?php

import('classes.plugins.PubObjectsExportPlugin');
import('plugins.importexport.rosetta.RosettaExportDeployment');

use TIBHannover\Rosetta\Form\RosettaSettingsForm;
use TIBHannover\Rosetta\RosettaExportDeployment;

class RosettaExportPlugin extends PubObjectsExportPlugin
{
	public array $rosettaContextSettings;
	public string $userAgent = 'OJSRosettaExportPlugin';
	public string $depositStatusSettingName = 'rosetta::deposit_status';
	public string $depositActivitySettingName = 'rosetta::deposit_activity_object';
	public int $depositHistoryInDays = 730;
	public string $registeredDoiSettingName = 'crossref::registeredDoi';

		function __construct()
	{
		$this->rosettaContextSettings = json_decode(file_get_contents(__DIR__ . '/settings.json'), true);
		parent::__construct();
	}

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

		function addToSchema($hookName, $params): void
	{
		// Get the schema object from the hook parameters
		$schema = $params[0];

		// Add the 'depositStatus' property to the schema
        $schema->properties->{$this->depositStatusSettingName} = (object)[
            'type' => 'string',
            'multilingual' => false,
            'writeOnly' => true,
            'validation' => ['nullable'],
        ];

		// Add the 'depositActivity' property to the schema
		$schema->properties->{$this->depositActivitySettingName} = (object)[
			'type' => 'string',
			'multilingual' => false,
			'writeOnly' => true,
			'validation' => ['nullable'],
		];
	}

		function getDisplayName(): string
	{
		return __('plugins.importexport.rosetta.displayName');
	}

		function getDescription(): string
	{
		return __('plugins.importexport.rosetta.description');
	}

		function getSettingsFormClassName(): string
	{
		return 'RosettaSettingsForm';
	}

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

		public function getContextSpecificPluginSettingsFile(): string
	{
		return $this->getPluginPath() . '/settings.xml';
	}

		public function executeCLI($scriptName, &$args): void
	{
		try {
			$commandLineArgument = array_shift($args);
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getByPath($commandLineArgument);

			if (!$journal) {
				$contextDao = Application::getContextDAO();
				$journalFactory = $contextDao->getAll();

				while ($journal = $journalFactory->next()) {

					if (key_exists(strtoupper($journal->getLocalizedAcronym()), array_change_key_case($this->rosettaContextSettings, CASE_UPPER))) {
						PluginRegistry::loadCategory('pubIds', true, $journal->getId()); // DO not remove
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
			$this->logError($exception);
		}
	}

		public function logError(string $message): void
	{
		TIBHannover\Rosetta\Utils\Utils::writeLog($message, 'ERROR');
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

		public function removeDirRecursively(string $dir): void
	{

		if (empty($dir)) return;

		try {
			if (is_dir($dir)) {
				// iterate through all items in current directory
				$items = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
				foreach ($items as $item) {
					$pathName = $item->getPathname();
					if ($item->isDir()) {
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

		public function logInfo(string $message): void
	{
		TIBHannover\Rosetta\Utils\Utils::writeLog($message, 'INFO');
	}

		public function usage($scriptName): void
	{
		echo __('plugins.importexport.rosetta.cliUsage', array(
				'scriptName' => $scriptName,
				'pluginName' => $this->getName()
			)) . "\n";
	}

		public function getName(): string
	{
		return __CLASS__;
	}

		public function depositXML($objects, $context, $filename): bool
	{
		return true;
	}

		public function getExportDeploymentClassName(): string
	{
		return '';
	}

		public function getBasePath(): string
	{
		return Config::getVar('files', 'files_dir');
	}
}
