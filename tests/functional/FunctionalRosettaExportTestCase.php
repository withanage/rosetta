<?php


import('plugins.importexport.rosetta.RosettaExportPlugin');
import('lib.pkp.tests.plugins.PluginTestCase');


class FunctionalRosettaExportTest extends PluginTestCase {
	const TEST_ACCOUNT = 'TEST_OJS';

	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() : void {
		$this->pluginId = 'rosetta';


		$this->defaultPluginSettings = array(
			'username' => self::TEST_ACCOUNT,
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

	public function testMods34MetadataPlugin($appSpecificFilters = array()) {

	}

}

