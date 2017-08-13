<?php

namespace Legoboy\Turrets\targeting;

use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\Monster;

class MobAssessor implements TargetAssessor{

	public function __construct(){

	}

	public function assessMob(Living $living) : int{
		if($living instanceof Monster || $living instanceof Human){
			return TargetAssessment::HOSTILE;
		}
		return TargetAssessment::MEH;
	}
}
