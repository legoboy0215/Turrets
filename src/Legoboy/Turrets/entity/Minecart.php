<?php

namespace Legoboy\Turrets\entity;

use pocketmine\entity\Vehicle;

use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class Minecart extends Vehicle{
	const NETWORK_ID = 84;

	const TYPE_NORMAL = 1;
	const TYPE_CHEST = 2;
	const TYPE_HOPPER = 3;
	const TYPE_TNT = 4;

	public $height = 0.7;
	public $width = 0.98;

	public $drag = 0.1;
	public $gravity = 0.5;

	public function initEntity(){
		$this->setMaxHealth(1);
		$this->setHealth($this->getMaxHealth());
		parent::initEntity();
	}

	public function getName() : string{
		return "Minecart";
	}

	public function getType() : int{
		return self::TYPE_NORMAL;
	}

	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->type = Minecart::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}
}
