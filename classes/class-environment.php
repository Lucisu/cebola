<?php

namespace Cebola\Classes;

class Environment {

	const CONTAINER_NAME = 'container-wp-1';
	private $variables   = array();

	public function set_container() {
		$running = shell_exec( 'docker ps --filter "name=' . $this->sanitize_var( self::CONTAINER_NAME ) . '" --filter "status=running" --quiet' );

		if ( empty( $running ) ) {
			Logger::info( 'Container is not running' );
			$this->run_container();
			return;
		}

		$info = shell_exec( 'docker inspect ' . $this->sanitize_var( self::CONTAINER_NAME ) );

		if ( empty( $info ) ) {
			Logger::error( 'Failed to inspect the container.' );
		}

		$info = json_decode( $info, true );

		if ( empty( $info ) || empty( $info[0] ) ) {
			Logger::error( 'Container inspect returned an invalid value.' );
		}

		$info = $info[0];

		if ( empty( $info['Config']['Env'] ) ) {
			Logger::error( 'Failed to get container\'s environment.' );
		}

		foreach ( $info['Config']['Env'] as $key => $value ) {
			$variable = explode( '=', $value );

			if ( count( $variable ) !== 2 ) {
				continue;
			}

			$this->variables[ $variable[0] ] = $variable[1];
		}

		$this->set_constants();
	}

	public function stop_container() {
		Logger::info( 'Stopping docker container...' );
		echo "\n";
		shell_exec( 'sh ' . CEBOLA_CONTAINER_DIR . '/stop.sh' );
	}

	public function run_container() {
		Logger::info( 'Starting docker container...' );
		shell_exec( 'sh ' . CEBOLA_CONTAINER_DIR . '/start.sh' );
		sleep( 5 );
		$this->set_container();
	}

	public function install_dependencies() {
		Logger::info( 'Installing Dependencies...' );
		shell_exec( 'docker exec -it --user root ' . $this->sanitize_var( self::CONTAINER_NAME ) . ' sh -cx "apt-get update && pecl install xdebug && docker-php-ext-enable xdebug && pecl install uopz && /etc/init.d/apache2 reload"' );
	}

	public function set_wp_debug( bool $value ) {
		Logger::info( sprintf( 'Setting WP debug to %s...', var_export( $value, true ) ) );
		shell_exec( 'docker exec -it container-wpcli-1 sh -cx "wp config set --raw WP_DEBUG ' . var_export( $value, true ) . '"' );
		shell_exec( 'docker exec -it container-wpcli-1 sh -cx "wp config set --raw WP_DEBUG_LOG ' . var_export( $value, true ) . '"' );
		shell_exec( 'docker exec -it container-wpcli-1 sh -cx "wp config set --raw WP_DEBUG_DISPLAY ' . var_export( $value, true ) . '"' );
	}

	public function set_plugin( string $plugin ) {
		Logger::info( sprintf( 'Activating plugin %s...', $plugin ) );
		$output = shell_exec( 'docker exec -it container-wpcli-1 sh -cx "wp plugin install ' . $plugin . ' --activate"' );
	}

	private function set_constants() {
		$vars = array( 'WORDPRESS_DB_NAME', 'WORDPRESS_DB_USER', 'WORDPRESS_DB_PASSWORD' );
		foreach ( $vars as $key => $value ) {
			$var = $this->get_var( $value );

			if ( empty( $var ) ) {
				Logger::error( sprintf( 'Unabled to find the %s variable inside %s', $value, self::CONTAINER_NAME ) );
			}

			define( str_replace( 'WORDPRESS_', 'CEBOLA_', $value ), $var );
		}
	}

	/**
	 * Gets an environment variable from the docker container.
	 *
	 * @param string $var The variable to be retrieved.
	 * @return string
	 */
	public function get_var( string $var ) {
		if ( empty( $this->variables[ $var ] ) ) {
			return null;
		}
		return $this->variables[ $var ];
	}

	/**
	 * Sanitizes a variable to be used inside system calls.
	 *
	 * @param string $var The variable to be sanitized.
	 * @return string
	 */
	private function sanitize_var( $var ) {
		return preg_replace( '/[^A-Za-z0-9_\-]/', '', $var );
	}
}
