<?php
/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author         Masayuki Ietomi <jyokyoku@gmail.com>
 * @copyright      Copyright(c) 2011 Masayuki Ietomi
 * @link           http://inspire-tech.jp
 */

if ( !defined( 'IWF_DS' ) ) {
	define( 'IWF_DS', DIRECTORY_SEPARATOR );
}

if ( !defined( 'IWF_TMPL_URI' ) ) {
	define( 'IWF_TMPL_URI', get_template_directory_uri() );
}

if ( !defined( 'IWF_SS_URI' ) ) {
	define( 'IWF_SS_URI', get_stylesheet_directory_uri() );
}

if ( !defined( 'IWF_SECOND' ) ) {
	define( 'IWF_SECOND', 1 );
}

if ( !defined( 'IWF_MINUTE' ) ) {
	define( 'IWF_MINUTE', IWF_SECOND * 60 );
}

if ( !defined( 'IWF_HOUR' ) ) {
	define( 'IWF_HOUR', IWF_MINUTE * 60 );
}

if ( !defined( 'IWF_DAY' ) ) {
	define( 'IWF_DAY', IWF_HOUR * 24 );
}

if ( !defined( 'IWF_WEEK' ) ) {
	define( 'IWF_WEEK', IWF_DAY * 7 );
}

if ( !defined( 'IWF_MONTH' ) ) {
	define( 'IWF_MONTH', IWF_DAY * 30 );
}

if ( !defined( 'IWF_YEAR' ) ) {
	define( 'IWF_YEAR', IWF_DAY * 365 );
}