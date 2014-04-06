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

class IWF_Taxonomy {
	protected $_slug;

	protected $_post_type;

	protected $_args = array();

	protected $_components = array();

	public function __construct( $slug, $post_type, $args = array() ) {
		global $wp_taxonomies;

		$this->_slug = $slug;
		$this->_post_type = $post_type;
		$this->_args = wp_parse_args( $args );

		if ( !has_action( 'edited_' . $this->_slug, array( $this, 'save' ) ) ) {
			add_action( 'edited_' . $this->_slug, array( $this, 'save' ), 10, 2 );
		}

		if ( !has_action( 'created_' . $this->_slug, array( $this, 'save' ) ) ) {
			add_action( 'created_' . $this->_slug, array( $this, 'save' ), 10, 2 );
		}

		if ( !has_action( 'init', array( 'IWF_Taxonomy', 'add_rewrite_hooks' ) ) ) {
			add_action( 'init', array( 'IWF_Taxonomy', 'add_rewrite_hooks' ), 10, 1 );
		}

		if ( !has_action( $this->_slug . '_add_form_fields', array( $this, 'display_add_form' ) ) ) {
			add_action( $this->_slug . '_add_form_fields', array( $this, 'display_add_form' ), 10, 1 );
		}

		if ( !has_action( $this->_slug . '_edit_form_fields', array( $this, 'display_edit_form' ) ) ) {
			add_action( $this->_slug . '_edit_form_fields', array( $this, 'display_edit_form' ), 10, 2 );
		}

		if ( !has_action( 'admin_head', array( 'IWF_Taxonomy', 'add_local_style' ) ) ) {
			add_action( 'admin_head', array( 'IWF_Taxonomy', 'add_local_style' ), 10 );
		}

		if ( !has_action( 'delete_term', array( 'IWF_Taxonomy', 'delete_term_meta' ) ) ) {
			add_action( 'delete_term', array( 'IWF_Taxonomy', 'delete_term_meta' ), 10, 4 );
		}

		if ( !isset( $wp_taxonomies[$this->_slug] ) ) {
			if ( empty( $this->_args['label'] ) ) {
				$this->_args['label'] = $this->_slug;
			}

			if ( empty( $this->_args['labels'] ) ) {
				$this->_args['labels'] = array(
					'name' => $this->_args['label'],
					'singular_name' => $this->_args['label'],
					'search_items' => sprintf( __( 'Search %s', 'iwf' ), $this->_args['label'] ),
					'popular_items' => sprintf( __( 'Popular %s', 'iwf' ), $this->_args['label'] ),
					'all_items' => sprintf( __( 'All %s', 'iwf' ), $this->_args['label'] ),
					'parent_item' => sprintf( __( 'Parent %s', 'iwf' ), $this->_args['label'] ),
					'parent_item_colon' => sprintf( __( 'Parent %s:', 'iwf' ), $this->_args['label'] ),
					'edit_item' => sprintf( __( 'Edit %s', 'iwf' ), $this->_args['label'] ),
					'view_item' => sprintf( __( 'View %s', 'iwf' ), $this->_args['label'] ),
					'update_item' => sprintf( __( 'Update %s', 'iwf' ), $this->_args['label'] ),
					'add_new_item' => sprintf( __( 'Add New %s', 'iwf' ), $this->_args['label'] ),
					'new_item_name' => sprintf( __( 'New %s Name', 'iwf' ), $this->_args['label'] ),
					'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'iwf' ), $this->_args['label'] ),
					'add_or_remove_items' => sprintf( __( 'Add or remove %s', 'iwf' ), $this->_args['label'] ),
					'choose_from_most_used' => sprintf( __( 'Choose from the most used %s', 'iwf' ), $this->_args['label'] ),
				);
			}

			add_action( 'registered_taxonomy', array( $this, 'add_rewrite_rules' ), 10, 3);

			register_taxonomy( $this->_slug, $this->_post_type, $this->_args );

		} else {
			register_taxonomy_for_object_type( $this->_slug, $this->_post_type );
		}
	}

	public function get_slug() {
		return $this->_slug;
	}

	public function get_post_type() {
		return $this->_post_type;
	}

