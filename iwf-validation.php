<?php
/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author         Masayuki Ietomi <jyokyoku@gmail.com>
 * @copyright      Copyright(c) 2011 Masayuki Ietomi
 * @link           http://inspire-tech.jp
 */

require_once dirname( __FILE__ ) . '/iwf-loader.php';

class IWF_Validation {
	protected $current_field;

	protected $current_rule;

	protected $validated = array();

	protected $errors = array();

	protected $fields = array();

	protected $forms = array();

	protected $rules = array();

	protected $messages = array();

	protected $config = array();

	protected $data = array();

	protected static $instances = array();

	protected function __construct( $config = array() ) {
		$config = wp_parse_args( $config, array(
			'messages' => array(
				'not_empty' => _x( 'The field :label is required and must contain value.', 'not_empty', 'iwf' ),
				'not_empty_if' => _x( 'The field :label is required and must contain value.', 'not_empty_if', 'iwf' ),
				'valid_string' => __( 'The valid string rule :rule(:param:1) failed for field :label.', 'iwf' ),
				'valid_email' => __( 'The field :label must contain a valid email address.', 'iwf' ),
				'valid_url' => __( 'The field :label must contain a valid URL.', 'iwf' ),
				'min_length' => __( 'The field :label may not contain more than :param:1 characters.', 'iwf' ),
				'max_length' => __( 'The field :label has to contain at least :param:1 characters.', 'iwf' ),
				'exact_length' => __( 'The field :label must equal :param:1 characters.', 'iwf' ),
				'numeric_min' => __( 'The minimum numeric value of :label must be :param:1', 'iwf' ),
				'numeric_max' => __( 'The maximum numeric value of :label must be :param:1', 'iwf' ),
				'integer' => __( 'The value of :label must be integer.', 'iwf' ),
				'decimal' => __( 'The value of :label must be decimal.', 'iwf' ),
				'match_value' => __( 'The field :label must contain the value :param:1.', 'iwf' ),
				'match_pattern' => __( 'The field :label must match the pattern :param:1.', 'iwf' )
			),
			'error_open' => '<span class="error">',
			'error_close' => '</span>'
		) );

		$this->set_default_message( $config['messages'] );
		$this->set_config( 'error_open', $config['error_open'] );
		$this->set_config( 'error_close', $config['error_close'] );
	}

	public function add_field( $field, $label = null, $type = null, $value = null, $attributes = array() ) {
		if ( !array_key_exists( $field, $this->fields ) ) {
			if ( !$label ) {
				$label = $field;
			}

			$this->fields[$field] = $label;
			$this->current_field = $field;
		}

		$this->forms[$field] = compact( 'type', 'value', 'attributes' );

		return $this;
	}

	public function add_rule( $rule ) {
		if ( !$this->current_field ) {
			trigger_error( 'There is no field that is currently selected.', E_USER_WARNING );

			return false;
		}

		if ( is_string( $rule ) && is_callable( array( 'IWF_Validation', $rule ) ) ) {
			$rule = array( 'IWF_Validation', $rule );
		}

		if ( !is_callable( $rule ) ) {
			trigger_error( 'The rule is not a correct validation rule.', E_USER_WARNING );

			return false;
		}

		$rule_name = $this->create_callback_name( $rule );

		$args = array_splice( func_get_args(), 1 );
		array_unshift( $args, $rule );

		$this->current_rule = $rule_name;
		$this->rules[$this->current_field][$rule_name] = $args;

		return $this;
	}

	public function set_message( $message ) {
		if ( !$this->current_field ) {
			trigger_error( 'There is no field that is currently selected.', E_USER_WARNING );

			return false;
		}

		if ( !$this->current_rule ) {
			trigger_error( 'There is no rule that is currently selected.', E_USER_WARNING );

			return false;
		}

		if ( is_null( $message ) || $message === false ) {
			unset( $this->messages[$this->current_field][$this->current_rule] );

		} else {
			$this->messages[$this->current_field][$this->current_rule] = $message;
		}

		return $this;
	}

