<?php
namespace Cebola\Classes;

class Functions {

	private $functions = array(
		'permissions' => array(
			'value'     => -5,
			'functions' => array(
				'current_user_can',
			),
		),
		'nonces'      => array(
			'unique'    => true,
			'value'     => -10,
			'functions' => array(
				'wp_verify_nonce',
				'check_admin_referer',
				'check_ajax_referer',
			),
		),
		'dangerous'   => array(
			'value'     => 25,
			'functions' => array(
				'eval',
				'exec',
				'passthru',
				'shell_exec',
				'popen',
				'proc_open',
				'pcntl_exec',
			),
		),
		'files'       => array(
			'value'     => 5,
			'functions' => array(
				'file_get_contents',
				'file_put_contents',
				'unlink',
			),
		),
		'user'        => array(
			'value'     => 5,
			'functions' => array(
				'add_user_meta',
				'update_user_meta',
				'delete_user_meta',
			),
		),
		'sensitive'   => array(
			'value'     => 5,
			'functions' => array(
				'update_option',
				'add_option',
				'delete_option',
			),
		),
		'posts'       => array(
			'value'     => 4,
			'functions' => array(
				'update_post_meta',
				'delete_post_meta',
				'wp_insert_post',
				'wp_delete_post'
			),
		),
		'mail'        => array(
			'value'     => 2,
			'functions' => array(
				'wp_mail',
				'mail',
			),
		),
		'redirects'   => array(
			'value'     => 2,
			'functions' => array(
				'wp_redirect',
			),
		),
		'requests'    => array(
			'value'     => 1,
			'functions' => array(
				'wp_remote_get',
				'wp_remote_post',
				'download_url',
			),
		),
	);

	public $register = array();

	public function __construct() {
		$this->hook_functions();
		add_action( 'shutdown', array( $this, 'register_functions' ) );
	}

	public function register_functions() {
		foreach ( $this->register as $key => $function ) {
			$this->register_function( $function['data'], $function['type'], $function['hook_name'], $function['callback'], $function['priority'], $function['accepted_args'] );
		}
	}

	private function hook_functions() {
		$class = $this;
		uopz_set_hook(
			'add_action',
			function( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) use ( $class ) {
				$is_plugin = $class->can_add( $hook_name, $callback );
				if ( $is_plugin ) {
					$class->register[] = array(
						'data'          => $is_plugin,
						'type'          => 'action',
						'hook_name'     => $hook_name,
						'callback'      => $callback,
						'priority'      => $priority,
						'accepted_args' => $accepted_args,
					);
				}
			}
		);
		uopz_set_hook(
			'add_filter',
			function( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) use ( $class ) {
				$is_plugin = $class->can_add( $hook_name, $callback );
				if ( $is_plugin ) {
					$class->register[] = array(
						'data'          => $is_plugin,
						'type'          => 'filter',
						'hook_name'     => $hook_name,
						'callback'      => $callback,
						'priority'      => $priority,
						'accepted_args' => $accepted_args,
					);
				}
			}
		);
		uopz_set_hook(
			'register_rest_route',
			function( $namespace, $route, $parameters ) use ( $class ) {
				if ( ! empty( $parameters[0] ) && ! empty( $parameters[0]['callback'] ) ) {
					$is_plugin = $class->can_add();
					if ( $is_plugin ) {
						$class->register[] = array(
							'data'          => $is_plugin,
							'type'          => 'route',
							'hook_name'     => $namespace . $route,
							'callback'      => $parameters[0]['callback'], // TODO: Consider the HTTP method to increase the attention value.
							'priority'      => 10,
							'accepted_args' => 1,
						);
					}
				}
			}
		);
	}

