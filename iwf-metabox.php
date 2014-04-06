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
require_once dirname( __FILE__ ) . '/iwf-component.php';

class IWF_MetaBox {
	public $title;

	protected $_context;

	protected $_priority;

	protected $_capability;

	protected $_option_set;

	protected $_screen;

	protected $_template;

	protected $_id;

	protected $_is_post = false;

	protected $_components = array();

	protected $_rendered_html = '';

	protected $_current_post;

	/**
	 * Constructor
	 *
	 * @param    string $screen
	 * @param    string $id
	 * @param    string $title
	 * @param    array  $args
	 */
	public function __construct( $screen, $id, $title = null, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'context' => 'normal', 'priority' => 'default', 'capability' => null,
			'register' => true, 'option_set' => null, 'template' => null
		) );

		$this->_screen = $screen;
		$this->_id = $id;
		$this->_is_post = !is_null( get_post_type_object( $this->_screen ) );
		$this->_context = $args['context'];
		$this->_priority = $args['priority'];
		$this->_capability = $args['capability'];
		$this->_option_set = $args['option_set'];
		$this->_template = $args['template'];
		$this->_component = new IWF_MetaBox_Component( $this, 'common', false, $this->_option_set );

		$this->title = empty( $title ) ? $this->_id : $title;

		if ( $args['register'] ) {
			add_action( 'admin_menu', array( $this, 'register' ) );
		}

		if ( $this->_is_post ) {
			add_action( 'load-post.php', array( $this, 'set_current_post' ) );
			add_action( 'load-post-new.php', array( $this, 'set_current_post' ) );
			add_action( 'load-post.php', array( $this, 'pre_render' ) );
			add_action( 'load-post-new.php', array( $this, 'pre_render' ) );
			add_action( 'add_meta_boxes_' . $this->_screen, array( $this, 'add_media_views_js' ), 10, 2 );

		} else {
			add_action( 'load-' . $this->_screen, array( $this, 'pre_render' ) );
		}
	}

	/**
	 * Magic method
	 *
	 * @param $method
	 * @param $args
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		return call_user_func_array( array( $this->_component, $method ), $args );
	}

	/**
	 * Magic method
	 *
	 * @param $property
	 * @return mixed
	 */
	public function __get( $property ) {
		return $this->{$property}();
	}

	/**
	 * Returns the post type
	 *
	 * @return    string
	 */
	public function get_screen() {
		return $this->_screen;
	}

	/**
	 * Returns the id
	 *
	 * @return    string
	 */
	public function get_id() {
		return $this->_id;
	}

	/**
	 * Returns it belongs post type
	 */
	public function is_post() {
		return $this->_is_post;
	}

	/**
	 * Returns the option set
	 *
	 * @return string|null
	 */
	public function get_option_set() {
		return $this->_option_set;
	}

	/**
	 * Returns the capability
	 *
	 * @return string
	 */
	public function get_capability() {
		return $this->_capability;
	}

	/**
	 * Returns the current post object
	 *
	 * @return mixed
	 */
	public function get_current_post() {
		return $this->_current_post;
	}

	/**
	 * Creates the IWF_MetaBox_Component
	 *
	 * @param    id|IWF_MetaBox_Component $id
	 * @param    string                   $title
	 * @param null                        $option_set
	 * @return    IWF_MetaBox_Component
	 */
	public function component( $id, $title = null, $option_set = null ) {
		if ( is_object( $id ) && is_a( $id, 'IWF_MetaBox_Component' ) ) {
			$component = $id;
			$id = $component->get_id();

			if ( isset( $this->_components[$id] ) && $this->_components[$id] !== $component ) {
				$this->_components[$id] = $component;
			}

		} else if ( is_string( $id ) && isset( $this->_components[$id] ) ) {
			$component = $this->_components[$id];

		} else {
			$option_set = empty( $option_set ) ? ( empty( $this->_option_set ) ? null : $this->_option_set ) : $option_set;
			$component = new IWF_MetaBox_Component( $this, $id, $title, $option_set );
			$this->_components[$id] = $component;
		}

		return $component;
	}

	/**
	 * Alias of 'component' method
	 *
	 * @param    id|IWF_MetaBox_Component $id
	 * @param    string                   $title
	 * @param null                        $option_set
	 * @return    IWF_MetaBox_Component
	 * @see        IWF_MetaBox::component
	 */
	public function c( $id, $title = null, $option_set = null ) {
		return $this->component( $id, $title, $option_set );
	}

	/**
	 * Registers to system
	 */
	public function register() {
		if ( empty( $this->_capability ) || ( !empty( $this->_capability ) && current_user_can( $this->_capability ) ) ) {
			add_meta_box( $this->_id, $this->title, array( $this, 'display' ), $this->_screen, $this->_context, $this->_priority );
		}
	}

	/**
	 * Displays the rendered html
	 *
	 * @param    mixed $object
	 */
	public function display( $object = null ) {
		echo $this->render( $object );
	}

	/**
	 * Set the current post object
	 */
	public function set_current_post() {
		global $pagenow;

		$object = $post_type = null;

		if ( $pagenow == 'post.php' ) {
			if ( isset( $_GET['post'] ) ) {
				$post_id = (int)$_GET['post'];

			} else if ( isset( $_POST['post_ID'] ) ) {
				$post_id = (int)$_POST['post_ID'];

			} else {
				$post_id = 0;
			}

			if ( $post_id ) {
				$object = get_post( $post_id );
			}

		} else if ( $pagenow == 'post-new.php' ) {
			if ( !isset( $_GET['post_type'] ) ) {
				$post_type = 'post';

			} else if ( in_array( $_GET['post_type'], get_post_types( array( 'show_ui' => true ) ) ) ) {
				$post_type = $_GET['post_type'];

			} else {
				wp_die( __( 'Invalid post type', 'iwf' ) );
			}

			$object = get_default_post_to_edit( $post_type, false );
		}

		if ( $object ) {
			$this->_current_post = $object;
		}
	}

	/**
	 * Render and cache the html
	 */
	public function pre_render() {
		$allow = true;

		if ( $this->is_post() ) {
			$current_post = $this->get_current_post();

			if ( !$current_post || $current_post->post_type != $this->get_screen() ) {
				$allow = false;
			}
		}

		if ( $allow ) {
			ob_start();

			$uniq_id = $this->generate_uniq_id();
			wp_nonce_field( $uniq_id, $uniq_id . '_nonce' );

			if ( $this->_template ) {
				if ( is_file( $this->_template ) && is_readable( $this->_template ) ) {
					include $this->_template;

				} else if ( is_callable( $this->_template ) ) {
					call_user_func( $this->_template, $this, $current_post );

				} else {
					wp_die( sprintf( __( 'Template file or function `%s` is not exists.', 'iwf' ), $this->_template ) );
				}

			} else {
				echo $this->get_component_html();
			}

			$this->_rendered_html = ob_get_clean();
		}
	}

	/**
	 * Returns the rendered html
	 *
	 * @return string
	 */
	public function render() {
		if ( !$this->_rendered_html ) {
			$this->pre_render();
		}

		return $this->_rendered_html;
	}

	/**
	 * Returns the rendered html of all components
	 *
	 * @return string
	 */
	public function get_component_html() {
		$html = '';

		foreach ( $this->_components as $component ) {
			$html .= $component->render();
		}

		return $html;
	}

	/**
	 * Returns the unique id from current state
	 *
	 * @return string
	 */
	public function generate_uniq_id() {
		return sha1( $this->_id . serialize( implode( '', array_keys( $this->_components ) ) ) );
	}

	/**
	 * Adds the extra code to JavaScript of media views
	 *
	 * @params stdClass|WP_Post $post Post object
	 */
	public function add_media_views_js( $post ) {
		global $wp_scripts;
		$handle = 'media-views';

		if ( !$scripts = $wp_scripts->get_data( $handle, 'data' ) ) {
			return false;
		}

		$settings = array(
			'id' => $post->ID,
			'nonce' => wp_create_nonce( 'update-post_' . $post->ID ),
		);

		if ( current_theme_supports( 'post-thumbnails', $this->_screen ) && post_type_supports( $this->_screen, 'thumbnail' ) ) {
			$featured_image_id = get_post_meta( $post->ID, '_thumbnail_id', true );
			$settings['featuredImageId'] = $featured_image_id ? $featured_image_id : -1;
		}

		$scripts .= ' _wpMediaViewsL10n.settings.post = ' . json_encode( $settings ) . ';';
		$wp_scripts->add_data( $handle, 'data', $scripts );

		return true;
	}
}

