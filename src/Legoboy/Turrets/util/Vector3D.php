<?php

namespace Legoboy\Turrets\util;

use pocketmine\math\Vector3;

class Vector3D{

	public static $zero = null;

	/** X coordinate of Vec3D */
	public $xCoord;

	/** Y coordinate of Vec3D */
	public $yCoord;

	/** Z coordinate of Vec3D */
	public $zCoord;

	public function __construct(double $x, double $y, double $z){
		if(self::$zero === null){
			self::$zero = new self(0, 0, 0);
		}
		if($x === -0.0){
			$x = 0.0;
		}

		if($y === -0.0){
			$y = 0.0;
		}

		if($z === -0.0){
			$z = 0.0;
		}

		$this->xCoord = $x;
		$this->yCoord = $y;
		$this->zCoord = $z;
	}

	public static function createFromVector(Vector3 $vector3){
		return new self($vector3->getX(), $vector3->getY(), $vector3->getZ());
	}

	/**
	 * Returns a new vector with the result of the specified vector minus this.
	 *
	 * @param Vector3D $vec
	 * @return Vector3D
	 */
	public function subtractReverse(Vector3D $vec) : Vector3D{
		return new self($vec->xCoord - $this->xCoord, $vec->yCoord - $this->yCoord, $vec->zCoord - $this->zCoord);
	}

	/**
	 * Normalizes the vector to a length of 1 (except if it is the zero vector)
	 */
	public function normalize() : Vector3D{
		$d0 = sqrt($this->xCoord * $this->xCoord + $this->yCoord * $this->yCoord + $this->zCoord * $this->zCoord);
		return $d0 < 1.0E-4 ? self::$zero : new self($this->xCoord / $d0, $this->yCoord / $d0, $this->zCoord / $d0);
	}

	public function dotProduct(Vector3D $vec) : float{
		return $this->xCoord * $vec->xCoord + $this->yCoord * $vec->yCoord + $this->zCoord * $vec->zCoord;
	}

	public function subtract(Vector3D $vec) : Vector3D{
		return $this->subtractVector($vec->xCoord, $vec->yCoord, $vec->zCoord);
	}

	public function subtractVector(double $x, double $y, double $z) : Vector3D{
		return $this->addVector(-$x, -$y, -$z);
	}

	public function add(Vector3D $vec) : Vector3D{
		return $this->addVector($vec->xCoord, $vec->yCoord, $vec->zCoord);
	}

	/**
	 * Adds the specified x,y,z vector components to this vector and returns the resulting vector. Does not change this
	 * vector.
	 *
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 * @return Vector3D
	 */
	public function addVector(float $x, float $y, float $z){
		return new Vector3D($this->xCoord + $x, $this->yCoord + $y, $this->zCoord + $z);
	}

	/**
	 * Euclidean distance between this and the specified vector, returned as double.
	 *
	 * @param Vector3D $vec
	 * @return float
	 */
	public function distanceTo(Vector3D $vec){
		return sqrt($this->squareDistanceTo($vec));
	}

	/**
	 * The square of the Euclidean distance between this and the specified vector.
	 *
	 * @param Vector3D $vec
	 * @return float
	 */
	public function squareDistanceTo(Vector3D $vec){
		return $this->squareDistanceToVector($vec->xCoord, $vec->yCoord, $vec->zCoord);
	}

	public function squareDistanceToVector(double $xIn, double $yIn, double $zIn){
		$d0 = $xIn - $this->xCoord;
		$d1 = $yIn - $this->yCoord;
		$d2 = $zIn - $this->zCoord;
		return $d0 * $d0 + $d1 * $d1 + $d2 * $d2;
	}

	public function scale(double $scale) : Vector3D{
		return new self($this->xCoord * $scale, $this->yCoord * $scale, $this->zCoord * $scale);
	}

