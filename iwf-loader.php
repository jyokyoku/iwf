<?php
/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author         Masayuki Ietomi <jyokyoku@gmail.com>
 * @copyright      Copyright(c) 2011 Masayuki Ietomi
 * @link           http://inspire-tech.jp
 */

$GLOBALS['iwf_versions']['1.1.0'] = __FILE__;

if ( !class_exists( 'IWF_Loader' ) ) {
	class IWF_Loader {
		protected static $loaded_files = array();

		protected static $loaded = false;

		protected static $get_archives_where_args = array();

		/**
		 * Initialize
		 *
		 * @param    mixed $callback
		 */
		public static function init( $callback = '' ) {
			$callbacks = array();

			if ( func_num_args() > 1 ) {
				$callbacks = func_get_args();

			} else if ( $callback ) {
				$callbacks = is_array( $callback ) && is_callable( $callback ) ? array( $callback ) : (array)$callback;
			}

			foreach ( $callbacks as $callback ) {
				if ( is_callable( $callback ) ) {
					add_action( 'iwf_loaded', $callback, 10, 1 );
				}
			}

			add_action( 'admin_menu', array( 'IWF_Loader', 'register_javascript' ) );
			add_action( 'admin_menu', array( 'IWF_Loader', 'register_css' ) );
			add_action( 'settings_page_loaded', array( 'IWF_Loader', 'register_media_scripts' ) );
			add_action( 'dbx_post_advanced', array( 'IWF_Loader', 'register_media_scripts' ) );
			add_action( 'admin_print_scripts', array( 'IWF_Loader', 'print_header_scripts' ) );
			add_action( 'admin_print_footer_scripts', array( 'IWF_Loader', 'load_wpeditor_html' ) );
			add_action( 'after_setup_theme', array( 'IWF_Loader', 'load' ) );
			add_action( 'iwf_loaded', array( 'IWF_Loader', 'startup' ) );

			add_filter( 'getarchives_join', array( 'IWF_Loader', 'filter_get_archives_join' ), 10, 2 );
			add_filter( 'getarchives_where', array( 'IWF_Loader', 'filter_get_archives_where' ), 10, 2 );
			add_filter( 'get_archives_link', array( 'IWF_Loader', 'filter_get_archives_link' ), 20, 1 );
		}

		/**
		 * Loads the class files
		 */
		public static function load() {
			if ( self::$loaded ) {
				return;
			}

			$base_dir = self::get_latest_version_dir();
			load_textdomain( 'iwf', $base_dir . '/languages/iwf-' . get_locale() . '.mo' );

			if ( $dh = opendir( $base_dir ) ) {
				while ( false !== ( $file = readdir( $dh ) ) ) {
					if ( $file === '.' || $file === '..' || $file[0] === '.' || strrpos( $file, '.php' ) === false ) {
						continue;
					}

					$filepath = $base_dir . '/' . $file;

					if ( file_exists( $filepath ) && is_readable( $filepath ) && @include_once $filepath ) {
						self::$loaded_files[] = $filepath;
					}
				}

				closedir( $dh );
			}

			do_action( 'iwf_loaded', self::$loaded_files );

			self::$loaded = self::get_latest_version();

			if ( !defined( 'IWF_DEBUG' ) ) {
				define( 'IWF_DEBUG', false );
			}
		}

		/**
		 * Setup environment
		 */
		public static function startup() {
			global $iwf_var;

			$iwf_var = IWF_Var::instance();
		}

		/**
		 * Returns the any version directory path
		 *
		 * @param $version
		 * @return bool|string
		 */
		public static function get_any_version_dir( $version ) {
			if ( empty( $version ) || !isset( $GLOBALS['iwf_versions'][$version] ) ) {
				return false;
			}

			return dirname( $GLOBALS['iwf_versions'][$version] );
		}

		/**
		 * Returns the any version directory url
		 *
		 * @param $version
		 * @return bool|string
		 */
		public static function get_any_version_url( $version ) {
			if ( empty( $version ) || !( $dir = self::get_any_version_dir( $version ) ) ) {
				return false;
			}

			return get_option( 'siteurl' ) . '/' . str_replace( ABSPATH, '', $dir );
		}

		/**
		 * Returns the loaded status
		 *
		 * @return bool
		 */
		public static function is_loaded() {
			return (bool)self::$loaded;
		}

		/**
		 * Returns the current loaded version
		 *
		 * @return bool|string
		 */
		public static function get_current_version() {
			return self::is_loaded() ? self::$loaded : false;
		}

		/**
		 * Returns the current loaded version directory path
		 *
		 * @return bool|string
		 */
		public static function get_current_version_dir() {
			return self::get_any_version_dir( self::get_current_version() );
		}

		/**
		 * Returns the current loaded version directory uri
		 *
		 * @return bool|string
		 */
		public static function get_current_version_url() {
			return self::get_any_version_url( self::get_current_version() );
		}

		/**
		 * Returns the current version number
		 *
		 * @return null|string
		 */
		public static function get_latest_version() {
			$latest = null;

			foreach ( array_keys( $GLOBALS['iwf_versions'] ) as $version ) {
				if ( !$latest ) {
					$latest = $version;
					continue;
				}

				if ( version_compare( $version, $latest ) > 0 ) {
					$latest = $version;
				}
			}

			return $latest;
		}

		/**
		 * Returns the latest version directory path of IWF
		 *
		 * @return    NULL|string
		 */
		public static function get_latest_version_dir() {
			return self::get_any_version_dir( self::get_latest_version() );
		}

		/**
		 * Returns the latest version url of IWF
		 *
		 * @return    NULL|string
		 */
		public static function get_latest_version_url() {
			return self::get_any_version_url( self::get_latest_version() );
		}

		/**
		 * Enqueue the JavaScript set
		 */
		public static function register_javascript() {
			if ( version_compare( get_bloginfo( 'version' ), '3.3', '>=' ) ) {
				wp_enqueue_script( 'wplink' );
				wp_enqueue_script( 'wpdialogs-popup' );
				wp_enqueue_script( 'iwf-active-editor', self::get_current_version_url() . '/js/active_editor.js', array( 'jquery' ), null, true );
				wp_enqueue_script( 'iwf-quicktags', self::get_current_version_url() . '/js/quicktags.js', array( 'quicktags' ), null, true );
			}

			if ( version_compare( get_bloginfo( 'version' ), '3.5', '>=' ) ) {
				wp_enqueue_script( 'iwf-media', self::get_current_version_url() . '/js/media.js', array( 'jquery' ), null, true );

			} else  {
				wp_enqueue_script( 'media-upload' );
				wp_enqueue_script( 'thickbox' );
				wp_enqueue_script( 'iwf-alt-media', self::get_current_version_url() . '/js/alt_media.js', array( 'jquery' ), null, true );
			}

			wp_enqueue_script( 'iwf-jquery-background-size', self::get_current_version_url() . '/js/jquery.backgroundSize.js', array( 'jquery' ), null, true );
			wp_enqueue_script( 'iwf-jquery-placeholder', self::get_current_version_url() . '/js/jquery.placeholder.js', array( 'jquery' ), null, true );

			if ( !wp_script_is( 'iwf-mobiscroll', 'registered' ) ) {
				wp_enqueue_script( 'iwf-mobiscroll', self::get_current_version_url() . '/js/mobiscroll/mobiscroll.custom-2.4.4.min.js', array( 'jquery' ), null, true );
			}

			if ( !wp_script_is( 'iwf-exvalidaion', 'registered' ) ) {
				wp_enqueue_script( 'iwf-exvalidation', self::get_current_version_url() . '/js/exvalidation/exvalidation.js', array( 'jquery' ), null, true );
			}

			if ( !wp_script_is( 'iwf-exchecker', 'registered' ) ) {
				$exchecker = 'exchecker-' . get_locale() . '.js';

				if ( !is_readable( self::get_current_version_dir() . '/js/exvalidation/' . $exchecker ) ) {
					$exchecker = 'exchecker-en_US.min.js';
				}

				wp_enqueue_script( 'iwf-exchecker', self::get_current_version_url() . '/js/exvalidation/' . $exchecker, array( 'jquery' ) );
			}

			if ( !wp_script_is( 'iwf-spectrum', 'registered' ) ) {
				wp_enqueue_script( 'iwf-spectrum', self::get_current_version_url() . '/js/spectrum/spectrum.js', array( 'jquery' ), null, true );
				$spectrum_i18n = '/js/spectrum/i18n/jquery.spectrum-' . get_locale() . '.js';

				if ( file_exists( self::get_current_version_dir() . $spectrum_i18n ) ) {
					wp_enqueue_script( 'iwf-spectrum-' . get_locale(), self::get_current_version_url() . $spectrum_i18n, array( 'jquery' ), null, true );
				}
			}

			if ( !wp_script_is( 'iwf-common', 'registered' ) ) {
				$assoc = array( 'jquery', 'iwf-exchecker', 'iwf-mobiscroll', 'iwf-jquery-background-size' );

				if ( version_compare( get_bloginfo( 'version' ), '3.5', '>=' ) ) {
					$assoc[] = 'iwf-media';

				} else {
					$assoc[] = 'thickbox';
					$assoc[] = 'media-upload';
					$assoc[] = 'iwf-alt-media';
				}

				wp_enqueue_script( 'iwf-common', self::get_current_version_url() . '/js/common.js', $assoc, null, true );
				wp_enqueue_script( 'iwf-metabox', self::get_current_version_url() . '/js/metabox.js', array( 'iwf-common' ), null, true );
				wp_enqueue_script( 'iwf-settingspage', self::get_current_version_url() . '/js/settingspage.js', array( 'iwf-common' ), null, true );

				wp_localize_script( 'iwf-common', 'iwfCommonL10n', array(
					'insertToField' => __( 'Insert to field', 'iwf' ),
					'cancelText' => __( 'Cancel', 'iwf' ),
					'dateFormat' => __( 'mm-dd-yy', 'iwf' ),
					'dateOrder' => __( 'mmddy', 'iwf' ),
					'sunday' => __( 'Sunday', 'iwf' ),
					'monday' => __( 'Monday', 'iwf' ),
					'tuesday' => __( 'Tuesday', 'iwf' ),
					'wednesday' => __( 'Wednesday', 'iwf' ),
					'thursday' => __( 'Thursday', 'iwf' ),
					'friday' => __( 'Friday', 'iwf' ),
					'saturday' => __( 'Saturday', 'iwf' ),
					'sundayShort' => __( 'Sun', 'iwf' ),
					'mondayShort' => __( 'Mon', 'iwf' ),
					'tuesdayShort' => __( 'Tue', 'iwf' ),
					'wednesdayShort' => __( 'Wed', 'iwf' ),
					'thursdayShort' => __( 'Thu', 'iwf' ),
					'fridayShort' => __( 'Fri', 'iwf' ),
					'saturdayShort' => __( 'Sat', 'iwf' ),
					'dayText' => __( 'Day', 'iwf' ),
					'hourText' => __( 'Hours', 'iwf' ),
					'minuteText' => __( 'Minutes', 'iwf' ),
					'january' => __( 'January', 'iwf' ),
					'february' => __( 'February', 'iwf' ),
					'march' => __( 'March', 'iwf' ),
					'april' => __( 'April', 'iwf' ),
					'may' => _x( 'May', 'long', 'iwf' ),
					'june' => __( 'June', 'iwf' ),
					'july' => __( 'July', 'iwf' ),
					'august' => __( 'August', 'iwf' ),
					'september' => __( 'September', 'iwf' ),
					'october' => __( 'October', 'iwf' ),
					'november' => __( 'November', 'iwf' ),
					'december' => __( 'December', 'iwf' ),
					'januaryShort' => __( 'Jan', 'iwf' ),
					'februaryShort' => __( 'Feb', 'iwf' ),
					'marchShort' => __( 'Mar', 'iwf' ),
					'aprilShort' => __( 'Apr', 'iwf' ),
					'mayShort' => _x( 'May', 'short', 'iwf' ),
					'juneShort' => __( 'Jun', 'iwf' ),
					'julyShort' => __( 'Jul', 'iwf' ),
					'augustShort' => __( 'Aug', 'iwf' ),
					'septemberShort' => __( 'Sep', 'iwf' ),
					'octoberShort' => __( 'Oct', 'iwf' ),
					'novemberShort' => __( 'Nov', 'iwf' ),
					'decemberShort' => __( 'Dec', 'iwf' ),
					'monthText' => __( 'Month', 'iwf' ),
					'secText' => __( 'Seconds', 'iwf' ),
					'setText' => __( 'Set', 'iwf' ),
					'timeFormat' => __( 'hh:ii A', 'iwf' ),
					'timeWheels' => __( 'hhiiA', 'iwf' ),
					'yearText' => __( 'Year', 'iwf' )
				) );
			}
		}

		/**
		 * Enqueue the CSS set
		 */
		public static function register_css() {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'iwf-common', self::get_current_version_url() . '/css/common.css' );

			if ( version_compare( get_bloginfo( 'version' ), '3.3', '>=' ) ) {
				wp_enqueue_style( 'wp-jquery-ui-dialog' );
			}

			if ( !wp_style_is( 'iwf-spectrum', 'registered' ) ) {
				wp_enqueue_style( 'iwf-spectrum', self::get_current_version_url() . '/js/spectrum/spectrum.css' );
			}

			if ( !wp_style_is( 'iwf-mobiscroll', 'registered' ) ) {
				wp_enqueue_style( 'iwf-mobiscroll', self::get_current_version_url() . '/js/mobiscroll/mobiscroll.custom-2.4.4.min.css' );
			}

			if ( !wp_style_is( 'iwf-exvalidation', 'registered' ) ) {
				wp_enqueue_style( 'iwf-exvalidation', self::get_current_version_url() . '/js/exvalidation/exvalidation.css' );
			}
		}

		/**
		 * Print the scripts in <head> tag
		 */
		public static function print_header_scripts() {
			wp_print_styles( 'editor-buttons' );
		}

		/**
		 * Adds the codes of link dialog
		 */
		public static function load_wpeditor_html() {
			if ( version_compare( get_bloginfo( 'version' ), '3.3', '>=' ) ) {
				include_once ABSPATH . WPINC . '/class-wp-editor.php';
				_WP_Editors::wp_link_dialog();
			}
		}

		/**
		 * Register the media scripts and css
		 */
		public static function register_media_scripts() {
			if ( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}
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
	}
}