class IWF_MetaBox_Component extends IWF_Component_Abstract {
	public $title;

	protected $_metabox;

	protected $_id;

	protected $_option_set;

	/**
	 * Constructor
	 *
	 * @param IWF_MetaBox $metabox
	 * @param    string   $id
	 * @param    string   $title
	 * @param null        $option_set
	 */
	public function __construct( IWF_MetaBox $metabox, $id, $title = '', $option_set = null ) {
		parent::__construct();

		$this->_metabox = $metabox;
		$this->_id = $id;
		$this->_option_set = $option_set;

		$this->title = ( empty( $title ) && $title !== false ) ? $id : $title;
	}

	/**
	 * Returns the id
	 *
	 * @return    string
	 */
	public function get_id() {
		return $this->_id;
	}

	/**
	 * Returns the MetaBox
	 *
	 * @return IWF_MetaBox
	 */
	public function get_metabox() {
		return $this->_metabox;
	}

	/**
	 * Returns the option set
	 *
	 * @return string|null
	 */
	public function get_option_set() {
		return $this->_option_set;
	}

	public function render() {
		$html = $this->title ? IWF_Tag::create( 'p', null, IWF_Tag::create( 'strong', null, $this->title ) ) : '';
		$html .= parent::render();

		return $html;
	}
}

