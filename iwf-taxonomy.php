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
	protected $slug;

	protected $post_type;

	protected $args = array();

	protected $components = array();

	public function __construct( $slug, $post_type, $args = array() ) {
		global $wp_taxonomies;

		$this->slug      = $slug;
		$this->post_type = $post_type;
		$this->args      = wp_parse_args( $args );

		if ( ! has_action( 'edited_' . $this->slug, array( $this, 'save' ) ) ) {
			add_action( 'edited_' . $this->slug, array( $this, 'save' ), 10, 2 );
		}

		if ( ! has_action( 'created_' . $this->slug, array( $this, 'save' ) ) ) {
			add_action( 'created_' . $this->slug, array( $this, 'save' ), 10, 2 );
		}

		if ( ! has_action( $this->slug . '_add_form_fields', array( $this, 'display_add_form' ) ) ) {
			add_action( $this->slug . '_add_form_fields', array( $this, 'display_add_form' ), 10, 1 );
		}

		if ( ! has_action( $this->slug . '_edit_form_fields', array( $this, 'display_edit_form' ) ) ) {
			add_action( $this->slug . '_edit_form_fields', array( $this, 'display_edit_form' ), 10, 2 );
		}

		if ( ! has_action( 'admin_head', array( 'IWF_Taxonomy', 'add_local_style' ) ) ) {
			add_action( 'admin_head', array( 'IWF_Taxonomy', 'add_local_style' ), 10 );
		}

		if ( ! has_action( 'delete_term', array( 'IWF_Taxonomy', 'delete_term_meta' ) ) ) {
			add_action( 'delete_term', array( 'IWF_Taxonomy', 'delete_term_meta' ), 10, 4 );
		}

		if ( ! isset( $wp_taxonomies[ $this->slug ] ) ) {
			if ( empty( $this->args['label'] ) ) {
				$this->args['label'] = $this->slug;
			}

			if ( empty( $this->args['labels'] ) ) {
				$this->args['labels'] = array(
					'name'                       => $this->args['label'],
					'singular_name'              => $this->args['label'],
					'search_items'               => sprintf( __( 'Search %s', 'iwf' ), $this->args['label'] ),
					'popular_items'              => sprintf( __( 'Popular %s', 'iwf' ), $this->args['label'] ),
					'all_items'                  => sprintf( __( 'All %s', 'iwf' ), $this->args['label'] ),
					'parent_item'                => sprintf( __( 'Parent %s', 'iwf' ), $this->args['label'] ),
					'parent_item_colon'          => sprintf( __( 'Parent %s:', 'iwf' ), $this->args['label'] ),
					'edit_item'                  => sprintf( __( 'Edit %s', 'iwf' ), $this->args['label'] ),
					'view_item'                  => sprintf( __( 'View %s', 'iwf' ), $this->args['label'] ),
					'update_item'                => sprintf( __( 'Update %s', 'iwf' ), $this->args['label'] ),
					'add_new_item'               => sprintf( __( 'Add New %s', 'iwf' ), $this->args['label'] ),
					'new_item_name'              => sprintf( __( 'New %s Name', 'iwf' ), $this->args['label'] ),
					'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'iwf' ), $this->args['label'] ),
					'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'iwf' ), $this->args['label'] ),
					'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'iwf' ), $this->args['label'] ),
				);
			}

			add_action( 'registered_taxonomy', array( $this, 'add_rewrite_rules' ), 10, 3 );

			register_taxonomy( $this->slug, $this->post_type, $this->args );

		} else {
			register_taxonomy_for_object_type( $this->slug, $this->post_type );
		}
	}

	public function get_slug() {
		return $this->slug;
	}

	public function get_post_type() {
		return $this->post_type;
	}

	public function component( $id, $title = null ) {
		if ( is_object( $id ) && is_a( $id, 'IWF_Taxonomy_Component' ) ) {
			$component = $id;
			$id        = $component->get_id();

			if ( isset( $this->components[ $id ] ) ) {
				if ( $this->components[ $id ] !== $component ) {
					$this->components[ $id ] = $component;
				}

				return $component;
			}

		} else if ( is_string( $id ) && isset( $this->components[ $id ] ) ) {
			return $this->components[ $id ];

		} else {
			$component = new IWF_Taxonomy_Component( $this, $id, $title );
		}

		$this->components[ $id ] = $component;

		return $component;
	}

	public function c( $id, $title = null ) {
		return $this->component( $id, $title );
	}

	public function save( $term_id, $tt_id ) {
		$option_key = self::get_option_key( $term_id, $this->slug );
		$values     = get_option( $option_key );

		if ( ! is_array( $values ) ) {
			$values = array();
		}

		do_action_ref_array( 'iwf_before_save_taxonomy', array( $this->slug, &$this, &$values, $term_id, $tt_id ) );
		do_action_ref_array( 'iwf_before_save_taxonomy_' . $this->slug, array( &$this, &$values, $term_id, $tt_id ) );

		foreach ( $this->components as $component ) {
			$component->save( $values, $term_id, $tt_id );
		}

		do_action_ref_array( 'iwf_after_save_taxonomy', array( $this->slug, &$this, &$values, $term_id, $tt_id ) );
		do_action_ref_array( 'iwf_after_save_taxonomy_' . $this->slug, array( &$this, &$values, $term_id, $tt_id ) );

		update_option( $option_key, $values );
	}

	public function display_add_form( $taxonomy ) {
		$html = '';

		do_action_ref_array( 'iwf_before_display_add_form_taxonomy', array( $this->slug, &$this, &$html, $taxonomy ) );
		do_action_ref_array( 'iwf_before_display_add_form_taxonomy_' . $this->slug, array( &$this, &$html, $taxonomy ) );

		foreach ( $this->components as $component ) {
			$label = IWF_Tag::create( 'label', null, $component->title );
			$body  = $component->render();
			$html .= IWF_Tag::create( 'div', array( 'class' => 'form-field' ), $label . "\n" . $body );
		}

		do_action_ref_array( 'iwf_after_display_add_form_taxonomy', array( $this->slug, &$this, &$html, $taxonomy ) );
		do_action_ref_array( 'iwf_after_display_add_form_taxonomy_' . $this->slug, array( &$this, &$html, $taxonomy ) );

		echo $html;
	}

	public function display_edit_form( $tag, $taxonomy ) {
		$html = '';

		do_action_ref_array( 'iwf_before_display_edit_form_taxonomy', array( $this->slug, &$this, &$html, $tag, $taxonomy ) );
		do_action_ref_array( 'iwf_before_display_edit_form_taxonomy_' . $this->slug, array( &$this, &$html, $tag, $taxonomy ) );

		foreach ( $this->components as $component ) {
			$th = IWF_Tag::create( 'th', array( 'scope' => 'row', 'valign' => 'top' ), $component->title );
			$td = IWF_Tag::create( 'td', null, $component->render( $tag ) );
			$html .= IWF_Tag::create( 'tr', array( 'class' => 'form-field' ), $th . "\n" . $td );
		}

		do_action_ref_array( 'iwf_after_display_edit_form_taxonomy', array( $this->slug, &$this, &$html, $tag, $taxonomy ) );
		do_action_ref_array( 'iwf_after_display_edit_form_taxonomy_' . $this->slug, array( &$this, &$html, $tag, $taxonomy ) );

		echo $html;
	}

	public function delete_term_meta( $term, $tt_id, $taxonomy, $deleted_term ) {
		delete_option( self::get_option_key( $term, $taxonomy ) );
	}

	public static function add_rewrite_rules( $taxonomy, $object_type, $args ) {
		global $wp_rewrite;

		if ( $wp_rewrite->permalink_structure ) {
			if ( $args['_builtin'] ) {
				return false;
			}

			foreach ( $args['object_type'] as $post_type ) {
				$post_type_object = get_post_type_object( $post_type );
				$front            = '';

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

				if ( ! empty( $post_type_object->rewrite['with_front'] ) ) {
					$taxonomy_part = substr( $wp_rewrite->front, 1 ) ? substr( $wp_rewrite->front, 1 ) . '/' . $taxonomy_part : $taxonomy_part;
				}

				// Archive by day
				// e.g) taxonomy/term/2014/01/01/page/1
				add_rewrite_rule( $taxonomy_part . '/([^/]+?)/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&monthnum=$matches[3]&day=$matches[4]', 'top' );
				add_rewrite_rule( $taxonomy_part . '/([^/]+?)/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/([0-9]{1,})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&monthnum=$matches[3]&day=$matches[4]&paged=$matches[5]', 'top' );

				// Archive by month
				// e.g) taxonomy/term/2014/01/page/1
				add_rewrite_rule( $taxonomy_part . '/([^/]+?)/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&monthnum=$matches[3]', 'top' );
				add_rewrite_rule( $taxonomy_part . '/([^/]+?)/([0-9]{4})/([0-9]{1,2})/page/([0-9]{1,})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]', 'top' );

				// Archive by year
				// e.g) taxonomy/term/2014/page/1
				add_rewrite_rule( $taxonomy_part . '/([^/]+?)/([0-9]{4})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]', 'top' );
				add_rewrite_rule( $taxonomy_part . '/([^/]+?)/([0-9]{4})/page/([0-9]{1,})/?$', 'index.php?' . $taxonomy_slug . '=$matches[1]&year=$matches[2]&paged=$matches[3]', 'top' );
			}
		}

		return true;
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

	public static function get_option( $term, $taxonomy = null, $key = null, $default = false ) {
		$term = self::get( $term, $taxonomy );

		if ( ! $term ) {
			return $default;
		}

		$values = get_option( self::get_option_key( $term->term_id, $term->taxonomy ), false );

		if ( $values === false || ! is_array( $values ) || ( $key && ! isset( $values[ $key ] ) ) ) {
			return $default;
		}

		return $key ? stripslashes_deep( $values[ $key ] ) : stripslashes_deep( $values );
	}

	public static function get_list_recursive( $taxonomy, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'key'     => '%name (ID:%term_id)',
			'value'   => 'term_id',
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
	 * @param int|string $slug
	 * @param string $taxonomy
	 * @param boolean $include_current
	 * @param boolean $reverse
	 *
	 * @return array
	 */
	public static function get_parents( $slug, $taxonomy = null, $include_current = false, $reverse = false ) {
		$term = self::get( $slug, $taxonomy );

		if ( ! $term ) {
			return array();
		}

		$tree = $include_current ? array( $term ) : array();

		if ( $term->parent ) {
			$tmp_term = $term;

			while ( $tmp_term->parent ) {
				$tmp_term = get_term_by( 'id', $tmp_term->parent, $tmp_term->taxonomy );

				if ( ! $tmp_term ) {
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
	 * @param int|string $term
	 * @param string $taxonomy
	 *
	 * @return bool|stdClass
	 */
	public static function get( $term, $taxonomy = null ) {
		$term_object = false;

		if ( ! is_object( $term ) && ! $taxonomy ) {
			return false;
		}

		if ( is_numeric( $term ) ) {
			$term_object = get_term_by( 'id', (int) $term, $taxonomy );

		} else if ( is_object( $term ) && ! empty( $term->slug ) && ! empty( $term->taxonomy ) ) {
			$term_object = get_term_by( 'slug', $term->slug, $term->taxonomy );

		} else if ( is_string( $term ) ) {
			$term_object = get_term_by( 'slug', $term, $taxonomy );
		}

		if ( ! $term_object ) {
			return false;
		}

		return $term_object;
	}

	/**
	 * Save the taxonomy meta
	 *
	 * @param      $term
	 * @param      $taxonomy
	 * @param      $key
	 * @param null $value
	 */
	public static function save_meta( $term, $taxonomy, $key, $value = null ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $_key => $_value ) {
				self::save_meta( $term, $taxonomy, $_key, $_value );
			}

		} else {
			$term = self::get( $term, $taxonomy );

			$option_key = self::get_option_key( $term->term_id, $term->slug );
			$values     = get_option( $option_key );

			if ( ! is_array( $values ) ) {
				$values = array();
			}

			$values[ $key ] = $value;

			update_option( $option_key, $values );
		}
	}

	/**
	 * Get the terms with ordered by recent added posts by $posts_query.
	 *
	 * @param string $post_type
	 * @param string $taxonomy
	 * @param array $post_query
	 * @param array $filter
	 * @param int $number
	 * @param int $posts_per_loop
	 * @param int $cache_time
	 *
	 * @return array|bool|mixed
	 */
	public static function get_posted( $post_type = '', $taxonomy = '', $post_query = array(), $filter = array(), $number = 0, $posts_per_loop = 10, $cache_time = 300 ) {
		global $wpdb;

		if ( ! $post_type ) {
			$post_type = 'post';
		}

		if ( ! $taxonomy ) {
			$taxonomy = 'category';
		}

		$cache_key = 'iwf_' . iwf_short_hash( __CLASS__ . __METHOD__ . serialize( func_get_args() ) );

		if ( $cache_time < 1 ) {
			delete_transient( $cache_key );
		}

		if ( $terms = get_transient( $cache_key ) ) {
			return $terms;
		}

		$total_posts    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) AS count FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = '%s'", $post_type ) );
		$posts_per_loop = $total_posts > (int) $posts_per_loop ? (int) $posts_per_loop : $total_posts;

		if ( $posts_per_loop < 1 ) {
			$posts_per_loop = 10;
		}

		$loaded = 0;
		$terms  = $term_ids = array();

		while ( true ) {
			$posts = get_posts( array_merge( (array) $post_query, array(
				'post_type'        => $post_type,
				'posts_per_page'   => $posts_per_loop,
				'offset'           => $loaded,
				'suppress_filters' => false
			) ) );

			foreach ( $posts as $post ) {
				$assoc_terms = get_the_terms( $post->ID, $taxonomy );

				if ( is_wp_error( $assoc_terms ) ) {
					return false;
				}

				foreach ( $assoc_terms as $assoc_term ) {
					if ( ! in_array( $assoc_term->term_id, $term_ids ) ) {
						$terms[]    = $assoc_term;
						$term_ids[] = $assoc_term->term_id;

						if ( count( $filter ) > 0 ) {
							$terms = wp_list_filter( $terms, $filter, 'AND' );
						}

						if ( $number && count( $terms ) >= $number ) {
							break 3;
						}
					}
				}
			}

			$loaded += $posts_per_loop;

			if ( $loaded >= $total_posts ) {
				break;
			}
		}

		if ( $cache_time > 0 ) {
			set_transient( $cache_key, $terms, $cache_time );
		}

		return $terms;
	}

	public static function get_root( $term, $taxonomy = null ) {
		$term = self::get( $term, $taxonomy );

		if ( ! $term ) {
			return false;
		}

		$taxonomy_object = get_taxonomy( $term->taxonomy );

		if ( ! $taxonomy_object->hierarchical ) {
			return false;
		}

		$parent_terms = self::get_parents( $term, null, true, false );

		return reset( $parent_terms );
	}
}

class IWF_Taxonomy_List_Walker extends Walker {
	public $tree_type = 'taxonomy';

	public $db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );

	public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$key_format = iwf_get_array( $args, 'key' );
		$value_prop = iwf_get_array( $args, 'value' );

		$replace = $search = array();

		foreach ( get_object_vars( $object ) as $key => $value ) {
			$search[]  = '%' . $key;
			$replace[] = $value;
		}

		$key   = str_replace( $search, $replace, $key_format );
		$value = isset( $object->{$value_prop} ) ? $object->{$value_prop} : null;

		$prefix = str_repeat( '-', $depth );

		if ( $prefix ) {
			$prefix .= ' ';
		}

		$output[ $prefix . $key ] = $value;
	}
}

