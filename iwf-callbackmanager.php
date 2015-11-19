<?php

class IWF_CallbackManager {
	protected $callable_classes = array();

	public function __get( $property ) {
		return $this->{$property};
	}

	public function set_callable_class( $class ) {
		$class_name                            = is_object( $class ) ? get_class( $class ) : $class;
		$this->callable_classes[ $class_name ] = is_object( $class ) ? $class : null;
	}

	public function get_callable_function( $func = null ) {
		if ( ! is_array( $func ) ) {
			foreach ( $this->callable_classes as $class_name => $class_object ) {
				if ( method_exists( $class_name, $func ) ) {
					$func = array( $class_object ? $class_object : $class_name, $func );
					break;
				}
			}
		}

		if ( ! is_callable( $func ) ) {
			$this->invalid_function( $func );

			return false;
		}

		return $func;
	}

	public function invalid_function( $func ) {
		is_callable( $func, true, $callable_name );
		trigger_error( sprintf( 'Could not call the function `%s`', $callable_name ), E_USER_WARNING );
	}
}

class IWF_CallbackManager_Hook extends IWF_CallbackManager {
	protected static $instances = array();

	/**
	 * Get the instance
	 *
	 * @param string $instance
	 *
	 * @return IWF_CallbackManager_Hook
	 */
	public static function get_instance( $instance = 'default', $args = array() ) {
		if ( empty( self::$instances[ $instance ] ) ) {
			self::$instances[ $instance ] = new IWF_CallbackManager_Hook( $args );
		}

		return self::$instances[ $instance ];
	}

	protected $action_prefix = '';

	protected $filter_prefix = '';

	protected $active_actions = array();

	protected $active_filters = array();

	protected $suspended_actions = array();

	protected $suspended_filters = array();

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

	public function add_action( $hook, $function = null, $priority = 10, $accepted_args = 1 ) {
		return $this->add( 'action', $hook, $function, $priority, $accepted_args );
	}

	public function add_filter( $hook, $function = null, $priority = 10, $accepted_args = 1 ) {
		return $this->add( 'filter', $hook, $function, $priority, $accepted_args );
	}

	public function remove_action( $hook, $function = null, $priority = null ) {
		return $this->remove( 'action', $hook, $function, $priority );
	}

	public function remove_filter( $hook, $function = null, $priority = null ) {
		return $this->remove( 'filter', $hook, $function, $priority );
	}

	public function suspend_action( $hook, $function = null, $priority = null ) {
		return $this->suspend( 'action', $hook, $function, $priority );
	}

	public function suspend_filter( $hook, $function = null, $priority = null ) {
		return $this->suspend( 'filter', $hook, $function, $priority );
	}

	public function resume_action( $hook, $function = null, $priority = null ) {
		return $this->resume( 'action', $hook, $function, $priority );
	}

	public function resume_filter( $hook, $function = null, $priority = null ) {
		return $this->resume( 'filter', $hook, $function, $priority );
	}

	protected function add( $type, $hook, $function = null, $priority = null, $accepted_args = 1 ) {
		if ( ! $function = $this->get_callable_hook_function( $type, $hook, $function ) ) {
			return false;
		}

		if ( ! is_null( $priority ) && ! is_numeric( $priority ) ) {
			$priority = 10;
		}

		$register_func = 'add_' . strtolower( $type );
		call_user_func( $register_func, $hook, $function, $priority, $accepted_args );

		$this->{"active_{$type}s"}[ $hook ][ $priority ][] = array( $function, $accepted_args );

		return true;
	}

	protected function remove( $type, $hook = null, $function = null, $priority = null ) {
		if ( ! $this->{strtolower( $type ) . '_prefix'} || ( ! $hook && ! $function ) ) {
			return false;
		}

		$remove_func = 'remove_' . strtolower( $type );

		if ( ! $hook ) {
			if ( ! $active_hooks = $this->get_active_hooks( $type, $function ) ) {
				return false;
			}

			foreach ( $active_hooks as $active_hook ) {
				call_user_func( $remove_func, $active_hook['hook'], $active_hook['func'], $active_hook['priority'] );
				unset( $this->{"suspended_{$type}s"}[ $active_hook['hook'] ][ $active_hook['priority'] ][ $active_hook['index'] ] );
			}

			return true;

		} else {
			if ( $function !== false ) {
				if ( ! $remove_active_functions = $this->get_active_functions( $type, $hook, $function, $priority ) ) {
					return false;
				}

				foreach ( $remove_active_functions as $remove_priority => $remove_functions ) {
					foreach ( $remove_functions as $remove_function ) {
						foreach ( $this->{"active_{$type}s"}[ $hook ][ $remove_priority ] as $i => $active_function ) {
							if ( $active_function[0] == $remove_function ) {
								unset( $this->{"active_{$type}s"}[ $hook ][ $remove_priority ][ $i ] );

								$remove_func( $hook, $active_function[0], $remove_priority );
							}
						}
					}
				}

			} else {
				if ( ! isset( $this->{"active_{$type}s"}[ $hook ] ) ) {
					return false;
				}

				foreach ( $this->{"active_{$type}s"}[ $hook ] as $active_priority => $active_functions ) {
					foreach ( $active_functions as $active_function ) {
						$remove_func( $hook, $active_function[0], $active_priority );
					}
				}

				unset( $this->{"active_{$type}s"}[ $hook ] );
			}
		}

		return true;
	}

