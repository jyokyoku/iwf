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

class IWF_View {
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
	 * @return IWF_View
	 */
	public static function instance( $instance = 'default', array $config = array() ) {
		if ( ! isset( self::$instances[ $instance ] ) ) {
			self::$instances[ $instance ] = new IWF_View( $config );
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
		if ( is_a( $instance, 'IWF_View' ) ) {
			$instance = array_search( $instance, self::$instances );
		}

		if ( ( is_string( $instance ) || is_numeric( $instance ) ) && isset( self::$instances[ $instance ] ) ) {
			unset( self::$instances[ $instance ] );

			return true;
		}

		return false;
	}

	/**
	 * Base directory of the template
	 *
	 * @var string
	 */
	protected $template_dir = '';

	/**
	 * Extension of the template
	 *
	 * @var string
	 */
	protected $template_ext = '';

	/**
	 * The template name prefix
	 *
	 * @var string
	 */
	protected $template_prefix = '';

	/**
	 * The template name suffix
	 *
	 * @var string
	 */
	protected $template_suffix = '';

	/**
	 * The bound between the variable for the text template
	 *
	 * @var string
	 */
	protected $bound = '%';

	/**
	 * View variables
	 *
	 * @var array
	 */
	protected $vars = array();

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
	 * Set the view variable
	 *
	 * @param string|array $key
	 * @param mixed $value
	 */
	public function set( $key, $value = null ) {
		iwf_set_array( $this->vars, $key, $value );
	}

	/**
	 * Get the view variable
	 *
	 * @param string|array $key
	 * @param mixed $default
	 *
	 * @return array|bool
	 */
	public function get( $key = null, $default = null ) {
		if ( ! $key ) {
			return $this->vars;

		} else {
			return iwf_get_array( $this->vars, $key, $default );
		}
	}

	/**
	 * Set the template directory
	 *
	 * @param string $dir
	 */
	public function set_template_dir( $dir ) {
		if ( is_dir( $dir ) ) {
			$this->template_dir = trailingslashit( $dir );
		}
	}

	/**
	 * Set the template extension
	 *
	 * @param string $ext
	 */
	public function set_template_ext( $ext ) {
		if ( strpos( $ext, '.' ) === 0 ) {
			$ext = substr( $ext, 1 );
		}

		$this->template_ext = $ext;
	}

	/**
	 * Set the bound of variable
	 *
	 * @param $bound
	 */
	public function set_bound( $bound ) {
		$this->bound = $bound;
	}

	/**
	 * Set the template suffix
	 *
	 * @param string $suffix
	 */
	public function set_template_suffix( $suffix ) {
		$this->template_suffix = $suffix;
	}

	/**
	 * Set the template prefix
	 *
	 * @param string $prefix
	 */
	public function set_template_prefix( $prefix ) {
		$this->template_prefix = $prefix;
	}

	/**
	 * Load a php template
	 *
	 * @param string $file_name
	 *
	 * @return IWF_View_Template_Php
	 */
	public function template_php( $file_name ) {
		$template_file_path = $this->template_dir . $this->template_prefix . $file_name . $this->template_suffix;

		if ( $this->template_ext ) {
			$template_file_path .= '.' . $this->template_ext;
		}

		$template_file_path = apply_filters( 'iwf_view_template_file_path', $template_file_path, $file_name );

		return new IWF_View_Template_Php( $this, $template_file_path );
	}

	/**
	 * Load a text template
	 *
	 * @param string $file_name
	 * @param string $bounds
	 *
	 * @return IWF_View_Template_Text
	 */
	public function template_text( $file_name, $bounds = null ) {
		if ( ! $bounds ) {
			$bounds = $this->bound;
		}

		$template_file_path = $this->template_dir . $this->template_prefix . $file_name . $this->template_suffix;

		if ( $this->template_ext ) {
			$template_file_path .= '.' . $this->template_ext;
		}

		$template_file_path = apply_filters( 'iwf_view_template_file_path', $template_file_path, $file_name );

		return new IWF_View_Template_Text( $this, $template_file_path, $bounds );
	}

	/**
	 * Register a callback function
	 *
	 * @param       $callback
	 * @param array $args
	 *
	 * @return IWF_View_Callback
	 */
	public function callback( $callback, array $args = array() ) {
		$callback = apply_filters( 'iwf_view_callback', $callback );

		return new IWF_View_Callback( $this, $callback, $args );
	}

	/**
	 * Replace the keyword to the variable
	 *
	 * @param string $text
	 * @param array $vars
	 * @param string $bounds
	 *
	 * @return mixed
	 */
	public function replace( $text, $bounds = null ) {
		if ( ! $bounds ) {
			$bounds = $this->bound;
		}

		return IWF_View_Template_Text::replace( $text, $this->vars, $bounds );
	}
}

abstract class IWF_View_Instance {
	/**
	 * View object
	 *
	 * @var IWF_View
	 */
	protected $view;

	/**
	 * Constructor
	 *
	 * @param IWF_View $view
	 */
	public function __construct( IWF_View $view ) {
		$this->view = $view;
	}

	/**
	 * Magic method
	 *
	 * @return string
	 */
	public function __toString() {
		return (string) $this->render();
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
	 * Check myself is valid
	 *
	 * @return mixed
	 */
	abstract public function is_valid();

	/**
	 * Render the result
	 *
	 * @return mixed
	 */
	abstract public function render();
}

class IWF_View_Callback extends IWF_View_Instance {
	/**
	 * The callback function
	 *
	 * @var callback
	 */
	protected $callback;

