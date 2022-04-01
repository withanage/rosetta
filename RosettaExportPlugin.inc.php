<?php


error_reporting(E_ERROR | E_PARSE);

import('classes.plugins.PubObjectsExportPlugin');
import('plugins.importexport.rosetta.RosettaExportDeployment');

class RosettaExportPlugin extends PubObjectsExportPlugin
{
	/**
	 * @copydoc Plugin::register()
	 * @param $category
	 * @param $path
	 * @param null $mainContextId
	 * @return bool
	 */
	public function register($category, $path, $mainContextId = null)
	{
		$success = parent::register($category, $path, $mainContextId);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName()
	{
		return __('plugins.importexport.rosetta.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription()
	{
		return __('plugins.importexport.rosetta.description');
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName()
	{
		return 'RosettaSettingsForm';
	}

	function getUnregisteredArticles($context)
	{
		// Retrieve all published submissions that have not yet been registered.
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		/* @var $submissionDao SubmissionDAO */
		$articles = $submissionDao->getExportable(
			$context->getId(),
			null,
			null,
			null,
			null,
			$this->getDepositStatusSettingName(),
			EXPORT_STATUS_NOT_DEPOSITED,
			null
		);
		return $articles->toArray();
	}

	public function getDepositStatusSettingName()
	{
		return $this->getPluginSettingsPrefix() . '::status';
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix()
	{
		return 'rosetta';
	}

	/**
	 * Get the canonical URL of an object.
	 * @param $request Request
	 * @param $context Context
	 * @param $object Issue|Submission|ArticleGalley
	 */
	function _getObjectUrl($request, $context, $object)
	{
		$router = $request->getRouter();
		// Retrieve the article of article files.
		if (is_a($object, 'ArticleGalley')) {
			$articleId = $object->getSubmissionId();
			$cache = $this->getCache();
			if ($cache->isCached('articles', $articleId)) {
				$article = $cache->get('articles', $articleId);
			} else {
				$article = Services::get('submission')->get($articleId);
			}
			assert(is_a($article, 'Submission'));
		}
		$url = null;
		switch (true) {
			case is_a($object, 'Submission'):
				$url = $router->url($request, $context->getPath(), 'article', 'view', $object->getBestId(), null, null, true);
				break;
		}
		if ($this->isTestMode($context)) {
			// Change server domain for testing.
			$url = PKPString::regexp_replace('#://[^\s]+/index.php#', '://example.com/index.php', $url);
		}
		return $url;
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	public function display($args, $request)
	{
		parent::display($args, $request);
		$templateManager = TemplateManager::getManager();
		$journal = $request->getContext();
		switch ($route = array_shift($args)) {
			case 'settings':
				return $this->manage($args, $request);
		}
		$templateManager->display($this->getTemplateResource('index.tpl'));
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	public function manage($args, $request)
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
					$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS);
					return new JSONMessage();
				}
			} else {
				$form->initData();
			}
			return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	function getCanEnable()
	{
		return true;
	}

	function getCanDisable()
	{
		return true;
	}

	function setEnabled($enabled)
	{
		$context = Application::get()->getRequest()->getContext();
		$this->updateSetting($context->getId(), 'enabled', $enabled, 'bool');
	}

	/**
	 * @param The $scriptName
	 * @param Parameters $args
	 */
	function executeCLI($scriptName, &$args)
	{
		$journalPath = array_shift($args);
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getByPath($journalPath);
		if ($journal == false) {

			$contextDao = Application::getContextDAO();
			/* @var $contextDao JournalDAO */
			$journalFactory = $contextDao->getAll(true);
			while ($journal = $journalFactory->next()) {
				if ($this->getEnabled($journal)) {
					$deployment = new RosettaExportDeployment($journal, $this);
					$deployment->getSubmissions();
				}
			}
		} else {
			// Deploy submissions
			$deployment = new RosettaExportDeployment($journal, $this);
			$deployment->getSubmissions();

		}
	}

	/**
	 * Determine whether or not this plugin is currently enabled.
	 * @return boolean
	 */
	function getEnabled($context = null)
	{
		return ($context != null) ? $this->getSetting($context->getId(), 'enabled') : false;
	}

	function getSetting($contextId, $name)
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
			default:
				return parent::getSetting($contextId, $name);
		}
		return $config_value ?: parent::getSetting($contextId, $name);
	}

	/**
	 * @param $dir
	 */
	function rrmdir($dir): void
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir")
						$this->rrmdir($dir . "/" . $object);
					else unlink($dir . "/" . $object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	public function logError(string $message)
	{
		self::writeLog($message, 'ERROR');
	}

	/**
	 * @param $message
	 * @param $level
	 */
	private static function writeLog(string $message, string $level): void
	{
		$fineStamp = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);
		error_log("$fineStamp $level $message\n", 3, self::logFilePath());
	}

	/**
	 * @return string
	 */
	public static function logFilePath(): string
	{
		return Config::getVar('files', 'files_dir') . '/rosetta.log';
	}

	/***
	 * @copyDoc  writeLog
	 * @param $message
	 */
	public function logInfo(string $message): void
	{
		self::writeLog($message, 'INFO');
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName)
	{
		echo __('plugins.importexport.rosetta.cliUsage', array(
				'scriptName' => $scriptName,
				'pluginName' => $this->getName()
			)) . "\n";
	}

	/**
	 * @see Plugin::getName()
	 */
	function getName()
	{
		return __CLASS__;
	}

	/**
	 * @copydoc Plugin::getContextSpecificPluginSettingsFile()
	 */
	function getContextSpecificPluginSettingsFile()
	{
		return $this->getPluginPath() . '/settings.xml';
	}

	function depositXML($objects, $context, $filename)
	{
		// TODO: Implement depositXML() method.
	}

	/**
	 * @inheritDoc
	 */
	function getExportDeploymentClassName()
	{
		// TODO: Implement getExportDeploymentClassName() method.
	}
}

