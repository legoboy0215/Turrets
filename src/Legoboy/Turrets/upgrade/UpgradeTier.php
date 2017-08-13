<?php

namespace Legoboy\Turrets\upgrade;

class UpgradeTier{

	/** @var int */
	private $firingInterval;

	/** @var float */
	private $range;

	/** @var float */
	private $rangeSquared;

	/** @var float */
	private $accuracy;

	public function __construct(int $firingInterval, float $range, float $accuracy){
		$this->firingInterval = $firingInterval;
		$this->range = $range;
		$this->rangeSquared = $range * $range;
		$this->accuracy = $accuracy;
	}

	public function getFiringInterval() : int{
		return $this->firingInterval;
	}

	public function getRange() : float{
		return $this->range;
	}

	public function getRangeSquared() : float{
		return $this->rangeSquared;
	}

	public function getAccuracy() : float{
		return $this->accuracy;
	}
}
