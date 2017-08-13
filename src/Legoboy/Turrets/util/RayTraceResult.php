<?php

namespace Legoboy\Turrets\util;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class RayTraceResult{

	const TYPE_MISS = 1;
	const TYPE_BLOCK = 2;
	const TYPE_ENTITY = 3;

	/** @var Vector3 */
	private $blockPos;

	/**
	 * The type of hit that occured, see {@link RayTraceResult#Type} for possibilities.
	 */
	public $typeOfHit;
	public $sideHit; //Facing

	/** The vector position of the hit */

	/** @var Vector3D */
	public $hitVec;

	/** The hit entity */

	/** @var Entity */
	public $entityHit;

	public function __construct(){

	}

	public static function createFrom3(Vector3 $hitVecIn, int $sideHitIn, Vector3 $blockPosIn){
		return (new self())->createBlockHit(self::TYPE_BLOCK, $hitVecIn, $sideHitIn, $blockPosIn);
	}

	public static function createFrom2(Vector3 $hitVecIn, int $sideHitIn){
		return (new self())->createBlockHit(self::TYPE_BLOCK, $hitVecIn, $sideHitIn, new Vector3(0, 0, 0));
	}

	public static function createFrom1(Entity $entityIn){
		return (new self())->createEntityHit($entityIn, new Vector3($entityIn->posX, $entityIn->posY, $entityIn->posZ));
	}

	public function createBlockHit(int $typeIn, Vector3 $hitVecIn, int $sideHitIn, Vector3 $blockPosIn){
		$this->typeOfHit = $typeIn;
		$this->blockPos = $blockPosIn;
		$this->sideHit = $sideHitIn;
		$this->hitVec = new Vector3($hitVecIn->xCoord, $hitVecIn->yCoord, $hitVecIn->zCoord);
		return $this;
	}

	public function createEntityHit(Entity $entityHitIn, Vector3 $hitVecIn){
		$this->typeOfHit = self::TYPE_ENTITY;
		$this->entityHit = $entityHitIn;
		$this->hitVec = $hitVecIn;
		return $this;
	}

	public function getBlockPos() : Vector3{
		return $this->blockPos;
	}

	public function __toString(){
		return "HitResult{type=" . $this->typeOfHit . ", blockpos=" . $this->blockPos . ", f=" . $this->sideHit . ", pos=" . $this->hitVec . ", entity=" . $this->entityHit . '}';
	}
}
