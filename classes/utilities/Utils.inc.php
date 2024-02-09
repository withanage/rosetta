<?php

class Utils
{
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
