<?php

namespace Legoboy\Turrets\entity;

use pocketmine\entity\Vehicle;

class Minecart extends Vehicle{
	const NETWORK_ID = 84;

	const TYPE_NORMAL = 1;
	const TYPE_CHEST = 2;
	const TYPE_HOPPER = 3;
	const TYPE_TNT = 4;

	public $height = 0.7;
	public $width = 0.98;

	protected $drag = 0.0;
	protected $gravity = 0.0;

	private $maxHealth = 1;
	private $health = 1.0;

	public function getName() : string{
		return "Minecart";
	}

	public function getType() : int{
		return self::TYPE_NORMAL;
	}
}
