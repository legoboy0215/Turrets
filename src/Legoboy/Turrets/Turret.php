<?php

namespace Legoboy\Turrets;

use Legoboy\Turrets\entity\EntityTurret;
use Legoboy\Turrets\targeting\TargetAssessor;
use Legoboy\Turrets\upgrade\UpgradeTier;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;

class Turret{

	/** @var Position */
	private $position;

	/** @var string */
	private $ownerName;

	/** @var TurretsPlugin */
	private $plugin;

	/** @var int */
	private $entityId = -1;

	/** @var UpgradeTier */
	private $upgradeTier;

	public function __construct(Position $position, $ownerName, TurretsPlugin $plugin, bool $createEntity = false){
		$this->position = $position;
		$this->ownerName = $ownerName;
		$this->plugin = $plugin;

		if($createEntity){
			$entity = Entity::createEntity('EntityTurret', $position->getLevel(), $this->generateNBT());
			if($entity instanceof EntityTurret){
				$this->setEntity($entity->getId());
				$entity->spawnToAll();
			}else{
				throw new \RuntimeException('Entity EntityTurret could not be created!');
			}
		}
		$this->initializeUpgradeTier();
	}

	public function getLevel(){
		return $this->position->getLevel();
	}

	public function getPosition() : Position{
		return $this->position;
	}

	public function getOwnerName() : string{
		return $this->ownerName;
	}

	public function createEntity(){
		$entity = Entity::createEntity('EntityTurret', $this->getLevel(), $this->generateNBT());
		if($entity instanceof EntityTurret){
			$this->setEntity($entity->getId());
			$entity->setTurret($this);
			$entity->spawnToAll();
		}else{
			throw new \RuntimeException('Entity EntityTurret could not be created!');
		}
	}

	public function setEntity(int $entityId){
		$this->entityId = $entityId;

		$this->initializeUpgradeTier();
	}

	/**
	 * @return EntityTurret|null
	 */
	public function getEntity(){
		$entity = $this->plugin->getServer()->findEntity($this->entityId);
		if($entity instanceof EntityTurret){
			return $entity;
		}
		return null;
	}

	public function getUpgradeTier() : UpgradeTier{
		return $this->upgradeTier;
	}

	/**
	 * @return TargetAssessor[]
	 */
	public function getTargetAssessors() : array{
		return $this->plugin->getTargetAssessors();
	}

	public function spawn(){
		if(!(($entity = $this->getEntity()) instanceof EntityTurret)){
			return false;
		}
		$entity->spawnToAll();
		return true;
	}

	public function despawn(){
		if(!($this->getEntity() instanceof EntityTurret)){
			return false;
		}
		$this->getEntity()->close();
		return true;
	}

	public function remove(bool $despawn = true){
		$this->plugin->removeTurret($this, $despawn);
	}

	public function updateUpgradeTier(int $blockId = -1) : UpgradeTier{
		if ($blockId === -1) {
			$blockId = $this->getLevel()->getBlock($this->getPosition()->subtract(0, 1, 0), false)->getId();
		}
		$this->upgradeTier = $this->getUpgradeTierById($blockId);
		return $this->upgradeTier;
	}

	public function getUpgradeTierById(int $blockId) : UpgradeTier{
		return $this->plugin->getUpgradeLadder()->getUpgradeTier($blockId);
	}

	private function initializeUpgradeTier(){
		if($this->updateUpgradeTier() === null){
			throw new \RuntimeException("Update tier could not be initialized!");
		}
	}

	public function equals($object) : bool{
		if($object === null)
			return false;
		if($object === $this)
			return true;
		if(!($object instanceof Turret)){
			return false;
		}
		return $object->getPosition()->equals($this->position);
	}

	public static function hash(Vector3 $vector) : string{
		return (string) Level::blockHash($vector->getX(), $vector->getY(), $vector->getZ());
	}

	public function getHash() : string{
		return self::hash($this->position);
	}

	public function generateNBT(){
		$nbt = Entity::createBaseNBT(
			$this->getPosition()->add(0, 0.45, 0)
		);
		$nbt->setString("Hash", $this->getHash());
		$nbt->setTag(new ListTag("Pivot", [
			new DoubleTag("", $this->position->getX() + 0.5),
			new DoubleTag("", $this->position->getY() + 1.3),
			new DoubleTag("", $this->position->getZ() + 0.5),
		]));
		return $nbt;
	}
}
