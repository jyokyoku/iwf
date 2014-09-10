<?php

class IWF_CallbackManager {
	protected $callable_classes = array();

	public function __get( $property ) {
		return $this->{$property};
	}

	public function set_callable_class( $class ) {
		$class_name = is_object( $class ) ? get_class( $class ) : $class;
		$this->callable_classes[$class_name] = is_object( $class ) ? $class : null;
	}

	public function get_callable_function( $func = null ) {
		if ( !is_array( $func ) ) {
			foreach ( $this->callable_classes as $class_name => $class_object ) {
				if ( method_exists( $class_name, $func ) ) {
					$func = array( $class_object ? $class_object : $class_name, $func );
					break;
				}
			}
		}

		if ( !is_callable( $func, false, $callable_name ) ) {
			return false;
		}

		return $func;
	}
}

class IWF_CallbackManager_Hook extends IWF_CallbackManager {
	protected static $instances = array();

	/**
	 * Get the instance
	 *
	 * @param string $instance
	 * @return IWF_CallbackManager_Hook
	 */
	public static function get_instance( $instance = 'default', $args = array() ) {
		if ( empty( self::$instances[$instance] ) ) {
			self::$instances[$instance] = new IWF_CallbackManager_Hook( $args );
		}

		return self::$instances[$instance];
	}

	protected $action_prefix = 'action_';

	protected $filter_prefix = 'filter_';

	protected $actions = array();

	protected $filters = array();

	protected function __construct( $args = array() ) {
		foreach ( $args as $key => $value ) {
			if ( method_exists( $this, 'set_' . $key ) ) {
				$this->{'set_' . $key}( $value );
			}
		}
	}

	public function __get( $property ) {
		return $this->{$property};
	}

	public function set_action_prefix( $action_prefix ) {
		$this->action_prefix = $action_prefix;
	}

	public function set_filter_prefix( $filter_prefix ) {
		$this->filter_prefix = $filter_prefix;
	}

	public function add_action( $hook, $func = null, $priority = 10, $accepted_args = 1 ) {
		return $this->add( 'action', $hook, $func, $priority, $accepted_args );
	}

	public function add_filter( $hook, $func = null, $priority = 10, $accepted_args = 1 ) {
		return $this->add( 'filter', $hook, $func, $priority, $accepted_args );
	}

	protected function add( $type, $hook, $func = null, $priority = 10, $accepted_args = 1 ) {
		if ( !$this->{strtolower( $type ) . '_prefix'} ) {
			return false;
		}

		$prefix = $this->{strtolower( $type ) . '_prefix'};
		$register_func = 'add_' . strtolower( $type );

		if ( !is_callable( $register_func ) ) {
			return false;
		}

		if ( !$func ) {
			$func = $hook;
		}

		if ( !is_array( $func ) ) {
			if ( strpos( $func, $prefix ) === false ) {
				$func = $prefix . $func;
			}
		}

		if ( !$func = $this->get_callable_function( $func ) ) {
			return false;
		}

		is_callable( $func, true, $callable_name );
		$this->{$type . 's'}[$hook][] = $callable_name;

		call_user_func( $register_func, $hook, $func, $priority, $accepted_args );

		return true;
	}
}

class IWF_CallbackManager_Shortcode extends IWF_CallbackManager {
	protected static $instances = array();

	/**
	 * Get the instance
	 *
	 * @param string $instance
	 * @return IWF_CallbackManager_Shortcode
	 */
	public static function get_instance( $instance = 'default', $args = array() ) {
		if ( empty( self::$instances[$instance] ) ) {
			self::$instances[$instance] = new IWF_CallbackManager_Shortcode( $args );
		}

		return self::$instances[$instance];
	}

	protected $func_prefix = '';

	protected $tag_prefix = '';

	protected $shortcodes = array();

	protected function __construct( $args = array() ) {
		foreach ( $args as $key => $value ) {
			if ( method_exists( $this, 'set_' . $key ) ) {
				$this->{'set_' . $key}( $value );
			}
		}
	}

	public function __get( $property ) {
		return $this->{$property};
	}

	public function set_tag_prefix( $tag_prefix ) {
		$this->tag_prefix = $tag_prefix;
	}

	public function set_func_prefix( $func_prefix ) {
		$this->func_prefix = $func_prefix;
	}

	public function add_shortcode( $tag, $func = null ) {
		if ( !$func ) {
			$func = $tag;
		}

		if ( !is_array( $func ) ) {
			if ( strpos( $func, $this->func_prefix ) === false ) {
				$func = $this->func_prefix . $func;
			}
		}

		if ( !$func = $this->get_callable_function( $func ) ) {
			return false;
		}

		is_callable( $func, true, $callable_name );
		$this->shortcodes[$tag] = $callable_name;

		add_shortcode( $this->tag_prefix . $tag, $func );

		return true;
	}

	public function strip_tag_prefix( $tag ) {
		return preg_replace( '|^' . preg_quote( $this->tag_prefix, '|' ) . '(.*?)|', '$1', $tag );
	}
}