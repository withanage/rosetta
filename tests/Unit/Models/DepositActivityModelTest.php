<?php

/**
 * @file plugins/importexport/rosetta/tests/Unit/Models/DepositActivityModelTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class DepositActivityModelTest
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Tests for DepositActivityModel — verifies construction and property assignment.
 */

namespace APP\plugins\importexport\rosetta\tests\Unit\Models;

use APP\plugins\importexport\rosetta\classes\models\DepositActivityModel;
use PHPUnit\Framework\TestCase;

class DepositActivityModelTest extends TestCase
{
	public function testDefaultValuesOnEmptyConstruction(): void
	{
		$model = new DepositActivityModel();

		$this->assertSame('', $model->subdirectory);
		$this->assertSame('', $model->id);
		$this->assertSame('', $model->creation_date);
		$this->assertSame('', $model->submission_date);
		$this->assertSame('', $model->update_date);
		$this->assertSame('', $model->status);
		$this->assertSame('', $model->title);
		$this->assertSame('', $model->sip_id);
		$this->assertSame('', $model->sip_reason);
		$this->assertSame(['value' => null, 'desc' => null], $model->producer_agent);
		$this->assertSame(['value' => null, 'desc' => null], $model->producer);
		$this->assertSame(['value' => null, 'desc' => null], $model->material_flow);
	}

	public function testConstructWithFullData(): void
	{
		$data = [
			'subdirectory' => '/mount/rosetta/sip1',
			'id' => 'ACT-001',
			'creation_date' => '2024-01-10',
			'submission_date' => '2024-01-11',
			'update_date' => '2024-01-12',
			'status' => 'approved',
			'title' => 'Test Deposit',
			'producer_agent' => ['value' => 'agent1', 'desc' => 'Agent One'],
			'producer' => ['value' => 'prod1', 'desc' => 'Producer One'],
			'material_flow' => ['value' => 'flow1', 'desc' => 'Flow One'],
			'sip_id' => 'SIP-123',
			'sip_reason' => 'Initial deposit',
		];

		$model = new DepositActivityModel($data);

		$this->assertSame('/mount/rosetta/sip1', $model->subdirectory);
		$this->assertSame('ACT-001', $model->id);
		$this->assertSame('approved', $model->status);
		$this->assertSame('SIP-123', $model->sip_id);
		$this->assertSame(['value' => 'agent1', 'desc' => 'Agent One'], $model->producer_agent);
	}

	public function testConstructWithPartialData(): void
	{
		$model = new DepositActivityModel(['status' => 'declined', 'sip_id' => 'SIP-456']);

		$this->assertSame('declined', $model->status);
		$this->assertSame('SIP-456', $model->sip_id);
		$this->assertSame('', $model->subdirectory);
	}

	public function testConstructWithNullData(): void
	{
		$model = new DepositActivityModel(null);

		$this->assertSame('', $model->id);
		$this->assertSame('', $model->status);
	}

	public function testUnknownKeysAreIgnored(): void
	{
		$model = new DepositActivityModel(['id' => 'ACT-1', 'nonExistentField' => 'foo']);

		$this->assertSame('ACT-1', $model->id);
	}
}
