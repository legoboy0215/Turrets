<?php

namespace Legoboy\Turrets\util;

use pocketmine\math\Vector3;

class ProjectileUtils{

	public static function getLookAtYaw(Vector3 $motion) : float{
		$dx = $motion->getX();
		$dz = $motion->getZ();
		$yaw = 0;
		// Set yaw
		if($dx !== 0){
			// Set yaw start value based on dx
			if($dx < 0){
				$yaw = 1.5 * M_PI;
			}else{
				$yaw = 0.5 * M_PI;
			}
			$yaw -= atan($dz / $dx);
		}else if($dz < 0){
			$yaw = M_PI;
		}
		return (float) (-$yaw * 180 / M_PI - 90);
	}

	public static function getLookAtYaw2(Vector3 $motion) : float{
		$dx = $motion->getX();
		$dz = $motion->getZ();
		$yaw = 0;
		// Set yaw
		if($dx !== 0){
			// Set yaw start value based on dx
			if($dx < 0){
				$yaw = 270;
			}else{
				$yaw = 90;
			}
			$yaw -= atan($dz / $dx);
		}else if($dz < 0){
			$yaw = 180;
		}
		return -$yaw - 90;
	}

	public static function getLookAtPitch(Vector3 $motion){
		return -atan($motion->getY() / self::length([$motion->getX(), $motion->getZ()]));
	}

	public static function lengthSquared(array $values) : float{
		$rval = 0;
		foreach($values as $value){
			$rval += $value * $value;
		}
		return $rval;
	}

	public static function length(array $values) : float{
		return sqrt(self::lengthSquared($values));
	}
}
