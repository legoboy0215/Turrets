<?php

namespace Legoboy\Turrets\util;

use pocketmine\utils\Random;

class RandomUtils{

	public static function randomElement($collection, Random $random){
		$index = $random->nextRange(0, count($collection));
		$i = 0;
		foreach($collection as $element){
			if($i === $index){
				return $element;
			}
			++$i;
		}

		return null;
	}
}
