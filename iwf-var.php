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
	protected $data;

	protected $namespace = 'default';

	protected function __construct() {
	}

	public function __set( $key, $value ) {
		$this->data[$this->namespace][$key] = $value;
	}

	public function __get( $key ) {
		return isset( $this->data[$this->namespace][$key] ) ? $this->data[$this->namespace][$key] : null;
	}

	public function __isset( $key ) {
		return isset( $this->data[$this->namespace][$key] );
	}

	public function __unset( $key ) {
		unset( $this->data[$this->namespace][$key] );
	}

	/**
	 * Set the namespace
	 *
	 * @param string $namespace
	 * @return $this
	 */
	public function ns( $namespace ) {
		if ( empty( $namespace ) || !is_string( $namespace ) ) {
			return $this;
		}

		$this->namespace = (string)$namespace;

		if ( !isset( $this->data[$this->namespace] ) ) {
			$this->data[$this->namespace] = array();
		}

		return $this;
	}

	/**
	 * Return the current namespace
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Check whether the namespace is specified
	 *
	 * @param string $namespace
	 * @return bool
	 */
	public function is( $namespace ) {
		return $this->namespace === (string)$namespace;
	}

	/**
	 * Set the value with key
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @return $this
	 */
	public function set( $key, $value = null ) {
		iwf_set_array( $this->data[$this->namespace], $key, $value );

		return $this;
	}

	/**
	 * Get the value of key
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get( $key = null, $default = null ) {
		if ( is_null( $key ) ) {
			return isset( $this->data[$this->namespace] ) ? $this->data[$this->namespace] : null;

		} else {
			return iwf_get_array( $this->data[$this->namespace], $key, $default );
		}
	}

	/**
	 * Delete the value with key
	 *
	 * @param string $key
	 * @return $this
	 */
	public function delete( $key ) {
		iwf_delete_array( $this->data[$this->namespace], $key );

		return $this;
	}

	/**
	 * Check the key is exists.
	 *
	 * @param $key
	 * @return bool
	 */
	public function exists( $key ) {
		return iwf_has_array( $this->data[$this->namespace], $key, false );
	}

	/**
	 * Clear the data of current namespace
	 *
	 * @param bool $all_namespaces
	 * @return $this
	 */
	public function clear( $all_namespaces = false ) {
		if ( $all_namespaces ) {
			$this->data = array();
			$this->ns( $this->namespace );

		} else {
			$this->data[$this->namespace] = array();
		}

		return $this;
	}

	/**
	 * Get the instance of IWF_Var of specified namespace
	 *
	 * @param string $namespace
	 * @return IWF_Var
	 */
	public static function instance( $namespace = 'default' ) {
		static $_instance;

		if ( !isset( $_instance ) ) {
			$_instance = new IWF_Var();
		}

		if ( $namespace ) {
			$_instance->ns( $namespace );
		}

		return $_instance;
	}

	/**
	 * Set the value with key as specified namespace
	 *
	 * @param string|array $key
	 * @param mixed        $value
	 * @param string       $namespace If not specified, use the current namespace.
	 * @static
	 */
	public static function set_as( $key, $value = null, $namespace = null ) {
		if ( is_array( $key ) ) {
			if ( $value && is_null( $namespace ) ) {
				$namespace = $value;
				$value = null;
			}

			foreach ( $key as $_key => $_value ) {
				list( $_namespace, $_key ) = self::namespace_split( $_key );
				self::instance( $_namespace ? $_namespace : $namespace )->set( $_key, $_value );
			}

		} else {
			list( $_namespace, $key ) = self::namespace_split( $key );
			self::instance( $_namespace ? $_namespace : $namespace )->set( $key, $value );
		}
	}

	/**
	 * Get the value of key as specified namespace
	 *
	 * @param string|array $key
	 * @param mixed        $default
	 * @param string       $namespace If not specified, use the current namespace.
	 * @return mixed
	 * @static
	 */
	public static function get_as( $key = null, $default = null, $namespace = null ) {
		if ( is_array( $key ) ) {
			if ( $default && is_null( $namespace ) ) {
				$namespace = $default;
				$default = null;
			}

			$results = array();

			foreach ( $key as $_key => $_default ) {
				if ( is_int( $_key ) && ( is_string( $_default ) || is_numeric( $_default ) ) ) {
					$_key = $_default;
					$_default = null;
				}

				list( $_namespace, $_key ) = self::namespace_split( $_key );
				$_key_parts = explode( '.', $_key );
				$results[$_key_parts[count( $_key_parts ) - 1]] = self::instance( $_namespace ? $_namespace : $namespace )->get( $_key, $_default ? $_default : $default );
			}

			return $results;

		} else {
			list( $_namespace, $key ) = self::namespace_split( $key );

			return self::instance( $_namespace ? $_namespace : $namespace )->get( $key, $default );
		}
	}

	/**
	 * Delete the value with key as specified namespace
	 *
	 * @param string|array $key
	 * @param mixed        $value
	 * @param string       $namespace If not specified, use the current namespace.
	 * @static
	 */
	public static function delete_as( $key, $namespace = null ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $_key ) {
				list( $_namespace, $_key ) = self::namespace_split( $_key );
				self::instance( $_namespace ? $_namespace : $namespace )->delete( $_key );
			}

		} else {
			list( $_namespace, $key ) = self::namespace_split( $key );
			self::instance( $_namespace ? $_namespace : $namespace )->delete( $key );
		}
	}

	/**
	 * Check the key exists in the specified namespace
	 *
	 * @param string $key
	 * @param string $namespace If not specified, use the current namespace.
	 * @return bool
	 * @static
	 */
	public static function exists_as( $key, $namespace = null ) {
		list( $_namespace, $key ) = self::namespace_split( $key );

		return self::instance( $_namespace ? $_namespace : $namespace )->exists( $key );
	}

	/**
	 * Split the namespace and the key from the key
	 *
	 * @param string $key
	 * @return array The array has two elements. The first element is namespace and the second element is key.
	 */
	protected static function namespace_split( $key ) {
		$namespace = '';

		if ( ( $pos = strrpos( $key, '\\' ) ) !== false ) {
			$namespace = mb_substr( $key, 0, $pos );
			$key = mb_substr( $key, $pos + 1 );
		}

		return array( $namespace, $key );
	}
}