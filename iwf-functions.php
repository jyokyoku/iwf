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

/**
 * Dump the values
 */
function iwf_dump() {
	$backtrace = debug_backtrace();

	if ( strpos( $backtrace[0]['file'], 'iwf/iwf-functions.php' ) !== false ) {
		$callee = $backtrace[1];

	} else {
		$callee = $backtrace[0];
	}

	$arguments = func_get_args();

	echo '<div style="text-align: left !important; font-size: 13px;background: #EEE !important; border:1px solid #666; color: #000 !important; padding:10px;">';
	echo '<h1 style="border-bottom: 1px solid #CCC; padding: 0 0 5px 0; margin: 0 0 5px 0; font: bold 120% sans-serif;">' . $callee['file'] . ' @ line: ' . $callee['line'] . '</h1>';
	echo '<pre style="overflow:auto;font-size:100%;">';

	$count = count( $arguments );

	for ( $i = 1; $i <= $count; $i ++ ) {
		echo '<strong>Variable #' . $i . ':</strong>' . PHP_EOL;
		var_dump( $arguments[ $i - 1 ] );
		echo PHP_EOL . PHP_EOL;
	}

	echo "</pre>";
	echo "</div>";
}

/**
 * Save the messages to file
 *
 * @param mixed $message
 *
 * @throws
 */
function iwf_log( $message = null, $with_callee = true ) {
	if ( ! is_string( $message ) ) {
		$message = print_r( $message, true );
	}

	$log_dir = WP_CONTENT_DIR . IWF_DS . 'iwf-logs';

	if ( ! is_dir( $log_dir ) ) {
		if ( ! @mkdir( $log_dir ) ) {
			trigger_error( 'Could not make a log directory.', E_USER_WARNING );
		}
	}

	$log_file = $log_dir . IWF_DS . date( 'Y-m-d', current_time( 'timestamp' ) ) . '.txt';

	if ( ! is_file( $log_file ) ) {
		if ( ! @touch( $log_file ) ) {
			trigger_error( 'Could not make a log file.', E_USER_WARNING );
		}
	}

	$time = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
	$line = sprintf( '[%s] %s', $time, $message );

	if ( $with_callee ) {
		$backtrace = debug_backtrace();

		if ( strpos( $backtrace[0]['file'], 'iwf/iwf-functions.php' ) !== false ) {
			$callee = $backtrace[1];

		} else {
			$callee = $backtrace[0];
		}

		$line .= sprintf( ' - in %s, line %s', $callee['file'], $callee['line'] );
	}

	$line .= PHP_EOL;

	file_put_contents( $log_file, $line, FILE_APPEND );
}

/**
 * Get the client ip address
 *
 * @param bool $safe
 *
 * @return string
 */
function iwf_get_ip( $safe = true ) {
	if ( ! $safe && iwf_get_array( $_SERVER, 'HTTP_X_FORWARDED_FOR' ) ) {
		$ip = preg_replace( '/(?:,.*)/', '', iwf_get_array( $_SERVER, 'HTTP_X_FORWARDED_FOR' ) );

	} else {
		if ( iwf_get_array( $_SERVER, 'HTTP_CLIENT_IP' ) ) {
			$ip = iwf_get_array( $_SERVER, 'HTTP_CLIENT_IP' );

		} else {
			$ip = iwf_get_array( $_SERVER, 'REMOTE_ADDR', '0.0.0.0' );
		}
	}

	return trim( $ip );
}

/**
 * Check request method is specified
 *
 * @param string $type
 *
 * @return bool|mixed
 * @link http://book.cakephp.org/2.0/ja/controllers/request-response.html
 */
function iwf_request_is( $type ) {
	$detector = array(
		'get'     => array( 'env' => 'REQUEST_METHOD', 'value' => 'GET' ),
		'post'    => array( 'env' => 'REQUEST_METHOD', 'value' => 'POST' ),
		'put'     => array( 'env' => 'REQUEST_METHOD', 'value' => 'PUT' ),
		'delete'  => array( 'env' => 'REQUEST_METHOD', 'value' => 'DELETE' ),
		'head'    => array( 'env' => 'REQUEST_METHOD', 'value' => 'HEAD' ),
		'options' => array( 'env' => 'REQUEST_METHOD', 'value' => 'OPTIONS' ),
		'ssl'     => array( 'env' => 'HTTPS', 'value' => 1 ),
		'ajax'    => array( 'env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest' ),
		'flash'   => array( 'env' => 'HTTP_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/' ),
		'mobile'  => array(
			'env'     => 'HTTP_USER_AGENT',
			'options' => array(
				'Android',
				'AvantGo',
				'BlackBerry',
				'DoCoMo',
				'Fennec',
				'iPod',
				'iPhone',
				'iPad',
				'J2ME',
				'MIDP',
				'NetFront',
				'Nokia',
				'Opera Mini',
				'Opera Mobi',
				'PalmOS',
				'PalmSource',
				'portalmmm',
				'Plucker',
				'ReqwirelessWeb',
				'SonyEricsson',
				'Symbian',
				'UP\\.Browser',
				'webOS',
				'Windows CE',
				'Windows Phone OS',
				'Xiino'
			)
		),
	);

	$type     = strtolower( $type );
	$detector = apply_filters( 'iwf_request_detector', $detector );

	if ( ! isset( $detector[ $type ] ) ) {
		return false;
	}

	$detect = $detector[ $type ];

	if ( isset( $detect['env'] ) ) {
		if ( isset( $detect['value'] ) ) {
			return iwf_get_array( $_SERVER, $detect['env'] ) == $detect['value'];
		}

		if ( isset( $detect['pattern'] ) ) {
			return (bool) preg_match( $detect['pattern'], iwf_get_array( $_SERVER, $detect['env'] ) );
		}

		if ( isset( $detect['options'] ) ) {
			$pattern = '/' . implode( '|', $detect['options'] ) . '/i';

			return (bool) preg_match( $pattern, iwf_get_array( $_SERVER, $detect['env'] ) );
		}
	}

	if ( isset( $detect['callback'] ) && is_callable( $detect['callback'] ) ) {
		return call_user_func( $detect['callback'] );
	}

	return false;
}

