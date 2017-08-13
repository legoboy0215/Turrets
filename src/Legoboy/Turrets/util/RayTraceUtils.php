<?php

namespace Legoboy\Turrets\util;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\MovingObjectPosition;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class RayTraceUtils{

	/**
	 * Performs a raycast against all blocks in the world.
	 *
	 * @param Level $level
	 * @param Vector3 $vec31
	 * @param Vector3 $vec32
	 * @param bool $ignoreBlockWithoutBoundingBox
	 * @param bool $returnLastUncollidableBlock
	 * @param bool $exitOnUncollidable
	 * @return MovingObjectPosition|null
	 */
	public static function rayTraceBlocks(Level $level, Vector3 $vec31, Vector3 $vec32, bool $ignoreBlockWithoutBoundingBox, bool $returnLastUncollidableBlock, bool $exitOnUncollidable){
		if(!is_nan($vec31->x) && !is_nan($vec31->y) && !is_nan($vec31->z)){
			if(!is_nan($vec32->x) && !is_nan($vec32->y) && !is_nan($vec32->z)){
				$i = floor($vec32->x);
				$j = floor($vec32->y);
				$k = floor($vec32->z);
				$l = floor($vec31->x);
				$i1 = floor($vec31->y);
				$j1 = floor($vec31->z);

				$blockPos = new Vector3($l, $i1, $j1);
				$block = $level->getBlock($blockPos);

				if((!$ignoreBlockWithoutBoundingBox || $block->getBoundingBox() !== null) && $block->canPassThrough()){
					$traceResult = self::collisionRayTrace($block, $blockPos, $vec31, $vec32);

					if($traceResult !== null){
						return $traceResult;
					}
				}

				$traceResult2 = null;
				$k1 = 200;
				while($k1-- >= 0){
					if(is_nan($vec31->x) || is_nan($vec31->y) || is_nan($vec31->z)){
						return null;
					}
					if($l === $i && $i1 === $j && $j1 === $k){
						return $returnLastUncollidableBlock ? $traceResult2 : null;
					}

					$flag2 = true;
					$flag = true;
					$flag1 = true;
					$d0 = 999.0;
					$d1 = 999.0;
					$d2 = 999.0;

					if($i > $l){
						$d0 = $l + 1.0;
					}else if($i < $l){
						$d0 = $l + 0.0;
					}else{
						$flag2 = false;
					}

					if($j > $i1){
						$d1 = $i1 + 1.0;
					}else if($j < $i1){
						$d1 = $i1 + 0.0;
					}else{
						$flag = false;
					}

					if($k > $j1){
						$d2 = $j1 + 1.0;
					}else if($k < $j1){
						$d2 = $j1 + 0.0;
					}else{
						$flag1 = false;
					}

					$d3 = 999.0;
					$d4 = 999.0;
					$d5 = 999.0;
					$d6 = $vec32->x - $vec31->x;
					$d7 = $vec32->y - $vec31->y;
					$d8 = $vec32->z - $vec31->z;

					if($flag2){
						$d3 = ($d0 - $vec31->x) / $d6;
					}
					if($flag){
						$d4 = ($d1 - $vec31->y) / $d7;
					}
					if($flag1){
						$d5 = ($d2 - $vec31->z) / $d8;
					}
					if($d3 == -0.0){
						$d3 = -1.0E-4;
					}
					if($d4 == -0.0){
						$d4 = -1.0E-4;
					}
					if($d5 == -0.0){
						$d5 = -1.0E-4;
					}
					if($d3 < $d4 && $d3 < $d5){
						$facing = $i > $l ? Vector3::SIDE_WEST : Vector3::SIDE_EAST;
						$vec31 = new Vector3($d0, $vec31->y + $d7 * $d3, $vec31->z + $d8 * $d3);
					}else if($d4 < $d5){
						$facing = $j > $i1 ? Vector3::SIDE_DOWN : Vector3::SIDE_UP;
						$vec31 = new Vector3($vec31->x + $d6 * $d4, $d1, $vec31->z + $d8 * $d4);
					}else{
						$facing = $k > $j1 ? Vector3::SIDE_NORTH : Vector3::SIDE_SOUTH;
						$vec31 = new Vector3($vec31->x + $d6 * $d5, $vec31->y + $d7 * $d5, $d2);
					}

					$l = floor($vec31->x) - ($facing === Vector3::SIDE_EAST ? 1 : 0);
					$i1 = floor($vec31->y) - ($facing === Vector3::SIDE_UP ? 1 : 0);
					$j1 = floor($vec31->z) - ($facing === Vector3::SIDE_SOUTH ? 1 : 0);

					$blockPos = new Vector3($l, $i1, $j1);
					$block1 = $level->getBlock($blockPos);

					if(!$ignoreBlockWithoutBoundingBox || $block1->getId() === Block::PORTAL || $block->getBoundingBox() !== null){
						//var_dump('Block ID: ' . $block1->getId());
						//var_dump('Can pass: ' . ($block1->canPassThrough() ? 'yes' : 'no'));
						if($block1->canPassThrough()){
							$traceResult1 = self::collisionRayTrace($block1, $blockPos, $vec31, $vec32);
							if($traceResult1 !== null){
								return $traceResult1;
							}
						}else{
							$traceResult2 = \Legoboy\Turrets\util\MovingObjectPosition::fromMiss($vec31, $facing, $blockPos); //MISS
							if($exitOnUncollidable){
								return $returnLastUncollidableBlock ? $traceResult2 : null;
							}
						}
					}
				}
				return $returnLastUncollidableBlock ? $traceResult2 : null;
			}else{
				return null;
			}
		}else{
			return null;
		}
	}

	/**
	 * Ray traces through the blocks collision from start vector to end vector returning a ray trace hit.
	 *
	 * @param Block $block
	 * @param Vector3 $pos
	 * @param Vector3 $start
	 * @param Vector3 $end
	 * @return MovingObjectPosition
	 */
	public static function collisionRayTrace(Block $block, Vector3 $pos, Vector3 $start, Vector3 $end){
		if($block->getBoundingBox() === null){
			return null;
		}
		$traced = self::rayTrace($pos, $start, $end, $block->getBoundingBox());
		return $traced;
	}

	public static function rayTrace(Vector3 $pos, Vector3 $start, Vector3 $end, AxisAlignedBB $boundingBox){
		$vec3d = $start->subtract($pos->getX(), $pos->getY(), $pos->getZ());
		$vec3d1 = $end->subtract($pos->getX(), $pos->getY(), $pos->getZ());
		$rayTraceResult = $boundingBox->calculateIntercept($vec3d, $vec3d1);
		return ($rayTraceResult === null ? null : MovingObjectPosition::fromBlock($rayTraceResult->hitVector->getX() + $pos->getX(), $rayTraceResult->hitVector->getY() + $pos->getY(), $rayTraceResult->hitVector->getZ() + $pos->getZ(), $rayTraceResult->sideHit, $pos));
	}
}