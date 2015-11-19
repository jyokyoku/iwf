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
require_once dirname( __FILE__ ) . '/iwf-functions.php';
require_once dirname( __FILE__ ) . '/iwf-form.php';

class IWF_Validation {
	/**
	 * All the instances of myself
	 *
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Returns the instance of self
	 *
	 * @param string $name
	 * @param array $config
	 *
	 * @return IWF_Validation
	 */
	public static function get_instance( $name = null, $config = array() ) {
		if ( is_array( $name ) && empty( $config ) ) {
			$config = $name;
			$name   = '';
		}

		if ( ! $name ) {
			$name = 'default';
		}

		if ( empty( self::$instances[ $name ] ) ) {
			self::$instances[ $name ] = new IWF_Validation( $config );
		}

		return self::$instances[ $name ];
	}

	/**
	 * Alias method of self::get_instance()
	 *
	 * @param string $name
	 * @param array $config
	 *
	 * @return IWF_Validation
	 */
	public static function instance( $name = null, $config = array() ) {
		return self::get_instance( $name, $config );
	}

	/**
	 * Delete the instance
	 *
	 * @param string|IWF_Validation $instance
	 *
	 * @return bool
	 */
	public static function destroy( $instance ) {
		if ( is_a( $instance, 'IWF_Validation' ) ) {
			$instance = array_search( $instance, self::$instances );
		}

		if ( ( is_string( $instance ) || is_numeric( $instance ) ) && isset( self::$instances[ $instance ] ) ) {
			unset( self::$instances[ $instance ] );

			return true;
		}

		return false;
	}

	/**
	 * Check whether the value is not empty
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function not_empty( $value ) {
		return ! ( $value === false || $value === null || $value === '' || $value === array() );
	}

	/**
	 * Check whether the value is not empty when specified the value is not empty
	 *
	 * @param string $value
	 * @param mixed $expr
	 * @param mixed $expr_equal
	 * @param bool $strict
	 *
	 * @return bool
	 */
	public static function not_empty_if( $value, $expr, $expr_equal = null, $strict = false ) {
		return (
			! self::not_empty( $expr )
			|| ( is_null( $expr_equal ) && self::not_empty( $expr ) && self::not_empty( $value ) )
			|| ( ! is_null( $expr_equal ) && ( ( $strict && $expr !== $expr_equal ) || ( ! $strict && $expr != $expr_equal ) ) )
			|| ( ! is_null( $expr_equal ) && ( ( $strict && $expr === $expr_equal ) || ( ! $strict && $expr == $expr_equal ) ) && self::not_empty( $value ) )
		);
	}

	/**
	 * Check whether the value is not empty when specified the value is empty
	 *
	 * @param string $value
	 * @param mixed $expr
	 * @param mixed $expr_not_equal
	 * @param bool $strict
	 *
	 * @return bool
	 */
	public static function not_empty_unless( $value, $expr, $expr_not_equal = null, $strict = false ) {
		return (
			self::not_empty( $expr )
			|| ( is_null( $expr_not_equal ) && ! self::not_empty( $expr ) && self::not_empty( $value ) )
			|| ( ! is_null( $expr_not_equal ) && ( ( $strict && $expr === $expr_not_equal ) || ( ! $strict && $expr == $expr_not_equal ) ) )
			|| ( ! is_null( $expr_not_equal ) && ( ( $strict && $expr !== $expr_not_equal ) || ( ! $strict && $expr != $expr_not_equal ) ) && self::not_empty( $value ) )
		);
	}

