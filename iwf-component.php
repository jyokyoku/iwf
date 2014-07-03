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
require_once dirname( __FILE__ ) . '/iwf-tag.php';
require_once dirname( __FILE__ ) . '/iwf-form.php';
require_once dirname( __FILE__ ) . '/iwf-inflector.php';

abstract class IWF_Component_Abstract extends IWF_Tag {
	protected $name = '';

	protected $name_cache = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( preg_match( '/^IWF_([A-Z][\w]+?)_Component$/', get_class( $this ), $matches ) ) {
			$this->name = $matches[1];
		}
	}

	/**
	 * Returns the name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	public function __call( $method, $args ) {
		$element_class = 'IWF_Component_Element_' . $this->classify( $method );
		$local_element_class = 'IWF_' . $this->name . '_Component_Element_' . $this->classify( $method );

		$form_element_class = 'IWF_Component_Element_FormField_' . $this->classify( $method );
		$local_form_element_class = 'IWF_' . $this->name . '_Component_Element_FormField_' . $this->classify( $method );

		$local = $is_form = false;

		if ( class_exists( $element_class ) || class_exists( $local_element_class ) ) {
			if ( class_exists( $local_element_class ) ) {
				$element_class = $local_element_class;
				$local = true;
			}

		} else if ( class_exists( $form_element_class ) || class_exists( $local_form_element_class ) ) {
			if ( class_exists( $local_form_element_class ) ) {
				$element_class = $local_form_element_class;
				$local = true;

			} else {
				$element_class = $form_element_class;
			}

			$is_form = true;

		} else {
			return parent::__call( $method, $args );
		}

		$reflection = new ReflectionClass( $element_class );

		array_unshift( $args, $this );
		$element = $reflection->newInstanceArgs( $args );

		if ( $local ) {
			$interface = $is_form
				? 'IWF_' . $this->name . '_Component_Element_FormField_Interface'
				: 'IWF_' . $this->name . '_Component_Element_Interface';

			if ( interface_exists( $interface ) && !( $element instanceof $interface ) ) {
				trigger_error( 'Class "' . $element_class . '" does not implements interface of the "' . $interface . '"', E_USER_WARNING );
			}
		}

		$sub_class = $is_form ? 'IWF_Component_Element_FormField_Abstract' : 'IWF_Component_Element_Abstract';

		if ( !is_subclass_of( $element, $sub_class ) ) {
			trigger_error( 'Class "' . $element_class . '" is not sub class of the "' . $sub_class . '"', E_USER_WARNING );
		}

		$this->element_trigger( $element, 'initialize' );
		$this->elements[] = $element;

		return $this;
	}

	public function render( $arg = null, $_ = null ) {
		$this->all_close();
		$this->capture_all_end();

		$args = func_get_args();
		$html = '';

		foreach ( $this->elements as $element ) {
			if ( $this->element_trigger( $element, 'before_render', $args ) === false ) {
				continue;
			}

			$result = $this->element_trigger( $element, 'render', $args );

			if ( ( $after = $this->element_trigger( $element, 'after_render', array( $result ) ) ) && $after !== true ) {
				$result = $after;
			}

			$html .= $result;
		}

		$this->clear();

		return $html;
	}

	public function display( $arg1 = null, $arg2 = null ) {
		$args = func_get_args();
		echo call_user_func_array( array( $this, 'render' ), $args );
	}

	public function __toString() {
		return $this->render();
	}

	/**
	 * Triggers function of element
	 *
	 * @param IWF_Tag_Element_Interface $element
	 * @param callback                  $function
	 * @param array                     $args
	 * @return mixed
	 */
	protected function element_trigger( IWF_Tag_Element_Interface $element, $function, array $args = array() ) {
		if ( method_exists( $element, $function ) ) {
			return call_user_func_array( array( $element, $function ), $args );
		}

		return true;
	}

	/**
	 * Changes $str to class name ("test_element" or "testElement" or etc.. to "Test_Element")
	 *
	 * @param string $str
	 * @return string
	 */
	protected function classify( $str ) {
		if ( !isset( $this->name_cache[$str] ) ) {
			$result = implode( '_', explode( ' ', IWF_Inflector::humanize( $str ) ) );

		} else {
			$result = $this->name_cache[$str];
		}

		return $result;
	}
}

class IWF_Component extends IWF_Component_Abstract {
	protected static $instances = array();