	public function component( $id, $title = null ) {
		if ( is_object( $id ) && is_a( $id, 'IWF_Taxonomy_Component' ) ) {
			$component = $id;
			$id = $component->get_id();

			if ( isset( $this->_components[$id] ) ) {
				if ( $this->_components[$id] !== $component ) {
					$this->_components[$id] = $component;
				}

				return $component;
			}

		} else if ( is_string( $id ) && isset( $this->_components[$id] ) ) {
			return $this->_components[$id];

		} else {
			$component = new IWF_Taxonomy_Component( $this, $id, $title );
		}

		$this->_components[$id] = $component;

		return $component;
	}

	public function c( $id, $title = null ) {
		return $this->component( $id, $title );
	}

	public function save( $term_id, $tt_id ) {
		$option_key = self::get_option_key( $term_id, $this->_slug );
		$values = get_option( $option_key );

		if ( !is_array( $values ) ) {
			$values = array();
		}

		do_action_ref_array( 'iwf_before_save_taxonomy', array( $this->_slug, &$this, &$values, $term_id, $tt_id ) );
		do_action_ref_array( 'iwf_before_save_taxonomy_' . $this->_slug, array( &$this, &$values, $term_id, $tt_id ) );

		foreach ( $this->_components as $component ) {
			$component->save( $values, $term_id, $tt_id );
		}

		do_action_ref_array( 'iwf_after_save_taxonomy', array( $this->_slug, &$this, &$values, $term_id, $tt_id ) );
		do_action_ref_array( 'iwf_after_save_taxonomy_' . $this->_slug, array( &$this, &$values, $term_id, $tt_id ) );

		update_option( $option_key, $values );
	}

	public function display_add_form( $taxonomy ) {
		$html = '';

		do_action_ref_array( 'iwf_before_display_add_form_taxonomy', array( $this->_slug, &$this, &$html, $taxonomy ) );
		do_action_ref_array( 'iwf_before_display_add_form_taxonomy_' . $this->_slug, array( &$this, &$html, $taxonomy ) );

		foreach ( $this->_components as $component ) {
			$label = IWF_Tag::create( 'label', null, $component->title );
			$body = $component->render();
			$html .= IWF_Tag::create( 'div', array( 'class' => 'form-field' ), $label . "\n" . $body );
		}

		do_action_ref_array( 'iwf_after_display_add_form_taxonomy', array( $this->_slug, &$this, &$html, $taxonomy ) );
		do_action_ref_array( 'iwf_after_display_add_form_taxonomy_' . $this->_slug, array( &$this, &$html, $taxonomy ) );

		echo $html;
	}

	public function display_edit_form( stdClass $tag, $taxonomy ) {
		$html = '';

		do_action_ref_array( 'iwf_before_display_edit_form_taxonomy', array( $this->_slug, &$this, &$html, $tag, $taxonomy ) );
		do_action_ref_array( 'iwf_before_display_edit_form_taxonomy_' . $this->_slug, array( &$this, &$html, $tag, $taxonomy ) );

		foreach ( $this->_components as $component ) {
			$th = IWF_Tag::create( 'th', array( 'scope' => 'row', 'valign' => 'top' ), $component->title );
			$td = IWF_Tag::create( 'td', null, $component->render( $tag ) );
			$html .= IWF_Tag::create( 'tr', array( 'class' => 'form-field' ), $th . "\n" . $td );
		}

		do_action_ref_array( 'iwf_after_display_edit_form_taxonomy', array( $this->_slug, &$this, &$html, $tag, $taxonomy ) );
		do_action_ref_array( 'iwf_after_display_edit_form_taxonomy_' . $this->_slug, array( &$this, &$html, $tag, $taxonomy ) );

		echo $html;
	}

	public function delete_term_meta( $term, $tt_id, $taxonomy, $deleted_term ) {
		delete_option( self::get_option_key( $term, $taxonomy ) );
	}

