<?php

namespace Legoboy\Turrets\util;

use pocketmine\math\Vector3;

class MovingObjectPosition extends \pocketmine\level\MovingObjectPosition{

	const TYPE_BLOCK = 0;
	const TYPE_ENTITY = 1;
	const TYPE_MISS = 2;

	public static function fromMiss(Vector3 $hitVectorIn, int $sideHitIn, Vector3 $blockPosIn){
		$ob = new \pocketmine\level\MovingObjectPosition();
		$ob->typeOfHit = self::TYPE_MISS;
		$ob->blockX = $blockPosIn->x;
		$ob->blockY = $blockPosIn->y;
		$ob->blockZ = $blockPosIn->z;
		$ob->sideHit = $sideHitIn;
		$ob->hitVector = clone $hitVectorIn;
		return $ob;
	}
}