/**
 * Returns a merged value of the specified key(s) of array and removes it from array.
 *
 * @param array $array
 * @param string|array $key
 * @param mixed $default
 *
 * @return array
 */
function iwf_extract_and_merge( array &$array, $key, $default = null ) {
	if ( ! is_array( $key ) ) {
		$key = array( $key => $default );
	}

	$values = array();

	foreach ( $key as $_key => $_default ) {
		if ( is_int( $_key ) && ( is_string( $_default ) || is_numeric( $_default ) ) ) {
			$_key     = $_default;
			$_default = $default;
		}

		$value = iwf_get_array_hard( $array, $_key, $_default );

		if ( ! is_null( $value ) ) {
			$values = array_merge( $values, (array) $value );
		}
	}

	return $values;
}

/**
 * Returns the file path of timthumb.php and the arguments
 *
 * @param string $file
 * @param int $width
 * @param int $height
 * @param array|string $attr
 *
 * @return string
 */
function iwf_timthumb( $file, $width = null, $height = null, $attr = array() ) {
	if ( is_array( $width ) && empty( $height ) && empty( $attr ) ) {
		$attr  = $width;
		$width = null;
	}

	$script_filename = str_replace( DIRECTORY_SEPARATOR, '/', iwf_get_array( $_SERVER, 'SCRIPT_FILENAME' ) );
	$php_self        = iwf_get_array( $_SERVER, 'PHP_SELF' );

	$defaults = array(
		'q'    => null,
		'a'    => null,
		'zc'   => null,
		'f'    => array(),
		's'    => null,
		'w'    => null,
		'h'    => null,
		'cc'   => null,
		'path' => ( $script_filename && $php_self && strpos( $script_filename, $php_self ) === false ),
	);

	$attr     = array_intersect_key( wp_parse_args( $attr, $defaults ), $defaults );
	$timthumb = IWF_Loader::get_current_version_url() . '/vendors/timthumb.php';

	$attr['src'] = iwf_get_array_hard( $attr, 'path' ) ? iwf_url_to_path( $file ) : $file;

	if ( $width ) {
		$attr['w'] = $width;
	}

	if ( $height ) {
		$attr['h'] = $height;
	}

	foreach ( $attr as $property => $value ) {
		switch ( $property ) {
			case 'zc':
			case 'q':
			case 's':
			case 'w':
			case 'h':
				if ( ! is_numeric( $value ) ) {
					unset( $$attr[ $property ] );
					continue;
				}

				$attr[ $property ] = (int) $value;
				break;

			case 'f':
				if ( ! is_array( $value ) ) {
					unset( $$attr[ $property ] );
					$value = array( $value );
				}

				$filters = array();

				foreach ( $value as $filter_name => $filter_args ) {
					$filter_args = is_array( $filter_args ) ? implode( ',', array_map( 'trim', $filter_args ) ) : trim( $filter_args );
					$filters[]   = implode( ',', array( trim( $filter_name ), $filter_args ) );
				}

				$attr[ $property ] = implode( '|', $filters );
				break;

			default:
				$attr[ $property ] = (string) $value;
				break;
		}
	}

	$attr = apply_filters( 'iwf_timthumb_attr', $attr );

	return $timthumb . '?' . http_build_query( array_filter( $attr ) );
}

/**
 * Returns the html tag
 *
 * @param string $tag
 * @param array $attributes
 * @param string $content
 *
 * @return string
 */
function iwf_html_tag( $tag, $attributes = array(), $content = null ) {
	return IWF_Tag::create( $tag, $attributes, $content );
}

/**
 * Returns the meta value from the term in the taxonomy
 *
 * @param string|stdClass $term
 * @param string $taxonomy
 * @param string $key
 * @param bool $default
 *
 * @return mixed
 */
function iwf_get_term_meta( $term, $taxonomy = null, $key = null, $default = false ) {
	return IWF_Taxonomy::get_option( $term, $taxonomy, $key, $default );
}

/**
 * Returns current page url
 *
 * @param array|string $query
 * @param bool $overwrite
 * @param string $glue
 *
 * @return string
 */
function iwf_get_current_url( $query = array(), $overwrite = false, $glue = '&' ) {
	$url          = ( is_ssl() ? 'https://' : 'http://' ) . iwf_get_array( $_SERVER, 'HTTP_HOST' ) . iwf_get_array( $_SERVER, 'REQUEST_URI' );
	$query_string = iwf_get_array( $_SERVER, 'QUERY_STRING' );

	if ( strpos( $url, '?' ) !== false ) {
		list( $url, $query_string ) = explode( '?', $url );
	}

	if ( $query_string ) {
		$query_string = wp_parse_args( $query_string );

	} else {
		$query_string = array();
	}

	if ( $query === false || $query === null ) {
		$query = array();

	} else {
		$query = wp_parse_args( $query );
	}

	if ( ! $overwrite ) {
		foreach ( $query as $key => $val ) {
			if ( $val == '__pass__' && array_key_exists( $key, $query_string ) ) {
				unset( $query[ $key ] );
			}
		}

		$query = array_merge( $query_string, $query );

	} else {
		foreach ( $query as $key => $val ) {
			if ( $val == '__pass__' && array_key_exists( $key, $query_string ) ) {
				$query[ $key ] = $query_string[ $key ];
			}
		}
	}

	foreach ( $query as $key => $val ) {
		if ( $val === false || $val === null || $val === '' || $val == '__pass__' ) {
			unset( $query[ $key ] );
		}
	}

	$url = iwf_create_url( $url, $query, $glue );

	return $url;
}