	/**
	 * Returns the length of the vector.
	 */
	public function lengthVector() : float{
		return sqrt($this->xCoord * $this->xCoord + $this->yCoord * $this->yCoord + $this->zCoord * $this->zCoord);
	}


	/**
	 * Returns a new vector with x value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 *
	 * @param Vector3D $vec
	 * @param float $x
	 * @return Vector3D|null
	 */
	public function getIntermediateWithXValue(Vector3D $vec, float $x){
		$d0 = $vec->xCoord - $this->xCoord;
		$d1 = $vec->yCoord - $this->yCoord;
		$d2 = $vec->zCoord - $this->zCoord;

		if($d0 * $d0 < 1.0000000116860974E-7){
			return null;
		}else{
			$d3 = ($x - $this->xCoord) / $d0;
			return $d3 >= 0.0 && $d3 <= 1.0 ? new self($this->xCoord + $d0 * $d3, $this->yCoord + $d1 * $d3, $this->zCoord + $d2 * $d3) : null;
		}
	}

	/**
	 * Returns a new vector with y value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 *
	 * @param Vector3D $vec
	 * @param float $y
	 * @return Vector3D|null
	 */
	public function getIntermediateWithYValue(Vector3D $vec, float $y){
		$d0 = $vec->xCoord - $this->xCoord;
		$d1 = $vec->yCoord - $this->yCoord;
		$d2 = $vec->zCoord - $this->zCoord;

		if($d1 * $d1 < 1.0000000116860974E-7){
			return null;
		}else{
			$d3 = ($y - $this->yCoord) / $d1;
			return $d3 >= 0.0 && $d3 <= 1.0 ? new self($this->xCoord + $d0 * $d3, $this->yCoord + $d1 * $d3, $this->zCoord + $d2 * $d3) : null;
		}
	}

	/**
	 * Returns a new vector with z value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 *
	 * @param Vector3D $vec
	 * @param float $z
	 * @return Vector3D|null
	 */
	public function getIntermediateWithZValue(Vector3D $vec, float $z){
		$d0 = $vec->xCoord - $this->xCoord;
		$d1 = $vec->yCoord - $this->yCoord;
		$d2 = $vec->zCoord - $this->zCoord;

		if($d2 * $d2 < 1.0000000116860974E-7){
			return null;
		}else{
			$d3 = ($z - $this->zCoord) / $d2;
			return $d3 >= 0.0 && $d3 <= 1.0 ? new self($this->xCoord + $d0 * $d3, $this->yCoord + $d1 * $d3, $this->zCoord + $d2 * $d3) : null;
		}
	}

	public function equals($object){
		if($this === $object){
			return true;
		}else if(!($object instanceof Vector3D)){
			return false;
		}else{
			$vec3d = $object;

			if(DoubleUtils::compare($vec3d->xCoord, $this->xCoord) != 0){
				return false;
			}else if(DoubleUtils::compare($vec3d->yCoord, $this->yCoord) != 0){
				return false;
			}else{
				return DoubleUtils::compare($vec3d->zCoord, $this->zCoord) === 0;
			}
		}
	}

	public function __toString(){
		return "(" . $this->xCoord . ", " . $this->yCoord . ", " . $this->zCoord . ")";
	}

	public function rotatePitch(float $pitch) : Vector3D{
		$f = cos($pitch);
		$f1 = sin($pitch);
		$d0 = $this->xCoord;
		$d1 = $this->yCoord * $f + $this->zCoord * $f1;
		$d2 = $this->zCoord * $f - $this->yCoord * $f1;
		return new self($d0, $d1, $d2);
	}

	public function rotateYaw(float $yaw) : Vector3D{
		$f = cos($yaw);
		$f1 = sin($yaw);
		$d0 = $this->xCoord * $f + $this->zCoord * $f1;
		$d1 = $this->yCoord;
		$d2 = $this->zCoord * $f - $this->xCoord * $f1;
		return new self($d0, $d1, $d2);
	}
}
