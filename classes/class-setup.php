<?php

namespace Cebola\Classes;

class Setup {

	/**
	 * Arguments passed by the command line.
	 *
	 * @var array
	 */
	private $args = array();

	/**
	 * Enviroment variable responsible to get env variables from docker.
	 *
	 * @var \Cebola\Classes\Environment
	 */
	private $environment;

	/**
	 * Provides the access to the WordPress database.
	 *
	 * @var \Cebola\Classes\Database
	 */
	private $database;

	/**
	 * Parses the provided arguments, sets initial variables and install the tool.
	 */
	public function __construct() {
		require CEBOLA_DIR . '/classes/class-database.php';
		require CEBOLA_DIR . '/classes/class-environment.php';

		$this->parse_arguments();
		$this->check_requirements();

		$this->environment = new Environment();
		$this->database    = new Database();

		$this->maybe_install();

		Logger::success( 'Running on http://localhost:8000 (user:admin, password:secret)' );
	}

	/**
	 * Checks some requirements needed by the tool.
	 *
	 * @return void
	 */
	private function check_requirements() {
		$functions = array( 'shell_exec', 'mysqli_connect', 'curl_init' );
		foreach ( $functions as $key => $value ) {
			if ( ! function_exists( $value ) ) {
				Logger::error( sprintf( '%s is not enabled', $value ) );
			}
		}

		$writable = array( CEBOLA_CONTAINER_DIR, CEBOLA_WP_DIR  );
		foreach ( $writable as $key => $value ) {
			if ( file_exists( $value ) && ! is_writable( $value ) ) {
				Logger::error( sprintf( '%s is not writable', $value ) );
			}
		}

		
	}

	/**
	 * Validates the arguments passed via command line.
	 *
	 * @return void
	 */
	private function parse_arguments() {
		$options = getopt(
			'v:',
			array(
				'wp-debug::',
				'plugin:',
				'fresh',
			)
		);

		$defaults = array(
			'wp-debug' => true,
			'v'        => 1,
			'fresh'    => isset( $options['fresh'] ),
		);

		unset( $options['fresh'] );

		$this->args = $options + $defaults;

		define( 'CEBOLA_VERBOSE', (int) $this->args['v'] );

		if ( empty( $this->args['plugin'] ) ) {
			Logger::error( 'You need to specify a plugin using --plugin' );
		}

		if ( ! empty( $this->args['fresh'] ) ) {
			Logger::info( 'Fresh option provided' );
		}

	}

	/**
	 * Checks if it's a fresh install before installing the tool.
	 *
	 * @return void
	 */
	private function maybe_install() {
		if ( ! empty( $this->args['fresh'] ) || $this->database->is_fresh || ! file_exists( CEBOLA_WP_DIR . '/wp-config.php' ) ) {
			$this->install();
		} else {
			$this->environment->set_container();
			$this->database->connect();
		}
	}

	private function install() {
		@rmdir( CEBOLA_WP_DIR );
		$this->environment->set_container();
		$this->database->connect();
		$this->environment->install_dependencies();
		$this->environment->set_wp_debug( $this->args['wp-debug'] );
		$this->environment->set_plugin( $this->args['plugin'] );
		$this->database->install( $this->args['plugin'] );
		$this->send_requests();
	}
    

	private function send_requests() {
		Logger::info( 'Sending initial requests...' );

		$requests = array( 'http://localhost:8000', 'http://localhost:8000/index.php?rest_route=/' );
		foreach ( $requests as $key => $value ) {
			// phpcs:disable
			$url = $value;
			$ch  = \curl_init( $url );
			\curl_setopt( $ch, CURLOPT_HEADER, true );
			\curl_setopt( $ch, CURLOPT_NOBODY, true );
			\curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			\curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
			\curl_exec( $ch );
			$httpcode = \curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			\curl_close( $ch );
			// phpcs:enable

			if ( $httpcode >= 400 ) {
				Logger::error( sprintf( 'Something went wrong when accessing %s', $value ) );
			}
		}

	}

	/**
	 * Recursively copies a directory to another.
	 *
	 * @see Adapted from: https://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php
	 *
	 * @param string $source_directory      The source directory.
	 * @param string $destination_directory The destination.
	 * @param bool   $first                 If it's the first run.
	 * @return bool
	 */
	private function recursive_copy( string $source_directory, string $destination_directory, bool $first = false ): bool {
		$directory = opendir( $source_directory );
		if ( is_dir( $destination_directory ) === false ) {
			if ( ! mkdir( $destination_directory ) ) {
				return false;
			}
		}

		if ( $first ) {
			$files = array_diff( scandir( $source_directory ), array( '.', '..' ) );
			foreach ( $files as $key => $value ) {
				if ( is_dir( $source_directory . '/' . $value ) ) {
					continue;
				}
				$content = file_get_contents( $source_directory . '/' . $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				file_put_contents( $destination_directory . '/' . $value, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
		}

		while ( ( $file = readdir( $directory ) ) !== false ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}
			if ( is_dir( "$source_directory/$file" ) === true ) {
				return $this->recursive_copy( "$source_directory/$file", "$destination_directory/$file" );
			} else {
				if ( ! copy( "$source_directory/$file", "$destination_directory/$file" ) ) {
					return false;
				}
			}
		}
		closedir( $directory );
		return true;
	}
}
