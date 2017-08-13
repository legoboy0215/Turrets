<?php

namespace Legoboy\Turrets\entity;

use Legoboy\Turrets\targeting\TargetAssessment;
use Legoboy\Turrets\Turret;
use Legoboy\Turrets\TurretsPlugin;
use Legoboy\Turrets\util\RayTraceUtils;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;

class EntityTurret extends Minecart{

	const REBOUND = 0.1;
	const ITEM_SPAWN_DISTANCE = 1.2;

	const TASK_RUN_INTERVAL = 5;

	/** @var Turret */
	private $turret;

	/** @var float */
	private $pivotX;

	/** @var float */
	private $pivotY;

	/** @var float */
	private $pivotZ;

	/** @var Vector3 */
	private $pivotVector = null;

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
	private $lastTick = -1;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		if(!$nbt->offsetExists('Pivot') || !$nbt->offsetExists('Hash')){
			throw new \InvalidArgumentException('NBT tag is invalid!');
		}
		$this->hash = (string) $nbt->offsetGet('Hash');

		$this->pivotX = $nbt['Pivot'][0];
		$this->pivotY = $nbt['Pivot'][1];
		$this->pivotZ = $nbt['Pivot'][2];
		$this->pivotVector = new Vector3($this->pivotX, $this->pivotY, $this->pivotZ);

		$this->setPosition($this->pivotVector);

		echo "Entity turret constructor...\n";

		$plugin = TurretsPlugin::getInstance();
		if(($turret = $plugin->getTurretFromHash($this->hash)) !== null){
			$this->setTurret($turret);
			if($turret->getEntity() === null){
				echo "Setting entity id...\n";
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

	public function onUpdate($currentTick){
		/*if ($this->getRollingAmplitude() > 0) {
			$this->setRollingAmplitude($this->getRollingAmplitude() - 1); // Decompiled server code with MCP
		}

		if ($this->getDamage() > 0) {
		  $this->setDamage($this->getDamage() - 1);
		}*/
		if($currentTick - $this->lastTick < self::TASK_RUN_INTERVAL){
			return true;
		}
		$this->lastTick = $currentTick;

		if($this->getTurret() === null){
			return true;
		}

		if($this->y < -64.0){
			$this->kill();
		}

		$hasUpdate = parent::onUpdate($currentTick);

		$this->lastX = $this->x;
		$this->lastY = $this->y;
		$this->lastZ = $this->z;

		$this->motionX = (($this->pivotX - $this->x) * 0.1);
		$this->motionY = (($this->pivotY - $this->y) * 0.1);
		$this->motionZ = (($this->pivotZ - $this->z) * 0.1);
		$this->move($this->motionX, $this->motionY, $this->motionZ);

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
					$distanceSquared = $this->pivotVector->distanceSquared($targetPos);
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
		$nmsEntities = $this->level->getNearbyEntities($this->boundingBox->grow($range, $range, $range), $this);
		$targets = [];
		foreach($nmsEntities as $nmsEntity){
			if($nmsEntity !== $this){
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

	private function canSee(Entity $nmsEntity) : bool{
		$start = microtime(true);
		$result = RayTraceUtils::rayTraceBlocks($this->level, $this->add(0, 0.595, 0), $nmsEntity->add(0, $nmsEntity->getEyeHeight(), 0), false, true, true) === null;
		var_dump('Ray trace: ' . number_format((microtime(true) - $start) * 1000, 6) . ' millisecs');
		return $result;
	}

	public function fireArrow(Living $target){
        /*$d0 = $target->x - $this->x;
        $d1 = $target->getBoundingBox()->minY + ($target->getEyeHeight() / 3.0)$target->y - $this->y;
        $d2 = $target->z - $this->z;
        $d3 = sqrt($d0 * $d0 + $d2 * $d2);*/

		$nbt = new CompoundTag("", [
			new ListTag("Pos", [
				new DoubleTag("", $this->x),
				new DoubleTag("", $this->y + 1),
				new DoubleTag("", $this->z)
			]),
			new ListTag("Motion", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
			]),
			new ListTag("Rotation", [
				new FloatTag("", 0),
				new FloatTag("", 0)
			]),
			new ShortTag("Fire", 0)
		]);
		$arrow = Entity::createEntity('TurretProjectile', $this->getLevel(), $nbt, $this, true);
		if($arrow instanceof TurretProjectile){
			$arrow->target($arrow->add(0, 1, 0), $target->add(0, $target->getEyeHeight(), 0), 5);
			$arrow->spawnToAll();
			//$arrow->setThrowableHeading($d0, $d1 + $d3 * 0.20000000298023224, $d2, 1.6);
			//$arrow->setMotion($arrow->getMotion()->multiply(3));
		}
		return true;
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->Hash = new StringTag('Hash', (string) $this->hash);

		$this->namedtag->pivotX = new DoubleTag("pivotX", $this->pivotX);
		$this->namedtag->pivotY = new DoubleTag("pivotY", $this->pivotY);
		$this->namedtag->pivotZ = new DoubleTag("pivotZ", $this->pivotZ);
	}
}
