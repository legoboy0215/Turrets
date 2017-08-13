<?php

namespace Legoboy\Turrets\util;

class DoubleUtils{
	
	public static function compare($double1, $double2){
		return bccomp(number_format($double1, 5, '.', ''), number_format($double2, 5, '.', ''), 5);
	}
}