	public function register_function( $data, $type, $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		global $wpdb;

		// Check AJAX functions.

		$attention = null;

		if ( ! empty( $data['function'] ) && 'add_menu_page' === $data['function'] ) {

		} else {
			$callback_data = false;
			if ( 'route' === $type ) {
				$callback_data = $callback;
			} elseif ( ! empty( $data['args'][1] ) && is_array( $data['args'][1] ) ) {
				$callback_data = array( $data['args'][1][0], $data['args'][1][1] );
			} elseif ( ! empty( $data['args'][1] ) ) {
				$callback_data = $data['args'][1];
			}

			$function = $this->get_function_body( $callback_data );

			if ( false !== $function ) {
				$parser = new Parser( $function, $this->functions );
				$attention = $parser->get_code_attention();
	
				$interesting_hooks = array(
					'admin_init'
				);
	
				if (  str_starts_with( $hook_name, 'wp_ajax_' ) || in_array( $hook_name, $interesting_hooks, true ) ) {
					$check_permissions = array_merge( $this->functions['nonces']['functions'], $this->functions['permissions']['functions'] );
					if ( empty( $parser->calls ) || empty( array_intersect( $parser->calls, $check_permissions ) ) ) {
						$attention *= 1.5;
	
						$sensitive_functions = array_merge(
							$this->functions['sensitive']['functions'],
							$this->functions['mail']['functions'],
							$this->functions['requests']['functions'],
						);
	
						if ( ! empty( array_intersect( $parser->calls, $sensitive_functions ) ) ) {
							$attention *= 1.5;
						}
					}
				}

				foreach ( $parser->array_accesses as $key => $value ) {

					$param = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT name FROM cebola_parameters WHERE name = %s',
							$value['key']
						)
					);

					if ( ! empty( $param ) ) {
						continue; // Prevent "duplicate entry" error messages.
					}

					$wpdb->insert(
						'cebola_parameters',
						array(
							'name' => $value['key'],
						)
					);
				}
			}
		}

		$added = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id FROM cebola_functions WHERE hook = %s AND file = %s AND line = %d',
				$hook_name,
				$data['file'],
				$data['line'],
			)
		);

		if ( ! empty( $added ) ) {
			return;
		}

		if ( is_array( $callback ) ) {
			if ( ! is_string( $callback[0] ) ) {
				$callback[0] = get_class( $callback[0] ) ? $callback[0]::class : $callback;
			}
			$callback = json_encode( $callback );
		}

		$wpdb->insert(
			'cebola_functions',
			array(
				'type'      => $type,
				'hook'      => $hook_name,
				'callback'  => $callback,
				'priority'  => $priority,
				'arguments' => $accepted_args,
				'file'      => $data['file'],
				'line'      => $data['line'],
				'attention' => $attention,
			)
		);
		
		// $message = sprintf( "/*\nHook added: %s | %s:%d\n%s\n*/\n%s\n", $hook_name, $data['file'], $data['line'], json_encode( $data, JSON_PRETTY_PRINT ), $function );
		// file_put_contents( '/var/www/html/wp-content/mu-plugins/cebola/logs/CEBOLA.txt', $message, FILE_APPEND );
	}

	public function can_add( $hook_name = '', $callback = '' ) {

		$ignore_hooks     = array();
		$ignore_callbacks = array( '_future_post_hook', '_wp_ajax_add_hierarchical_term' );

		if ( in_array( $hook_name, $ignore_hooks, true ) || in_array( $callback, $ignore_callbacks, true ) ) {
			return;
		}

		$debug = debug_backtrace();
	
		$checks = 10;
		foreach ( $debug as $key => $value ) {
			if ( 0 === $checks ) {
				break;
			}
			if ( ! empty( $value['file'] ) && str_starts_with( $value['file'], CEBOLA_TESTING_PLUGIN ) ) {
				return $value;
			}
			$checks--;
		}
		return false;
	}

	private function get_function_body( $function ) {
		if ( ! is_callable( $function ) ) {
			return false;
		}
		try {
			if ( is_array( $function ) ) {
				$func = new \ReflectionMethod( $function[0], $function[1] );
			} else {
				$func = new \ReflectionFunction( $function );
			}
		} catch (\Throwable $th) {
			return false;
		}
		$filename   = $func->getFileName();
		$start_line = $func->getStartLine() - 1;
		$end_line   = $func->getEndLine();
		$length     = $end_line - $start_line;
	
		if ( empty( $filename ) ) {
			return false;
		}

		$source = file( $filename );
		$body   = implode( '', array_slice( $source, $start_line, $length ) );
		return $body;
	}

}