	public function form_field( $field, $type = null, $value = null, $attributes = array() ) {
		if ( !isset( $this->forms[$field] ) ) {
			return null;
		}

		$form = $this->forms[$field];

		foreach ( array( 'type', 'value', 'attributes' ) as $varname ) {
			if ( ${$varname} ) {
				$form[$varname] = ${$varname};
			}
		}

		$value = iwf_get_array( $this->data, $field );

		if ( !method_exists( 'IWF_Form', $form['type'] ) ) {
			return null;
		}

		if ( !is_null( $value ) ) {
			switch ( $form['type'] ) {
				case 'checkbox':
					if ( $form['value'] && $value == $form['value'] ) {
						$form['attributes']['checked'] = 'checked';
					}

					break;

				case 'checkboxes':
					if ( is_array( $form['value'] ) && is_array( $value ) ) {
						$form['attributes']['checked'] = array();

						foreach ( $value as $_value ) {
							if ( in_array( $_value, $form['value'] ) ) {
								$form['attributes']['checked'][] = $_value;
							}
						}
					}

					break;

				case 'radio':
					if ( $form['value'] ) {
						$form['attributes']['checked'] = $value;
					}

					break;

				case 'select':
					if ( $form['value'] ) {
						$form['attributes']['selected'] = $value;
					}

					break;

				default:
					$form['value'] = $value;
			}
		}

		return call_user_func( array( 'IWF_Form', $form['type'] ), $field, $form['value'], $form['attributes'] );
	}

	public function validated( $field = null ) {
		if ( func_num_args() > 1 ) {
			$field = func_get_args();
		}

		if ( !$field ) {
			return $this->validated;

		} else if ( is_array( $field ) ) {
			$validated_values = array();

			foreach ( $field as $_field ) {
				if ( !$_field || !isset( $this->validated[$_field] ) ) {
					continue;
				}

				$validated_values[$_field] = $this->validated[$_field];
			}

			return $validated_values;

		} else if ( isset( $this->validated[$field] ) ) {
			return $this->validated[$field];
		}

		return false;
	}

	public function set_validated( $field, $value = null ) {
		if ( is_array( $field ) ) {
			foreach ( $field as $_field => $_value ) {
				$this->set_validated( $_field, $_value );
			}

		} else {
			$this->validated[$field] = $value;
		}
	}

	public function error( $field = null, $open = null, $close = null ) {
		$error_messages = $this->error_message( $field );

		if ( !$error_messages ) {
			return $error_messages;
		}

		if ( !is_array( $error_messages ) ) {
			$error_messages = array( $error_messages );
		}

		$open = is_null( $open ) ? $this->get_config( 'error_open' ) : $open;
		$close = is_null( $close ) ? $this->get_config( 'error_close' ) : $close;
		$errors = array();

		foreach ( $error_messages as $error_message ) {
			$errors[] = $open . $error_message . $close;
		}

		return count( $error_messages ) > 1 ? $errors : reset( $errors );
	}

	public function error_message( $field = null ) {
		if ( func_num_args() > 1 ) {
			$field = func_get_args();
		}

		if ( !$field ) {
			return $this->errors;

		} else if ( is_array( $field ) ) {
			$errors = array();

			foreach ( $field as $_field ) {
				if ( !$_field || !( $error = $this->error( $_field ) ) ) {
					continue;
				}

				$errors[] = $error;
			}

			return $errors;

		} else if ( isset( $this->errors[$field] ) ) {
			return $this->errors[$field];
		}

		return false;
	}

	public function set_error( $field, $message = null ) {
		if ( is_array( $field ) ) {
			foreach ( $field as $_field => $_message ) {
				$this->set_error( $_field, $_message );
			}

		} else {
			$this->errors[$field] = $message;
		}
	}

	public function set_data( $data = array() ) {
		$this->data = (array)$data;
	}

	public function get_data() {
		return $this->data;
	}

	public function run( $data = array() ) {
		$this->errors = $this->validated = array();

		if ( empty( $data ) ) {
			if ( empty( $this->data ) ) {
				return true;
			}

		} else {
			$this->data = (array)$data;
		}

		foreach ( $this->fields as $field => $label ) {
			$value = iwf_get_array( $this->data, $field );

			if ( is_array( $value ) ) {
				$value = array_filter( $value );
			}

			if ( !empty( $this->rules[$field] ) ) {
				foreach ( $this->rules[$field] as $rule => $params ) {
					$function = array_shift( $params );
					$args = $params;

					foreach ( $args as $i => $arg ) {
						if ( is_string( $arg ) && strpos( $arg, ':' ) === 0 ) {
							$data_field = substr( $arg, 1 );
							$args[$i] = iwf_get_array( $this->data, $data_field );
						}
					}

					$result = self::callback( $value, $function, $args );

					if ( $result === false ) {
						$message = isset( $this->messages[$field][$rule] )
							? $this->messages[$field][$rule]
							: $this->get_default_message( $rule );

						$find = array( ':field', ':label', ':value', ':rule' );
						$replace = array( $field, $label, $this->convert_to_string( $value ), $rule );

						foreach ( $params as $param_key => $param_value ) {
							$find[] = ':param:' . ( $param_key + 1 );
							$replace[] = $this->convert_to_string( $param_value );
						}

						$this->set_error( $field, str_replace( $find, $replace, $message ) );

						continue 2;

					} else if ( $result !== true ) {
						$value = $result;
					}
				}
			}

			$this->set_validated( $field, $value );
		}

		return $this->is_valid();
	}

