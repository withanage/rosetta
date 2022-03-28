<?php


import('plugins.importexport.rosetta.RosettaExportPlugin');
import('lib.pkp.tests.plugins.PluginTestCase');


class FunctionalRosettaExportTest extends PluginTestCase {

	protected function setUp() : void {
		$this->pluginId = 'rosetta';


		$this->defaultPluginSettings = array(
			'username' => '',
			'password' => '',
			'registrantName' => 'Registrant',
			'fromCompany' => 'From Company',
			'fromName' => 'From Person',
			'fromEmail' => 'from@email.com',
			'publicationCountry' => 'US',
			'exportIssuesAs' => O4DOI_ISSUE_AS_WORK
		);

		parent::setUp('1749');
	}
	public function testExportRoseetaExport() {
		$exportPages = array('issues', 'articles', 'galleys', 'all');

		// Test that the plug-in cannot be used when required configuration parameters are missing.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(1, $this->pluginId . 'exportplugin', 'registrantName', '');
		$this->assertConfigurationError($exportPages, 'The plug-in is not fully set up');
	}


}