	public function add_rewrite_rules( $taxonomy, $object_type, $args ) {
		global $wp_rewrite;

		if ( $wp_rewrite->permalink_structure ) {
			if ( $args['_builtin'] ) {
				return false;
			}

			if ( $taxonomy == 'category' ) {
				$taxonomy_part = ( $category_base = get_option( 'category_base' ) ) ? $category_base : $taxonomy;
				$taxonomy_slug = 'category_name';

			} else {
				if ( isset( $args['rewrite']['slug'] ) ) {
					$taxonomy_part = $args['rewrite']['slug'];

				} else {
					$taxonomy_part = $taxonomy;
				}

				$taxonomy_slug = $taxonomy;
			}

			// Archive by day
			// e.g) taxonomy/term/2014/01/01/page/1
			add_rewrite_rule( $taxonomy_part . '/(.+?)/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&monthnum=$matches[3]&day=$matches[4]', 'top' );
			add_rewrite_rule( $taxonomy_part . '/(.+?)/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/([0-9]{1,})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&monthnum=$matches[3]&day=$matches[4]&paged=$matches[5]', 'top' );

			// Archive by month
			// e.g) taxonomy/term/2014/01/page/1
			add_rewrite_rule( $taxonomy_part . '/(.+?)/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&monthnum=$matches[3]', 'top' );
			add_rewrite_rule( $taxonomy_part . '/(.+?)/([0-9]{4})/([0-9]{1,2})/page/([0-9]{1,})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]', 'top' );

			// Archive by year
			// e.g) taxonomy/term/2014/page/1
			add_rewrite_rule( $taxonomy_part . '/(.+?)/([0-9]{4})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]', 'top' );
			add_rewrite_rule( $taxonomy_part . '/(.+?)/([0-9]{4})/page/([0-9]{1,})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&paged=$matches[3]', 'top' );
		}

		return true;
	}

	protected static $get_archives_where_args = array();

	public static function add_rewrite_hooks() {
		add_filter( 'getarchives_join', array( 'IWF_Taxonomy', 'filter_get_archives_join' ), 10, 2 );
		add_filter( 'getarchives_where', array( 'IWF_Taxonomy', 'filter_get_archives_where' ), 10, 2 );
		add_filter( 'get_archives_link', array( 'IWF_Taxonomy', 'filter_get_archives_link' ), 20, 1 );
	}

	public static function filter_get_archives_where( $where, $args ) {
		self::$get_archives_where_args = $args;

		if ( isset( $args['post_type'] ) ) {
			$where = str_replace( "'post'", "'{$args['post_type']}'", $where );
		}

		$term_id = null;

		if ( !empty( $args['taxonomy'] ) && !empty( $args['term'] ) ) {
			if ( is_numeric( $args['term'] ) ) {
				$term_id = (int)$args['term'];

			} else {
				if ( $term = get_term_by( 'slug', $args['term'], $args['taxonomy'] ) ) {
					$term_id = $term->term_id;
				}
			}

			self::$get_archives_where_args['term_id'] = $term_id;
		}

		if ( !empty( $args['taxonomy'] ) && !empty( $term_id ) ) {
			global $wpdb;
			$where = $where . " AND {$wpdb->term_taxonomy}.taxonomy = '{$args['taxonomy']}' AND {$wpdb->term_taxonomy}.term_id = '{$term_id}'";
		}

		return $where;
	}

	public static function filter_get_archives_join( $join, $args ) {
		global $wpdb;

		if ( !empty( $args['taxonomy'] ) && !empty( self::$get_archives_where_args['term_id'] ) ) {
			$join = $join
				. " INNER JOIN {$wpdb->term_relationships} ON ( {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id )"
				. " INNER JOIN {$wpdb->term_taxonomy} ON ( {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id) ";
		}

		return $join;
	}

