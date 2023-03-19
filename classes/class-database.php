<?php

namespace Cebola\Classes;

class Database {

	public $is_fresh = true;
	public $conn;

	public function connect() {
		$tries = 10;
		while ( empty( $this->conn ) || $this->conn->connect_errno ) {
			Logger::info( 'Trying to connect to the WordPress database...' );
			$this->try_connection();
			$tries--;
			if ( 0 === $tries ) {
				Logger::error( 'Failed to connect to the WordPress database.' );
			}
			sleep( 3 );
		}
		global $ceboladb;
		$ceboladb = $this;
	}

	private function try_connection() {
		try {
			$this->conn     = \mysqli_connect( '127.0.0.1', CEBOLA_DB_USER, CEBOLA_DB_PASSWORD, CEBOLA_DB_NAME, 3068 );
			$this->is_fresh = $this->is_fresh();
		} catch ( \Exception $e ) {
			Logger::error( 'Error connecting to the database: ' . $e->getMessage() );
			return $e;
		}
	}

	public function query( $sql ) {
		return $this->conn->query( $sql );
	}

	public function is_fresh() {
		return empty( $this->query( 'SHOW TABLES LIKE "cebola_meta";' ) );
	}

	public function install( $plugin_name ) {
		Logger::info( 'Installing the database...' );
		$this->create_tables();

		$stmt = $this->conn->prepare(
			'INSERT INTO cebola_meta(`name`, `value`) VALUES ("plugin", ?);'
		);

		$stmt->bind_param( 's', $plugin_name );
		$stmt->execute();
	}

	private function create_tables() {

		$this->query( 'DROP TABLE IF EXISTS cebola_meta;' );
		$this->query( 'DROP TABLE IF EXISTS cebola_functions;' );
		$this->query( 'DROP TABLE IF EXISTS cebola_reports;' );
		$this->query( 'DROP TABLE IF EXISTS cebola_parameters;' );
		$this->query( 'DROP TABLE IF EXISTS cebola_nonces;' );
		$this->query( 'DROP TABLE IF EXISTS cebola_urls;' );

		$this->query(
			'CREATE TABLE cebola_meta (
				`id` INT NOT NULL AUTO_INCREMENT,
				`name` VARCHAR(255) NOT NULL,
				`value` TEXT NOT NULL,
				PRIMARY KEY (`id`)
			);'
		);
		$this->query(
			'CREATE TABLE cebola_functions (
				`id` INT NOT NULL AUTO_INCREMENT,
				`type` VARCHAR(50) NOT NULL,
				`hook` VARCHAR(255),
				`callback` VARCHAR(255) NOT NULL,
				`priority` INT,
				`arguments` INT,
				`file` VARCHAR(255),
				`line` INT,
				`attention` FLOAT,
				PRIMARY KEY (`id`)
			);'
		);
		$this->query(
			'CREATE TABLE cebola_reports (
				`id` INT NOT NULL AUTO_INCREMENT,
				`data` JSON,
				`payload` VARCHAR(100),
				`severity` ENUM("low", "medium", "high", "critical"),
				`confidence` FLOAT,
				PRIMARY KEY (`id`)
			);'
		);
		$this->query(
			'CREATE TABLE cebola_parameters (
				`id` INT NOT NULL AUTO_INCREMENT,
				`name` VARCHAR(100) UNIQUE,
				PRIMARY KEY (`id`)
			);'
		);
		$this->query(
			'CREATE TABLE cebola_nonces (
				`id` INT NOT NULL AUTO_INCREMENT,
				`action` VARCHAR(200) NOT NULL,
				`value` VARCHAR(200),
				PRIMARY KEY (`id`)
			);'
		);
		$this->query(
			'CREATE TABLE cebola_urls (
				`id` INT NOT NULL AUTO_INCREMENT,
				`url` VARCHAR(500) UNIQUE,
				PRIMARY KEY (`id`)
			);'
		);
	}
}
