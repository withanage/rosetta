<?php

error_reporting(E_ERROR | E_PARSE);

import('classes.plugins.PubObjectsExportPlugin');

class RosettaExportPlugin extends PubObjectsExportPlugin {
	/**
	 * @copydoc Plugin::register()
	 * @param $category
	 * @param $path
	 * @param null $mainContextId
	 * @return bool
	 */
	public function register($category, $path, $mainContextId = null) {
		return parent::register($category, $path, $mainContextId);

	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.rosetta.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.rosetta.description');
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'rosetta';
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'RosettaSettingsForm';
	}


	function getUnregisteredArticles($context) {
		// Retrieve all published submissions that have not yet been registered.
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
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
	public function getDepositStatusSettingName() {
		return $this->getPluginSettingsPrefix().'::status';
	}



	/**
	 * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'RosettaExportDeployment';
	}

	function _tarFiles($targetPath, $targetFile, $sourceFiles) {
		assert((boolean)$this->createSIP());
		//TODO change the commands
		$tarCommand = Config::getVar('cli', 'tar') . ' -czf ' . escapeshellarg($targetFile);
		$tarCommand .= ' -C ' . escapeshellarg($targetPath);
		$tarCommand .= ' --owner 0 --group 0 --';
		foreach ($sourceFiles as $sourceFile) {
			assert(dirname($sourceFile) . '/' === $targetPath);
			if (dirname($sourceFile) . '/' !== $targetPath) continue;
			$tarCommand .= ' ' . escapeshellarg(basename($sourceFile));
		}
		// Execute the command.
		exec($tarCommand);
	}

	function checkZIPCommand() {
		$zipBinary = Config::getVar('cli', 'zip');
		if (empty($zipBinary) || is_executable($zipBinary) == false) {
			$result = array(
				array('manager.plugins.zipCommandNotFound')
			);
		} else {
			$result = true;
		}
		return $result;
	}


	/**
	 * Get the canonical URL of an object.
	 * @param $request Request
	 * @param $context Context
	 * @param $object Issue|Submission|ArticleGalley
	 */
	function _getObjectUrl($request, $context, $object) {
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
	public function display($args, $request) {
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
	public function manage($args, $request) {
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

	/**
	 * @param The $scriptName
	 * @param Parameters $args
	 */
	function executeCLI($scriptName, &$args) {
		$journalPath = array_shift($args);
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getByPath($journalPath);
		if ($journal == false) {
			if ($journalPath != '') {
				echo __('plugins.importexport.rosetta.cliError') . "\n";
				echo __('plugins.importexport.rosetta.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		} else {
			// Deploy submissions
			import('plugins.importexport.rosetta.RosettaExportDeployment');
			$deployment = new RosettaExportDeployment($journal, $this);
			$deployment->depositSubmissions();


		}
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.rosetta.cliUsage', array(
				'scriptName' => $scriptName,
				'pluginName' => $this->getName()
			)) . "\n";
	}

	/**
	 * @see Plugin::getName()
	 */
	function getName() {
		return __CLASS__;
	}

	/**
	 * @copydoc Plugin::getContextSpecificPluginSettingsFile()
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	function getSetting($contextId, $name) {
		switch ($name) {
			case 'rosettaHost':
				$config_value = Config::getVar('rosetta', 'host');
				break;
			case 'rosettaInstitutionCode':
				$config_value = Config::getVar('rosetta', 'institution_code');
				break;
			case 'rosettaSubDirectoryName':
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

	function depositXML($objects, $context, $filename) {
		// TODO: Implement depositXML() method.
	}
}
