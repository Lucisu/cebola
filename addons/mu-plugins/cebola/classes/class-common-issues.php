<?php
namespace Cebola\Classes;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

// TODO: check if "! empty && nonce" is evrified.
class CommonIssues extends NodeVisitorAbstract {
	private $return_value = -1;
	private $wp_funcs = array( 'update_option', 'update_user', 'wp_delete_user' );
	private $user_meta_funcs = array( 'update_user_meta', 'add_user_meta' );
	private $php_globals = array( '_GET', '_POST', '_REQUEST', '_SERVER' );

	public function enterNode( Node $node ) {
		if ( $node instanceof Node\Stmt\If_ ) {
			// Check if the "if" statement has a condition
			if ( $node->cond !== null ) {
				if ( $node->cond instanceof Node\Expr\BinaryOp\BooleanAnd ) {
					// The condition is a boolean AND operator
					$leftExpr = $node->cond->left;
					$rightExpr = $node->cond->right;
					// Check if the left expression contains to any global and wp_verify_nonce
					$leftContainsGetAndNonce = $this->containsGetOrVerifyNonce( $leftExpr );
					// Check if the right expression contains to any global and wp_verify_nonce
					$rightContainsGetAndNonce = $this->containsGetOrVerifyNonce( $rightExpr );
					if ( $leftContainsGetAndNonce && $rightContainsGetAndNonce ) {
					// The condition contains to any global, wp_verify_nonce, and an AND operator
						$this->return_value = 1;
					} else {
						$this->return_value = -1;
					}
				}
			}
		}

		// Check for WP Vulnerable functions
		if ( $node instanceof Node\Expr\FuncCall ) {
			if ( $node->name instanceof Node\Name ) {
				$parts = $node->name->parts;
				$args  = $node->args;

				// Check for user meta functions
				if ( in_array( $parts[0], $this->user_meta_funcs ) ) {
					$third_arg_val = isset( $args[2] ) ? $this->checkValueIsStringVarOrGlobal( $args[2] ) : 0;
					// Return importance based on the 3rd argument's value
					switch ( $third_arg_val ) {
						case 0: $this->return_value = 0; break;
						case 1: $this->return_value = 1; break;
						case 2: $this->return_value = 2; break;
					}
				} elseif ( in_array( $parts[0], $this->wp_funcs ) ) {
					$first_arg_val = isset( $args[0] ) ? $this->checkValueIsStringVarOrGlobal( $args[0] ) : 0;
					$second_arg_val = isset( $args[1] ) ? $this->checkValueIsStringVarOrGlobal( $args[1] ) : 0;

					if ( $first_arg_val === 0 && $second_arg_val === 1 ) {
						// if first arg is string/number
						// and if second argument is variable, return 1.
						$this->return_value = 1;
					} elseif ( $first_arg_val === 0 && $second_arg_val === 2 ) {
						// if first arg is string/number
						// and if second argument is PHP global array, return 2.
						$this->return_value = 2;
					} elseif ( $first_arg_val === 1 ) {
						// if first argument is PHP variable or non PHP global array, return 1.
						$this->return_value = 1;
					} elseif ( $first_arg_val === 2 ) {
						// if first argument is PHP global array, return 3.
						$this->return_value = 3;
					}
				}
			}
		}
	}

	/**
	 * Check if Containers Get or Verify Nonce
	 *
	 * @param object $expr
	 * @return boolean
	 */
	private function containsGetOrVerifyNonce( $expr ) {
		if ( $expr instanceof Node\Expr\FuncCall ) {
			// Check if the function call is wp_verify_nonce
			$funcName = $expr->name->toString();
			if ( $funcName === 'wp_verify_nonce' ) {
				// Check if the function call has one argument
				$argList = $expr->args;
				if ( count( $argList ) === 1 ) {
					// Check if the argument is any global.
					$argExpr = $argList[0]->value;
					if ( $argExpr instanceof Node\Expr\ArrayDimFetch ) {
						$varNode = $argExpr->var;
						if ( $varNode instanceof Node\Expr\Variable && in_array( $varNode->name, $this->php_globals, true ) ) {
							return true;
						}
					}
				}
			}
		} elseif ( $expr instanceof Node\Expr\BooleanNot ) {
			return $this->containsGetOrVerifyNonce( $expr->expr );
		} elseif ( $expr instanceof Node\Expr\Assign && $expr->var instanceof Node\Expr\ArrayDimFetch ) {
			// Check if the assignment is to any global.
			$varNode = $expr->var->var;
			if ( $varNode instanceof Node\Expr\Variable && in_array( $varNode->name, $this->php_globals, true ) ) {
				return true;
			}
		} elseif ( $expr instanceof Node\Expr\Isset_ ) {
			// Check if the isset call has an argument that is to any global.
			$varNodes = $expr->vars;
			foreach ( $varNodes as $varNode ) {
				if ( $varNode instanceof Node\Expr\ArrayDimFetch ) {
					$varNode = $varNode->var;
					if ( $varNode instanceof Node\Expr\Variable && in_array( $varNode->name, $this->php_globals, true ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Check Value is String, Variable, or Global
	 *
	 * @param object $expr
	 * @return integer
	 */
	private function checkValueIsStringVarOrGlobal( $expr ) {
		if ( $expr->value instanceof Node\Scalar\String_ ) {
			// Returning string as 0.
			return -1;
		} elseif ( $expr->value instanceof Node\Scalar\LNumber ) {
			// returning number as 0.
			return -1;
		} elseif ( $expr->value instanceof Node\Expr\Variable ) {
			// returning variable as 1.
			return 1;
		} elseif ( $expr->value instanceof Node\Expr\ArrayDimFetch ) {
			// Check if the array variable name is one of PHP globals.
			if ( in_array( $expr->value->var->name, $this->php_globals ) ) {
				// then return 2
				return 2;
			}
			// Otherwise return 1.
			return 1;
		} elseif ( $expr->value instanceof Node\Expr\FuncCall ) {
			// Check if value is an instance of 'function'.
			return $this->checkIfValueIsFunctionSecondLevel( $expr );
		}
	}

	/**
	 * Check Value is String, Variable, or Global
	 *
	 * @param object $expr
	 * @return integer
	 */
	private function checkIfValueIsFunctionSecondLevel( $expr ) {
		// Check if the value is instance of function.
		if ( $expr->value instanceof Node\Expr\FuncCall ) {
			$found_global = false;
			$found_var = false;

			foreach ( $expr->value->args as $arg ) {
				if ( $arg->value instanceof Node\Expr\ArrayDimFetch ) {
					if ( in_array( $arg->value->var->name, $this->php_globals ) ) {
						$found_global = true;
						break;
					}
				} elseif ( $arg->value instanceof Node\Expr\Variable ) {
					$found_var = true;
				}
			}

			// First check if there was a PHP global variable. If yes return highest value 2
			if ( $found_global ) {
				return 2;
			} elseif ( $found_var ) {
				// If it found a variable instead, return 1.
				return 1;
			} else {
				// Else recursive through the function again.
				return $this->checkIfValueIsFunctionSecondLevel( $arg );
			}
		}

		// If not return 0.
		return 0;
	}

	/**
	 * Get Return Value
	 *
	 * @return integer
	 */
	public function getValue() {
		return $this->return_value;
	}
}
