<?php
/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author         Masayuki Ietomi <jyokyoku@gmail.com>
 * @copyright      Copyright(c) 2011 Masayuki Ietomi
 * @link           http://inspire-tech.jp
 */

require_once dirname( __FILE__ ) . '/iwf-functions.php';

class IWF_Var {
	/**
	 * @var array
	 */
	protected $data;

	/**
	 * Constructor
	 */
	protected function __construct() {
	}

	/**
	 * @var IWF_Var $instance
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	protected static $instances;

	/**
	 * Get the instance of IWF_Var of specified namespace
	 *
	 * @param string $namespace
	 *
	 * @return IWF_Var_Instance
	 */
	public static function instance( $namespace = null ) {
		if ( empty( self::$instance ) ) {
			self::$instance = new IWF_Var();
		}

		if ( ! $namespace ) {
			$namespace = 'default';
		}

		if ( ! isset( self::$instances[ $namespace ] ) ) {
			self::$instances[ $namespace ] = new IWF_Var_Instance( self::$instance, $namespace );
		}

		return self::$instances[ $namespace ];
	}

	/**
	 * Set the value with key as specified namespace
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @param string $namespace If not specified, use the current namespace.
	 *
	 * @static
	 */
	public static function set_as( $key, $value = null, $namespace = null ) {
		if ( is_array( $key ) ) {
			if ( $value && is_null( $namespace ) ) {
				$namespace = $value;
				$value     = null;
			}

			foreach ( $key as $_key => $_value ) {
				self::set_as( $_key, $_value, $namespace );
			}

		} else {
			list( $_namespace, $key ) = self::namespace_split( $key );

			if ( $_namespace ) {
				$namespace = $_namespace;
			}

			if ( ! $namespace ) {
				$namespace = 'default';
			}

			iwf_set_array( self::$instance->data, $namespace . '.' . $key, $value );
		}
	}

	/**
	 * Get the value of key as specified namespace
	 *
	 * @param string|array $key
	 * @param mixed $default
	 * @param string $namespace If not specified, use the current namespace.
	 *
	 * @return mixed
	 * @static
	 */
	public static function get_as( $key = null, $default = null, $namespace = null ) {
		if ( is_array( $key ) ) {
			if ( $default && is_null( $namespace ) ) {
				$namespace = $default;
				$default   = null;
			}

			$results = array();

			foreach ( $key as $_key => $_default ) {
				if ( is_int( $_key ) && ( is_string( $_default ) || is_numeric( $_default ) ) ) {
					$_key     = $_default;
					$_default = null;
				}

				list( $_namespace, $_key ) = self::namespace_split( $_key );
				$_key_parts                                         = explode( '.', $_key );
				$results[ $_key_parts[ count( $_key_parts ) - 1 ] ] = self::get_as( $_key, $_default ? $_default : $default, $_namespace ? $_namespace : $namespace );
			}

			return $results;

		} else {
			list( $_namespace, $key ) = self::namespace_split( $key );

			if ( $_namespace ) {
				$namespace = $_namespace;
			}

			if ( ! $namespace ) {
				$namespace = 'default';
			}

			return iwf_get_array( self::$instance->data, $key ? $namespace . '.' . $key : $namespace, $default );
		}
	}

	/**
	 * Delete the value with key as specified namespace
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @param string $namespace If not specified, use the current namespace.
	 *
	 * @static
	 */
	public static function delete_as( $key, $namespace = null ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $_key ) {
				list( $_namespace, $_key ) = self::namespace_split( $_key );
				self::delete_as( $_key, $_namespace ? $_namespace : $namespace );
			}

		} else {
			list( $_namespace, $key ) = self::namespace_split( $key );

			if ( $_namespace ) {
				$namespace = $_namespace;
			}

			if ( ! $namespace ) {
				$namespace = 'default';
			}

			iwf_delete_array( self::$instance->data, $namespace . '.' . $key );
		}
	}

	/**
	 * Check the key exists in the specified namespace
	 *
	 * @param string $key
	 * @param string $namespace If not specified, use the current namespace.
	 *
	 * @return bool
	 * @static
	 */
	public static function exists_as( $key, $namespace = null ) {
		list( $_namespace, $key ) = self::namespace_split( $key );

		if ( $_namespace ) {
			$namespace = $_namespace;
		}

		if ( ! $namespace ) {
			$namespace = 'default';
		}

		return iwf_has_array( self::$instance->data, $namespace . '.' . $key );
	}

	/**
	 * Split the namespace and the key from the key
	 *
	 * @param string $key
	 *
	 * @return array The array has two elements. The first element is namespace and the second element is key.
	 */
	protected static function namespace_split( $key ) {
		$namespace = '';

		if ( ( $pos = strrpos( $key, '\\' ) ) !== false ) {
			$namespace = mb_substr( $key, 0, $pos );
			$key       = mb_substr( $key, $pos + 1 );
		}

		return array( $namespace, $key );
	}
}

class IWF_Var_Instance {
	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @var IWF_Var
	 */
	protected $var;

	/**
	 * Constructor
	 *
	 * @param IWF_Var $var
	 * @param $namespace
	 */
	public function __construct( IWF_Var $var, $namespace ) {
		$this->var       = $var;
		$this->namespace = $namespace;
	}

	/**
	 * Magic method
	 *
	 * @param $key
	 * @param $value
	 */
	public function __set( $key, $value ) {
		$this->var->set_as( $key, $value, $this->namespace );
	}

	/**
	 * Magic method
	 *
	 * @param $key
	 */
	public function __get( $key ) {
		return $this->var->get_as( $key, null, $this->namespace );
	}

	/**
	 * Magic method
	 *
	 * @param $key
	 */
	public function __isset( $key ) {
		return $this->var->exists_as( $key, $this->namespace );
	}

	/**
	 * Magic method
	 *
	 * @param $key
	 */
	public function __unset( $key ) {
		$this->var->delete_as( $key, $this->namespace );
	}

	/**
	 * Set the value with key
	 *
	 * @param $key
	 * @param $value
	 */
	public function set( $key, $value ) {
		$this->var->set_as( $key, $value, $this->namespace );
	}

	/**
	 * Get the value of key
	 *
	 * @param $key
	 */
	public function get( $key = null, $default = null ) {
		return $this->var->get_as( $key, $default, $this->namespace );
	}

	/**
	 * Check the key exists
	 *
	 * @param $key
	 */
	public function exists( $key ) {
		return $this->var->exists_as( $key, $this->namespace );
	}

	/**
	 * Delete the value with key
	 *
	 * @param $key
	 */
	public function delete( $key ) {
		$this->var->delete_as( $key, $this->namespace );
	}
}
