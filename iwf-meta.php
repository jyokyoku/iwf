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
require_once dirname( __FILE__ ) . '/iwf-meta.php';

class IWF_Meta {
	protected static $types = array( 'post', 'user', 'option', 'comment' );

	public static function post( $post, $key, $attr = array() ) {
		return self::get( 'post', $post, $key, $attr );
	}

	public static function post_iteration( $post, $key, $min, $max, $attr = array() ) {
		return self::iterate( 'post', $key, $min, $max, $post, $attr );
	}

	public static function current_post( $key, $attr = array() ) {
		global $post;

		return self::post( $post, $key, $attr );
	}

	public static function current_post_iteration( $key, $min, $max, $attr = array() ) {
		return self::iterate( 'post', $key, $min, $max, null, $attr );
	}

	public static function update_post( $post, $key, $value = null ) {
		return self::update( 'post', $post, $key, $value );
	}

	public static function update_current_post( $key, $value = null ) {
		global $post;

		return self::update_post( $post, $key, $value );
	}

	public static function user( $user, $key, $attr = array() ) {
		return self::get( 'user', $user, $key, $attr );
	}

	public static function user_iteration( $user, $key, $min, $max, $attr = array() ) {
		return self::iterate( 'user', $key, $min, $max, $user, $attr );
	}

	public static function current_user( $key, $attr = array() ) {
		return self::user( get_current_user_id(), $key, $attr );
	}

	public static function current_user_iteration( $key, $min, $max, $attr = array() ) {
		return self::iterate( 'user', $key, $min, $max, null, $attr );
	}

	public static function update_user( $user, $key, $value = null ) {
		return self::update( 'user', $user, $key, $value );
	}

	public static function update_update_user( $key, $value = null ) {
		return self::update_post( get_current_user_id(), $key, $value );
	}

	public static function comment( $comment, $key, $attr = array() ) {
		return self::get( 'comment', $comment, $key, $attr );
	}

	public static function comment_iteration( $comment, $key, $min, $max, $attr = array() ) {
		return self::iterate( 'comment', $key, $min, $max, $comment, $attr );
	}

	public static function current_comment( $key, $attr = array() ) {
		global $comment;

		return self::comment( $comment, $key, $attr );
	}

	public static function current_comment_iteration( $key, $min, $max, $attr = array() ) {
		return self::iterate( 'comment', $key, $min, $max, null, $attr );
	}

	public static function update_comment( $comment, $key, $value = null ) {
		return self::update( 'comment', $comment, $key, $value );
	}

	public static function update_current_comment( $key, $value = null ) {
		global $comment;

		return self::update_comment( $comment, $key, $value );
	}

	public static function option( $key, $attr = array() ) {
		if ( is_array( $key ) ) {
			$values = array();
			$value_only = iwf_check_value_only( $key );

			foreach ( $key as $_key => $_attr ) {
				if ( $value_only && ( is_string( $_attr ) || is_numeric( $_attr ) ) ) {
					$_key = $_attr;
					$_attr = array();
				}

				$_key_parts = explode( '.', $_key );
				$values[$_key_parts[count( $_key_parts ) - 1]] = iwf_get_option( $_key, $_attr );
			}

			return $values;

		} else {
			if ( strpos( $key, '.' ) !== false ) {
				list( $option_set, $key ) = explode( '.', $key, 2 );

				if ( !$option_set || !$key ) {
					return $default;
				}

				$option = get_option( $option_set );

				if ( empty( $option ) || !is_array( $option ) ) {
					$option = array();
				}

				$value = iwf_get_array( $option, $key );

			} else {
				$value = get_option( $key );
			}

			if ( $value && !is_string( $value ) ) {
				return $value;
			}

			if ( is_string( $attr ) || is_numeric( $attr ) ) {
				$attr = array( 'default' => $attr );
			}

			return self::filter( $value, $attr );
		}
	}

	public static function option_iteration( $key, $min, $max, $attr = array() ) {
		return self::iterate( 'option', $key, $min, $max, null, $attr );
	}

	public static function update_option( $key, $value = null ) {
		if ( is_array( $key ) ) {
			if ( iwf_check_value_only( $key ) ) {
				return false;
			}

			$results = array();

			foreach ( $key as $_key => $_value ) {
				$results[$_key] = self::update_option( $_key, $_value );
			}

			return $results;

		} else {
			if ( strpos( $key, '.' ) !== false ) {
				list( $option_set, $key ) = explode( '.', $key, 2 );

				if ( !$option_set || !$key ) {
					return false;
				}

				$option = get_option( $option_set );

				if ( empty( $option ) || !is_array( $option ) ) {
					$option = array();
				}

				iwf_set_array( $option, $key, $value );

				return update_option( $option_set, $option );

			} else {
				return update_option( $key, $value );
			}
		}
	}

	protected static function filter( $value, $attr = array() ) {
		return iwf_filter( $value, $attr );
	}