	public static function filter_get_archives_link( $link ) {
		global $wp_rewrite;

		$post_type = iwf_get_array( self::$get_archives_where_args, 'post_type' );

		if ( !$post_type ) {
			return $link;
		}

		$taxonomy = iwf_get_array( self::$get_archives_where_args, 'taxonomy' );
		$term_id = iwf_get_array( self::$get_archives_where_args, 'term_id' );
		$term = null;

		if ( $taxonomy && $term_id ) {
			$term = get_term( (int)$term_id, $taxonomy );

			if ( !$term ) {
				return $link;
			}

		} else {
			$taxonomy = $term = false;
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( $wp_rewrite->rules ) {
			$blog_url = untrailingslashit( home_url() );

			$front = substr( $wp_rewrite->front, 1 );
			$link = str_replace( $front, "", $link );

			$blog_url = preg_replace( '/https?:\/\//', '', $blog_url );
			$ret_link = str_replace( $blog_url, $blog_url . '/' . '%link_dir%', $link );

			if ( $taxonomy && $term ) {
				$taxonomy = ( $taxonomy == 'category' && get_option( 'category_base' ) ) ? get_option( 'category_base' ) : $taxonomy;
				$link_dir = $taxonomy . '/' . $term->slug;

			} else {
				if ( isset( $post_type_object->rewrite['slug'] ) ) {
					$link_dir = $post_type_object->rewrite['slug'];

				} else {
					$link_dir = $post_type;
				}
			}

			if ( $post_type_object->rewrite['with_front'] ) {
				$link_dir = $front . $link_dir;
			}

			$ret_link = str_replace( '%link_dir%', $link_dir, $ret_link );

		} else {
			if ( !preg_match( "|href='(.+?)'|", $link, $matches ) ) {
				return $link;

			} else {
				$url = iwf_create_url( $matches[1], array( 'post_type' => $post_type ) );
				$ret_link = preg_replace( "|href='(.+?)'|", "href='" . $url . "'", $link );
			}
		}

		return $ret_link;
	}

	public static function add_local_style() {
		global $pagenow;

		if ( $pagenow == 'edit-tags.php' ) {
			?>
			<style type="text/css">
				.form-field input[type=button],
				.form-field input[type=submit],
				.form-field input[type=reset],
				.form-field input[type=radio],
				.form-field input[type=checkbox] {
					width: auto;
				}

				.form-field .wp-editor-wrap textarea {
					border: none;
					width: 99.5%;
				}

				.form-wrap label {
					display: inline;
				}

				.form-wrap label:first-child {
					display: block;
				}
			</style>
			<?php
		}
	}

	public static function get_option_key( $term_id, $taxonomy ) {
		return 'term_meta_' . $taxonomy . '_' . $term_id;
	}

	public static function get_option( $term, $taxonomy, $key, $default = false ) {
		if ( !is_object( $term ) ) {
			if ( is_numeric( $term ) ) {
				$term = get_term( $term, $taxonomy );

			} else {
				$term = get_term_by( 'slug', $term, $taxonomy );
			}
		}

		if ( !is_object( $term ) || is_wp_error( $term ) ) {
			return $default;
		}

		$values = get_option( self::get_option_key( $term->term_id, $taxonomy ), false );

		if ( $values === false || !is_array( $values ) || !isset( $values[$key] ) ) {
			return $default;
		}

		return stripslashes_deep( $values[$key] );
	}

	public static function get_list_recursive( $taxonomy, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'key' => '%name (ID:%term_id)',
			'value' => 'term_id',
			'orderby' => 'name'
		) );

		$terms = get_terms( $taxonomy, array( 'get' => 'all', 'orderby' => $args['orderby'] ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$walker = new IWF_Taxonomy_List_Walker();

		return $walker->walk( $terms, 0, $args );
	}

	/**
	 * Get the parent terms of specified term
	 *
	 * @param int|string|stdClass $slug
	 * @param string $taxonomy
	 * @param boolean $include_current
	 * @param boolean $reverse
	 * @return array
	 */
	public static function get_parents( $slug, $taxonomy, $include_current = false, $reverse = false ) {
		if ( is_numeric( $slug ) ) {
			$slug = (int)$slug;
			$term = get_term_by( 'id', $slug, $taxonomy );

		} else if ( isset( $slug->term_id ) ) {
			$term = get_term_by( 'id', $slug->term_id, $taxonomy );

		} else {
			$term = get_term_by( 'slug', $slug, $taxonomy );
		}

		if ( !$term ) {
			return array();
		}

		$tree = $include_current ? array( $term ) : array();

		if ( $term->parent ) {
			$tmp_term = $term;

			while ( $tmp_term->parent ) {
				$tmp_term = get_term_by( 'id', $tmp_term->parent, $tmp_term->taxonomy );

				if ( !$tmp_term ) {
					break;

				} else {
					$tree[] = $tmp_term;
				}
			}
		}

		return $reverse ? $tree : array_reverse( $tree );
	}

	/**
	 * Get the term object by term id or slug or object.
	 *
	 * @param int|string|stdClass $term
	 * @param string $taxonomy
	 * @return bool|stdClass
	 */
	public static function get( $term, $taxonomy ) {
		$slug = $term_object = false;

		if ( is_numeric( $term ) ) {
			$term_object = get_term( (int)$term, $taxonomy );

		} else if ( is_object( $term ) && !empty( $term->slug ) ) {
			$slug = $term->slug;

		} else {
			$slug = $term;
		}

		if ( !$slug && !$term_object ) {
			return false;

		} else if ( !$term_object ) {
			$term_object = get_term_by( 'slug', (string)$slug, $taxonomy );
		}

		return $term_object;
	}
}

class IWF_Taxonomy_List_Walker extends Walker {
	public $tree_type = 'taxonomy';