	public function is_valid() {
		return count( $this->errors ) == 0;
	}

	public function validated_hidden_fields() {
		$hidden = array();

		foreach ( $this->validated() as $field_name => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $_key => $_value ) {
					$hidden[] = IWF_Form::hidden( $field_name . '[' . $_key . ']', iwf_convert( $_value, 'string' ) );
				}

			} else {
				$hidden[] = IWF_Form::hidden( $field_name, $value );
			}
		}

		return implode( "\n", $hidden );
	}

	public function set_default_message( $rule, $message = null ) {
		if ( is_array( $rule ) && empty( $message ) ) {
			foreach ( $rule as $_rule => $message ) {
				if ( is_int( $_rule ) ) {
					continue;
				}

				$this->set_default_message( $_rule, $message );
			}

		} else {
			if ( !$rule_name = $this->create_callback_name( $rule ) ) {
				return false;
			}

			if ( is_null( $message ) || $message === false ) {
				iwf_delete_array( $this->config, 'message.' . $rule_name );

			} else {
				$this->set_config( 'message.' . $rule_name, $message );
			}
		}

		return true;
	}

	public function get_default_message( $rule = null ) {
		if ( empty( $rule ) ) {
			return iwf_get_array( $this->config, 'message' );

		} else {
			return iwf_get_array( $this->config, 'message.' . $rule, $rule );
		}
	}

	public function set_config( $key, $value = null ) {
		if ( is_null( $value ) ) {
			iwf_delete_array( $this->config, $key );

		} else {
			iwf_set_array( $this->config, $key, $value );
		}
	}

	public function get_config( $key, $default = null ) {
		return iwf_get_array( $this->config, $key, $default );
	}

	public function create_callback_name( $callback ) {
		if ( is_string( $callback ) && strpos( $callback, '::' ) ) {
			$callback = explode( '::', $callback, 2 );
		}

		if ( is_array( $callback ) && reset( $callback ) == 'IWF_Validation' ) {
			$callback = $callback[1];
		}

		if ( is_string( $callback ) && is_callable( array( 'IWF_Validation', $callback ) ) ) {
			$callback_name = $callback;

		} else if ( is_callable( $callback ) ) {
			if ( is_array( $callback ) ) {
				$callback_name = ( is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0] ) . '::' . $callback[1];

			} else {
				$callback_name = $callback;
			}

		} else {
			$callback_name = '';
		}

		return $callback_name;
	}

	protected function convert_to_string( $value ) {
		$result = '';

		if ( is_array( $value ) ) {
			$text = '';

			foreach ( $value as $_value ) {
				if ( is_array( $_value ) ) {
					$_value = '(array)';

				} elseif ( is_object( $_value ) ) {
					$_value = '(object)';

				} elseif ( is_bool( $_value ) ) {
					$_value = $_value ? 'true' : 'false';
				}

				$text .= empty( $text ) ? $_value : ( ', ' . $_value );
			}

			$result = $text;

		} elseif ( is_bool( $value ) ) {
			$result = $value ? 'true' : 'false';

		} elseif ( is_object( $value ) ) {
			$result = method_exists( $value, '__toString' ) ? (string)$value : get_class( $value );

		} else {
			$result = (string)$value;
		}

		return $result;
	}

	/**
	 * Returns the instance of self
	 *
	 * @param null  $name
	 * @param array $config
	 * @return IWF_Validation
	 */
	public static function get_instance( $name = null, $config = array() ) {
		if ( is_array( $name ) && empty( $config ) ) {
			$config = $name;
			$name = '';
		}

		if ( !$name ) {
			$name = 'default';
		}

		if ( empty( self::$instances[$name] ) ) {
			self::$instances[$name] = new IWF_Validation( $config );
		}

		return self::$instances[$name];
	}

	/**
	 * Alias method of self::get_instance()
	 *
	 * @param null  $name
	 * @param array $config
	 * @return IWF_Validation
	 */
	public static function instance( $name = null, $config = array() ) {
		return self::get_instance( $name, $config );
	}

	public static function not_empty( $value ) {
		return !( $value === false || $value === null || $value === '' || $value === array() );
	}

	public static function not_empty_if( $value, $expr ) {
		return !self::not_empty( $expr ) || ( self::not_empty( $expr ) && self::not_empty( $value ) );
	}

	public static function valid_string( $value, $flags = array( 'alpha', 'utf8' ) ) {
		if ( !is_array( $flags ) ) {
			if ( $flags == 'alpha' ) {
				$flags = array( 'alpha', 'utf8' );

			} elseif ( $flags == 'alpha_numeric' ) {
				$flags = array( 'alpha', 'utf8', 'numeric' );

			} elseif ( $flags == 'url_safe' ) {
				$flags = array( 'alpha', 'numeric', 'dashes' );

			} elseif ( $flags == 'integer' or $flags == 'numeric' ) {
				$flags = array( 'numeric' );

			} elseif ( $flags == 'float' ) {
				$flags = array( 'numeric', 'dots' );

			} elseif ( $flags == 'all' ) {
				$flags = array( 'alpha', 'utf8', 'numeric', 'spaces', 'newlines', 'tabs', 'punctuation', 'dashes' );

			} else {
				return false;
			}
		}

		$pattern = !in_array( 'uppercase', $flags ) && in_array( 'alpha', $flags ) ? 'a-z' : '';
		$pattern .= !in_array( 'lowercase', $flags ) && in_array( 'alpha', $flags ) ? 'A-Z' : '';
		$pattern .= in_array( 'numeric', $flags ) ? '0-9' : '';
		$pattern .= in_array( 'spaces', $flags ) ? ' ' : '';
		$pattern .= in_array( 'newlines', $flags ) ? "\n" : '';
		$pattern .= in_array( 'tabs', $flags ) ? "\t" : '';
		$pattern .= in_array( 'dots', $flags ) && !in_array( 'punctuation', $flags ) ? '\.' : '';
		$pattern .= in_array( 'commas', $flags ) && !in_array( 'punctuation', $flags ) ? ',' : '';
		$pattern .= in_array( 'punctuation', $flags ) ? "\.,\!\?:;\&" : '';
		$pattern .= in_array( 'dashes', $flags ) ? '_\-' : '';
		$pattern = empty( $pattern ) ? '/^(.*)$/' : ( '/^([' . $pattern . '])+$/' );
		$pattern .= in_array( 'utf8', $flags ) ? 'u' : '';

		return preg_match( $pattern, $value ) > 0;
	}

	public static function valid_email( $value ) {
		return (bool)preg_match( "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $value );
	}

	public static function valid_url( $value ) {
		return (bool)preg_match( "/^(((http|ftp|https):\/\/){1}([a-zA-Z0-9_-]+)(\.[a-zA-Z0-9_-]+)+([\S,:\/\.\?=a-zA-Z0-9_-]+))$/ix", $value );
	}

	public static function min_length( $value, $length ) {
		return mb_strlen( $value ) >= $length;
	}

	public static function max_length( $value, $length ) {
		return mb_strlen( $value ) <= $length;
	}

	public static function exact_length( $value, $length ) {
		return mb_strlen( $value ) == $length;
	}

	public static function numeric_min( $value, $min ) {
		return floatval( $value ) >= floatval( $min );
	}

	public static function numeric_max( $value, $max ) {
		return floatval( $value ) <= floatval( $max );
	}

	public static function integer( $value ) {
		return (bool)preg_match( '/^[\-+]?[0-9]+$/', $value );
	}

	public static function decimal( $value ) {
		return (bool)preg_match( '/^[\-+]?[0-9]+\.[0-9]+$/', $value );
	}

	public static function match_value( $value, $compare, $strict = false ) {
		if ( $value === $compare || ( !$strict && $value == $compare ) ) {
			return true;
		}

		if ( is_array( $compare ) ) {
			foreach ( $compare as $_compare ) {
				if ( $value === $_compare || ( !$strict && $value == $_compare ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function match_pattern( $value, $pattern ) {
		return (bool)preg_match( $pattern, $value );
	}

	protected static function callback( $value, $callback, $attr = array() ) {
		if (
			!is_callable( $callback, false, $callable_name )
			|| (
				$callable_name != 'IWF_Validation::not_empty'
				&& $callable_name != 'IWF_Validation::not_empty_if'
				&& !self::not_empty( $value )
			)
		) {
			return true;
		}

		if ( !is_array( $attr ) ) {
			$attr = array( $attr );
		}

		array_unshift( $attr, $value );

		return (bool)call_user_func_array( $callback, $attr );
	}
}