	public static function get_instance( $name = null ) {
		if ( !$name ) {
			$name = 'default';
		}

		if ( !isset( $_instances[$name] ) ) {
			self::$instances[$name] = new IWF_Component();
		}

		return self::$instances[$name];
	}

	public static function instance( $name = null ) {
		return self::get_instance( $name );
	}
}

abstract class IWF_Component_Element_Abstract implements IWF_Tag_Element_Interface {
	protected $component;

	/**
	 * Constructor
	 *
	 * @param IWF_Component|IWF_Component_Abstract $component
	 */
	public function __construct( IWF_Component_Abstract $component ) {
		$this->component = $component;
	}

	public function initialize() {
	}

	public function before_render() {
	}

	public function after_render() {
	}

	protected static function parse_validation_rules( $rules ) {
		if ( !is_array( $rules ) ) {
			$rules = array_filter( array_map( 'trim', explode( ' ', trim( (string)$rules ) ) ) );
		}

		foreach ( $rules as $i => $rule ) {
			if ( strpos( $rule, 'chk' ) !== 0 ) {
				$rules[$i] = 'chk' . $rule;
			}
		}

		return $rules;
	}
}

abstract class IWF_Component_Element_FormField_Abstract extends IWF_Component_Element_Abstract {
	protected $name;

	protected $type;

	protected $value;

	protected $args;

	protected $container;

	protected $validation;

	protected $single_form_types = array( 'text', 'textarea', 'select', 'file', 'password' );

	protected $multiple_form_types = array( 'radio' );

	/**
	 * Constructor
	 *
	 * @param IWF_Component $component
	 * @param string        $name
	 * @param int|string    $value
	 * @param array         $args
	 */
	public function __construct( IWF_Component_Abstract $component, $name, $value = null, array $args = array() ) {
		if ( is_array( $name ) ) {
			$args = $name;

		} else {
			$args['name'] = $name;
			$args['value'] = $value;
		}

		if ( empty( $args['name'] ) ) {
			trigger_error( 'Class "' . __CLASS__ . '" requires the "name" attribute', E_USER_WARNING );
		}

		if ( $component_name = $component->get_name() ) {
			$component_name .= '_';
		}

		if ( preg_match( '/^IWF_' . $component_name . 'Component_Element_FormField_([a-zA-Z0-9]+)$/', get_class( $this ), $matches ) ) {
			$this->type = strtolower( $matches[1] );
		}

		$this->name = iwf_get_array_hard( $args, 'name' );
		$this->value = iwf_get_array_hard( $args, 'value' );
		$this->args = $args;

		parent::__construct( $component );
	}

	public function initialize() {
		list( $this->container, $this->validation ) = array_values( iwf_get_array_hard( $this->args, array( 'container', 'validation' ) ) );
		$this->validation = self::parse_validation_rules( $this->validation );

		if ( $this->validation && in_array( $this->type, $this->single_form_types ) ) {
			IWF_Tag_Element_Node::add_class( $this->args, implode( ' ', $this->validation ) );
		}
	}

	public function render() {
		if ( !$this->type || !method_exists( 'IWF_Form', $this->type ) ) {
			return '';
		}

		return call_user_func( array( 'IWF_Form', $this->type ), $this->name, $this->value, $this->args );
	}

	public function after_render( $html = null ) {
		$container = $this->container;
		$container_args = array();

		if ( is_array( $container ) ) {
			list( $container, $container_args ) = array_values( $container ) + array( 'span', array() );
		}

		if ( $this->validation && in_array( $this->type, $this->multiple_form_types ) ) {
			if ( empty( $container ) ) {
				$container = 'span';
			}

			if ( empty( $container_args['id'] ) ) {
				$container_args['id'] = $this->name . '_group';
			}

			IWF_Tag_Element_Node::add_class( $container_args, $this->validation );
		}

		return $container ? IWF_Tag::create( $container, $container_args, $html ) : $html;
	}
}

class IWF_Component_Element_Validation extends IWF_Component_Element_Abstract {
	protected $rules = array();