	protected static function get_object_id( $type, $object ) {
		$id = null;

		switch ( $type ) {
			case 'post':
				if ( is_object( $object ) && isset( $object->ID ) ) {
					$id = $object->ID;

				} else if ( is_numeric( $object ) ) {
					$id = (int)$object;
				}

				break;

			case 'user':
				if ( is_object( $object ) && isset( $object->ID ) ) {
					$id = $object->ID;

				} else if ( is_numeric( $object ) ) {
					$id = (int)$object;
				}

				break;

			case 'comment':
				if ( is_object( $object ) && isset( $object->commnet_ID ) ) {
					$id = $object->comment_ID;

				} else if ( is_numeric( $object ) ) {
					$id = (int)$object;
				}

				break;
		}

		return $id;
	}

	protected static function get_object_data( $type, $id ) {
		$data = array();
		$id = intval( $id );

		switch ( $type ) {
			case 'post':
				if ( $post = get_post( $id ) ) {
					$data = get_object_vars( $post );
				}

				break;

			case 'user':
				$user = get_userdata( $id );

				if ( $user && !is_wp_error( $user ) ) {
					$data = get_object_vars( $user );
				}

				break;

			case 'comment':
				if ( $comment = get_comment( $id ) ) {
					$data = get_object_vars( $comment );
				}

				break;
		}

		return $data;
	}

	protected static function get_meta_data( $type, $id, $key ) {
		$value = null;

		switch ( $type ) {
			case 'post':
				$value = get_post_meta( $id, $key, true );
				break;

			case 'user':
				$value = get_user_meta( $id, $key, true );
				break;

			case 'comment':
				$value = get_comment_meta( $id, $key, true );
				break;
		}

		return $value;
	}

	protected static function update_meta_data( $type, $id, $key, $value ) {
		$result = null;

		switch ( $type ) {
			case 'post':
				$result = update_post_meta( $id, $key, $value );
				break;

			case 'user':
				$result = update_user_meta( $id, $key, $value );
				break;

			case 'comment':
				$result = update_comment_meta( $id, $key, $value );
				break;
		}

		return $result;
	}

	protected static function get( $type, $object, $key, $attr = array() ) {
		if ( is_array( $key ) ) {
			$value_only = iwf_check_value_only( $key );
			$result = array();

			foreach ( $key as $_key => $_attr ) {
				if ( $value_only && ( is_string( $_attr ) || is_numeric( $_attr ) ) ) {
					$_key = $_attr;
					$_attr = array();
				}

				$result[$_key] = self::get( $type, $object, $_key, $_attr );
			}

			return $result;

		} else {
			if ( !$id = self::get_object_id( $type, $object ) ) {
				return false;
			}

			$primary_data = self::get_object_data( $type, $id );

			if ( is_scalar( $attr ) ) {
				$attr = array( 'default' => $attr );
			}

			$value = null;

			if ( $id && $primary_data ) {
				if ( strpos( $key, '.' ) !== false ) {
					list( $base_key, $key ) = explode( '.', $key, 2 );
					$value = self::get_meta_data( $type, $id, $base_key );

					if ( is_array( $value ) ) {
						$value = iwf_get_array( $value, $key );
					}

				} else {
					$value = array_key_exists( $key, $primary_data ) ? $primary_data[$key] : self::get_meta_data( $type, $id, $key );
				}
			}

			if ( !is_scalar( $value ) ) {
				return $value;
			}

			return self::filter( $value, $attr );
		}
	}

	protected static function iterate( $type, $key, $min, $max, $object = null, $attr = array() ) {
		$values = array();

		if ( !method_exists( 'IWF_Meta', $type ) || !is_numeric( $min ) || !is_numeric( $max ) || $min > $max ) {
			return $values;
		}

		if ( !$object && $type != 'option' ) {
			$type = 'current_' . $type;
		}

		if ( strpos( $key, ':index' ) === false ) {
			$key .= ':index';
		}

		for ( $i = $min; $i <= $max; $i++ ) {
			$_key = str_replace( ':index', $i, $key );

			if ( !$object ) {
				$value = call_user_func( array( 'IWF_Meta', $type ), $_key, $attr );

			} else {
				$value = call_user_func( array( 'IWF_Meta', $type ), $object, $_key, $attr );
			}

			if ( $value ) {
				$values[$i] = $value;
			}
		}

		return $values;
	}

	protected static function update( $type, $object, $key, $value ) {
		if ( is_array( $key ) ) {
			if ( iwf_check_value_only( $key ) ) {
				return false;
			}

			$results = array();

			foreach ( $key as $_key => $_value ) {
				$results[$_key] = self::update( $type, $object, $_key, $_value );
			}

			return $results;

		} else {
			if ( !$id = self::get_object_id( $type, $object ) ) {
				return false;
			}

			if ( $type == 'post' && ( $base_post_id = wp_is_post_revision( $id ) ) ) {
				$id = $base_post_id;
			}

			if ( strpos( $key, '.' ) !== false ) {
				list( $base_key, $key ) = array_filter( explode( '.', $key, 2 ) );

				if ( !$base_key || !$key ) {
					return false;
				}

				$value = self::get_meta_data( $type, $id, $base_key );

				if ( empty( $value ) || !is_array( $value ) ) {
					$value = array();
				}

				iwf_set_array( $value, $key, $value );

				return self::update_meta_data( $type, $id, $base_key, $value );

			} else {
				return self::update_meta_data( $type, $id, $key, $value );
			}
		}
	}
}