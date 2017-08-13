<?php

namespace Legoboy\Turrets\entity;

use pocketmine\entity\projectile\Arrow;
use pocketmine\math\Vector3;

class TurretProjectile extends Arrow{

	protected $gravity = 0;
	protected $drag = 0;

	protected $damage = 2;

	public function setThrowableHeading(float $x, float $y, float $z, float $velocity){
		$f = sqrt($x * $x + $y * $y + $z * $z);
		$x = $x / $f;
		$y = $y / $f;
		$z = $z / $f;
		$x = $x * $velocity;
		$y = $y * $velocity;
		$z = $z * $velocity;
		$this->motionX = $x;
		$this->motionY = $y;
		$this->motionZ = $z;

		$f1 = sqrt($x * $x + $z * $z);
		$this->yaw = (float) (atan2($x, $z) * (180 / M_PI));
		$this->pitch = (float) (atan2($y, $f1) * (180 / M_PI));

		$this->lastYaw = $this->yaw;
		$this->lastPitch = $this->pitch;
	}

	public function target(Vector3 $origin, Vector3 $target, float $velocity = 1.6){
		/*$deltaX = $target->x - $origin->x;
		$deltaY = $target->y - $origin->y;
		$deltaZ = $target->z - $origin->z;

		$length = sqrt($deltaX ** 2 + $deltaY ** 2 + $deltaZ ** 2);

		$unitX = $deltaX / $length;
		$unitY = $deltaY / $length;
		$unitZ = $deltaZ / $length;*/

		$dirVector = $target->subtract($origin);

		$unit = $dirVector->normalize(); // Unit vector of the velocity

		$yaw = atan2($unit->z, $unit->x); // https://stackoverflow.com/a/12011762/5716711  atan2 = from all 4 quadrants
		$pitch = asin($unit->y);

		//to degree
		$yaw = $yaw * 180.0 / M_PI;
		$pitch = $pitch * 180.0 / M_PI;

		$yaw += 90;

		$this->motionX = $unit->x * $velocity;
		$this->motionY = $unit->y * $velocity;
		$this->motionZ = $unit->z * $velocity;

		$this->yaw = (float) $yaw;
		$this->pitch = (float) $pitch;

		$this->lastMotionX = $this->motionX;
		$this->lastMotionY = $this->motionY;
		$this->lastMotionZ = $this->motionZ;
		$this->lastYaw = $this->yaw;
		$this->lastPitch = $this->pitch;
	}

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);

		/*if($this->onGround){
			$this->close();
			$hasUpdate = true;
		}*/

		if($this->age > 1200){
			$this->close();
			$hasUpdate = true;
		}

		$this->timings->stopTiming();

		return $hasUpdate;
	}
}