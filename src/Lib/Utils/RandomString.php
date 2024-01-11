<?php

namespace App\Lib\Utils;

class RandomString
{
	public static function generate($type = 'alphanum', $length = 8)
	{
		switch($type)
		{
			case 'basic'    : return mt_rand();
				break;
			case 'alpha'    :
			case 'alphanum' :
			case 'id'       :
			case 'num'      :
			case 'nozero'   :
				$seedings             = array();
				$seedings['alpha']    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$seedings['alphanum'] = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$seedings['id']       = '0123456789abcdefghijklmnopqrstuvwxyz';
				$seedings['num']      = '0123456789';
				$seedings['nozero']   = '123456789';

				$pool = $seedings[$type];

				$str = '';
				for ($i=0; $i < $length; $i++)
				{
					$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
				}
				return $str;
				break;
			case 'unique'   :
			case 'md5'      :
				return md5(uniqid(mt_rand()));
				break;
		}
		return 'error';
	}

	/*======================================================+
	 Usage Example:
	 ---------------
		# Generate 10 chars. alphanumeric string
		echo "Alpha-Numeric: " . random_str('alphanum', 10) . "\n<br>";

		# Generate 10 chars. alpha string
		echo "Alpha: " . random_str('alpha', 10) . "\n<br>";

		# Generate 10 chars. numeric string
		echo "Numeric: " . random_str('num', 10) . "\n<br>";

		# Generate 10 chars. nozero-numeric string
		echo "NoZero Numeric: " . random_str('nozero', 10) . "\n<br>";

		# Generate basic string
		echo "Basic: " . random_str('basic') . "\n<br>";

		# Generate unique string
		echo "Unique: " . random_str('unique') . "\n<br>";

		# Generate md5 string
		echo "Md5: " . random_str('md5') . "\n<br>";

	+======================================================*/
}