	/**
	 * The callback function name
	 *
	 * @var string
	 */
	protected $callable_name = null;

	/**
	 * Arguments of the callback function
	 *
	 * @var array
	 */
	protected $args = array();

	/**
	 * Constructor
	 *
	 * @param IWF_View $view
	 * @param callback $callback
	 * @param array $args
	 */
	public function __construct( IWF_View $view, $callback, array $args = array() ) {
		parent::__construct( $view );
		$this->callback = $callback;
		$this->args     = $args;
	}

	/**
	 * Check myself is valid
	 *
	 * @return bool|mixed
	 */
	public function is_valid() {
		if ( ! is_callable( $this->callback, null, $this->callable_name ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Process the callback function
	 *
	 * @param array $args
	 *
	 * @return mixed|string
	 */
	public function render( array $args = array() ) {
		if ( ! $this->is_valid() ) {
			wp_die( sprintf( 'Callback function `%s` does not callable.', $this->callable_name ) );
		}

		$args = wp_parse_args( $args, $this->args );
		$args = apply_filters( 'iwf_view_callback_args', $args, $this );

		if ( ! is_array( $args ) || empty( $args ) ) {
			$args = array();
		}

		array_unshift( $args, $this->view );

		do_action_ref_array( 'iwf_process_view_callback_pre', array( $this, $args ) );

		ob_start();
		call_user_func_array( $this->callback, $args );
		$result = ob_get_clean();

		do_action_ref_array( 'iwf_process_view_callback', array( &$result, $this, $args ) );

		return (string) apply_filters( 'iwf_view_callback_result', $result, $this, $args );
	}
}

class IWF_View_Template_Php extends IWF_View_Instance {
	/**
	 * The template file path
	 *
	 * @var string
	 */
	protected $file_path = '';

	/**
	 * Constructor
	 *
	 * @param IWF_View $view
	 * @param string $file_path
	 */
	public function __construct( IWF_View $view, $file_path ) {
		parent::__construct( $view );
		$this->file_path = $file_path;
	}

	/**
	 * Check myself is valid
	 *
	 * @return bool|mixed
	 */
	public function is_valid() {
		if ( ! is_file( $this->file_path ) || ! is_readable( $this->file_path ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Render the template
	 *
	 * @param array $vars
	 *
	 * @return mixed|string
	 */
	public function render( array $vars = array() ) {
		if ( ! $this->is_valid() ) {
			wp_die( sprintf( 'PHP template file `%s` does not exists (or not readable).', $this->file_path ) );
		}

		$vars = wp_parse_args( $vars, $this->view->get() );
		$vars = apply_filters( 'iwf_php_template_vars', $vars, $this );

		if ( ! is_array( $vars ) || empty( $vars ) ) {
			$vars = array();
		}

		do_action_ref_array( 'iwf_render_php_template_pre', array( $this, $vars ) );

		extract( $vars, EXTR_SKIP );

		ob_start();
		include $this->file_path;
		$result = ob_get_clean();

		do_action_ref_array( 'iwf_render_php_template', array( &$result, $this, $vars ) );

		return (string) apply_filters( 'iwf_rendered_php_template', $result, $this, $vars );
	}
}

class IWF_View_Template_Text extends IWF_View_Instance {
	/**
	 * The template file path
	 *
	 * @var string
	 */
	protected $file_path = '';

	/**
	 * Boundary character
	 *
	 * @var string
	 */
	protected $bound = '%';

	/**
	 * Constructor
	 *
	 * @param IWF_View $view
	 * @param string $file_path
	 */
	public function __construct( IWF_View $view, $file_path, $bound = null ) {
		parent::__construct( $view );
		$this->file_path = $file_path;

		if ( $bound ) {
			$this->bound = $bound;
		}
	}

	/**
	 * Check myself is valid
	 *
	 * @return bool|mixed
	 */
	public function is_valid() {
		if ( ! is_file( $this->file_path ) || ! is_readable( $this->file_path ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Render the template
	 *
	 * @param array $vars
	 * @param string $bounds
	 *
	 * @return mixed|string
	 */
	public function render( array $vars = array(), $bounds = null ) {
		if ( ! $this->is_valid() ) {
			wp_die( sprintf( 'Text template file `%s` does not exists (or not readable).', $this->file_path ) );
		}

		if ( ! $bounds ) {
			$bounds = $this->bound;
		}

		$vars = wp_parse_args( $vars, $this->view->get() );
		$vars = apply_filters( 'iwf_text_template_vars', $vars, $this );

		if ( ! is_array( $vars ) || empty( $vars ) ) {
			$vars = array();
		}

		do_action_ref_array( 'iwf_render_text_template_pre', array( $this, $vars ) );

		$result = self::replace( file_get_contents( $this->file_path ), $vars, $bounds );

		do_action_ref_array( 'iwf_render_text_template', array( &$result, $this, $vars ) );

		return (string) apply_filters( 'iwf_rendered_text_template', $result, $this, $vars );
	}

	/**
	 * Replace the keyword to the variable
	 *
	 * @param string $text
	 * @param array $vars
	 * @param string $bounds
	 *
	 * @return mixed
	 */
	public static function replace( $text, array $vars = array(), $bounds = null ) {
		$replaces = $searches = array();

		foreach ( $vars as $key => $value ) {
			$searches[] = $bounds . $key . $bounds;
			$replaces[] = $value ? iwf_convert( $value, 's' ) : '';
		}

		return str_replace( $searches, $replaces, $text );
	}
}