/**
 * Create the url with specified the url and the query strings.
 *
 * @param string $url
 * @param array|string $query
 * @param string $glue
 *
 * @return string
 */
function iwf_create_url( $url, $query = array(), $glue = '&' ) {
	$query = http_build_query( wp_parse_args( $query ) );

	if ( $query ) {
		$url .= ( strrpos( $url, '?' ) !== false ) ? $glue . $query : '?' . $query;
	}

	return $url;
}

/**
 * Alias method of IWF_Post::get_thumbnail()
 *
 * @param int|stdClass|WP_Post $post_id
 * @param string $fallback_var_name
 *
 * @return array
 * @see IWF_Post::get_thumbnail
 */
function iwf_get_post_thumbnail_data( $post_id = null, $fallback_var_name = 'post_content' ) {
	return IWF_Post::get_thumbnail( $post_id, $fallback_var_name );
}

/**
 * Get the document root path
 *
 * @return string|bool
 */
function iwf_get_document_root() {
	$script_path   = iwf_get_array( $_SERVER, 'SCRIPT_FILENAME' );
	$script_url    = iwf_get_array( $_SERVER, 'SCRIPT_NAME' );
	$document_root = iwf_get_array( $_SERVER, 'DOCUMENT_ROOT' );

	if ( ! $document_root && ( ! $script_path || ! $script_url ) ) {
		return false;
	}

	if ( $script_path && $script_url && ( ! $document_root || strpos( $script_path, $document_root ) === false ) ) {
		$script_path = str_replace( DIRECTORY_SEPARATOR, '/', $script_path );

		if ( strrpos( $script_path, $script_url ) === 0 ) {
			$document_root = substr( $script_path, 0, 0 - strlen( $script_url ) );

		} else {
			$tmp_script_path = $script_path;
			$tmp_script_url  = $script_url;

			while ( basename( $tmp_script_path ) === basename( $tmp_script_url ) ) {
				$tmp_script_path = dirname( $tmp_script_path );
				$tmp_script_url  = dirname( $tmp_script_url );
			}

			if ( $tmp_script_path != '.' ) {
				$document_root = $tmp_script_path;
			}
		}
	}

	$document_root = untrailingslashit( $document_root );

	return $document_root;
}

/**
 * Calculate the file path from url
 *
 * @param string $url
 *
 * @return bool|string
 */
function iwf_url_to_path( $url ) {
	$host = preg_replace( '|^www\.|i', '', iwf_get_array( $_SERVER, 'HTTP_HOST' ) );
	$url  = ltrim( preg_replace( '|https?://(?:www\.)?' . $host . '|i', '', $url ), '/' );

	if ( preg_match( '/^https?:\/\/[^\/]+/i', $url ) ) {
		return false;
	}

	$script_path = str_replace( DIRECTORY_SEPARATOR, '/', iwf_get_array( $_SERVER, 'SCRIPT_FILENAME' ) );
	$script_url  = iwf_get_array( $_SERVER, 'SCRIPT_NAME' );

	if ( $script_path && $script_url && strpos( $script_path, $script_url ) === false ) {
		$tmp_script_path = $script_path;
		$tmp_script_url  = $script_url;

		while ( basename( $tmp_script_path ) === basename( $tmp_script_url ) ) {
			$tmp_script_path = dirname( $tmp_script_path );
			$tmp_script_url  = dirname( $tmp_script_url );
		}

		if ( $tmp_script_url != '/' || $tmp_script_url != '.' ) {
			$url = str_replace( trailingslashit( ltrim( $tmp_script_url, '/' ) ), '', $url );
		}
	}

	$document_root = iwf_get_document_root();

	if ( ! $document_root ) {
		$file_name = basename( $url );

		if ( is_file( $file_name ) ) {
			return realpath( $file_name );
		}
	}

	if ( file_exists( $document_root . '/' . $url ) ) {
		$abs_file_path = realpath( $document_root . '/' . $url );

		if ( stripos( $abs_file_path, $document_root ) === 0 ) {
			return $abs_file_path;
		}
	}

	$abs_file_path = realpath( '/' . $url );

	if ( $abs_file_path && file_exists( $abs_file_path ) ) {
		if ( stripos( $abs_file_path, $document_root ) === 0 ) {
			return $abs_file_path;
		}
	}

	$base_path       = $document_root;
	$sub_directories = array_filter( explode( '/', str_replace( $document_root, '', $script_path ) ) );

	foreach ( $sub_directories as $sub_directory ) {
		$base_path .= '/' . $sub_directory;

		if ( file_exists( $base_path . '/' . $url ) ) {
			$abs_file_path = realpath( $base_path . '/' . $url );

			if ( stripos( $abs_file_path, $document_root ) === 0 ) {
				return $abs_file_path;
			}
		}
	}

	return false;
}

