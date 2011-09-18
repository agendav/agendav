<?php
$x = new DateTime();
$y = clone $x;
//$y->add(new DateInterval('P8DT10M'));
//$y->add(new DateInterval('PT50M'));
//$y->add(new DateInterval('P3W'));

$y->sub(new DateInterval('PT50M'));
$dif = $x->diff($y);

echo di2duration($dif);

function di2duration($obj) {
	/*
	   dur-value  = (["+"] / "-") "P" (dur-date / dur-time / dur-week)

	   dur-date   = dur-day [dur-time]
	   dur-time   = "T" (dur-hour / dur-minute / dur-second)
	   dur-week   = 1*DIGIT "W"
	   dur-hour   = 1*DIGIT "H" [dur-minute]
	   dur-minute = 1*DIGIT "M" [dur-second]
	   dur-second = 1*DIGIT "S"
	   dur-day    = 1*DIGIT "D"
	 */

	var_dump($obj);

	if ($obj->days === FALSE) {
		// We have a problem
		return FALSE;
	}

	$days = $obj->days;
	$seconds = $obj->s + $obj->i*60 + $obj->h*3600;
	$str = '';

	// Simplest case
	if ($days%7 == 0 && $seconds == 0) {
		$str = ($days/7) . 'W';
	} else {
		$time_units = array(
				'3600' => 'H',
				'60' => 'M',
				'1' => 'S',
				);
		$str_time = '';
		foreach ($time_units as $k => $v) {
			if ($seconds >= $k) {
				$str_time .= floor($seconds/$k) . $v;
				$seconds %= $k;
			}
		}

		// No days
		if ($days == 0) {
			$str = 'T' . $str_time;
		} else {
			$str = $days . 'D' . (empty($str_time) ? '' : 'T' . $str_time);
		}
	}

	return ($obj->invert == '1' ? '-' : '') . 'P' . $str;


}
