<?php


import('classes.journal.Section');
class TestSection extends Section
{
	private $currentLocale;
	public function __construct($locale)
	{
		$this->currentLocale = $locale;


	}

	public function createSection(): Section
	{

		$section = new Section();
		$section->setIdentifyType('section-identify-type', $this->getCurrentLocale);

		return $section;
	}

		public function getCurrentLocale()
	{
		return $this->currentLocale;
	}


}