/**
 * Calculate the image sizes
 *
 * @param int $width
 * @param int $height
 * @param int $new_width
 * @param int $new_height
 *
 * @return array The first element of the array is the width, the second element is the height
 */
function iwf_calc_image_size( $width, $height, $new_width = 0, $new_height = 0 ) {
	$sizes = array( 'width' => $new_width, 'height' => $new_height );

	if ( $new_width > 0 ) {
		$ratio           = $new_width / $width;
		$sizes['height'] = floor( $height * $ratio );

		if ( $new_height > 0 && $sizes['height'] > $new_height ) {
			$ratio           = ( 100 * $new_height ) / $sizes['height'];
			$sizes['width']  = floor( ( $sizes['width'] * $ratio ) / 100 );
			$sizes['height'] = $new_height;
		}

	} else if ( $new_height > 0 ) {
		$ratio          = $new_height / $height;
		$sizes['width'] = floor( $width * $ratio );

		if ( $new_width > 0 && $sizes['width'] > $new_width ) {
			$ratio           = ( 100 * $new_width ) / $sizes['width'];
			$sizes['height'] = floor( ( $sizes['height'] * $ratio ) / 100 );
			$sizes['width']  = $new_width;
		}
	}

	return $sizes;
}

/**
 * Get the value using any key from the array
 *
 * @param array $array
 * @param string|array $key
 * @param mixed $default
 *
 * @return array
 */
function iwf_get_array( &$array, $key, $default = null, $hard = false ) {
	if ( is_null( $key ) ) {
		return $array;
	}

	if ( is_array( $key ) ) {
		$return = array();

		foreach ( $key as $_key => $_default ) {
			if ( is_int( $_key ) && ( is_string( $_default ) || is_numeric( $_default ) ) ) {
				$_key     = (string) $_default;
				$_default = $default;
			}

			$_key_parts                                        = explode( '.', $_key );
			$return[ $_key_parts[ count( $_key_parts ) - 1 ] ] = iwf_get_array( $array, $_key, $_default, $hard );
		}

		return $return;
	}

	$key_parts  = explode( '.', $key );
	$key_size   = count( $key_parts );
	$joined_key = '';
	$return     = $array;

	foreach ( $key_parts as $i => $key_part ) {
		if ( ! is_array( $return ) || ( ! array_key_exists( $key_part, $return ) ) ) {
			return $default;
		}

		$return = $return[ $key_part ];

		if ( $hard ) {
			$joined_key .= "['{$key_part}']";

			if ( $key_size <= $i + 1 ) {
				eval( "unset( \$array$joined_key );" );
			}
		}
	}

	return $return;
}

/**
 * Check the key in the array
 *
 * @param array $array
 * @param string $key
 *
 * @return bool
 */
function iwf_has_array( $array, $key, $check_not_empty = false ) {
	$key_parts = explode( '.', $key );
	$current   = $array;

	foreach ( $key_parts as $key_part ) {
		if ( ! is_array( $current ) || ! array_key_exists( $key_part, $current ) ) {
			return false;
		}

		$current = $current[ $key_part ];
	}

	return $check_not_empty ? ! empty( $current ) : true;
}

/**
 * Get the value using any key from the array, and then delete that value
 *
 * @param array $array
 * @param array|string $key
 * @param mixed $default
 *
 * @return array
 */
function iwf_get_array_hard( &$array, $key, $default = null ) {
	return iwf_get_array( $array, $key, $default, true );
}

/**
 * Sets the value using any key to the array
 *
 * @param array $array
 * @param string|array $key
 * @param mixed $value
 */
