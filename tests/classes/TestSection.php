<?php

/**
 * @file plugins/importexport/rosetta/tests/classes/TestSection.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class TestSection
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Rosetta export plugin
 */

namespace APP\plugins\importexport\rosetta\tests\classes;

use APP\section\Section;

class TestSection extends Section
{
	private string $currentLocale;

	public function __construct(?string $locale)
	{
		parent::__construct();

		$this->currentLocale = $locale;
	}

	public function createSection(): Section
	{
		$section = new Section();
		$section->setIdentifyType('section-identify-type', $this->getCurrentLocale());

		return $section;
	}

	public function getCurrentLocale(): ?string
	{
		return $this->currentLocale;
	}
}
