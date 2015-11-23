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
require_once dirname( __FILE__ ) . '/iwf-taxonomy.php';
require_once dirname( __FILE__ ) . '/iwf-metabox.php';

/**
 * Class IWF_Post
 */
class IWF_Post {
	protected $post_type;

	protected $enter_title_here;

	protected $taxonomies = array();

	protected $metaboxes = array();

	/**
	 * Constructor
	 *
	 * @param string $post_type
	 * @param array $args
	 */
	public function __construct( $post_type, $args = array() ) {
		$this->post_type = $post_type;
		$args            = wp_parse_args( $args, array(
			'public' => true
		) );

		if ( empty( $args['label'] ) ) {
			$args['label'] = $post_type;
		}

		if ( empty( $args['labels'] ) ) {
			$args['labels'] = array(
				'name'               => $args['label'],
				'singular_name'      => $args['label'],
				'add_new'            => __( 'Add New', 'iwf' ),
				'add_new_item'       => sprintf( __( 'Add New %s', 'iwf' ), $args['label'] ),
				'edit_item'          => sprintf( __( 'Edit %s', 'iwf' ), $args['label'] ),
				'new_item'           => sprintf( __( 'New %s', 'iwf' ), $args['label'] ),
				'view_item'          => sprintf( __( 'View %s', 'iwf' ), $args['label'] ),
				'search_items'       => sprintf( __( 'Search %s', 'iwf' ), $args['label'] ),
				'not_found'          => sprintf( __( 'No %s found.', 'iwf' ), $args['label'] ),
				'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'iwf' ), $args['label'] ),
				'parent_item_colon'  => sprintf( __( 'Parent %s:', 'iwf' ), $args['label'] ),
				'all_items'          => sprintf( __( 'All %s', 'iwf' ), $args['label'] )
			);
		}

		$thumbnail_support_types = get_theme_support( 'post-thumbnails' );

		if (
			isset( $args['supports'] )
			&& in_array( 'thumbnail', (array) $args['supports'] )
			&& (
				(
					is_array( $thumbnail_support_types )
					&& ! in_array( $this->post_type, $thumbnail_support_types[0] )
				)
				|| ( empty( $thumbnail_support_types ) )
			)
		) {
			$thumbnail_support_types = empty( $thumbnail_support_types )
				? array( $this->post_type )
				: array_merge( $thumbnail_support_types[0], (array) $this->post_type );

			add_theme_support( 'post-thumbnails', $thumbnail_support_types );
		}

		if ( $enter_title_here = iwf_get_array_hard( $args, 'enter_title_here' ) ) {
			$this->enter_title_here = $enter_title_here;
			add_filter( 'enter_title_here', array( $this, 'rewrite_title_watermark' ) );
		}

		if ( ! has_action( 'registered_post_type', array( 'IWF_Post', 'add_rewrite_rules' ) ) ) {
			add_action( 'registered_post_type', array( 'IWF_Post', 'add_rewrite_rules' ), 10, 2 );
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

		if ( $screen->post_type == $this->post_type ) {
			$title = $this->enter_title_here;
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
			$slug     = $taxonomy->get_slug();

			if ( isset( $this->taxonomies[ $slug ] ) && $this->taxonomies[ $slug ] !== $taxonomy ) {
				$this->taxonomies[ $slug ] = $taxonomy;
			}

		} else if ( is_string( $slug ) && isset( $this->taxonomies[ $slug ] ) ) {
			$taxonomy = $this->taxonomies[ $slug ];

		} else {
			$taxonomy                  = new IWF_Taxonomy( $slug, $this->post_type, $args );
			$this->taxonomies[ $slug ] = $taxonomy;
		}

		$post_type_object = get_post_type_object( $this->post_type );

		if ( ! in_array( $taxonomy->get_slug(), $post_type_object->taxonomies ) ) {
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
			$id      = $metabox->get_id();

			if ( isset( $this->metaboxes[ $id ] ) && $this->metaboxes[ $id ] !== $metabox ) {
				$this->metaboxes[ $id ] = $metabox;
			}

		} else if ( is_string( $id ) && isset( $this->metaboxes[ $id ] ) ) {
			$metabox = $this->metaboxes[ $id ];

		} else {
			$metabox                = new IWF_MetaBox( $this->post_type, $id, $title, $args );
			$this->metaboxes[ $id ] = $metabox;
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

	public static function add_rewrite_rules( $post_type, $args ) {
		global $wp_rewrite;

		if ( $wp_rewrite->permalink_structure ) {
			if ( $args->_builtin ) {
				return false;
			}

			$post_part = $args->rewrite['slug'];

			if ( $args->rewrite['with_front'] ) {
				$post_part = substr( $wp_rewrite->front, 1 ) ? substr( $wp_rewrite->front, 1 ) . '/' . $post_part : $post_part;
			}

			// Archive by day
			// e.g) post_type/2014/01/01/page/1
			add_rewrite_rule( $post_part . '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$', 'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]', 'top' );
			add_rewrite_rule( $post_part . '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/([0-9]{1,})/?$', 'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]', 'top' );

			// Archive by month
			// e.g) post_type/2014/01/page/1
			add_rewrite_rule( $post_part . '/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]', 'top' );
			add_rewrite_rule( $post_part . '/([0-9]{4})/([0-9]{1,2})/page/([0-9]{1,})/?$', 'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]', 'top' );

			// Archive by year
			// e.g) post_type/2014/page/1
			add_rewrite_rule( $post_part . '/([0-9]{4})/?$', 'index.php?post_type=' . $post_type . '&year=$matches[1]', 'top' );
			add_rewrite_rule( $post_part . '/([0-9]{4})/page/([0-9]{1,})/?$', 'index.php?post_type=' . $post_type . '&year=$matches[1]&paged=$matches[2]', 'top' );
		}

		return true;
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
			'key'            => '%post_title (ID:%ID)',
			'value'          => 'ID',
			'orderby'        => 'menu_order',
			'post_status'    => 'publish',
			'posts_per_page' => 100
		) );

		$posts = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => iwf_get_array_hard( $args, 'post_status' ),
			'orderby'        => iwf_get_array_hard( $args, 'orderby' ),
			'posts_per_page' => iwf_get_array_hard( $args, 'posts_per_page' ),
		) );