function iwf_set_array( &$array, $key, $value = null ) {
	if ( is_null( $key ) ) {
		return;
	}

	if ( is_array( $key ) ) {
		foreach ( $key as $k => $v ) {
			iwf_set_array( $array, $k, $v );
		}

	} else {
		$keys = explode( '.', $key );

		while ( count( $keys ) > 1 ) {
			$key = array_shift( $keys );

			if ( ! isset( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
				$array[ $key ] = array();
			}

			$array =& $array[ $key ];
		}

		$array[ array_shift( $keys ) ] = $value;
	}
}

/**
 * Delete the value with any key from the array
 *
 * @param array $array
 * @param string|array $key
 *
 * @return bool
 */
function iwf_delete_array( &$array, $key ) {
	if ( is_null( $key ) ) {
		return false;
	}

	if ( is_array( $key ) ) {
		$return = array();

		foreach ( $key as $k ) {
			$return[ $k ] = iwf_delete_array( $array, $k );
		}

		return $return;
	}

	$key_parts = explode( '.', $key );

	if ( ! is_array( $array ) || ! array_key_exists( $key_parts[0], $array ) ) {
		return false;
	}

	$this_key = array_shift( $key_parts );

	if ( ! empty( $key_parts ) ) {
		$key = implode( '.', $key_parts );

		return iwf_delete_array( $array[ $this_key ], $key );

	} else {
		unset( $array[ $this_key ] );
	}

	return true;
}

/**
 * Convert the value to any type.
 *
 * @param mixed $value
 * @param string $type
 *
 * @return mixed
 */
function iwf_convert( $value, $type ) {
	switch ( $type ) {
		case 'i':
		case 'int':
		case 'integer':
			$value = is_scalar( $value ) || is_array( $value ) ? intval( $value ) : 0;
			break;

		case 'f':
		case 'float':
		case 'double':
		case 'real':
			$value = is_scalar( $value ) || is_array( $value ) ? floatval( $value ) : 0;
			break;

		case 'b':
		case 'bool':
		case 'boolean':
			$value = (bool) $value;
			break;

		case 's':
		case 'string':
			if ( is_array( $value ) ) {
				$is_value_only  = iwf_check_value_only( $value );
				$encoded_values = array();

				foreach ( $value as $_key => $_value ) {
					$encoded_value = ( $is_value_only ? '' : $_key . ':' );
					$encoded_value .= is_array( $_value ) ? '[' . iwf_convert( $_value, 'string' ) . ']' : iwf_convert( $_value, 'string' );
					$encoded_values[] = $encoded_value;
				}

				$value = implode( ', ', $encoded_values );

			} else if ( is_object( $value ) ) {
				if ( method_exists( $value, '__toString' ) ) {
					$value = $value->__toString();

				} else {
					$value = '(' . get_class( $value ) . ')';
				}

			} else if ( is_bool( $value ) ) {
				$value = ( $value === true ) ? 'true' : 'false';

			} else {
				$value = (string) $value;
			}

			break;

		case 'a':
		case 'array':
			if ( is_object( $value ) ) {
				$value = get_object_vars( $value );

			} else if ( ! is_array( $value ) ) {
				$value = array( $value );
			}

			break;

		case 'o':
		case 'object':
			if ( is_array( $value ) ) {
				foreach ( $value as $_key => $_value ) {
					if ( is_numeric( $_key ) ) {
						unset( $value[ $_key ] );
					}
				}
			}

			if ( ! is_object( $value ) ) {
				$value = (object) $value;
			}

			break;
	}

	return $value;
}

/**
 * Apply functions to the value.
 *
 * @param mixed $value
 * @param callback|array $callback
 *
 * @return mixed
 */
function iwf_callback( $value, $callback ) {
	if ( is_callable( $callback ) ) {
		/**
		 * $callback is:
		 * - 'function'
		 * - array( 'class', 'method' )
		 * - Closure
		 */
		$value = call_user_func( $callback, $value );

	} else {
		if ( is_string( $callback ) ) {
			/**
			 * $callback is:
			 * - 'function1 function2 function3'
			 */
			$callback = array_map( create_function( '$a', 'return array( $a );' ), array_filter( explode( ' ', $callback ) ) );
		}

		if ( is_array( $callback ) ) {
			$callbacks = $callback;

			if ( iwf_check_value_only( $callbacks ) ) {
				if (
					is_callable( $callbacks[0] ) && (
						count( $callbacks ) == 1
						|| (
							count( $callbacks ) > 1
							&& ! is_callable( $callbacks[1] )
							&& ( ! is_array( $callbacks[1] ) || ! is_callable( key( $callbacks[1] ) ) )
						)
					)
				) {
					/**
					 * $callback is:
					 * - array( 'function' )
					 * - array( array( 'class', 'method' ) )
					 * - array( 'function', 'arg_1', 'arg_2' )
					 * - array( array( 'class', 'method' ), 'arg_1', 'arg_2' )
					 */
					$callbacks = array( $callbacks );

				} else if ( count( $callbacks ) > 1 ) {
					/**
					 * $callback is:
					 * - array( 'function', 'function', 'function' )
					 * - array( array( 'function' => array( 'arg_1', 'arg_2' ) ), 'function' )
					 */
					foreach ( $callbacks as $i => $callback ) {
						if ( ! is_array( $callback ) ) {
							if ( ! is_callable( $callback ) ) {
								unset( $callbacks[ $i ] );
								continue;
							}

						} else {
							if ( ! is_callable( $callback ) ) {
								list( $callback_key, $callback_value ) = each( $callback );

								if ( ! is_numeric( $callback_key ) ) {
									if ( ! is_callable( $callback_key ) ) {
										unset( $callbacks[ $i ] );
										continue;
									}

									if ( is_array( $callback_value ) ) {
										array_unshift( $callback_value, $callback_key );

									} else {
										$callback_value = array( $callback_key, $callback_value );
									}

									$callbacks[ $i ] = $callback_value;

								} else {
									if ( ! iwf_check_value_only( $callback_value ) || ! is_callable( $callback_value[0] ) ) {
										unset( $callbacks[ $i ] );
									}

									$callbacks[ $i ] = $callback_value;
								}
							}
						}
					}
				}

			} else {
				/**
				 * $callback is:
				 * - array( 'function' => array( 'arg_1', 'arg_2' ) )
				 */
				$_callbacks = array();

				foreach ( $callbacks as $function => $args ) {
					if ( is_int( $function ) && $args ) {
						$function = $args;
						$args     = array();
					}

					if ( is_array( $function ) ) {
						$_function = array_values( $function );

						if ( is_callable( $_function[0] ) ) {
							$function = array_shift( $_function );
							$args     = $_function;
						}
					}

					if ( ! is_callable( $function ) ) {
						continue;
					}


					array_unshift( $args, $function );
					$_callbacks[] = $args;
				}

				$callbacks = $_callbacks;
			}

			// Process the all callbacks
			foreach ( $callbacks as $callback ) {
				if ( is_callable( $callback ) ) {
					$callback = array( $callback );
				}

				if ( ! is_array( $callback ) ) {
					continue;
				}

				$function = array_shift( $callback );
				$args     = $callback;

				if ( ! is_callable( $function ) ) {
					continue;
				}

				if ( ! $args ) {
					$args = array();

				} else if ( ! is_array( $args ) ) {
					$args = array( $args );
				}

				if ( ( $value_index = array_search( '%value%', $args, true ) ) !== false ) {
					// The '%value%' of the text in the $args will be replaced to the $value.
					$args[ $value_index ] = $value;

				} else {
					array_unshift( $args, $value );
				}

				$value = call_user_func_array( $function, $args );
			}
		}
	}

	return $value;
}

/**
 * Apply filters to the value.
 *
 * @param mixed $value
 * @param string|array $attr
 *
 * @return mixed
 */
function iwf_filter( $value, $attr = array() ) {
	if ( ! is_array( $attr ) ) {
		$attr = array( 'default' => $attr );
	}

	$attr = wp_parse_args( $attr, array(
		'default'     => null,
		'empty_value' => false,
		'before'      => '',
		'after'       => ''
	) );

	if ( ! $attr['empty_value'] && empty( $value ) ) {
		return ! is_null( $attr['default'] ) ? $attr['default'] : $value;
	}

	if ( ! empty( $attr['filter'] ) ) {
		$attr['callback'] = $attr['filter'];
	}

	foreach ( $attr as $attr_key => $attr_value ) {
		if ( $attr_key == 'convert' && $attr_value ) {
			$value = iwf_convert( $value, $attr_value );

		} else if ( $attr_key == 'callback' && $attr_value ) {
			$value = iwf_callback( $value, $attr_value );
		}
	}

	if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
		return $value;

	} else {
		return ( $attr['before'] || $attr['after'] ) ? $attr['before'] . (string) $value . $attr['after'] : $value;
	}
}