class IWF_MetaBox_Component_Element_Value extends IWF_Component_Element_Abstract {
	protected $_name;

	protected $_default;

	public function __construct( IWF_MetaBox_Component $component, $name, $default = null ) {
		$this->_name = $name;
		$this->_default = $default;

		parent::__construct( $component );
	}

	public function render() {
		$args = func_get_args();
		$value = $this->_default;

		if ( $this->_component->get_metabox()->is_post() ) {
			if ( $post = array_shift( $args ) ) {
				$value = ( $meta_value = get_post_meta( $post->ID, $this->_name, true ) ) ? $meta_value : $value;
			}

		} else {
			$value = get_option( $this->_name, $value );
		}

		return $value;
	}
}

abstract class IWF_MetaBox_Component_Element_FormField_Abstract extends IWF_Component_Element_FormField_Abstract {
	protected $_stored_value = false;

	public function __construct( IWF_MetaBox_Component $component, $name, $value = null, array $args = array() ) {
		parent::__construct( $component, $name, $value, $args );

		if ( $this->_component->get_metabox()->is_post() ) {
			add_action( 'save_post', array( $this, 'save_post_meta_by_request' ) );
			add_action( 'save_post', array( $this, 'save_preview_postmeta' ) );

			if ( !has_filter( 'get_post_metadata', array( 'IWF_MetaBox_Component_Element_FormField_Abstract', 'get_preview_postmeta' ) ) ) {
				add_filter( 'get_post_metadata', array( 'IWF_MetaBox_Component_Element_FormField_Abstract', 'get_preview_postmeta' ), 10, 4 );
			}

		} else {
			add_action( 'admin_menu', array( $this, 'register_option' ) );
			add_action( 'iwf_settings_page_save_' . $this->_component->get_metabox()->get_screen(), array( $this, 'save_option_by_request' ) );
		}
	}

	public function initialize() {
		parent::initialize();

		if ( in_array( 'chkrequired', $this->_validation ) ) {
			$required_mark = '<span style="color: #B00C0C;">*</span>';

			if ( $this->_component->title && !preg_match( '|' . preg_quote( $required_mark ) . '$|', $this->_component->title ) ) {
				$this->_component->title .= ' ' . $required_mark;

			} else if ( !preg_match( '|' . preg_quote( $required_mark ) . '$|', $this->_component->get_metabox()->title ) ) {
				$this->_component->get_metabox()->title .= ' ' . $required_mark;
			}
		}
	}

	public function before_render() {
		$value = false;

		if ( $this->_component->get_metabox()->is_post() ) {
			$post = $this->_component->get_metabox()->get_current_post();

			if ( !isset( $post->ID ) ) {
				trigger_error( __( 'The meta box of post is required the `Post` object', 'iwf' ), E_USER_WARNING );

			} else {
				$value = $this->read_post_meta( $post->ID );
			}

		} else {
			$value = $this->read_option();
		}

		if ( $value !== false ) {
			$this->_value = $value;
		}
	}

	public function save_post_meta_by_request( $post_id ) {
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| wp_is_post_revision( $post_id )
			|| !isset( $_POST[$this->_name] )
			|| empty( $_POST['post_type'] )
			|| $_POST['post_type'] != $this->_component->get_metabox()->get_screen()
			|| (
				$this->_component->get_metabox()->get_capability()
				&& !current_user_can( $this->_component->get_metabox()->get_capability(), $post_id )
			)
		) {
			return $post_id;
		}

		$uniq_id = $this->_component->get_metabox()->generate_uniq_id();
		$nonce = isset( $_POST[$uniq_id . '_nonce'] ) ? $_POST[$uniq_id . '_nonce'] : '';

		if ( !$nonce || !wp_verify_nonce( $nonce, $uniq_id ) ) {
			return $post_id;
		}

		$this->save_post_meta( $post_id, $_POST[$this->_name] );

