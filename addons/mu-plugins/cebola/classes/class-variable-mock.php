<?php

namespace Cebola\Classes;

class VariableMock implements \ArrayAccess {
	private $container = array();
	
	public function __construct( $container ) {
		$this->container = $container;
	}

	public function offsetSet( $offset, $value ) {
		echo $offset;
		echo "\n";
		if ( is_null( $offset ) ) {
			$this->container[] = $value;
		} else {
			$this->container[ $offset ] = $value;
		}
	}

	public function offsetExists( $offset ) {
		echo $offset;
		echo "\n";
		return isset( $this->container[ $offset ] );
	}

	public function offsetUnset( $offset ) {
		echo $offset;
		echo "\n";
		unset( $this->container[ $offset ] );
	}

	public function offsetGet( $offset ) {
		return '__CEBOLA__"`';
		echo $offset;
		echo "\n";
		return isset( $this->container[ $offset ] ) ? $this->container[ $offset ] : null;
	}
}
