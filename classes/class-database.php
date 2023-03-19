<?php

namespace Cebola\Classes;

class Database {

	public $is_fresh = true;
	public $conn;

	public function install( $plugin_name ) {
		Logger::info( 'Installing the database...' );
		$this->create_database();
		$this->create_tables();
	}

	private function connect() {
		$dbhost = 'localhost:3309';
		$dbuser = 'root';
		$dbpass = 'password';
		$dbname = CEBOLA_DB_NAME;

		$conn = \mysqli_connect( $dbhost, $dbuser, $dbpass, $dbname );

		if ( ! $conn ) {
			Logger::error( 'Could not connect: ' . \mysqli_error() );
		}

	}

	private function disconnect() {
		\mysqli_close( $conn );
	}

	private function create_database() {

		$dbhost = 'localhost:3309';
		$dbuser = 'root';
		$dbpass = 'password';

		$conn = \mysqli_connect( $dbhost, $dbuser, $dbpass );

		if ( ! $conn ) {
			Logger::error( 'Error connecting to the database: ' . conn->connect_errno );
		}

		$sql    = 'CREATE DATABASE IF NOT EXISTS ' . CEBOLA_DB_NAME;
		$retval = \mysqli_query( $conn, $sql );
		if ( ! $retval ) {
			Logger::error( 'Error creating database: ' . mysqli_error( $conn ) );
		}
		$conn->close();
	}



	// public function connect() {
	// $tries = 10;
	// while ( empty( $this->conn ) || $this->conn->connect_errno ) {
	// Logger::info( 'Trying to connect to the report\'s database called ' . CEBOLA_DB_NAME . '...' );
	// $this->try_connection();
	// $tries--;
	// if ( 0 === $tries ) {
	// Logger::error( 'Failed to connect to the WordPress database.' );
	// }
	// sleep( 5 );
	// }
	// global $ceboladb;
	// $ceboladb = $this;
	// }

	// private function create_database() {
	// Verify if the database exists.
	// $special_conn = \mysqli_connect( '127.0.0.1', 'root', 'password', 3308 );
	// $exists       = $special_conn->query( "SHOW DATABASES LIKE '" . CEBOLA_DB_NAME . "';" );
	// if ( empty( $exists ) ) {
	// Create the database
	// $special_conn->query( 'CREATE DATABASE ' . CEBOLA_DB_NAME . ';' );
	// }
	// $special_conn->close();
	// }

	// private function try_connection() {
	// try {
	// $this->conn     = \mysqli_connect( '127.0.0.1', CEBOLA_DB_USER, CEBOLA_DB_PASSWORD, CEBOLA_DB_NAME, 3308 );
	// $this->is_fresh = $this->is_fresh();
	// } catch ( \Exception $e ) {
	// return $e;
	// }
	// }

	// public function install( $plugin_name ) {
	// Logger::info( 'Installing the database...' );
	// $this->create_database();
	// $this->connect();
	// $this->create_tables();

	// $stmt = $this->conn->prepare(
	// 'INSERT INTO cebola_meta( `name`, `value` ) VALUES( "plugin", ? );'
	// );

	// $stmt->bind_param( 's', $plugin_name );
	// $stmt->execute();
	// }

	public function query( $sql ) {
		return $this->conn->query( $sql );
	}

	public function is_fresh() {
		return empty( $this->query( 'SHOW TABLES LIKE "cebola_meta";' ) );
	}

	private function create_tables() {

		$this->connect();

		$this->query( 'DROP TABLE if EXISTS cebola_meta;' );
		$this->query( 'DROP TABLE if EXISTS cebola_functions;' );
		$this->query( 'DROP TABLE if EXISTS cebola_reports;' );
		$this->query( 'DROP TABLE if EXISTS cebola_parameters;' );

		$this->query(
			'CREATE TABLE cebola_meta(
			`id` INT NOT null AUTO_INCREMENT,
			`name` VARCHAR( 255 ) NOT null,
			`value` TEXT NOT null,
			PRIMARY KEY( `id` )
		) {;
}'
		);
		$this->query(
			'CREATE TABLE cebola_functions(
	`id` INT NOT null AUTO_INCREMENT,
	`type` VARCHAR( 50 ) NOT null,
	`hook` VARCHAR( 255 ),
	`callback` VARCHAR( 255 ) NOT null,
	`priority` INT,
	`arguments` INT,
	`file` VARCHAR( 255 ),
	`line` INT,
	`attention` FLOAT,
	PRIMARY KEY( `id` )
);'
		);
		$this->query(
			'CREATE TABLE cebola_reports(
	`id` INT NOT null AUTO_INCREMENT,
	`data` JSON,
	`payload` VARCHAR( 100 ),
	`severity` ENUM( "low", "medium", "high", "critical" ),
	`confidence` FLOAT,
	PRIMARY KEY( `id` )
);'
		);
		$this->query(
			'CREATE TABLE cebola_parameters(
	`id` INT NOT null AUTO_INCREMENT,
	`name` VARCHAR( 100 ) UNIQUE,
	PRIMARY KEY( `id` )
);'
		);
		$this->disconnect();
	}
}