	protected function suspend( $type, $hook = null, $function = null, $priority = null ) {
		if ( ! $this->{strtolower( $type ) . '_prefix'} ) {
			return false;
		}

		$remove_func = 'remove_' . strtolower( $type );

		if ( ! $hook ) {
			if ( ! $active_hooks = $this->get_active_hooks( $type, $function ) ) {
				return false;
			}

			foreach ( $active_hooks as $active_hook ) {
				call_user_func( $remove_func, $active_hook['hook'], $active_hook['func'], $active_hook['priority'] );
				unset( $this->{"suspended_{$type}s"}[ $active_hook['hook'] ][ $active_hook['priority'] ][ $active_hook['index'] ] );

				$this->{"suspended_{$type}s"}[ $active_hook['hook'] ][ $active_hook['priority'] ][] = array( $active_hook['func'], $active_hook['accepted_args'] );
			}

			return true;

		} else {
			if ( $function !== false ) {
				if ( ! $suspend_active_functions = $this->get_active_functions( $type, $hook, $function, $priority ) ) {
					return false;
				}

				foreach ( $suspend_active_functions as $suspend_priority => $suspend_functions ) {
					foreach ( $suspend_functions as $suspend_function ) {
						foreach ( $this->{"active_{$type}s"}[ $hook ][ $suspend_priority ] as $i => $active_function ) {
							if ( $active_function[0] == $suspend_function ) {
								unset( $this->{"active_{$type}s"}[ $hook ][ $suspend_priority ][ $i ] );
								$this->{"suspended_{$type}s"}[ $hook ][ $suspend_priority ][] = $active_function;

								$remove_func( $hook, $active_function[0], $suspend_priority );
							}
						}
					}
				}

			} else {
				if ( ! isset( $this->{"active_{$type}s"}[ $hook ] ) ) {
					return false;
				}

				foreach ( $this->{"active_{$type}s"}[ $hook ] as $active_priority => $active_functions ) {
					foreach ( $active_functions as $active_function ) {
						$remove_func( $hook, $active_function[0], $active_priority );
						$this->{"suspended_{$type}s"}[ $hook ][ $active_priority ][] = $active_function;
					}
				}

				unset( $this->{"active_{$type}s"}[ $hook ] );
			}
		}

		return true;
	}

	protected function resume( $type, $hook = null, $function = null, $priority = null ) {
		if ( ! $this->{strtolower( $type ) . '_prefix'} || ( ! $hook && ! $function ) ) {
			return false;
		}

		$register_func = 'add_' . strtolower( $type );

		if ( ! $hook ) {
			if ( ! $suspended_hooks = $this->get_suspended_hooks( $type, $function ) ) {
				return false;
			}

			foreach ( $suspended_hooks as $suspended_hook ) {
				call_user_func( $register_func, $suspended_hook['hook'], $suspended_hook['func'], $suspended_hook['priority'], $suspended_hook['accepted_args'] );
				unset( $this->{"suspended_{$type}s"}[ $suspended_hook['hook'] ][ $suspended_hook['priority'] ][ $suspended_hook['index'] ] );

				$this->{"active_{$type}s"}[ $suspended_hook['hook'] ][ $suspended_hook['priority'] ][] = array( $suspended_hook['func'], $suspended_hook['accepted_args'] );
			}

			return true;

		} else {
			if ( $function !== false ) {
				if ( ! $suspended_resume_functions = $this->get_suspended_functions( $type, $hook, $function, $priority ) ) {
					return false;
				}

				foreach ( $suspended_resume_functions as $resume_priority => $resume_functions ) {
					foreach ( $resume_functions as $resume_function ) {
						foreach ( $this->{"suspended_{$type}s"}[ $hook ][ $resume_priority ] as $i => $suspended_function ) {
							if ( $suspended_function[0] == $resume_function ) {
								unset( $this->{"suspended_{$type}s"}[ $hook ][ $resume_priority ][ $i ] );
								$this->{"active_{$type}s"}[ $hook ][ $resume_priority ][] = $suspended_function;

								$register_func( $hook, $suspended_function[0], $resume_priority, $suspended_function[1] );
							}
						}
					}
				}

			} else {
				if ( ! isset( $this->{"suspended_{$type}s"}[ $hook ] ) ) {
					return false;
				}

				foreach ( $this->{"suspended_{$type}s"}[ $hook ] as $suspended_priority => $suspended_functions ) {
					foreach ( $suspended_functions as $suspended_function ) {
						$register_func( $hook, $suspended_function[0], $suspended_priority, $suspended_function[1] );
						$this->{"active_{$type}s"}[ $hook ][ $suspended_priority ][] = $suspended_function;
					}
				}

				unset( $this->{"suspended_{$type}s"}[ $hook ] );
			}
		}

		return true;
	}

