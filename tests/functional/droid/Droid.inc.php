<?php

import('plugins.importexport.rosetta.classes.droid.DroidService');

use TIBHannover\Rosetta\Droid\DroidService;

class Droid
{

	public function testDroid(RosettaFunctionsTest $rosettaFunctionsTest): void
	{
		$rosettaFunctionsTest->createRouter();
		$plugin = $rosettaFunctionsTest->getPlugin();
		$droid = new DroidService($plugin, 0);

		$pdf = join(DIRECTORY_SEPARATOR, array(getcwd(), $plugin->getPluginPath(),
			'tests', 'sip', 'content', 'streams', 'MASTER', '114-53-3063-1-10-20220929.pdf'));

		$result = $droid->identify($pdf);

		$rosettaFunctionsTest->assertTrue($result->identified);
		$rosettaFunctionsTest->assertEquals('fmt/276', $result->puid);
		$rosettaFunctionsTest->assertEquals('application/pdf', $result->mime);
		$rosettaFunctionsTest->assertFalse($result->extensionMismatch);
	}
}