class IWF_Taxonomy_Component extends IWF_Component {
	public $title;

	protected $id;

	protected $taxonomy;

	public function __construct( IWF_Taxonomy $taxonomy, $id, $title = null ) {
		parent::__construct();

		$this->id       = $id;
		$this->taxonomy = $taxonomy;

		$this->title = empty( $title ) ? $this->id : $title;
	}

	public function get_taxonomy() {
		return $this->taxonomy;
	}

	public function get_id() {
		return $this->id;
	}

	public function save( array &$values, $term_id, $tt_id ) {
		foreach ( $this->elements as $element ) {
			if ( is_subclass_of( $element, 'IWF_Taxonomy_Component_Element_FormField_Abstract' ) ) {
				$element->save( $values, $term_id, $tt_id );
			}
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Abstract extends IWF_Component_Element_FormField_Abstract {
	protected $stored_value = false;

	public function __construct( IWF_Taxonomy_Component $component, $name, $value = null, array $args = array() ) {
		parent::__construct( $component, $name, $value, $args );
	}

	public function save( array &$values, $term_id, $tt_id ) {
		if ( ! isset( $_POST[ $this->name ] ) ) {
			return false;
		}

		$values[ $this->name ] = $_POST[ $this->name ];

		return true;
	}

	public function before_render( $tag = null ) {
		if ( $tag && ! empty( $tag->term_id ) ) {
			$this->stored_value = IWF_Taxonomy::get_option( $tag->term_id, $this->component->get_taxonomy()->get_slug(), $this->name );
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Text extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( $tag = null ) {
		parent::before_render( $tag );

		if ( $this->stored_value !== false ) {
			$this->value = $this->stored_value;
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Textarea extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( $tag = null ) {
		parent::before_render( $tag );

		if ( $this->stored_value !== false ) {
			$this->value = $this->stored_value;
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Checkbox extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( $tag = null ) {
		parent::before_render( $tag );

		if ( $this->stored_value !== false ) {
			unset( $this->args['checked'], $this->args['selected'] );
			$this->args['checked'] = ( $this->stored_value == $this->value );
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Radio extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( $tag = null ) {
		parent::before_render( $tag );

		if ( $this->stored_value !== false ) {
			unset( $this->args['checked'], $this->args['selected'] );
			$this->args['checked'] = in_array( $this->stored_value, (array) $this->value ) ? $this->stored_value : false;
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Select extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function before_render( $tag = null ) {
		parent::before_render( $tag );

		if ( $this->stored_value !== false ) {
			unset( $this->args['checked'], $this->args['selected'] );
			$this->args['selected'] = in_array( $this->stored_value, (array) $this->value ) ? $this->stored_value : false;
		}
	}
}

class IWF_Taxonomy_Component_Element_FormField_Wysiwyg extends IWF_Taxonomy_Component_Element_FormField_Abstract {
	public function initialize() {
		parent::initialize();

		if ( ! isset( $this->args['settings'] ) ) {
			$this->args['settings'] = array();
		}

		$this->args['id'] = $this->name;
	}

	public function before_render( $tag = null ) {
		parent::before_render( $tag );

		if ( $this->stored_value !== false ) {
			$this->value = $this->stored_value;
		}
	}

	public function render() {
		ob_start();
		wp_editor( $this->value, $this->args['id'], $this->args['settings'] );

		return ob_get_clean();
	}
}

class IWF_Taxonomy_Component_Element_FormField_Visual extends IWF_Taxonomy_Component_Element_FormField_Wysiwyg {
}