	public function __construct( IWF_Component_Abstract $component, $rules = array(), $container = null ) {
		parent::__construct( $component );
		$container_args = array();

		if ( is_array( $container ) ) {
			list( $container, $container_args ) = $container + array( 'span', array() );

		} else if ( !$container ) {
			$container = 'span';
		}

		$rules = self::parse_validation_rules( $rules );
		$rules[] = 'chkgroup';

		if ( empty( $container_args['id'] ) ) {
			$container_args['id'] = 'v_' . uniqid();
		}

		IWF_Tag_Element_Node::add_class( $container_args, $rules );
		$this->component->open( $container, $container_args );
	}

	public function render() {
	}
}

class IWF_Component_Element_Nbsp extends IWF_Component_Element_Abstract {
	protected $repeat = 1;

	public function __construct( IWF_Component_Abstract $component, $repeat = 1 ) {
		parent::__construct( $component );

		if ( $repeat < 1 ) {
			$repeat = 1;
		}

		$this->repeat = $repeat;
	}

	public function render() {
		return str_repeat( '&nbsp;', $this->repeat );
	}
}

class IWF_Component_Element_Preview extends IWF_Component_Element_Abstract {
	protected $for;

	protected $args = array();

	public function __construct( IWF_Component_Abstract $component, $for = null, $args = array() ) {
		parent::__construct( $component );
		$this->for = $for;
		$this->args = $args;
	}

	public function initialize() {
		$args = $this->args;
		$args['data-for'] = $this->for;
		IWF_Tag_Element_Node::add_class( $args, 'iwf-preview' );

		$this->component->div( $args )->close;
	}

	public function render() {
	}
}

class IWF_Component_Element_Description extends IWF_Component_Element_Abstract {
	public function __construct( IWF_Component_Abstract $component, $value = null, $args = array() ) {
		parent::__construct( $component );

		if ( is_array( $value ) && empty( $args ) ) {
			$args = $value;
			$value = null;
		}

		IWF_Tag_Element_Node::add_class( $args, 'description' );
		$this->component->p( $args );

		if ( $value ) {
			$this->component->html( (string)$value )->close();
		}
	}

	public function render() {
	}
}

class IWF_Component_Element_Button_Secondary extends IWF_Component_Element_Abstract {
	protected $value;

	protected $args = array();

	public function __construct( IWF_Component_Abstract $component, $value = null, $args = array() ) {
		$this->value = $value;
		$this->args = $args;
	}

	public function before_render() {
		IWF_Tag_Element_Node::add_class( $this->args, 'button' );
	}

	public function render() {
		return IWF_Tag::create( 'button', $this->args, $this->value );
	}
}

class IWF_Component_Element_Button_Primary extends IWF_Component_Element_Button_Secondary {
	public function before_render() {
		IWF_Tag_Element_Node::add_class( $this->args, 'button-primary' );
	}
}

class IWF_Component_Element_Button_Media extends IWF_Component_Element_Abstract {
	protected $for;

	public function __construct( IWF_Component_Abstract $component, $for = null, $value = null, $args = array() ) {
		$this->for = $for;
		$this->value = $value;
		$this->args = $args;
	}

	public function before_render() {
		$data = array_combine(
			array( 'type', 'mode', 'filter', 'value', 'format' ),
			iwf_get_array_hard( $this->args, array( 'type', 'mode', 'filter', 'value', 'format' ) )
		);

		foreach ( $data as $key => $value ) {
			if ( !empty( $value ) ) {
				$this->args['data-' . $key] = $value;
			}
		}

		$this->args['data-for'] = $this->for;
		$this->args['type'] = 'button';
		IWF_Tag_Element_Node::add_class( $this->args, 'button media_button' );
	}

	public function render() {
		return IWF_Tag::create( 'button', $this->args, $this->value );
	}
}

class IWF_Component_Element_Button_Reset extends IWF_Component_Element_Button_Media {
	public function before_render() {
		if ( empty( $this->value ) ) {
			$this->value = __( 'Clear', 'iwf' );
		}

		$this->args['data-for'] = $this->for;
		$this->args['type'] = 'button';
		IWF_Tag_Element_Node::add_class( $this->args, 'button reset_button' );
	}
}

class IWF_Component_Element_FormField_Text extends IWF_Component_Element_FormField_Abstract {
}

class IWF_Component_Element_FormField_Password extends IWF_Component_Element_FormField_Abstract {
}

class IWF_Component_Element_FormField_Hidden extends IWF_Component_Element_FormField_Abstract {
}

class IWF_Component_Element_FormField_File extends IWF_Component_Element_FormField_Abstract {
	public function __construct( IWF_Component_Abstract $component, $name, array $args = array() ) {
		parent::__construct( $component, $name, null, $args );
	}