/**
 * Return the blogs
 *
 * @param array $args
 *
 * @return array
 */
function iwf_get_blogs( $args = array() ) {
	global $wpdb;

	$args = wp_parse_args( $args, array(
		'include_id' => null,
		'exclude_id' => null,
		'orderby'    => null,
		'order'      => 'desc'
	) );

	if ( ! $args['orderby'] || ! in_array( $args['orderby'], array( 'blog_id', 'site_id', 'domain', 'path', 'registered', 'last_updated', 'pubilc', 'archived', 'mature', 'spam', 'deleted', 'lang_id' ) ) ) {
		$args['orderby'] = 'registered';
	}

	if ( strtolower( $args['order'] ) != 'desc' ) {
		$args['order'] = 'asc';
	}

	$query[] = "SELECT blog_id, domain, path";
	$query[] = "FROM $wpdb->blogs";
	$query[] = sprintf( "WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'", $wpdb->siteid );

	if ( $args['include_id'] ) {
		$args['include_id'] = wp_parse_id_list( $args['include_id'] );
		$query[]            = sprintf( "AND blog_id IN ( %s )", implode( ', ', $args['include_id'] ) );
	}

	if ( $args['exclude_id'] ) {
		$args['exclude_id'] = wp_parse_id_list( $args['exclude_id'] );
		$query[]            = sprintf( "AND blog_id NOT IN ( %s )", implode( ', ', $args['exclude_id'] ) );
	}

	$query[] = sprintf( "ORDER BY %s %s", $args['orderby'], strtoupper( $args['order'] ) );
	$key     = md5( implode( '', $query ) );

	if ( ! empty( $GLOBALS['_iwf_all_blogs'][ $key ] ) ) {
		return $GLOBALS['_iwf_all_blogs'][ $key ];
	}

	$GLOBALS['_iwf_all_blogs'][ $key ] = $blogs = $wpdb->get_results( implode( ' ', $query ) );

	return $blogs ? $blogs : array();
}

/**
 * Get the option with the option set
 *
 * @param string|array $key Dot separated key, First part of separated key with dot is option set name
 * @param mixed $default
 *
 * @return array|mixed
 */
function iwf_get_option( $key, $default = false ) {
	return IWF_Meta::option( $key, $default );
}

/**
 * Update the option with the option set
 *
 * @param string|array $key Dot separated key, First part of separated key with dot is option set name
 * @param mixed $value
 *
 * @return bool
 */
function iwf_update_option( $key, $value = null ) {
	return IWF_Meta::update_option( $key, $value );
}

/**
 * Get the plugin base name from any plugin files.
 *
 * @param string $file
 *
 * @return bool|string
 */
function iwf_plugin_basename( $file ) {
	$file          = str_replace( '\\', '/', $file );
	$file          = preg_replace( '|/+|', '/', $file );
	$plugin_dir    = str_replace( '\\', '/', WP_PLUGIN_DIR );
	$plugin_dir    = preg_replace( '|/+|', '/', $plugin_dir );
	$mu_plugin_dir = str_replace( '\\', '/', WPMU_PLUGIN_DIR );
	$mu_plugin_dir = preg_replace( '|/+|', '/', $mu_plugin_dir );

	if ( ! file_exists( $file ) || ( strpos( $file, $plugin_dir ) !== 0 && strpos( $file, $mu_plugin_dir ) !== 0 ) ) {
		return false;
	}

	$file = preg_replace( '#^' . preg_quote( $plugin_dir, '#' ) . '/|^' . preg_quote( $mu_plugin_dir, '#' ) . '/#', '', $file );
	$file = trim( $file, '/' );

	while ( ( $tmp_file = dirname( $file ) ) != '.' ) {
		$file = $tmp_file;
	}

	return $file;
}

/**
 * Get the tweet count of specified URL
 *
 * @param string $url
 * @param int $cache_time
 *
 * @return int
 */
