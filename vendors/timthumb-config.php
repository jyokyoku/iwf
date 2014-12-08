<?php
$iwf_dir = str_replace( '\\', '/', dirname( __FILE__ ) );

if ( ( $pos = strrpos( $iwf_dir, 'iwf' ) ) === false ) {
	exit();
}

$iwf_dir    = rtrim( substr( $iwf_dir, 0, $pos ), '/' ) . '/iwf';
$iwf_loader = $iwf_dir . '/iwf-loader.php';

if ( ! is_file( $iwf_loader ) || ! is_readable( $iwf_loader ) ) {
	exit();
}

$loaded       = false;
$i            = 0;
$search_depth = 3;
$config_dir   = $iwf_dir;

while ( $config_dir !== '/' && count( $config_dir ) > 0 && $i < $search_depth ) {
	$config_dir = dirname( $config_dir );
	$config     = $config_dir . '/timthumb-config.php';

	if ( is_file( $config ) && is_readable( $config ) ) {
		include_once $config;
		$loaded = true;
		break;
	}

	$i ++;
}

if ( ! $loaded && ! defined( 'FILE_CACHE_DIRECTORY' ) ) {
	if ( ( $pos = strrpos( $iwf_dir, 'wp-content' ) ) !== false ) {
		$content_dirs[] = rtrim( substr( $iwf_dir, 0, $pos ), '/' ) . '/wp-content';
	}

	$content_dirs[] = dirname( $iwf_dir );

	foreach ( $content_dirs as $content_dir ) {
		if ( is_dir( $content_dir ) && is_writable( $content_dir ) ) {
			define( 'FILE_CACHE_DIRECTORY', $content_dir . '/timthumb-cache' );
			break;
		}
	}
}