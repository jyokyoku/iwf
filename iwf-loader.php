<?php
/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author         Masayuki Ietomi <jyokyoku@gmail.com>
 * @copyright      Copyright(c) 2011 Masayuki Ietomi
 * @link           http://inspire-tech.jp
 */

$GLOBALS['iwf_versions']['1.8.1'] = __FILE__;

if ( !class_exists( 'IWF_Loader' ) ) {
	class IWF_Loader {
		protected static $_loaded_files = array();

		protected static $_loaded = false;

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

			add_action( 'admin_init', array( 'IWF_Loader', 'register_javascript' ) );
			add_action( 'admin_init', array( 'IWF_Loader', 'register_css' ) );
			add_action( 'admin_print_footer_scripts', array( 'IWF_Loader', 'load_wpeditor_html' ) );
			add_action( 'plugins_loaded', array( 'IWF_Loader', 'load' ) );
			add_action( 'after_setup_theme', array( 'IWF_Loader', 'load' ) );
		}

		/**
		 * Loads the class files
		 */
		public static function load() {
			if ( self::$_loaded ) {
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

					if ( is_file( $filepath ) && is_readable( $filepath ) && @include_once $filepath ) {
						self::$_loaded_files[] = $filepath;
					}
				}

				closedir( $dh );
			}

			do_action( 'iwf_loaded', self::$_loaded_files );

			self::$_loaded = self::get_latest_version();

			if ( !defined( 'IWF_DEBUG' ) ) {
				define( 'IWF_DEBUG', false );
			}
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
			return (bool)self::$_loaded;
		}

		/**
		 * Returns the current loaded version
		 *
		 * @return bool|string
		 */
		public static function get_current_version() {
			return self::is_loaded() ? self::$_loaded : false;
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
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'iwf-flexible-wh', self::get_current_version_url() . '/js/flexible_wh.js', array( 'jquery' ), null, true );

			if ( version_compare( get_bloginfo( 'version' ), '3.3', '>=' ) ) {
				wp_enqueue_script( 'wplink' );
				wp_enqueue_script( 'wpdialogs-popup' );
				wp_enqueue_script( 'iwf-active-editor', self::get_current_version_url() . '/js/active_editor.js', array( 'jquery' ), null, true );
				wp_enqueue_script( 'iwf-quicktags', self::get_current_version_url() . '/js/quicktags.js', array( 'quicktags' ), null, true );
			}

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

			if ( !wp_script_is( 'iwf-common', 'registered' ) ) {
				$assoc = array( 'jquery', 'media-upload', 'thickbox', 'iwf-exchecker', 'iwf-mobiscroll' );

				wp_enqueue_script( 'iwf-common', self::get_current_version_url() . '/js/common.js', $assoc, null, true );
				wp_enqueue_script( 'iwf-metabox', self::get_current_version_url() . '/js/metabox.js', array( 'iwf-common' ), null, true );
				wp_enqueue_script( 'iwf-settingspage', self::get_current_version_url() . '/js/settingspage.js', array( 'iwf-common' ), null, true );

				wp_localize_script( 'iwf-common', 'iwfCommonL10n', array(
					'insertToField' => __( 'Insert to field', 'iwf' ),
					'cancelText' => __( 'Cancel', 'iwf' ),
					'dateFormat' => __( 'mm/dd/yy', 'iwf' ),
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
			wp_enqueue_style( 'editor-buttons' );
			wp_enqueue_style( 'iwf-common', self::get_current_version_url() . '/css/common.css' );

			if ( version_compare( get_bloginfo( 'version' ), '3.3', '>=' ) ) {
				wp_enqueue_style( 'wp-jquery-ui-dialog' );
			}

			if ( !wp_style_is( 'iwf-mobiscroll', 'registered' ) ) {
				wp_enqueue_style( 'iwf-mobiscroll', self::get_current_version_url() . '/js/mobiscroll/mobiscroll.custom-2.4.4.min.css' );
			}

			if ( !wp_style_is( 'iwf-exvalidation', 'registered' ) ) {
				wp_enqueue_style( 'iwf-exvalidation', self::get_current_version_url() . '/js/exvalidation/exvalidation.css' );
			}
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
	}
}