function iwf_get_tweet_count( $url, $cache_time = 86400 ) {
	$cache_key = 'iwf_tweet_count_' . iwf_short_hash( $url );

	if ( $cache_time < 1 ) {
		delete_transient( $cache_key );
	}

	if ( ( $cache = get_transient( $cache_key ) ) !== false ) {
		return $cache;
	}

	$json = 'http://urls.api.twitter.com/1/urls/count.json?url=' . urlencode( $url );

	if ( $result = file_get_contents( $json ) ) {
		$result = json_decode( $result );

		if ( isset( $result->count ) ) {
			$count = (int) $result->count;

			if ( $cache_time ) {
				set_transient( $cache_key, $count, $cache_time );
			}

			return $count;
		}
	}

	return 0;
}

/**
 * Get the facebook like count of specified URL
 *
 * @param string $url
 * @param int $cache_time
 *
 * @return int
 */
function iwf_get_fb_like_count( $url, $cache_time = 86400 ) {
	$cache_key = 'iwf_fb_like_count_' . iwf_short_hash( $url );

	if ( $cache_time < 1 ) {
		delete_transient( $cache_key );
	}

	if ( ( $cache = get_transient( $cache_key ) ) !== false ) {
		return $cache;
	}

	$xml = 'http://api.facebook.com/method/fql.query?query=select%20total_count%20from%20link_stat%20where%20url=%22' . urlencode( $url ) . '%22';

	if ( $result = file_get_contents( $xml ) ) {
		$result = simplexml_load_string( $result );

		if ( isset( $result->link_stat->total_count ) ) {
			$count = (int) $result->link_stat->total_count;

			if ( $cache_time ) {
				set_transient( $cache_key, $count, $cache_time );
			}

			return $count;
		}
	}

	return 0;
}

/**
 * Get the geo location data of google map of specified URL
 *
 * @param string $address
 * @param int $cache_time
 *
 * @return array
 */
function iwf_get_google_geo_location( $address, $cache_time = 86400 ) {
	$cache_key = 'iwf_google_geo_location_' . iwf_short_hash( $address );

	if ( $cache_time < 1 ) {
		delete_transient( $cache_key );
	}

	if ( ( $cache = get_transient( $cache_key ) ) !== false ) {
		return $cache;
	}

	$data = file_get_contents( 'http://maps.google.co.jp/maps/api/geocode/json?address=' . urlencode( $address ) . '&sensor=false' );

	if ( ( $json = json_decode( $data, true ) ) && $json['status'] == 'OK' ) {
		$geo_location = $json['results'][0];

		if ( $cache_time ) {
			set_transient( $cache_key, $geo_location, $cache_time );
		}

		return $geo_location;
	}

	return array();
}

/**
 * Alias method of IWF_Post::get()
 *
 * @param int $post_id
 * @param array|string $args
 *
 * @return mixed
 * @see bool|stdClass|WP_Post
 */
function iwf_get_post( $post_id, $args = array() ) {
	return IWF_Post::get( $post_id, $args );
}

/**
 * Check only the values array.
 *
 * @param array $values
 *
 * @return bool
 */
