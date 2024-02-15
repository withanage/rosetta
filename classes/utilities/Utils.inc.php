<?php

class Utils
{
	public static function writeLog(string $message, string $level): void
	{
		try {
			// Generate a timestamp with microsecond precision.
			$fineStamp = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);

			// Construct the log entry.
			$logEntry = "$fineStamp $level $message\n";

			// Write the log entry to the log file.
			error_log($logEntry, 3, self::logFilePath());
		} catch (Exception $e) {
			var_dump($e->getMessage());
		}
	}

	public static function logFilePath(): string
	{
		return Config::getVar('rosetta', 'subDirectoryName') . '/rosetta.log';

	}

	public  function print_rr($input, $level = 0)
	{
		if ($level == 4) {
			return;
		}

		if (is_object($input)) {
			$vars = get_object_vars($input);

		}

		if (is_array($input)) {
			$vars = $input;
		}
		if (!$vars) {
			print " $input \n";
			return;
		}

		foreach ($vars as $k => $v) {
			if (is_object($v)) return print_rr($v, $level++);
			if (is_array($v)) return print_rr($v, $level++);

		}

	}

}