		return $post_id;
	}

	public function save_preview_postmeta( $post_id ) {
		global $wpdb;

		if (
			wp_is_post_revision( $post_id )
			&& isset( $_POST[$this->_name] )
			&& (
				!$this->_component->get_metabox()->get_capability()
				|| current_user_can( $this->_component->get_metabox()->get_capability(), $post_id )
			)
		) {
			$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id = {$post_id} AND meta_key = '{$this->_name}'" );
			$value = $_POST[$this->_name];

			if ( !is_array( $value ) ) {
				$value = trim( $value );
			}

			$value = stripslashes_deep( $value );
			add_metadata( 'post', $post_id, $this->_name, $value );
		}
	}

	public function read_post_meta( $post_id ) {
		$value = get_post_meta( $post_id, $this->_name, true );

		return ( !empty( $value ) || $value === '0' || $value === '' ) ? $value : false;
	}

	public function save_post_meta( $post_id, $value ) {
		if ( !is_array( $value ) ) {
			$value = trim( $value );
		}

		$value = stripslashes_deep( $value );
		update_post_meta( $post_id, $this->_name, $value );

		return true;
	}

	public function register_option() {
		if ( $this->read_option() === false && ( !empty( $this->_value ) || $this->_value === 0 ) ) {
			update_option( $this->_name, $this->_value );
		}
	}

	public function save_option_by_request() {
		if ( isset( $_POST[$this->_name] ) ) {
			$this->save_option( $_POST[$this->_name] );
		}
	}

	public function read_option() {
		if ( $this->_component->get_option_set() ) {
			$values = (array)get_option( $this->_component->get_option_set() );
			$value = iwf_get_array( $values, $this->_name );

		} else {
			$value = get_option( $this->_name );
		}

		return ( !empty( $value ) || $value === '0' || $value === '' ) ? $value : false;
	}

	public function save_option( $value ) {
		if ( !is_array( $value ) ) {
			$value = trim( $value );
		}

		$value = stripslashes_deep( $value );

		if ( $this->_component->get_option_set() ) {
			$values = (array)get_option( $this->_component->get_option_set() );
			iwf_set_array( $values, $this->_name, $value );
			update_option( $this->_component->get_option_set(), $values );

		} else {
			update_option( $this->_name, $value );
		}
	}

	public static function get_preview_postmeta( $return, $post_id, $meta_key, $single ) {
		if ( ( $preview_id = IWF_Post::get_preview_id( $post_id ) ) && $meta_key != '_wp_page_template' ) {
			if ( $post_id != $preview_id ) {
				$return = get_post_meta( $preview_id, $meta_key, $single );
			}
		}

		return $return;
	}
}

class IWF_MetaBox_Component_Element_FormField_Text extends IWF_MetaBox_Component_Element_FormField_Abstract {
}

class IWF_MetaBox_Component_Element_FormField_Password extends IWF_MetaBox_Component_Element_FormField_Abstract {
}

class IWF_MetaBox_Component_Element_FormField_Hidden extends IWF_MetaBox_Component_Element_FormField_Abstract {
}

class IWF_MetaBox_Component_Element_FormField_Textarea extends IWF_MetaBox_Component_Element_FormField_Abstract {
}

class IWF_MetaBox_Component_Element_FormField_Checkbox extends IWF_MetaBox_Component_Element_FormField_Abstract {
	public function register_option() {
		if ( $this->read_option() === false && !empty( $this->_value ) && !empty( $this->_args['checked'] ) ) {
			$this->save_option( $this->_value );
		}
	}

	public function before_render() {
		$value = false;

		if ( $this->_component->get_metabox()->is_post() ) {
			$post = $this->_component->get_metabox()->get_current_post();

			if ( !isset( $post->ID ) ) {
				trigger_error( __( 'The meta box of post is required the `Post` object', 'iwf' ), E_USER_WARNING );

			} else {
				$value = $this->read_post_meta( $post->ID );
			}

		} else {
			$value = $this->read_option();
		}

		if ( $value !== false ) {
			$this->_args['checked'] = ( $value == $this->_value );
			unset( $this->_args['selected'] );
		}
	}
}

class IWF_MetaBox_Component_Element_FormField_Radio extends IWF_MetaBox_Component_Element_FormField_Abstract {
	public function register_option() {
		if (
			$this->read_option() === false
			&& !empty( $this->_value )
			&& !empty( $this->_args['checked'] )
			&& in_array( $this->_args['checked'], array_values( (array)$this->_value ) )
		) {
			$this->save_option( $this->_args['checked'] );
		}
	}