	/**
	 * Check whether the value is matched the rules
	 *
	 * @param string $value
	 * @param array $flags
	 *
	 * @return bool
	 */
	public static function valid_string( $value, $flags = array( 'alpha', 'utf8' ) ) {
		if ( ! is_array( $flags ) ) {
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

		$pattern = ! in_array( 'uppercase', $flags ) && in_array( 'alpha', $flags ) ? 'a-z' : '';
		$pattern .= ! in_array( 'lowercase', $flags ) && in_array( 'alpha', $flags ) ? 'A-Z' : '';
		$pattern .= in_array( 'numeric', $flags ) ? '0-9' : '';
		$pattern .= in_array( 'spaces', $flags ) ? ' ' : '';
		$pattern .= in_array( 'newlines', $flags ) ? "\n" : '';
		$pattern .= in_array( 'tabs', $flags ) ? "\t" : '';
		$pattern .= in_array( 'dots', $flags ) && ! in_array( 'punctuation', $flags ) ? '\.' : '';
		$pattern .= in_array( 'commas', $flags ) && ! in_array( 'punctuation', $flags ) ? ',' : '';
		$pattern .= in_array( 'punctuation', $flags ) ? "\.,\!\?:;\&" : '';
		$pattern .= in_array( 'dashes', $flags ) ? '_\-' : '';
		$pattern = empty( $pattern ) ? '/^(.*)$/' : ( '/^([' . $pattern . '])+$/' );
		$pattern .= in_array( 'utf8', $flags ) ? 'u' : '';

		return preg_match( $pattern, $value ) > 0;
	}

	/**
	 * Check whether the value is valid the e-mail address
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function valid_email( $value ) {
		return (bool) preg_match( "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $value );
	}

	/**
	 * Check whether the value is valid the URL
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function valid_url( $value ) {
		return (bool) preg_match( "/^(((http|ftp|https):\/\/){1}([a-zA-Z0-9_-]+)(\.[a-zA-Z0-9_-]+)+([\S,:\/\.\?=a-zA-Z0-9_-]+))$/ix", $value );
	}

	/**
	 * Check whether the length of value is greater than specified the length
	 *
	 * @param string $value
	 * @param int $length
	 *
	 * @return bool
	 */
	public static function min_length( $value, $length ) {
		return mb_strlen( $value ) >= $length;
	}

	/**
	 * Check whether the length of value is less than specified the length
	 *
	 * @param string $value
	 * @param int $length
	 *
	 * @return bool
	 */
	public static function max_length( $value, $length ) {
		return mb_strlen( $value ) <= $length;
	}

	/**
	 * Check whether the length of value is equal to specified the length
	 *
	 * @param string $value
	 * @param int $length
	 *
	 * @return bool
	 */
	public static function exact_length( $value, $length ) {
		return mb_strlen( $value ) == $length;
	}

	/**
	 * Check whether the value is greater than specified the count
	 *
	 * @param string $value
	 * @param int $min
	 *
	 * @return bool
	 */
	public static function numeric_min( $value, $min ) {
		return floatval( $value ) >= floatval( $min );
	}

	/**
	 * Check whether the value is less than specified the count
	 *
	 * @param string $value
	 * @param int $max
	 *
	 * @return bool
	 */
	public static function numeric_max( $value, $max ) {
		return floatval( $value ) <= floatval( $max );
	}

	/**
	 * Check whether the value is integer
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function integer( $value ) {
		return (bool) preg_match( '/^[\-+]?[0-9]+$/', $value );
	}

	/**
	 * Check whether the value is numeric
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function decimal( $value ) {
		return (bool) preg_match( '/^[\-+]?[0-9]+(?:\.[0-9]+)?$/', $value );
	}

	/**
	 * Check whether the value is matched the specified value
	 *
	 * @param string $value
	 * @param string $compare
	 * @param bool $strict
	 *
	 * @return bool
	 */
	public static function match_value( $value, $compare, $strict = false ) {
		if ( $value === $compare || ( ! $strict && $value == $compare ) ) {
			return true;
		}

		if ( is_array( $compare ) ) {
			foreach ( $compare as $_compare ) {
				if ( $value === $_compare || ( ! $strict && $value == $_compare ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check whether the value is matched the regex pattern
	 *
	 * @param string $value
	 * @param string $pattern
	 *
	 * @return bool
	 */
	public static function match_pattern( $value, $pattern ) {
		return (bool) preg_match( $pattern, $value );
	}

	/**
	 * Process the callback function
	 *
	 * @param string|array $value
	 * @param callback $callback
	 * @param array $attr
	 *
	 * @return bool
	 */
	protected static function callback( $value, $callback, $attr = array() ) {
		if (
			! is_callable( $callback, false, $callable_name )
			|| (
				$callable_name != 'IWF_Validation::not_empty'
				&& $callable_name != 'IWF_Validation::not_empty_if'
				&& $callable_name != 'IWF_Validation::not_empty_unless'
				&& ! self::not_empty( $value )
			)
		) {
			return true;
		}

		if ( ! is_array( $attr ) ) {
			$attr = array( $attr );
		}

		array_unshift( $attr, $value );

		return (bool) call_user_func_array( $callback, $attr );
	}

	/**
	 * The form field's prefix
	 *
	 * @var string
	 */
	protected $form_field_prefix;

	/**
	 * Current the field name
	 *
	 * @var string
	 */
	protected $current_field;

	/**
	 * Current the rule name
	 *
	 * @var string
	 */
	protected $current_rule;

	/**
	 * The valid values
	 *
	 * @var array
	 */
	protected $validated = array();

	/**
	 * The errors
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Registered the fields
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Registered the forms
	 *
	 * @var array
	 */
	protected $forms = array();

	/**
	 * Registered the rules
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 * Registered the messages
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Default error messages of validation rules
	 *
	 * @var array
	 */
	protected $default_messages = array();

	/**
	 * The error open tag
	 *
	 * @var string
	 */
	protected $error_open = '';

	/**
	 * The error close tag
	 *
	 * @var string
	 */
	protected $error_close = '';

	/**
	 * The error form class
	 *
	 * @var string
	 */
	protected $error_form_class = '';

	/**
	 * The data for validation
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	protected function __construct( $config = array() ) {
		$config = wp_parse_args( $config, array(
			'messages'          => array(
				'not_empty'     => _x( 'The field :label is required and must contain value.', 'not_empty', 'iwf' ),
				'not_empty_if'  => _x( 'The field :label is required and must contain value.', 'not_empty_if', 'iwf' ),
				'valid_string'  => __( 'The valid string rule :rule(:param:1) failed for field :label.', 'iwf' ),
				'valid_email'   => __( 'The field :label must contain a valid email address.', 'iwf' ),
				'valid_url'     => __( 'The field :label must contain a valid URL.', 'iwf' ),
				'min_length'    => __( 'The field :label may not contain more than :param:1 characters.', 'iwf' ),
				'max_length'    => __( 'The field :label has to contain at least :param:1 characters.', 'iwf' ),
				'exact_length'  => __( 'The field :label must equal :param:1 characters.', 'iwf' ),
				'numeric_min'   => __( 'The minimum numeric value of :label must be :param:1', 'iwf' ),
				'numeric_max'   => __( 'The maximum numeric value of :label must be :param:1', 'iwf' ),
				'integer'       => __( 'The value of :label must be integer.', 'iwf' ),
				'decimal'       => __( 'The value of :label must be decimal.', 'iwf' ),
				'match_value'   => __( 'The field :label must contain the value :param:1.', 'iwf' ),
				'match_pattern' => __( 'The field :label must match the pattern :param:1.', 'iwf' )
			),
			'error_open'        => '<span class="error">',
			'error_close'       => '</span>',
			'error_form_class'  => 'error',
			'form_field_prefix' => '',
		) );

		$this->set_form_field_prefix( $config['form_field_prefix'] );
		$this->set_default_message( $config['messages'] );
		$this->set_error_open( $config['error_open'] );
		$this->set_error_close( $config['error_close'] );
		$this->set_error_form_class( $config['error_form_class'] );
	}

	/**
	 * Magic method
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get( $property ) {
		return $this->{$property};
	}

	/**
	 * Set the form field's prefix
	 *
	 * @param string $form_field_prefix
	 */
	public function set_form_field_prefix( $form_field_prefix ) {
		$this->form_field_prefix = $form_field_prefix;
	}

	/**
	 * Set the error open tag
	 *
	 * @param string $error_open
	 */
	public function set_error_open( $error_open ) {
		$this->error_open = $error_open;
	}

	/**
	 * Set the error close tag
	 *
	 * @param string $error_close
	 */
	public function set_error_close( $error_close ) {
		$this->error_close = $error_close;
	}

	/**
	 * Set the error form class
	 *
	 * @param string $class
	 */
	public function set_error_form_class( $class ) {
		$this->error_form_class = $class;
	}

	/**
	 * Add the field and the form structures
	 *
	 * @param string $field
	 * @param string $label
	 * @param string $type
	 * @param string|array $value
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function add_field( $field, $label = null, $type = null, $value = null, $attributes = array() ) {
		if ( ! array_key_exists( $field, $this->fields ) ) {
			if ( ! $label ) {
				$label = $field;
			}

			$this->fields[ $field ] = $label;
			$this->current_field    = $field;
		}

		$this->forms[ $field ] = compact( 'type', 'value', 'attributes' );

		return $this;
	}

	/**
	 * Add the validation rule to the current field
	 *
	 * @param callback $rule
	 *
	 * @return $this|bool
	 */
	public function add_rule( $rule ) {
		if ( ! $this->current_field ) {
			trigger_error( 'There is no field that is currently selected.', E_USER_WARNING );

			return false;
		}

		if ( is_string( $rule ) && is_callable( array( 'IWF_Validation', $rule ) ) ) {
			$rule = array( 'IWF_Validation', $rule );
		}

		if ( ! is_callable( $rule ) ) {
			trigger_error( 'The rule is not a correct validation rule.', E_USER_WARNING );

			return false;
		}

		$rule_name = $this->create_callback_name( $rule );

		$args = func_get_args();
		$args = array_splice( $args, 1 );
		array_unshift( $args, $rule );

		$this->current_rule                                = $rule_name;
		$this->rules[ $this->current_field ][ $rule_name ] = $args;

		return $this;
	}

	/**
	 * Add the validation messages to current validation rule
	 *
	 * @param string $message
	 *
	 * @return $this|bool
	 */
	public function set_message( $message ) {
		if ( ! $this->current_field ) {
			trigger_error( 'There is no field that is currently selected.', E_USER_WARNING );

			return false;
		}

		if ( ! $this->current_rule ) {
			trigger_error( 'There is no rule that is currently selected.', E_USER_WARNING );

			return false;
		}

		if ( is_null( $message ) || $message === false ) {
			unset( $this->messages[ $this->current_field ][ $this->current_rule ] );

		} else {
			$this->messages[ $this->current_field ][ $this->current_rule ] = $message;
		}

		return $this;
	}

	/**
	 * Render the form field
	 *
	 * @param string $field
	 * @param string $type
	 * @param string|array $value
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function form_field( $field, $type = null, $value = null, $attributes = array() ) {
		if ( ! isset( $this->forms[ $field ] ) ) {
			return null;
		}

		$form = $this->forms[ $field ];

		foreach ( array( 'type', 'value', 'attributes' ) as $varname ) {
			if ( ${$varname} ) {
				$form[ $varname ] = ${$varname};
			}
		}

		$value = $this->get_data( $field );

		if ( ! method_exists( 'IWF_Form', $form['type'] ) ) {
			return null;
		}

		if ( ! is_null( $value ) ) {
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

		if ( $this->error_message( $field ) ) {
			IWF_Tag_Element_Node::add_class( $form['attributes'], $this->error_form_class );
		}

		return call_user_func( array( 'IWF_Form', $form['type'] ), $this->form_field_prefix . $field, $form['value'], $form['attributes'] );
	}

	/**
	 * Return the valid value of the field
	 *
	 * @param string|array $field
	 *
	 * @return mixed
	 */
	public function validated( $field = null ) {
		if ( func_num_args() > 1 ) {
			$field = func_get_args();
		}

		if ( ! $field ) {
			return $this->validated;

		} else if ( is_array( $field ) ) {
			$validated_values = array();

			foreach ( $field as $_field ) {
				if ( ! $_field || ! isset( $this->validated[ $_field ] ) ) {
					continue;
				}

				$validated_values[ $_field ] = $this->validated[ $_field ];
			}

			return $validated_values;

		} else if ( is_string( $field ) && isset( $this->validated[ $field ] ) ) {
			return $this->validated[ $field ];
		}

		return false;
	}

	/**
	 * Return the validation fields from all of the valid data
	 *
	 * @return string
	 */
	public function validated_hidden_fields() {
		$hidden = array();

		foreach ( $this->validated() as $field_name => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $_key => $_value ) {
					$hidden[] = IWF_Form::hidden( $this->form_field_prefix . $field_name . '[' . $_key . ']', iwf_convert( $_value, 'string' ) );
				}

			} else {
				$hidden[] = IWF_Form::hidden( $this->form_field_prefix . $field_name, $value );
			}
		}

		return implode( "\n", $hidden );
	}

	/**
	 * Return the error message of the field with the open tag and the close tag
	 *
	 * @param string $field
	 * @param string $open
	 * @param string $close
	 *
	 * @return string
	 */
	public function error( $field = null, $open = null, $close = null ) {
		$error_messages = $this->error_message( $field );

		if ( ! $error_messages ) {
			return $error_messages;
		}

		if ( ! is_array( $error_messages ) ) {
			$error_messages = array( $error_messages );
		}

		$open   = is_null( $open ) ? $this->error_open : $open;
		$close  = is_null( $close ) ? $this->error_close : $close;
		$errors = array();

		foreach ( $error_messages as $error_message ) {
			$errors[] = $open . $error_message . $close;
		}

		return ! empty( $field ) && ! is_array( $field ) ? reset( $errors ) : $errors;
	}

	/**
	 * Return the error message of the field
	 *
	 * @param string $field
	 *
	 * @return string|bool
	 */
	public function error_message( $field = null ) {
		if ( func_num_args() > 1 ) {
			$field = func_get_args();
		}

		if ( ! $field ) {
			return array_map( create_function( '$a', 'return (string)$a;' ), $this->errors );

		} else if ( is_array( $field ) ) {
			$errors = array();

			foreach ( $field as $_field ) {
				if ( ! $_field || ! isset( $this->errors[ $_field ] ) ) {
					continue;
				}

				$errors[] = (string) $this->errors[ $_field ];
			}

			return $errors;

		} else if ( isset( $this->errors[ $field ] ) ) {
			return (string) $this->errors[ $field ];
		}

		return false;
	}

	/**
	 * Return the validation result
	 *
	 * @return bool
	 */
	public function is_valid() {
		return count( $this->errors ) == 0;
	}

	/**
	 * Get the data for validation
	 *
	 * @param int|string $key
	 *
	 * @return mixed
	 */
	public function get_data( $key = null ) {
		if ( ! $key ) {
			return $this->data;

		} else {
			if ( strpos( $key, $this->form_field_prefix ) !== 0 ) {
				$key = $this->form_field_prefix . $key;
			}

			return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
		}
	}

	/**
	 * Return the default message of specified the rule
	 *
	 * @param string $rule
	 *
	 * @return array|string
	 */
	public function get_default_message( $rule = null ) {
		if ( empty( $rule ) ) {
			return $this->default_messages;

		} else {
			$rule = preg_replace( '|\([\d]+\)$|', '', $rule );

			return ! empty( $this->default_messages[ $rule ] ) ? $this->default_messages[ $rule ] : $rule;
		}
	}

	/**
	 * Set the valid value of the field
	 *
	 * @param string $field
	 * @param string|array $value
	 */
	public function set_validated( $field, $value = null ) {
		if ( is_array( $field ) ) {
			foreach ( $field as $_field => $_value ) {
				$this->set_validated( $_field, $_value );
			}

		} else {
			$this->validated[ $field ] = $value;
		}
	}

	/**
	 * Set the error message to the field
	 *
	 * @param string $field
	 * @param string $message
	 */
	public function set_error( $field, $message = null ) {
		if ( is_array( $field ) ) {
			foreach ( $field as $_field => $_message ) {
				$this->set_error( $_field, $_message );
			}

		} else {
			$this->errors[ $field ] = $message;
		}
	}

	/**
	 * Set the data for validation
	 *
	 * @param int|string|array $key
	 * @param mixed $data
	 */
	public function set_data( $key, $data = null ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $_key => $value ) {
				$this->set_data( $_key, $value );
			}

		} else {
			if ( $this->form_field_prefix && strpos( $key, $this->form_field_prefix ) !== 0 ) {
				$key = $this->form_field_prefix . $key;
			}

			$this->data[ $key ] = $data;
		}
	}

	/**
	 * Set the default message for the validation rule
	 *
	 * @param string $rule
	 * @param string $message
	 *
	 * @return bool
	 */
	public function set_default_message( $rule, $message = null ) {
		if ( is_array( $rule ) && empty( $message ) ) {
			foreach ( $rule as $_rule => $message ) {
				if ( is_int( $_rule ) ) {
					continue;
				}

				$this->set_default_message( $_rule, $message );
			}

		} else {
			if ( ! $rule_name = $this->create_callback_name( $rule ) ) {
				return false;
			}

			if ( is_null( $message ) || $message === false ) {
				unset( $this->default_messages[ $rule_name ] );

			} else {
				$this->default_messages[ $rule_name ] = $message;
			}
		}

		return true;
	}

	/**
	 * Validate the specified field
	 *
	 * @param string $field
	 * @param array $data
	 *
	 * @return mixed|IWF_Validation_Error
	 */
	public function validate_field( $field, array $data = null ) {
		if ( empty( $data ) ) {
			$this->set_data( $data );
		}

		$value = $this->get_data( $field );

		if ( is_array( $value ) ) {
			$value = array_filter( $value );
		}

		if ( empty( $this->rules[ $field ] ) ) {
			return $value;
		}

		foreach ( $this->rules[ $field ] as $rule => $params ) {
			$function = array_shift( $params );
			$args     = $params;

			foreach ( $args as $i => $arg ) {
				if ( is_string( $arg ) && ( strpos( $arg, ':' ) === 0 || preg_match( '|^%.+?%$|', $arg ) ) ) {
					$data_field = ( strpos( $arg, ':' ) === 0 ) ? substr( $arg, 1 ) : trim( $arg, '%' );

					switch ( $data_field ) {
						case 'validator':
							$args[ $i ] = $this;
							break;

						default:
							$args[ $i ] = $this->get_data( $data_field );
					}
				}
			}

			$result = self::callback( $value, $function, $args );

			if ( $result === false ) {
				return new IWF_Validation_Error( $this, $field, $rule, $value, $params );

			} else if ( $result !== true ) {
				$value = $result;
			}
		}

		return $value;
	}

	/**
	 * Process the validation
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function run( $data = array() ) {
		$this->errors = $this->validated = array();

		if ( ! empty( $data ) ) {
			$this->set_data( $data );

		} else if ( empty( $this->data ) && iwf_request_is( 'post' ) ) {
			$this->set_data( $_POST );
		}

		foreach ( $this->fields as $field => $label ) {
			$result = $this->validate_field( $field );

			if ( is_a( $result, 'IWF_Validation_Error' ) ) {
				$this->set_error( $field, $result );
				continue;
			}

			$this->set_validated( $field, $result );
		}

		return $this->is_valid();
	}

	/**
	 * Create the name of callback function
	 *
	 * @param callback $callback
	 *
	 * @return string
	 */
	protected function create_callback_name( $callback ) {
		$callable_name = null;

		if ( is_string( $callback ) && method_exists( 'IWF_Validation', $callback ) ) {
			$callback = array( 'IWF_Validation', $callback );
		}

		if ( ! is_callable( $callback, null, $callable_name ) ) {
			return false;
		}

		if ( strpos( $callable_name, 'IWF_Validation::' ) ) {
			$callable_name = str_replace( 'IWF_Validation::', '', $callable_name );
		}

		if ( ! empty( $this->current_field ) && ! empty( $this->rules[ $this->current_field ] ) ) {
			$same_rules = array();

			foreach ( array_keys( $this->rules[ $this->current_field ] ) as $rule ) {
				if ( preg_match( '|^' . $callable_name . '(?:\(([0-9]+?)\))?$|', $rule, $matches ) ) {
					$same_rules[] = array( $rule, ! empty( $matches[1] ) ? $matches[1] : 1 );
				}
			}

			if ( $same_rules ) {
				usort( $same_rules, create_function( '$a, $b', 'return (int)$a[1] < (int)$b[1];' ) );
				$callable_name = $callable_name . '(' . ( (int) $same_rules[0][1] + 1 ) . ')';
			}
		}

		return $callable_name;
	}
}

class IWF_Validation_Error {
	protected $validation;

	protected $field;

	protected $label;

	protected $rule;

	protected $value;

	protected $params;

	/**
	 * IWF_Validation_Error constructor.
	 *
	 * @param IWF_Validation $validation
	 * @param string $field
	 * @param string $rule
	 * @param mixed $value
	 * @param array $params
	 */
	public function __construct( IWF_Validation $validation, $field, $rule, $value, $params ) {
		$this->validation = $validation;
		$this->field      = $field;
		$this->rule       = $rule;
		$this->params     = $params;
	}

	public function __toString() {
		return $this->get_message();
	}

	/**
	 * Get the error message
	 *
	 * @return string
	 */
	public function get_message() {
		$message = isset( $this->validation->messages[ $this->field ][ $this->rule ] )
			? $this->validation->messages[ $this->field ][ $this->rule ]
			: $this->validation->get_default_message( $this->rule );

		$value   = iwf_convert( $this->value, 's' );
		$label   = isset( $this->validation->fields[ $this->field ] ) ? $this->validation->fields[ $this->field ] : '';
		$find    = array( ':field', '%field%', ':label', '%label%', ':value', '%value%', ':rule', '%rule%' );
		$replace = array( $this->field, $this->field, $label, $label, $value, $value, $this->rule, $this->rule );

		foreach ( $this->params as $param_key => $param_value ) {
			$param_value = iwf_convert( $param_value, 's' );

			$find[]    = ':param:' . ( $param_key + 1 );
			$replace[] = $param_value;

			$find[]    = '%param:' . ( $param_key + 1 ) . '%';
			$replace[] = $param_value;
		}

		return str_replace( $find, $replace, $message );
	}
}