<?php

namespace Legoboy\Turrets\targeting;

use pocketmine\entity\Living;

interface TargetAssessor{

	public function assessMob(Living $living) : int;
}