	public function render() {
		if ( !$this->type || !method_exists( 'IWF_Form', $this->type ) ) {
			return '';
		}

		return call_user_func( array( 'IWF_Form', $this->type ), $this->name, $this->args );
	}
}

class IWF_Component_Element_FormField_Checkbox extends IWF_Component_Element_FormField_Abstract {
}

class IWF_Component_Element_FormField_Radio extends IWF_Component_Element_FormField_Abstract {
}

class IWF_Component_Element_FormField_Textarea extends IWF_Component_Element_FormField_Abstract {
}

class IWF_Component_Element_FormField_Select extends IWF_Component_Element_FormField_Abstract {
}

class IWF_Component_Element_FormField_Checkboxes extends IWF_Component_Element_FormField_Abstract {
}

class IWF_Component_Element_FormField_Quicktag extends IWF_Component_Element_FormField_Abstract {
	public function initialize() {
		if ( version_compare( get_bloginfo( 'version' ), '3.3', '>=' ) ) {
			parent::initialize();

			$buttons = iwf_get_array_hard( $this->args, 'buttons' );

			if ( $buttons ) {
				$this->args['data-buttons'] = is_array( $buttons ) ? implode( ' ', $buttons ) : $buttons;
			}

			IWF_Tag_Element_Node::add_class( $this->args, 'iwf-quicktag wp-editor-area' );
			$this->args['id'] = 'iwf-quicktag-' . sha1( $this->name );

			$this->component
				->div( array( 'class' => 'wp-editor-container' ) )
				->div( array( 'class' => 'wp-editor-wrap', 'id' => 'wp-' . $this->args['id'] . '-wrap' ) )
				->textarea( $this->name, $this->value, $this->args )
				->close
				->close;
		}
	}

	public function render() {
		if ( version_compare( get_bloginfo( 'version' ), '3.3', '<' ) ) {
			trigger_error( 'The Quicktag has been required for the WordPress 3.3 or above' );
		}
	}
}

class IWF_Component_Element_FormField_Wysiwyg extends IWF_Component_Element_FormField_Abstract {
	public function initialize() {
		parent::initialize();

		if ( !isset( $this->args['settings'] ) ) {
			$this->args['settings'] = array();
		}

		$this->args['id'] = $this->name;
	}

	public function render() {
		$editor = '';

		if ( version_compare( get_bloginfo( 'version' ), '3.3', '>=' ) && function_exists( 'wp_editor' ) ) {
			ob_start();
			wp_editor( $this->value, $this->args['id'], $this->args['settings'] );
			$editor = ob_get_clean();

		} else {
			trigger_error( 'The TinyMCE has been required for the WordPress 3.3 or above' );
		}

		return $editor;
	}
}

class IWF_Component_Element_FormField_Visual extends IWF_Component_Element_FormField_Wysiwyg {
}

class IWF_Component_Element_FormField_Date extends IWF_Component_Element_FormField_Abstract {
	protected $date_type = array( 'date', 'datetime', 'time' );

	public function initialize() {
		list( $pick, $reset ) = array_values( iwf_get_array_hard( $this->args, array( 'pick', 'reset' ) ) );

		$settings = array_combine(
			array( 'data-preset', 'data-step-year', 'data-step-hour', 'data-step-minute', 'data-step-second', 'data-start-year', 'data-end-year' ),
			iwf_get_array_hard( $this->args, array( 'preset', 'step_year', 'step_hour', 'step_minute', 'step_second', 'start_year', 'end_year' ) )
		);

		if ( !in_array( $settings['data-preset'], $this->date_type ) ) {
			$settings['data-preset'] = 'date';
		}

		if ( empty( $settings['data-start-year'] ) ) {
			$settings['data-start-year'] = date( 'Y' ) - 10;
		}

		if ( empty( $settings['data-end-year'] ) ) {
			$settings['data-end-year'] = date( 'Y' ) + 10;
		}

		$settings = array_filter( $settings );

		IWF_Tag_Element_Node::add_class( $this->args, 'date_field' );
		$this->args = array_merge( $this->args, $settings );

		if ( !empty( $this->value ) ) {
			$this->value = strtotime( $this->value );
		}

		if ( $pick !== false ) {
			if ( is_array( $pick ) ) {
				$pick_label = reset( iwf_extract_and_merge( $pick, array( 'value', 'label' ) ) );

			} else {
				$pick_label = $pick;
				$pick = array();
			}

			if ( !$pick_label ) {
				$pick_label = __( 'Pick', 'iwf' );
			}

			$pick['type'] = 'button';
			$pick['data-for'] = $this->name;
			IWF_Tag_Element_Node::add_class( $pick, 'date_picker' );
		}

		if ( $reset !== false ) {
			if ( is_array( $reset ) ) {
				$reset_label = reset( iwf_extract_and_merge( $reset, array( 'value', 'label' ) ) );

			} else {
				$reset_label = $reset;
				$reset = array();
			}

			if ( !$reset_label ) {
				$reset_label = __( 'Clear', 'iwf' );
			}

			if ( !isset( $this->args['readonly'] ) ) {
				$this->args['readonly'] = true;
			}
		}

		$this->component->text( $this->name, $this->value, $this->args );

		if ( $pick !== false ) {
			$this->component
				->nbsp( 1 )
				->button_secondary( $pick_label, $pick );
		}

		if ( $reset !== false ) {
			$this->component
				->nbsp( 1 )
				->button_reset( $this->name, $reset_label, $reset );
		}
	}

