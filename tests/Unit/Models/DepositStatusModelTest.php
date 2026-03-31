<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Models/DepositStatusModelTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class DepositStatusModelTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Tests for DepositStatusModel — verifies construction and property assignment.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Models;

use APP\plugins\importexport\rosetta\classes\models\DepositStatusModel;
use PHPUnit\Framework\TestCase;

class DepositStatusModelTest extends TestCase
{
	public function testDefaultValuesOnEmptyConstruction(): void
	{
		$model = new DepositStatusModel();

		$this->assertSame('', $model->id);
		$this->assertFalse($model->status);
		$this->assertSame('', $model->date);
		$this->assertSame('', $model->doi);
	}

	public function testConstructWithData(): void
	{
		$data = [
			'id' => 'SIP-12345',
			'status' => true,
			'date' => '2024-01-15',
			'doi' => '10.1234/test.doi',
		];

		$model = new DepositStatusModel($data);

		$this->assertSame('SIP-12345', $model->id);
		$this->assertTrue($model->status);
		$this->assertSame('2024-01-15', $model->date);
		$this->assertSame('10.1234/test.doi', $model->doi);
	}

	public function testConstructWithPartialData(): void
	{
		$model = new DepositStatusModel(['id' => 'SIP-99', 'doi' => '10.5678/partial']);

		$this->assertSame('SIP-99', $model->id);
		$this->assertFalse($model->status);
		$this->assertSame('', $model->date);
		$this->assertSame('10.5678/partial', $model->doi);
	}

	public function testConstructWithNullData(): void
	{
		$model = new DepositStatusModel(null);

		$this->assertSame('', $model->id);
		$this->assertFalse($model->status);
	}

	public function testUnknownKeysAreIgnored(): void
	{
		$model = new DepositStatusModel(['id' => 'SIP-1', 'unknownField' => 'value']);

		$this->assertSame('SIP-1', $model->id);
		$this->assertFalse(property_exists($model, 'unknownField') && isset($model->unknownField));
	}

	public function testEmptyValuesAreNotAssigned(): void
	{
		$model = new DepositStatusModel(['id' => '', 'doi' => '']);

		$this->assertSame('', $model->id);
		$this->assertSame('', $model->doi);
	}
}
