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

		$commands = array( 'composer', 'docker compose' );
		foreach ( $commands as $key => $value ) {
			if ( empty( shell_exec( 'which ' . $value ) ) ) {
				Logger::error( sprintf( '%s is not available', $value ) );
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
			'v'        => 2,
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
		@unlink( CEBOLA_DIR . '/container/wp-data/wp-content/urls.txt' );
		@rmdir( CEBOLA_WP_DIR );
		$this->environment->set_container();
		$this->database->connect();
		$this->environment->install_dependencies();
		$this->environment->set_wp_debug( $this->args['wp-debug'] );
		$this->environment->set_plugin( $this->args['plugin'] );
		$this->database->install( $this->args['plugin'] );
		$this->send_requests();
		$this->run_tools();
		$this->show_results();
	}

	private function send_requests() {
		Logger::info( 'Sending initial requests...' );

		$requests = array( 'http://localhost:8000', 'http://localhost:8000/wp-admin/admin-ajax.php', 'http://localhost:8000/index.php?rest_route=/' );
		foreach ( $requests as $key => $value ) {
			$ch       = $this->http_request( $value, false );
			$httpcode = \curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			\curl_close( $ch );
			if ( ! str_contains( $value, 'wp-admin/admin-ajax.php' ) && $httpcode >= 400 ) {
				Logger::error( sprintf( 'Something went wrong when accessing %s', $value ) );
			}
		}
	}

	private function http_request( $url, $close = true ) {
		// phpcs:disable
		$ch  = \curl_init( $url );
		\curl_setopt( $ch, CURLOPT_HEADER, true );
		\curl_setopt( $ch, CURLOPT_NOBODY, true );
		\curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		\curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		\curl_exec( $ch );
		if ( $close ) {
			\curl_close( $ch );
		}
		// phpcs:enable
		return $ch;
	}

	private function run_tools( $step = 1 ) {
		Logger::info( 'Installing external tools...' );
		$this->database->query( 'INSERT INTO cebola_meta(`name`, `value`) VALUES ("xsstrike", "' . (int) $step . '");' );

		shell_exec( 'git clone https://github.com/s0md3v/XSStrike.git 2>/dev/null ' . CEBOLA_DIR . '/tools' );

		Logger::info( 'Running external tools...' );

		$urls_file = CEBOLA_DIR . '/container/wp-data/wp-content/urls.txt';

		if ( file_exists( $urls_file ) ) {
			$urls = file_get_contents( $urls_file );
			$urls = explode( "\n", $urls );
			$urls = array_filter( $urls );
			$urls = array_unique( $urls );

			$urls = array_filter(
				$urls,
				function( $value ) use ( $urls ) {
					return in_array( $value . '/', $urls, true );
				}
			);
		} else {
			$urls = array();
		}

		file_put_contents( CEBOLA_DIR . '/urls.txt', implode( "\n", $urls ) );

		foreach ( $urls as $key => $value ) {
			Logger::info( 'Requesting ' . strtok( $value, '?' ) . ' with parameters...' );

			$this->http_request( $value );

			Logger::info( 'Running XSStrike ' . ( (int) $key + 1 ) . '...' );

			$log_file = CEBOLA_DIR . '/xsstrike' . $key . '.log';

			@unlink( $log_file );

			// shell_exec( 'python3 ' . CEBOLA_DIR . '/tools/XSStrike/xsstrike.py --skip --file-log-level VULN -l 1 -t 20 --log-file ' . $log_file . ' -u "' . $value . '"' );

			if ( file_exists( $log_file ) ) {
				$report = file_get_contents( $log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( ! empty( trim( $report ) ) ) {
					Logger::success( 'XSS Found:' );
					echo $report; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}

		if ( 1 === $step ) {
			Logger::info( 'Entering in authenticated mode...' );
			$this->run_tools( 2 );
		}

	}

	private function show_results() {
		echo "\n";
		Logger::success( 'Results (LIMIT 5):' );
		echo "\n";
		$results = mysqli_fetch_all( $this->database->query( 'SELECT * FROM `cebola_functions` ORDER BY attention DESC LIMIT 5;' ), MYSQLI_ASSOC ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_fetch_all

		$mask = "| %20.20s | %45.45s | %70.70s | %9.9s |\n";
		printf( $mask, 'Hook', 'File', 'Callback', 'Attention' );
		foreach ( $results as $key => $value ) {
			printf( $mask, $value['hook'], basename( $value['file'] ), $value['callback'], $value['attention'] );
		}
		echo "\n";
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
