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
require_once dirname( __FILE__ ) . '/iwf-taxonomy.php';
require_once dirname( __FILE__ ) . '/iwf-metabox.php';

class IWF_CustomPost {
	protected $_post_type;

	protected $_enter_title_here;

	protected $_taxonomies = array();

	protected $_metaboxes = array();

	/**
	 * Constructor
	 *
	 * @param    string $post_type
	 * @param    array  $args
	 */
	public function __construct( $post_type, $args = array() ) {
		$this->_post_type = $post_type;
		$args = wp_parse_args( $args );

		if ( empty( $args['label'] ) ) {
			$args['label'] = $post_type;
		}

		if ( empty( $args['labels'] ) ) {
			$args['labels'] = array(
				'name' => $args['label'],
				'singular_name' => $args['label'],
				'add_new' => __( 'Add New', 'iwf' ),
				'add_new_item' => sprintf( __( 'Add New %s', 'iwf' ), $args['label'] ),
				'edit_item' => sprintf( __( 'Edit %s', 'iwf' ), $args['label'] ),
				'new_item' => sprintf( __( 'New %s', 'iwf' ), $args['label'] ),
				'view_item' => sprintf( __( 'View %s', 'iwf' ), $args['label'] ),
				'search_items' => sprintf( __( 'Search %s', 'iwf' ), $args['label'] ),
				'not_found' => sprintf( __( 'No %s found.', 'iwf' ), $args['label'] ),
				'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'iwf' ), $args['label'] ),
				'parent_item_colon' => sprintf( __( 'Parent %s:', 'iwf' ), $args['label'] ),
				'all_items' => sprintf( __( 'All %s', 'iwf' ), $args['label'] )
			);
		}

		$thumbnail_support_types = get_theme_support( 'post-thumbnails' );

		if (
			isset( $args['supports'] )
			&& in_array( 'thumbnail', (array)$args['supports'] )
			&& (
				(
					is_array( $thumbnail_support_types )
					&& !in_array( $this->_post_type, $thumbnail_support_types[0] )
				)
				|| ( empty( $thumbnail_support_types ) )
			)
		) {
			$thumbnail_support_types = empty( $thumbnail_support_types )
				? array( $this->_post_type )
				: array_merge( $thumbnail_support_types[0], (array)$this->_post_type );

			add_theme_support( 'post-thumbnails', $thumbnail_support_types );
		}

		if ( $enter_title_here = iwf_get_array_hard( $args, 'enter_title_here' ) ) {
			$this->_enter_title_here = $enter_title_here;
			add_filter( 'enter_title_here', array( $this, 'rewrite_title_watermark' ) );
		}

		register_post_type( $post_type, $args );
	}

	/**
	 * Rewrites the watermark of title field
	 *
	 * @param    string $title
	 * @return array|bool|string
	 */
	public function rewrite_title_watermark( $title ) {
		$screen = get_current_screen();

		if ( $screen->post_type == $this->_post_type ) {
			$title = $this->_enter_title_here;
		}

		return $title;
	}

	/**
	 * Registers the taxonomy
	 *
	 * @param          $slug
	 * @param    array $args
	 * @return    IWF_Taxonomy
	 * @see        IWF_Taxonomy::__construct
	 */
	public function taxonomy( $slug, $args = array() ) {
		if ( is_object( $slug ) && is_a( $slug, 'IWF_Taxonomy' ) ) {
			$taxonomy = $slug;
			$slug = $taxonomy->get_slug();

			if ( isset( $this->_taxonomies[$slug] ) && $this->_taxonomies[$slug] !== $taxonomy ) {
				$this->_taxonomies[$slug] = $taxonomy;
			}

		} else if ( is_string( $slug ) && isset( $this->_taxonomies[$slug] ) ) {
			$taxonomy = $this->_taxonomies[$slug];

		} else {
			$taxonomy = new IWF_Taxonomy( $slug, $this->_post_type, $args );
			$this->_taxonomies[$slug] = $taxonomy;
		}

		return $taxonomy;
	}

	/**
	 * Alias of 'taxonomy' method
	 *
	 * @param          $slug
	 * @param    array $args
	 * @return    IWF_Taxonomy
	 * @see        IWF_CustomPost::taxonomy
	 */
	public function t( $slug, $args = array() ) {
		return $this->taxonomy( $slug, $args );
	}

	/**
	 * Creates the IWF_MetaBox
	 *
	 * @param    string|IWF_MetaBox $id
	 * @param    string             $title
	 * @param    array              $args
	 * @return    IWF_MetaBox
	 */
	public function metabox( $id, $title = null, $args = array() ) {
		if ( is_object( $id ) && is_a( $id, 'IWF_MetaBox' ) ) {
			$metabox = $id;
			$id = $metabox->get_id();

			if ( isset( $this->_metaboxes[$id] ) && $this->_metaboxes[$id] !== $metabox ) {
				$this->_metaboxes[$id] = $metabox;
			}

		} else if ( is_string( $id ) && isset( $this->_metaboxes[$id] ) ) {
			$metabox = $this->_metaboxes[$id];

		} else {
			$metabox = new IWF_MetaBox( $this->_post_type, $id, $title, $args );
			$this->_metaboxes[$id] = $metabox;
		}

		return $metabox;
	}

	/**
	 * Alias of 'metabox' method
	 *
	 * @param    string|IWF_MetaBox $id
	 * @param    string             $title
	 * @param    array              $args
	 * @return    IWF_MetaBox
	 * @see        IWF_CustomPost::metabox
	 */
	public function m( $id, $title = null, $args = array() ) {
		return $this->metabox( $id, $title, $args );
	}

	public static function get_list_recursive( $post_type, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'key' => '%post_title (ID:%ID)',
			'value' => 'ID',
			'orderby' => 'menu_order',
			'post_status' => 'publish',
			'posts_per_page' => 100
		) );

		$posts = get_posts( array(
			'post_type' => $post_type,
			'post_status' => iwf_get_array_hard( $args, 'post_status' ),
			'orderby' => iwf_get_array_hard( $args, 'orderby' ),
			'posts_per_page' => iwf_get_array_hard( $args, 'posts_per_page' ),
		) );

		if ( !$posts ) {
			return array();
		}

		$walker = new IWF_CustomPost_List_Walker();

		return $walker->walk( $posts, 0, $args );
	}
}

class IWF_CustomPost_List_Walker extends Walker {
	public $tree_type = 'post';

	public $db_fields = array( 'parent' => 'post_parent', 'id' => 'ID' );

	public function start_el( &$output, $term, $depth, $args, $id = 0 ) {
		$key_format = iwf_get_array_hard( $args, 'key' );
		$value_prop = iwf_get_array_hard( $args, 'value' );

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
