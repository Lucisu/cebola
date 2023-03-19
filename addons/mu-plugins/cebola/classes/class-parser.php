<?php

namespace Cebola\Classes;

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\{Node, NodeTraverser, NodeFinder, NodeVisitorAbstract};

class Parser {

	private $code;
	private $functions;
	private $ast;
	public $variables;
	public $calls = array();
	public $array_accesses;

	public function __construct( string $code, array $functions = array() ) {
		// This ugly workaround makes the parser correctly read functions with visibility defined.
		$code            = "<?php\nclass CEBOLA {\n" . $code . '}';
		$this->code      = $code;
		$this->functions = $functions;

		$this->parse();
	}

	private function parse() {
		$parser = ( new ParserFactory )->create( ParserFactory::PREFER_PHP7 );
		try {
			$ast = $parser->parse( $this->code );
		} catch (Error $error) {
			echo "Parse error: {$error->getMessage()}\n";
			return;
		}

		$this->ast = $ast;

		// $traverser = new NodeTraverser;
		// $traverser->addVisitor(new class extends NodeVisitorAbstract {
		// 	public function enterNode(Node $node) {
		// 		$node;
		// 	}
		// });

		// $modifiedStmts = $traverser->traverse($ast);

	}

	public function get_calls() {
		$nodeFinder = new NodeFinder;
		$calls      = array();
		$funcs      = $nodeFinder->find(
			$this->ast,
			function( Node $node ) use ( &$calls ) {

				if ( $node instanceof \PhpParser\Node\Expr\Eval_ ) {
					$calls[] = 'eval';
				} elseif ( $node instanceof \PhpParser\Node\Expr\ShellExec ) {
					$calls[] = 'shell_exec';
				}

				return $node instanceof \PhpParser\Node\Expr\FuncCall;
			}
		);
		foreach ( $funcs as $key => $func ) {

			$args = array();

			foreach ( $func->args as $key => $v ) {
				if ( ! empty( $v->value ) ) {
					$raw = $v->value->getAttribute( 'rawValue' );

					$raw = trim( $raw, '\'"' );
					$raw = ltrim( $raw, '\'"' );

					$args[] = $raw;
				}
			}

			$calls[] = array(
				'name' => $func->name->parts[0],
				'args' => $args,
			);

		}

		$this->calls = $calls;
		return $this->calls;
	}

	public function get_array_accesses() {
		$nodeFinder = new NodeFinder;

		$dims = $nodeFinder->find( $this->ast, function( Node $node ) {
			return $node instanceof \PhpParser\Node\Expr\ArrayDimFetch &&
				$node->dim instanceof \PhpParser\Node\Scalar\String_;
		});

		$keys = array();

		foreach ( $dims as $key => $dim ) {
			if ( ! empty( $dim->var->var ) ) {
				$dim->var = $dim->var->var;
			}
			$value = array(
				'variable' => '',
				'key'      => '',
			);

			if ( ! empty( $dim->var->name ) && is_string( $dim->var->name ) ) {
				$value['variable'] = $dim->var->name;
			}
			$value['key'] = $dim->dim->value;

			$keys[] = $value;
		}

		$this->array_accesses = array_unique( $keys, SORT_REGULAR );
		return $this->array_accesses;

	}

	public function get_variables() {
		$nodeFinder = new NodeFinder;
		$dims = $nodeFinder->find( $this->ast, function( Node $node ) {
			return $node instanceof \PhpParser\Node\Expr\Variable;
		});

		$variables = array();

		foreach ( $dims as $key => $value ) {
			$variables[] = $value->name;
		}

		$this->variables = $variables;
		return $this->variables;
	}

	public function get_code_attention() {

		$attention = 0;

		$array_accesses = $this->get_array_accesses();

		$variables = array_column( $array_accesses, 'variable' );

		$variables_count = array_count_values( $variables );

		$interesting_variables = array(
			'_REQUEST' => 2.5,
			'_POST'    => 2.5,
			'_GET'     => 2,
			'_SERVER'  => 1,
		);

		foreach ( $interesting_variables as $key => $value ) {
			if ( ! empty( $variables_count[ $key ] ) ) {
				$count = $variables_count[ $key ];
				$count = $count > 5 ? 5 : $count;

				$attention += $count * $value;
			}
		}

		$variables = $this->get_variables();

		if ( $attention > 0 && in_array( 'wpdb', $variables, true ) ) {
			$attention += 5;
		}

		$calls = $this->get_calls();

		foreach ( $this->functions as $key => $value ) {
			foreach ( $value['functions'] as $k => $function ) {

				$called = array_search( $function, array_column( $calls, 'name' ), true );
				if ( false !== $called ) {

					if ( 'nonces' === $key ) {
						$nonce = '';
						switch ( $function ) {
							case 'wp_verify_nonce':
								$nonce = $calls[ $called ]['args'][1];
								break;
						}

						if ( ! empty( $nonce ) ) {
							global $wpdb;
							$added = $wpdb->get_row(
								$wpdb->prepare(
									'SELECT id FROM cebola_nonces WHERE action = %s',
									$nonce,
								)
							);
							if ( ! empty( $added ) ) {
								continue;
							}
						}
					}

					$attention += $value['value'];
					if ( ! empty( $value['unique'] ) ) {
						break;
					}
				}
			}
		}
		return $attention;
	}
}