	public function before_render() {
		$value = false;

		if ( $this->_component->get_metabox()->is_post() ) {
			$post = $this->_component->get_metabox()->get_current_post();

			if ( !isset( $post->ID ) ) {
				trigger_error( __( 'The meta box of post is required the `Post` object', 'iwf' ), E_USER_WARNING );

			} else {
				$value = $this->read_post_meta( $post->ID );
			}

		} else {
			$value = $this->read_option();
		}

		if ( $value !== false ) {
			$this->_args['checked'] = in_array( $value, (array)$this->_value ) ? $value : false;
			unset( $this->_args['selected'] );
		}
	}
}

class IWF_MetaBox_Component_Element_FormField_Select extends IWF_MetaBox_Component_Element_FormField_Abstract {
	public function register_option() {
		if (
			$this->read_option() === false
			&& !empty( $this->_value )
			&& !empty( $this->_args['selected'] )
			&& in_array( $this->_args['selected'], array_values( (array)$this->_value ) )
		) {
			$this->save_option( $this->_args['selected'] );
		}
	}

	public function before_render() {
		$value = false;

		if ( $this->_component->get_metabox()->is_post() ) {
			$post = $this->_component->get_metabox()->get_current_post();

			if ( !isset( $post->ID ) ) {
				trigger_error( __( 'The meta box of post is required the `Post` object', 'iwf' ), E_USER_WARNING );

			} else {
				$value = $this->read_post_meta( $post->ID );
			}

		} else {
			$value = $this->read_option();
		}

		if ( $value !== false ) {
			$this->_args['selected'] = in_array( $value, (array)$this->_value ) ? $value : false;
			unset( $this->_args['checked'] );
		}
	}
}


class IWF_MetaBox_Component_Element_FormField_Checkboxes extends IWF_MetaBox_Component_Element_FormField_Abstract {
	public function register() {
		if ( !is_array( $this->_value ) ) {
			$this->_value = (array)$this->_value;
		}

		if (
			$this->read_option() === false
			&& !empty( $this->_value )
			&& !empty( $this->_args['selected'] )
			&& in_array( $this->_args['selected'], array_values( (array)$this->_value ) )
		) {
			if ( !is_array( $this->_args['selected'] ) ) {
				$this->_args['selected'] = (array)$this->_args['selected'];
			}

			foreach ( $this->_args['selected'] as $i => $selected ) {
				if ( !in_array( $selected, $this->_value ) ) {
					unset( $this->_args['selected'][$i] );
				}
			}

			$this->save_option( $this->_args['selected'] );
		}
	}

	public function before_render() {
		if ( $this->_component->get_metabox()->is_post() ) {
			$post = $this->_component->get_metabox()->get_current_post();

			if ( !isset( $post->ID ) ) {
				trigger_error( __( 'The meta box of post is required the `Post` object', 'iwf' ), E_USER_WARNING );

			} else {
				$value = $this->read_post_meta( $post->ID );
			}

		} else {
			$value = $this->read_option();
		}

		if ( $value !== false ) {
			unset( $this->_args['checked'], $this->_args['selected'] );

			if ( !is_array( $value ) ) {
				$value = (array)$value;
			}

			foreach ( $value as $_value ) {
				if ( in_array( $_value, $this->_value ) ) {
					$this->_args['selected'][] = $_value;
				}
			}
		}
	}

	public function save_option( $value ) {
		if ( is_array( $value ) ) {
			$value = array_filter( $value );
		}

		parent::save_option( $value );
	}

	public function save_post_meta( $post_id, $value ) {
		if ( is_array( $value ) ) {
			$value = array_filter( $value );
		}

		parent::save_post_meta( $post_id, $value );
	}
}

class IWF_MetaBox_Component_Element_FormField_Wysiwyg extends IWF_MetaBox_Component_Element_FormField_Abstract {
	public function initialize() {
		parent::initialize();

		if ( !isset( $this->_args['settings'] ) ) {
			$this->_args['settings'] = array();
		}

		$this->_args['id'] = $this->_name;
	}

	public function render() {
		$editor = '';

		if ( version_compare( get_bloginfo( 'version' ), '3.3', '>=' ) && function_exists( 'wp_editor' ) ) {
			ob_start();
			wp_editor( $this->_value, $this->_args['id'], $this->_args['settings'] );
			$editor = ob_get_clean();

		} else {
			trigger_error( 'The TinyMCE has been required for the WordPress 3.3 or above' );
		}

		return $editor;
	}
}

class IWF_MetaBox_Component_Element_FormField_Visual extends IWF_MetaBox_Component_Element_FormField_Wysiwyg {
}
