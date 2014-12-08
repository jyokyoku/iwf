<?php
/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author         Masayuki Ietomi <jyokyoku@gmail.com>
 * @copyright      Copyright(c) 2011 Masayuki Ietomi
 * @link           http://inspire-tech.jp
 */

require_once dirname( __FILE__ ) . '/iwf-constants.php';
require_once dirname( __FILE__ ) . '/iwf-functions.php';
require_once dirname( __FILE__ ) . '/iwf-view.php';

class IWF_Dispatcher {
	/**
	 * All the instances of myself
	 *
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Create the instance of myself
	 *
	 * @param string $instance
	 * @param array $config
	 *
	 * @return IWF_Dispatcher
	 */
	public static function instance( $instance = 'default', array $config = array() ) {
		if ( ! isset( self::$instances[ $instance ] ) ) {
			self::$instances[ $instance ] = new IWF_Dispatcher( $config );
		}

		return self::$instances[ $instance ];
	}

	/**
	 * Delete the instance
	 *
	 * @param $instance
	 *
	 * @return bool
	 */
	public static function destroy( $instance ) {
		if ( is_a( $instance, 'IWF_Dispatcher' ) ) {
			$instance = array_search( $instance, self::$instances );
		}

		if ( ( is_string( $instance ) || is_numeric( $instance ) ) && isset( self::$instances[ $instance ] ) ) {
			unset( self::$instances[ $instance ] );

			return true;
		}

		return false;
	}

	/**
	 * The action key
	 *
	 * @var string
	 */
	protected $action_key = 'action';

	/**
	 * Registered the actions
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Responses the actions
	 *
	 * @var array
	 */
	protected $responses = null;

	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	protected function __construct( array $config = array() ) {
		foreach ( $config as $key => $value ) {
			if ( method_exists( $this, 'set_' . $key ) ) {
				$this->{'set_' . $key}( $value );
			}
		}
	}

	/**
	 * Magic method
	 *
	 * @param $property
	 *
	 * @return mixed
	 */
	public function __get( $property ) {
		return $this->{$property};
	}

	/**
	 * Dispatch the action
	 *
	 * @return array
	 */
	public function dispatch_action() {
		if ( ! $action = iwf_get_array( $_GET, $this->action_key ) ) {
			return false;
		}

		$this->responses[ $action ] = array();

		if ( isset( $this->actions[ $action ] ) ) {
			ksort( $this->actions[ $action ] );
			do_action( 'iwf_dispatch_action_pre', $this->action_key, $action );

			$responses = array();

			foreach ( $this->actions[ $action ] as $functions ) {
				foreach ( $functions as $function ) {
					$response = call_user_func( $function );

					if ( is_string( $response ) || is_numeric( $response ) || $response instanceof IWF_View_Instance ) {
						$responses[] = $response;
					}
				}
			}

			do_action_ref_array( 'iwf_dispatch_action', array( &$responses, $this->action_key, $action ) );

			$this->responses[ $action ] = $responses;
		}

		return $this->responses[ $action ];
	}

	/**
	 * Add the action
	 *
	 * @param string $key
	 * @param callback $function
	 * @param int $priority
	 */
	public function add_action( $key, $function, $priority = 10 ) {
		if ( is_callable( $function ) ) {
			$action_id                                        = $this->build_action_unique_id( $key, $function, $priority );
			$this->actions[ $key ][ $priority ][ $action_id ] = $function;
		}
	}

	/**
	 * Remove the action
	 *
	 * @param string $key
	 * @param callback $function
	 * @param int $priority
	 */
	public function remove_action( $key, $function, $priority = 10 ) {
		$action_id = $this->build_action_unique_id( $key, $function, $priority );

		if ( isset( $this->actions[ $key ][ $priority ][ $action_id ] ) ) {
			unset( $this->actions[ $key ][ $priority ][ $action_id ] );
		}
	}

	/**
	 * Set the action key
	 *
	 * @param string $action_key
	 */
	public function set_action_key( $action_key ) {
		$this->action_key = $action_key;
	}

	/**
	 * Return the unique ID of callback function
	 *
	 * @param $key
	 * @param $function
	 * @param $priority
	 *
	 * @return array|bool|string
	 */
	protected function build_action_unique_id( $key, $function, $priority ) {
		static $action_id_count = 0;

		if ( is_string( $function ) ) {
			return $function;
		}

		if ( is_object( $function ) ) {
			$function = array( $function, '' );

		} else {
			$function = (array) $function;
		}

		if ( is_object( $function[0] ) ) {
			if ( function_exists( 'spl_object_hash' ) ) {
				return spl_object_hash( $function[0] ) . $function[1];

			} else {
				$obj_idx = get_class( $function[0] ) . $function[1];

				if ( ! isset( $function[0]->ipf_action_id ) ) {
					if ( false === $priority ) {
						return false;
					}

					$obj_idx .= isset( $this->actions[ $key ][ $priority ] ) ? count( (array) $this->actions[ $key ][ $priority ] ) : $action_id_count;
					$function[0]->ipf_action_id = $action_id_count;

					++ $action_id_count;

				} else {
					$obj_idx .= $function[0]->ipf_action_id;
				}

				return $obj_idx;
			}

		} else if ( is_string( $function[0] ) ) {
			return $function[0] . $function[1];
		}

		return false;
	}
}