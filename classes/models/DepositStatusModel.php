<?php

namespace TIBHannover\Rosetta\Models;

class DepositStatusModel
{
	public string $id = '';
	public bool $status = false;
	public string $date = '';
	public string $doi = '';

		function __construct(?array $data = [])
	{
		if (!empty($data)) $this->assignValues($data);
	}

		private function assignValues(array $data): void
	{
		foreach ($data as $key => $value) {
			if (property_exists(__CLASS__, $key)) {
				if (!empty($value) and isset($value)) $this->$key = $value;
			}
		}
	}
}