	public function render() {
	}
}

class IWF_Component_Element_FormField_Color extends IWF_Component_Element_FormField_Abstract {
	public function initialize() {
		$settings = array_combine(
			array( 'data-show-input', 'data-show-alpha', 'data-show-initial', 'data-show-palette', 'data-allow-empty', 'data-show-selection-palette', 'data-max-palette-size' ),
			iwf_get_array_hard( $this->args, array( 'show_input', 'show_alpha', 'show_initial', 'show_palette', 'allow_empty', 'show_selection_palette', 'max_palette_size' ) )
		);

		foreach ($settings as $i => $setting) {
			if (is_null($setting)) {
				unset($settings[$i]);

			} else if (is_bool($setting)) {
				$settings[$i] = $setting ? 1 : 0;
			}
		}

		IWF_Tag_Element_Node::add_class( $this->args, 'color_picker_field' );
		$this->args = array_merge( $this->args, $settings );
		$this->component->text( $this->name, $this->value, $this->args );
	}

	public function render() {
	}
}

class IWF_Component_Element_FormField_Media extends IWF_Component_Element_FormField_Abstract {
	public function initialize() {
		list( $media, $reset, $preview, $type, $format, $filter ) = array_values( iwf_get_array_hard( $this->args, array( 'media', 'reset', 'preview', 'type', 'format', 'filter' ) ) );

		if ( is_array( $media ) ) {
			$media_label = reset( iwf_extract_and_merge( $media, array( 'value', 'label' ) ) );

		} else {
			$media_label = $media;
			$media = array();
		}

		if ( !$media_label ) {
			$media_label = __( 'Select File', 'iwf' );
		}

		$media['type'] = !empty( $filter ) ? $filter : $type;

		if ( $format ) {
			$media['format'] = $format;
		}

		if ( $reset !== false ) {
			if ( is_array( $reset ) ) {
				$reset_label = reset( iwf_extract_and_merge( $reset, array( 'value', 'label' ) ) );

			} else {
				$reset_label = $reset;
				$reset = array();
			}

			if ( !$reset_label ) {
				$reset_label = __( 'Clear', 'iwf' );
			}
		}

		if ($preview) {
			$this->component
				->div(array('class' => 'iwf-preview-wrapper'))
				->preview( $this->name )
				->div(array('class' => 'iwf-media-form'))
				->div(array('class' => 'iwf-media-form-inner'))
				->text( $this->name, $this->value, $this->args )
				->div(array('style' => 'margin-top: 5px'))
				->button_media( $this->name, $media_label, $media );

			if ( $reset !== false ) {
				$this->component
					->nbsp( 1 )
					->button_reset( $this->name, $reset_label, $reset );
			}

			$this->component->close->close->close->close;

		} else {
			$this->component
				->text( $this->name, $this->value, $this->args )
				->div(array('style' => 'margin-top: 5px;'))
				->button_media( $this->name, $media_label, $media );

			if ( $reset !== false ) {
				$this->component
					->nbsp( 1 )
					->button_reset( $this->name, $reset_label, $reset );
			}

			$this->component->close;
		}
	}

	public function render() {
	}
}