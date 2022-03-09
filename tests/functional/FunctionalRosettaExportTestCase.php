<?php

import('tests.functional.plugins.importexport.FunctionalDoiExportTest');
import('plugins.importexport.rosetta.RosettaExportPlugin');

class FunctionalRosettaExportTest extends FunctionalDoiExportTest {
	const TEST_ACCOUNT = 'TEST_OJS';

	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() : void {
		$this->pluginId = 'rosetta';

		// Retrieve and check configuration. (We're in a chicken
		// and egg situation: This means that we cannot call
		// parent::setUp() at this point so we have to retrieve
		// the base URL here although it will be retrieved again
		// in the parent class.)
		$baseUrl = Config::getVar('debug', 'webtest_base_url');
		$rosettaPassword = Config::getVar('debug', 'webtest_rosetta_pw');
		if (empty($baseUrl) || empty($rosettaPassword)) {
			$this->markTestSkipped(
				'Please set webtest_base_url and webtest_rosetta_pw in your ' .
				'config.php\'s [debug] section to the base url of your test server ' .
				'and the password of your Rosetta test account.'
			);
		}

		$this->pages = array(
			'index' => $baseUrl . '/index.php/test/manager/importexport/plugin/RosettaExportPlugin',
			'settings' => $baseUrl . '/index.php/test/manager/plugin/importexport/RosettaExportPlugin/settings'
		);

		$this->defaultPluginSettings = array(
			'username' => self::TEST_ACCOUNT,
			'password' => $rosettaPassword,
			'registrantName' => 'Registrant',
			'fromCompany' => 'From Company',
			'fromName' => 'From Person',
			'fromEmail' => 'from@email.com',
			'publicationCountry' => 'US',
			'exportIssuesAs' => O4DOI_ISSUE_AS_WORK
		);

		parent::setUp('1749');
	}



}

