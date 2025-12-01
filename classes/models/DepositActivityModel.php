<?php

namespace TIBHannover\Rosetta\Models;

class DepositActivityModel
{
	public string $subdirectory = '';
	public string $id = '';
	public string $creation_date = '';
	public string $submission_date = '';
	public string $update_date = '';
	public string $status = '';
	public string $title = '';
	public array $producer_agent = ['value' => null, 'desc' => null];
	public array $producer = ['value' => null, 'desc' => null];
	public array $material_flow = ['value' => null, 'desc' => null];
	public string $sip_id = '';
	public string $sip_reason = '';

		function __construct(?array $data = [])
	{
		if (!empty($data)) $this->assignValues($data);
	}

		private function assignValues(array $data): void
	{
		foreach ($data as $key => $value) {
			if (property_exists(__CLASS__, $key)) {
				if (!empty($value)) $this->$key = $value;
			}
		}
	}
}