		if ( ! $posts ) {
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

		if ( ! $post ) {
			return array();
		}

		$tree = $include_current ? array( $post ) : array();

		if ( $post->post_parent ) {
			$tmp_post = $post;

			while ( $tmp_post->post_parent ) {
				$tmp_post = get_post( $tmp_post->post_parent );

				if ( ! $tmp_post ) {
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
	 * @param int $post_id
	 * @param array $args
	 * @return mixed
	 */
	public static function get( $post_id, $args = array() ) {
		if ( empty( $post_id ) && $post_id !== false ) {
			return false;
		}

		if ( is_array( $post_id ) && empty( $args ) ) {
			$args    = $post_id;
			$post_id = false;
		}

		if ( $post_id ) {
			if ( is_object( $post_id ) && is_a( $post_id, 'WP_Post' ) ) {
				$post_id = (int) $post_id->ID;

			} else if ( is_object( $post_id ) && ! empty( $post_id->ID ) ) {
				$post_id = (int) $post_id->ID;

			} else {
				$post_id = (int) $post_id;
			}
		}

		if ( $args ) {
			$args = wp_parse_args( $args, array(
				'post_status'      => 'any',
				'post_type'        => 'any',
				'numberposts'      => 1,
				'suppress_filters' => true
			) );

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
	 * @param string $fallback_var_name
	 *
	 * @return array
	 */
	public static function get_thumbnail( $post_id = null, $fallback_var_name = 'post_content' ) {
		global $post;

		if ( $post_id && is_object( $post_id ) && ! empty( $post_id->ID ) ) {
			$post_id = $post_id->ID;
		}

		if ( ! $post_id && $post && is_object( $post ) && ! empty( $post->ID ) ) {
			$post_id = $post->ID;
		}

		$data = array(
			'src' => '',
			'alt' => ''
		);

		$fallback_var_name = apply_filters( 'iwf_post_get_thumbnail_fallback_var_name', $fallback_var_name );

		if ( has_post_thumbnail( $post_id ) ) {
			$data['src'] = iwf_get_array( wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), '' ), 0 );

		} else if ( $fallback_var_name && preg_match( '/<img[^>]*?src\s*=\s*["\']([^"\']+)["\'].*?\/?>/i', $post->{$fallback_var_name}, $matches ) ) {
			$data['src'] = $matches[1];

		} else {
			return false;
		}

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

		if ( ! empty( $post ) && $post->ID == $post_id && is_preview() && $preview = wp_get_post_autosave( $post->ID ) ) {
			$preview_id = $preview->ID;
		}

		return $preview_id;
	}

	/**
	 * Get the first term of taxonomy associated with a post.
	 *
	 * @param int|stdClass|WP_Post $post_id
	 * @param string $taxonomy
	 * @param array $args
	 * @return bool|stdClass
	 */
	public static function get_first_term( $post_id, $taxonomy, $args = array() ) {
		if ( ! $post = self::get( $post_id ) ) {
			return false;
		}

		$args = wp_parse_args( $args, array(
			'orderby' => 'name',
			'order'   => 'ASC',
			'fields'  => 'all'
		) );

		$terms = wp_get_object_terms( $post->ID, $taxonomy, $args );

		if ( is_wp_error( $terms ) ) {
			return false;
		}

		return reset( $terms );
	}

	/**
	 * Get the post by template name
	 *
	 * @param $template_name
	 * @param array $args
	 *
	 * @return bool|stdClass
	 */
	public static function get_by_template( $template_name, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'post_type'        => 'page',
			'posts_per_page'   => 1,
			'orderby'          => 'date',
			'order'            => 'desc',
			'suppress_filters' => false,
			'meta_query'       => array(
				array(
					'key'   => '_wp_page_template',
					'value' => $template_name
				)
			)
		) );

		$posts = get_posts( $args );

		if ( ! $posts ) {
			return false;
		}

		return reset( $posts );
	}
}

/**
 * Class IWF_Post_List_Walker
 */
class IWF_Post_List_Walker extends Walker {
	public $tree_type = 'post';

	public $db_fields = array( 'parent' => 'post_parent', 'id' => 'ID' );

	public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$key_format = iwf_get_array_hard( $args, 'key' );
		$value_prop = iwf_get_array_hard( $args, 'value' );

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

/**
 * Class IWF_CustomPost
 *
 * @deprecated
 */
class IWF_CustomPost extends IWF_Post {
}
