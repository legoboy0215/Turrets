<?php

namespace Legoboy\Turrets\persistence;

interface TurretDatabase{

  public function loadTurrets();
  
  public function saveTurrets(array $paramCollection);
}
