<?php

declare(strict_types=1);

namespace Legoboy\Turrets;

class TurretsMessage{

	const TURRET_CREATED = 0;
	const TURRET_DESTROYED = 1;
	const TURRET_UPGRADED = 2;
	const TURRET_CANNOT_BUILD = 3;
	const NO_CREATE_PERM = 4;
	const NO_DESTROY_PERM = 5;

}