	public function get_callable_hook_function( $type, $hook, $function = null ) {
		if ( ! isset( $this->{strtolower( $type ) . '_prefix'} ) ) {
			trigger_error( sprintf( 'Undefined the prefix for %s', $type ) );

			return false;
		}

		$prefix = $this->{strtolower( $type ) . '_prefix'};

		if ( ! $function ) {
			$function = $hook;
		}

		if ( ! is_array( $function ) ) {
			if ( $prefix && strpos( $function, $prefix ) !== 0 ) {
				$function = $prefix . $function;
			}
		}

		if ( ! $function = $this->get_callable_function( $function ) ) {
			return false;
		}

		return $function;
	}

	protected function get_active_functions( $type, $hook, $function = null, $priority = null ) {
		if ( ! $function = $this->get_callable_hook_function( $type, $hook, $function ) ) {
			return false;
		}

		return $this->get_registered_functions( $this->{"active_{$type}s"}, $hook, $function, $priority );
	}

	protected function get_suspended_functions( $type, $hook, $function = null, $priority = null ) {
		if ( ! $function = $this->get_callable_hook_function( $type, $hook, $function ) ) {
			return false;
		}

		return $this->get_registered_functions( $this->{"suspended_{$type}s"}, $hook, $function, $priority );
	}

	protected function get_active_hooks( $type, $function ) {
		if ( ! isset( $this->{"active_{$type}s"} ) ) {
			return false;
		}

		return $this->filter_registered_hooks( $this->{"active_{$type}s"}, $function );
	}

	protected function get_suspended_hooks( $type, $function ) {
		if ( ! isset( $this->{"suspended_{$type}s"} ) ) {
			return false;
		}

		return $this->filter_registered_hooks( $this->{"suspended_{$type}s"}, $function );
	}

	protected function filter_registered_hooks( $hooks, $function ) {
		$filtered_hooks = array();

		foreach ( $hooks as $hook => $priorities ) {
			foreach ( $priorities as $priority => $registered_functions ) {
				foreach ( $registered_functions as $i => $registered_function ) {
					if ( $registered_function[0] == $function ) {
						$filtered_hooks[] = array(
							'hook'          => $hook,
							'func'          => $registered_function[0],
							'accepted_args' => $registered_function[1],
							'priority'      => $priority,
							'index'         => $i
						);
					}
				}
			}
		}

		return $filtered_hooks;
	}

	protected function get_registered_functions( $hooks, $hook, $function = null, $priority = null ) {
		$registered_functions = array();

		if ( ! $priority ) {
			if ( ! isset( $hooks[ $hook ] ) ) {
				return false;
			}

			foreach ( $hooks[ $hook ] as $active_priority => $active_functions ) {
				foreach ( $active_functions as $active_function ) {
					if ( $active_function[0] == $function ) {
						$registered_functions[ $active_priority ][] = $function;
					}
				}
			}

		} else {
			if ( ! isset( $hooks[ $hook ][ $priority ] ) ) {
				return false;
			}

			foreach ( $hooks[ $hook ][ $priority ] as $active_function ) {
				if ( $active_function[0] == $function ) {
					$registered_functions[ $priority ][] = $function;
				}
			}
		}

		return $registered_functions;
	}
}

class IWF_CallbackManager_Shortcode extends IWF_CallbackManager {
	protected static $instances = array();

	/**
	 * Get the instance
	 *
	 * @param string $instance
	 *
	 * @return IWF_CallbackManager_Shortcode
	 */
	public static function get_instance( $instance = 'default', $args = array() ) {
		if ( empty( self::$instances[ $instance ] ) ) {
			self::$instances[ $instance ] = new IWF_CallbackManager_Shortcode( $args );
		}

		return self::$instances[ $instance ];
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

	public function do_shortcode( $tag, $attr = null, $content = null ) {
		if ( strpos( $tag, $this->tag_prefix ) === 0 ) {
			$tag = mb_substr( $tag, count( $this->tag_prefix ) );
		}

		if ( ! isset( $this->shortcodes[ $tag ] ) ) {
			return '';
		}

		return iwf_do_shortcode( $this->tag_prefix . $tag, $attr, $content );
	}

	public function add_shortcode( $tag, $func = null ) {
		if ( ! $func ) {
			$func = $tag;
		}

		if ( ! is_array( $func ) ) {
			if ( $this->func_prefix && strpos( $func, $this->func_prefix ) !== 0 ) {
				$func = $this->func_prefix . $func;
			}
		}

		if ( ! $func = $this->get_callable_function( $func ) ) {
			return false;
		}

		$this->shortcodes[ $tag ] = $func;

		add_shortcode( $this->tag_prefix . $tag, $func );

		return true;
	}

	public function apply( $tag, $attr = null, $content = null ) {
		return $this->do_shortcode( $tag, $attr, $content );
	}

	public function add( $tag, $func = null ) {
		return $this->add_shortcode( $tag, $func );
	}

	public function strip_tag_prefix( $tag ) {
		return preg_replace( '|^' . preg_quote( $this->tag_prefix, '|' ) . '(.*?)|', '$1', $tag );
	}
}
