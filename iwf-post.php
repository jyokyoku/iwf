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

/**
 * Class IWF_Post
 */
class IWF_Post {
	protected $_post_type;

	protected $_enter_title_here;

	protected $_taxonomies = array();

	protected $_metaboxes = array();

	/**
	 * Constructor
	 *
	 * @param string $post_type
	 * @param array $args
	 */
	public function __construct( $post_type, $args = array() ) {
		$this->_post_type = $post_type;
		$args = wp_parse_args( $args, array(
			'public' => true
		) );

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
	 * @param string $title
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
	 * @param string|IWF_Taxonomy $slug
	 * @param array $args
	 * @return IWF_Taxonomy
	 * @see IWF_Taxonomy::__construct
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

		$post_type_object = get_post_type_object( $this->_post_type );

		if ( !in_array( $taxonomy->get_slug(), $post_type_object->taxonomies ) ) {
			$post_type_object->taxonomies[] = $taxonomy->get_slug();
		}

		return $taxonomy;
	}

	/**
	 * Alias of 'taxonomy' method
	 *
	 * @param string|IWF_Taxonomy $slug
	 * @param array $args
	 * @return IWF_Taxonomy
	 * @see IWF_CustomPost::taxonomy
	 */
	public function t( $slug, $args = array() ) {
		return $this->taxonomy( $slug, $args );
	}

	/**
	 * Creates the IWF_MetaBox
	 *
	 * @param string|IWF_MetaBox $id
	 * @param string $title
	 * @param array $args
	 * @return IWF_MetaBox
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
	 * @param string|IWF_MetaBox $id
	 * @param string $title
	 * @param array $args
	 * @return IWF_MetaBox
	 * @see IWF_CustomPost::metabox
	 */
	public function m( $id, $title = null, $args = array() ) {
		return $this->metabox( $id, $title, $args );
	}

	/**
	 * Get the post_title and ID pairs
	 *
	 * @param string $post_type
	 * @param array $args
	 * @return array|string
	 */
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

		$walker = new IWF_Post_List_Walker();

		return $walker->walk( $posts, 0, $args );
	}

	/**
	 * Get the parent posts of specified post
	 *
	 * @param int|stdClass|WP_Post $slug
	 * @param boolean $include_current
	 * @param boolean $reverse
	 * @return array
	 */
	public static function get_parents( $post, $include_current = false, $reverse = false ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );

		} else if ( isset( $post->ID ) ) {
			$post = get_post( $post->ID );
		}

		if ( !$post ) {
			return array();
		}

		$tree = $include_current ? array( $post ) : array();

		if ( $post->post_parent ) {
			$tmp_post = $post;

			while ( $tmp_post->post_parent ) {
				$tmp_post = get_post( $tmp_post->post_parent );

				if ( !$tmp_post ) {
					break;

				} else {
					$tree[] = $tmp_post;
				}
			}
		}

		return $reverse ? $tree : array_reverse( $tree );
	}

	/**
	 * Get the post that has been filtered by $args
	 *
	 * @param id $post_id
	 * @param array $args
	 * @return mixed
	 */
	public static function get( $post_id, $args = array() ) {
		if ( empty( $post_id ) && $post_id !== false ) {
			return false;
		}

		if ( is_array( $post_id ) && empty( $args ) ) {
			$args = $post_id;
			$post_id = false;
		}

		$args = wp_parse_args( $args, array(
			'post_status' => 'any'
		) );

		if ( $args ) {
			if ( $post_id ) {
				$args['p'] = $post_id;
			}

			if ( $posts = get_posts( $args ) ) {
				return reset( $posts );
			}

			return false;

		} else {
			return get_post( $post_id );
		}
	}

	/**
	 * Get the featured image data of post
	 *
	 * @param int $post_id
	 * @return array|bool
	 */
	public static function get_thumbnail( $post_id = null ) {
		global $post;

		if ( $post_id && is_object( $post_id ) && !empty( $post_id->ID ) ) {
			$post_id = $post_id->ID;
		}

		if ( !$post_id && $post && is_object( $post ) && !empty( $post->ID ) ) {
			$post_id = $post->ID;
		}

		if ( !has_post_thumbnail( $post_id ) ) {
			return false;
		}

		$post_thumbnail_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), '' );
		$data = array( 'src' => $post_thumbnail_src[0] );

		if (
			( $attachment_id = get_post_thumbnail_id( $post_id ) )
			&& ( $attachment = get_post( $attachment_id ) )
		) {
			$alt = trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );

			if ( empty( $alt ) ) {
				$alt = trim( strip_tags( $attachment->post_excerpt ) );
			}

			if ( empty( $alt ) ) {
				$alt = trim( strip_tags( $attachment->post_title ) );
			}

			$data['alt'] = $alt;
		}

		return $data;
	}

	/**
	 * Get the ID of post preview
	 *
	 * @param $post_id
	 * @return int
	 */
	public static function get_preview_id( $post_id ) {
		global $post;
		$preview_id = 0;

		if ( $post->ID == $post_id && is_preview() && $preview = wp_get_post_autosave( $post->ID ) ) {
			$preview_id = $preview->ID;
		}

		return $preview_id;
	}
}

/**
 * Class IWF_Post_List_Walker
 */
class IWF_Post_List_Walker extends Walker {
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

/**
 * Class IWF_CustomPost
 *
 * @deprecated
 */
class IWF_CustomPost extends IWF_Post {
}