	public $db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );

	public function start_el( &$output, $term, $depth, $args, $id = 0 ) {
		$key_format = iwf_get_array( $args, 'key' );
		$value_prop = iwf_get_array( $args, 'value' );

		$replace = $search = array();

		foreach ( get_object_vars( $term ) as $key => $value ) {
			$search[] = '%' . $key;
			$replace[] = $value;
		}

		$key = str_replace( $search, $replace, $key_format );
		$value = isset( $term->{$value_prop} ) ? $term->{$value_prop} : null;

		$prefix = str_repeat( '-', $depth );

		if ( $prefix ) {
			$prefix .= ' ';
		}

		$output[$prefix . $key] = $value;
	}
}

class IWF_Taxonomy_Component extends IWF_Component {
	public $title;

	protected $_id;

	protected $_taxonomy;

	public function __construct( IWF_Taxonomy $taxonomy, $id, $title = null ) {
		parent::__construct();

		$this->_id = $id;
		$this->_taxonomy = $taxonomy;

		$this->title = empty( $title ) ? $this->_id : $title;
	}

	public function get_taxonomy() {
		return $this->_taxonomy;
	}

	public function get_id() {
		return $this->_id;
	}

	public function save( array &$values, $term_id, $tt_id ) {
		foreach ( $this->_elements as $element ) {
			if ( is_subclass_of( $element, 'IWF_Taxonomy_Component_Element_FormField_Abstract' ) ) {
				$element->save( $values, $term_id, $tt_id );
			}
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Abstract extends IWF_Component_Element_FormField_Abstract {
	protected $_stored_value;

	public function __construct( IWF_Taxonomy_Component $component, $name, $value = null, array $args = array() ) {
		parent::__construct( $component, $name, $value, $args );
	}

	public function save( array &$values, $term_id, $tt_id ) {
		if ( !isset( $_POST[$this->_name] ) ) {
			return false;
		}

		$values[$this->_name] = $_POST[$this->_name];

		return true;
	}

	public function before_render( stdClass $tag = null ) {
		if ( $tag && !empty( $tag->term_id ) ) {
			$this->_stored_value = IWF_Taxonomy::get_option( $tag->term_id, $this->_component->get_taxonomy()->get_slug(), $this->_name );
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Text extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( stdClass $tag = null ) {
		parent::before_render( $tag );

		if ( $this->_stored_value !== false ) {
			$this->_value = $this->_stored_value;
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Textarea extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( stdClass $tag = null ) {
		parent::before_render( $tag );

		if ( $this->_stored_value !== false ) {
			$this->_value = $this->_stored_value;
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Checkbox extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( stdClass $tag = null ) {
		parent::before_render( $tag );

		if ( $this->_stored_value !== false ) {
			unset( $this->_args['checked'], $this->_args['selected'] );
			$this->_args['checked'] = ( $this->_stored_value == $this->_value );
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Radio extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( stdClass $tag = null ) {
		parent::before_render( $tag );

		if ( $this->_stored_value !== false ) {
			unset( $this->_args['checked'], $this->_args['selected'] );
			$this->_args['checked'] = in_array( $this->_stored_value, (array)$this->_value ) ? $this->_stored_value : false;
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Select extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( stdClass $tag = null ) {
		parent::before_render( $tag );

		if ( $this->_stored_value !== false ) {
			unset( $this->_args['checked'], $this->_args['selected'] );
			$this->_args['selected'] = in_array( $this->_stored_value, (array)$this->_value ) ? $this->_stored_value : false;
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Wysiwyg extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function initialize() {
		parent::initialize();

		if ( !isset( $this->_args['settings'] ) ) {
			$this->_args['settings'] = array();
		}

		$this->_args['id'] = $this->_name;
	}

	public function before_render( stdClass $tag = null ) {
		parent::before_render( $tag );

		if ( $this->_stored_value !== false ) {
			$this->_value = $this->_stored_value;
		}
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