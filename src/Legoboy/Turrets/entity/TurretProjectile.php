<?php

namespace Legoboy\Turrets\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class TurretProjectile extends Arrow{

	protected $gravity = 0.0;
	protected $drag = 0.0;

	protected $damage = 10.0;

	public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = null, bool $critical = false){
		parent::__construct($level, $nbt, $shootingEntity, $critical);
		$this->setPickupMode(self::PICKUP_NONE);
		$this->setPunchKnockback(0);
		$this->setBaseDamage($this->damage);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->isCollided && $this->collideTicks > 100){
			$this->flagForDespawn();
			$hasUpdate = true;
		}
		return $hasUpdate;
	}

	public function target(Vector3 $origin, Vector3 $target, float $velocity = 1.6){
		$dirVector = $target->subtract($origin);

		$unit = $dirVector->normalize(); // Unit vector of the direction

		$yaw = atan2($unit->z, $unit->x); // https://stackoverflow.com/a/12011762/5716711  atan2 = from all 4 quadrants
		$pitch = asin($unit->y);

		//to degree
		$yaw = $yaw * 180.0 / M_PI;
		$pitch = $pitch * 180.0 / M_PI;

		$yaw += 90;

		$this->motion = $unit->multiply($velocity);

		$this->yaw = (float) $yaw;
		$this->pitch = (float) $pitch;

		$this->lastMotion = $this->motion->asVector3();
	}
}