<?php

namespace Legoboy\Turrets\entity;

use Legoboy\Turrets\targeting\TargetAssessment;
use Legoboy\Turrets\Turret;
use Legoboy\Turrets\TurretsPlugin;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

class EntityTurret extends Minecart {

	const REBOUND = 0.1;
	const ITEM_SPAWN_DISTANCE = 1.2;

	const TASK_RUN_INTERVAL = 5;

	/** @var Turret */
	private $turret;

	/** @var Vector3 */
	private $pivot = null;

	/** @var int */
	private $hash = null;

	/** @var Living|null */
	private $target;

	/** @var int */
	private $firingCooldown = 0;

	/** @var int */
	private $targetSearchCooldown = 0;

	/** @var int */
	private $targetSearchInterval = 40;

	/** @var int */
	private $tickSum = 0;

	protected function initEntity(CompoundTag $nbt) : void {
		parent::initEntity($nbt);
		if(!$nbt->hasTag("Pivot", ListTag::class) || !$nbt->hasTag("Hash", StringTag::class)){
			throw new \InvalidArgumentException('NBT tag is invalid!');
		}
		$this->hash = (string) $nbt->getString("Hash");

		$pivot = $nbt->getListTag("Pivot");
		$this->pivot = new Vector3(...$pivot->getAllValues());

		$this->setPosition($this->pivot);

		$plugin = TurretsPlugin::getInstance();
		if(($turret = $plugin->getTurretFromHash($this->hash)) !== null){
			$this->setTurret($turret);
			if($turret->getEntity() === null){
				$turret->setEntity($this->getId());
			}
		}
		$this->spawnToAll();
	}

	public function setTurret(Turret $turret){
		$this->turret = $turret;
	}

	public function getTurret(){
		return $this->turret;
	}

	protected function setYawAndPitch(float $yaw, float $pitch){
		$this->yaw = $yaw % 360;
		$this->pitch = $pitch % 360;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		$this->tickSum += $tickDiff;
		if($this->tickSum < self::TASK_RUN_INTERVAL){
			return true;
		}
		$this->tickSum = 0;

		if($this->getTurret() === null){
			return true;
		}

		if($this->y < -64.0){
			$this->kill();
		}
		$this->motion = $this->pivot->subtract($this)->multiply(0.1);

		$this->move($this->motion->x, $this->motion->y, $this->motion->z);

		$upgradeTier = $this->getTurret()->getUpgradeTier();

		$range = $upgradeTier->getRange();
		//$accuracy = $upgradeTier->getAccuracy();

		$target = null;
		if(($this->target === null) && ($this->targetSearchCooldown === 0)){
			$foundTarget = $this->findTarget($range);

			if($foundTarget !== null){
				$target = $foundTarget;
			}else{
				$this->targetSearchCooldown = $this->targetSearchInterval;
			}
		}

		if($this->targetSearchCooldown > 0){
			$this->targetSearchCooldown -= self::TASK_RUN_INTERVAL;
		}

		$lockedOn = false;
		if($target instanceof Entity){
			if($this->canSee($target)){
				if($target->isAlive()){
					$targetPos = $target->add(0, $target->getEyeHeight(), 0);
					$distanceSquared = $this->pivot->distanceSquared($targetPos);
					if($distanceSquared <= $range * $range){
						$this->lookAt($targetPos);
						$lockedOn = true;
					}else{
						$target = null;
					}
				}else{
					$target = null;
				}
			}else{
				$target = null;
			}
		}

		$this->setYawAndPitch($this->yaw, $this->pitch);
		$this->updateMovement();

		if($lockedOn && ($this->firingCooldown === 0)){
			//fireItemStack(accuracy);
			//if($target instanceof Player) $target->sendPopup('You have been shot!');
			$this->fireArrow($target);
			$this->firingCooldown = $upgradeTier->getFiringInterval();
		}

		if($this->firingCooldown > 0){
			$this->firingCooldown -= self::TASK_RUN_INTERVAL;
		}
		return $hasUpdate;
	}

	public function lookAt(Vector3 $targetPos){
		$dx = $targetPos->x - $this->x;
		$dy = $targetPos->y - $this->y;
		$dz = $targetPos->z - $this->z;
		$dh = sqrt($dx * $dx + $dz * $dz);

		$yaw = (float) (atan2($dz, $dx) * 180.0 / M_PI);
		$pitch = (float) (tan($dy / $dh) * 180.0 / M_PI);

		$this->yaw = $yaw;
		$this->pitch = $pitch;
	}

	public function findTarget(float $range){
		$nmsEntities = $this->level->getNearbyEntities($this->boundingBox->expandedCopy($range, $range, $range), $this);
		$targets = [];
		foreach($nmsEntities as $nmsEntity){
			if($nmsEntity->getId() !== $this->getId()){
				if($this->distanceSquared($nmsEntity) <= $range * $range){
					$entity = $nmsEntity;
					if($entity instanceof Living){
						$targets[] = $entity;
					}
				}
			}
		}
		if(empty($targets)){
			return null;
		}

		$this->filterTargets($targets);

		while(!empty($targets)){
			$key = array_rand($targets);
			$possibleTarget = $targets[$key];
			if($possibleTarget === null){
				continue;
			}
			if($this->canSee($possibleTarget)){
				return $possibleTarget;
			}
			unset($targets[$key]);
		}
		return null;
	}

	private function filterTargets(array &$targets){
		foreach($targets as $key => $mob){
			$assessment = $this->assessTarget($mob);
			if($assessment !== TargetAssessment::HOSTILE){
				unset($targets[$key]);
			}
		}
	}

	private function assessTarget(Living $mob) : int{
		$overallAssessment = TargetAssessment::MEH;
		foreach($this->turret->getTargetAssessors() as $assessor){
			$assessment = $assessor->assessMob($mob);
			if($assessment !== TargetAssessment::MEH){
				$overallAssessment = $assessment;
			}
		}
		return $overallAssessment;
	}

	private function canSee(Entity $entity) : bool{
		$startTime = microtime(true);

		$result = true;

		$start = $this->add(0, 0.595, 0);
		$target = $entity->add(0, $entity->getEyeHeight(), 0);
		foreach(VoxelRayTrace::betweenPoints($start, $target) as $vector) {
			$block = $this->level->getBlockAt($vector->x, $vector->y, $vector->z);
			if ($block->getId() != 0) {
				$result = false;
				break;
			}
		}
		//var_dump('Ray trace: ' . number_format((microtime(true) - $startTime) * 1000, 6) . ' msec');
		return $result;
	}

	public function fireArrow(Living $target){
		$nbt = Entity::createBaseNBT(
			$this->add(0, 1, 0)

		);
		$nbt->setShort("Fire", 0);

		$arrow = Entity::createEntity('TurretProjectile', $this->getLevel(), $nbt, $this, true);
		if($arrow instanceof TurretProjectile){
			$arrow->target($arrow->add(0, 1, 0), $target->add(0, $target->getEyeHeight(), 0), 5);
			$arrow->spawnToAll();
		}
		return true;
	}

	public function getHash() : string {
		return $this->hash;
	}

	public function getPivot() : Vector3 {
		return $this->pivot;
	}

	public function saveNBT() : CompoundTag {
		$nbt = parent::saveNBT();
		$nbt->setString("Hash", (string) $this->hash, true);
		$nbt->setTag(new ListTag("Pivot", [
			new DoubleTag("", $this->pivot->x),
			new DoubleTag("", $this->pivot->y),
			new DoubleTag("", $this->pivot->z),
		]), true);
		return $nbt;
	}
}