function iwf_check_value_only( array $values = array() ) {
	for ( $i = 0; $i < count( $values ); $i ++ ) {
		if ( ! array_key_exists( $i, $values ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Create the url of file path
 *
 * @param string $file_path
 * @param int $default_port
 *
 * @return bool|string
 */
function iwf_path_to_url( $file_path, $default_port = 80 ) {
	if ( ! $abs_file_path = realpath( $file_path ) ) {
		return false;
	}

	$script_path = str_replace( DIRECTORY_SEPARATOR, '/', iwf_get_array( $_SERVER, 'SCRIPT_FILENAME' ) );
	$script_url  = iwf_get_array( $_SERVER, 'SCRIPT_NAME' );

	while ( basename( $script_path ) === basename( $script_url ) ) {
		$script_path = dirname( $script_path );
		$script_url  = dirname( $script_url );
	}

	if ( $script_path === '/' || $script_path === '.' ) {
		$script_path = '';
	}

	if ( $script_url === '/' || $script_url === '.' ) {
		$script_url = '';
	}

	$protocol   = ( ! empty( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) !== 'off' ) ? 'https' : 'http';
	$port       = ( ! empty( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != $default_port ) ? ':' . $_SERVER['SERVER_PORT'] : '';
	$script_url = $protocol . '://' . iwf_get_array( $_SERVER, 'SERVER_NAME' ) . $port . $script_url;

	$file_path     = str_replace( DIRECTORY_SEPARATOR, '/', $file_path );
	$abs_file_path = str_replace( DIRECTORY_SEPARATOR, '/', $abs_file_path );

	if ( substr( $abs_file_path, - 1 ) !== '/' && substr( $file_path, - 1 ) === '/' ) {
		$abs_file_path .= '/';
	}

	$url = str_replace( $script_path, $script_url, $abs_file_path );

	if ( $abs_file_path === $url ) {
		return false;
	}

	return $url;
}

/**
 * Get the term link
 *
 * @param string|stdClass $term
 * @param                 $taxonomy
 *
 * @return string
 */
function iwf_get_term_link_safe( $term, $taxonomy = null ) {
	if ( ! $term = IWF_Taxonomy::get( $term, $taxonomy ) ) {
		return '';

	} else {
		$link = get_term_link( $term->slug, $term->taxonomy );

		if ( is_wp_error( $link ) ) {
			return '';
		}
	}

	return (string) $link;
}

/**
 * Apply the basic authentication
 *
 * @param array $auth_list
 * @param string $realm
 * @param string $failed_text
 *
 * @return mixed
 */
function iwf_basic_auth( array $auth_list, $realm = 'Restricted Area', $failed_text = 'Authentication Failed.' ) {
	if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $auth_list[ $_SERVER['PHP_AUTH_USER'] ] ) ) {
		if ( $auth_list[ $_SERVER['PHP_AUTH_USER'] ] == $_SERVER['PHP_AUTH_PW'] ) {
			return $_SERVER['PHP_AUTH_USER'];
		}
	}

	header( 'WWW-Authenticate: Basic realm="' . $realm . '"' );
	header( 'HTTP/1.0 401 Unauthorized' );

	exit( $failed_text );
}

/**
 * Convert the EOL to any character
 *
 * @param string $string
 * @param string $to
 *
 * @return string
 */
function iwf_convert_eol( $string, $to = "\n" ) {
	return strtr( $string, array( "\r\n" => $to, "\r" => $to, "\n" => $to ) );
}

/**
 * Convert string to short hash
 *
 * @param        $string
 * @param string $algorithm
 *
 * @return string
 */
function iwf_short_hash( $string, $algorithm = 'CRC32' ) {
	return strtr( rtrim( base64_encode( pack( 'H*', $algorithm( $string ) ) ), '=' ), '+/', '-_' );
}

/**
 * Returns the truncated text with ellipsis
 *
 * @param        $text
 * @param int $length
 * @param string $ellipsis
 *
 * @return string
 */
function iwf_truncate( $text, $length = 200, $ellipsis = '...' ) {
	$text = strip_tags( strip_shortcodes( iwf_convert_eol( $text, "" ) ) );

	if ( mb_strlen( $text ) > $length ) {
		$text = mb_substr( $text, 0, $length ) . $ellipsis;
	}

	return $text;
}

/**
 * Returns the text that has been set link from url or e-mail
 *
 * @param      $text
 * @param bool $target_blank
 *
 * @return string
 */
function iwf_auto_link( $text, $target_blank = false ) {
	$placeholders = array();
	$patterns     = array(
		'#(?<!href="|src="|">)((?:https?|ftp|nntp)://[^\s<>()]+)#i',
		'#(?<!href="|">)(?<!\b[[:punct:]])(?<!http://|https://|ftp://|nntp://)www.[^\n\%\ <]+[^<\n\%\,\.\ <](?<!\))#i'
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match_all( $pattern, $text, $matches ) ) {
			foreach ( $matches[0] as $match ) {
				$key                  = md5( $match );
				$placeholders[ $key ] = $match;
				$text                 = str_replace( $match, $key, $text );
			}
		}
	}

	$replace = array();

	foreach ( $placeholders as $md5 => $url ) {
		if ( ! preg_match( '#^[a-z]+\://#', $url ) ) {
			$url = 'http://' . $url;
		}

		$tag_args = array( 'href' => $url );

		if ( $target_blank ) {
			$tag_args['target'] = '_blank';
		}

		$replace[ $md5 ] = iwf_html_tag( 'a', $tag_args, $url );
	}

	return strtr( $text, $replace );
}

/**
 * Do shortcode
 *
 * @param       $tag
 * @param array $attr
 * @param null $content
 */
function iwf_do_shortcode( $tag, $attr = array(), $content = null ) {
	if ( $content ) {
		if ( ! is_scalar( $content ) ) {
			return '';
		}

		$code = "[{$tag} " . IWF_Tag_Element_Node::parse_attributes( $attr ) . "]" . (string) $content . "[/{$tag}]";

	} else {
		$code = "[{$tag} " . IWF_Tag_Element_Node::parse_attributes( $attr ) . "]";
	}

	return do_shortcode( $code );
}

/**
 * Get the image file width and height
 *
 * @param $file_path
 * @param int $new_width
 * @param int $new_height
 *
 * @return array
 */
function iwf_get_image_size( $file_path, $new_width = 0, $new_height = 0 ) {
	if ( strpos( $file_path, 'http://' ) === 0 ) {
		$file_path = iwf_url_to_path( $file_path );
	}

	if ( ! $file_path || ! file_exists( $file_path ) ) {
		return array();
	}

	if ( ! $image_sizes = @getimagesize( $file_path ) ) {
		return array();
	}

	$sizes = array( 'width' => $image_sizes[0], 'height' => $image_sizes[1] );

	if ( $new_width || $new_height ) {
		$sizes = iwf_calc_image_size( $sizes['width'], $sizes['height'], $new_width, $new_height );
	}

	return $sizes;
}

/**
 * Returns the image tag
 *
 * @param string $file_path
 * @param int $width
 * @param int $height
 * @param array $args
 *
 * @return string
 */
function iwf_img_tag( $file_path, $width = 0, $height = 0, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'width'  => 0,
		'height' => 0,
		'alt'    => '',
		'zc'     => 1,
		'cc'     => '',
	) );

	if ( ! $width || ! $height ) {
		if ( $sizes = iwf_get_image_size( $file_path, $width, $height ) ) {
			$args = array_merge( $args, $sizes );
		}

	} else {
		$args['width']  = $width;
		$args['height'] = $height;
	}

	$zc = iwf_get_array_hard( $args, 'zc' );
	$cc = iwf_get_array_hard( $args, 'cc' );

	$args['src'] = iwf_timthumb( $file_path, $args['width'], $args['height'], array( 'zc' => $zc, 'cc' => $cc ) );

	if ( ! $args['width'] ) {
		unset( $args['width'] );
	}

	if ( ! $args['height'] ) {
		unset( $args['height'] );
	}

	return iwf_html_tag( 'img', $args );
}
