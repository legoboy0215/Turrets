<?php

namespace Legoboy\Turrets;

use Legoboy\Turrets\entity\EntityTurret;
use Legoboy\Turrets\targeting\TargetAssessor;
use Legoboy\Turrets\upgrade\UpgradeTier;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

class Turret{

	/** @var Position */
	private $location;

	/** @var string */
	private $ownerName;

	/** @var TurretsPlugin */
	private $plugin;

	/** @var int */
	private $entityId = -1;

	/** @var UpgradeTier */
	private $upgradeTier;

	public function __construct(Position $location, $ownerName, TurretsPlugin $plugin, bool $createEntity = false){
		$this->location = $location;
		$this->ownerName = $ownerName;
		$this->plugin = $plugin;

		if($createEntity){
			$entity = Entity::createEntity('EntityTurret', $location->getLevel(), $this->generateNBT());
			if($entity instanceof EntityTurret){
				$this->setEntity($entity->getId());
				$entity->spawnToAll();
			}else{
				throw new \RuntimeException('Entity EntityTurret could not be created!');
			}
		}
		$this->initializeUpgradeTier();
	}

	public function getX(){
		return $this->location->getX();
	}

	public function getY(){
		return $this->location->getY();
	}

	public function getZ(){
		return $this->location->getZ();
	}

	public function getLevel(){
		return $this->location->getLevel();
	}

	public function getLocation() : Position{
		return $this->location;
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
		if(!($this->getEntity() instanceof EntityTurret)){
			return false;
		}
		$this->getEntity()->spawnToAll();
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

	public function updateUpgradeTier() : UpgradeTier{
		$baseBlock = $this->location->getLevel()->getBlock($this->location->subtract(0, 1, 0), false);
		$this->upgradeTier = $this->getUpgradeTierById($baseBlock->getId());
		return $this->upgradeTier;
	}

	public function getUpgradeTierById(int $id) : UpgradeTier{
		return $this->plugin->getUpgradeLadder()->getUpgradeTier($id);
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
		return $object->getLocation()->equals($this->location);
	}

	public function getHash() : string{
		return (string) Level::blockHash($this->location->getX(), $this->location->getY(), $this->location->getZ());
	}

	public function generateNBT(){
		return new CompoundTag("", [
				new ListTag("Pos", [
					new DoubleTag("", $this->location->getX()),
					new DoubleTag("", $this->location->getY() + 0.45),
					new DoubleTag("", $this->location->getZ())
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
				new StringTag("Hash", $this->getHash()),
				new ListTag("Pivot", [
					new DoubleTag("", $this->getX() + 0.5),
					new DoubleTag("", $this->getY() + 1.3),
					new DoubleTag("", $this->getZ() + 0.5),
				])
			]
		);
	}
}
