<?php
require_once 'wp-load.php';
require_once 'vfsStream/vfsStream.php';
require_once dirname( dirname( __FILE__ ) ) . '/iwf-functions.php';

class IWF_FunctionsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
	}

	protected function tearDown() {
	}

	/**
	 * @covers iwf_get_array
	 */
	public function testGetArray() {
		$array = array(
			'testKey' => 'testValue',
			'testKey2' => array(
				'deepKey' => 'deepValue'
			),
			'testKey3' => array(
				'deepKey2' => 'deepValue2',
				'deepKey3' => array(
					'deepDeepKey1' => 'deepDeepValue1',
					'deepDeepKey2' => 'deepDeepValue2'
				)
			),
			'valueOnly',
		);

		$this->assertEquals( 'testValue', iwf_get_array( $array, 'testKey' ) );

		$this->assertEquals( 'valueOnly', iwf_get_array( $array, 0 ) );

		$this->assertEquals( array(
			'deepKey' => 'deepValue'
		), iwf_get_array( $array, 'testKey2' ) );

		$this->assertEquals( 'deepValue', iwf_get_array( $array, 'testKey2.deepKey' ) );

		$this->assertEquals( array(
			'testKey' => 'testValue',
			'valueOnly'
		), iwf_get_array( $array, array( 'testKey', 0 ) ) );

		$this->assertNull( iwf_get_array( $array, 'testKey4' ) );

		$this->assertNull( iwf_get_array( $array, 'testKey2.deepKey.none' ) );

		$this->assertEquals( 'default', iwf_get_array( $array, 'testKey4', 'default' ) );

		$this->assertEquals( array(
			'testKey' => 'testValue',
			'testKey4' => 'default',
			'testKey5' => null,
			1 => null,
			0 => 'valueOnly'
		), iwf_get_array( $array, array(
			'testKey',
			'testKey4' => 'default',
			'testKey5',
			1,
			0
		) ) );

		$this->assertEquals( array( 'default' => null ), iwf_get_array( $array, array( 0 => 'default' ) ) );

		$this->assertEquals( array(
			'testKey' => 'testValue',
			'deepKey' => 'deepValue',
			'deepKey2' => 'deepValue2',
			'deepDeepKey1' => 'deepDeepValue1'
		), iwf_get_array( $array, array(
			'testKey',
			'testKey2.deepKey',
			'testKey3.deepKey2',
			'testKey3.deepKey3.deepDeepKey1',
		) ) );
	}

	/**
	 * @covers iwf_get_array_hard
	 */
	public function testGetArrayHard() {
		$array = array(
			'testKey' => 'testValue',
			'valueOnly',
			'deep' => array(
				'key' => 'value'
			),
		);

		$this->assertEquals( 'testValue', iwf_get_array_hard( $array, 'testKey' ) );

		$this->assertEquals( array(
			'valueOnly',
			'deep' => array(
				'key' => 'value'
			),
		), $array );

		$this->assertEquals( 'value', iwf_get_array_hard( $array, 'deep.key' ) );

		$this->assertEquals( array(
			'valueOnly',
			'deep' => array()
		), $array );
	}

	/**
	 * @covers iwf_extract_and_merge
	 */
	public function testExtractAndMerge() {
		$array = array(
			'testKey' => 'testValue',
			'valueOnly',
			'deep' => array(
				'key' => 'value'
			),
			'deep2' => array(
				'key2' => 'value2'
			),
		);

		$this->assertEquals( array( 'testValue' ), iwf_extract_and_merge( $array, 'testKey' ) );

		$this->assertEquals( array(
			'valueOnly',
			'deep' => array(
				'key' => 'value'
			),
			'deep2' => array(
				'key2' => 'value2'
			),
		), $array );

		$this->assertEquals( array(), iwf_extract_and_merge( $array, 'deep3.key3' ) );

		$this->assertEquals( array(
			'valueOnly',
			'key' => 'value',
			'key2' => 'value2'
		), iwf_extract_and_merge( $array, array( 'deep', 'deep2', 0 ) ) );

		$this->assertEquals( array(), $array );
	}

	/**
	 * @covers iwf_calc_image_size
	 */
	public function testCalcImageSize() {
		$sizes = iwf_calc_image_size( 100, 75, 200, 200 );
		$expected = array( 'width' => 200, 'height' => 150 );

		$this->assertEquals( $expected, $sizes );

		$sizes = iwf_calc_image_size( 75, 100, 200, 200 );
		$expected = array( 'width' => 150, 'height' => 200 );

		$this->assertEquals( $expected, $sizes );

		$sizes = iwf_calc_image_size( 100, 75, 0, 150 );
		$expected = array( 'width' => 200, 'height' => 150 );

		$this->assertEquals( $expected, $sizes );

		$sizes = iwf_calc_image_size( 75, 100, 150, 0 );
		$expected = array( 'width' => 150, 'height' => 200 );

		$this->assertEquals( $expected, $sizes );
	}

	/**
	 * @covers iwf_get_ip
	 */
	public function testGetIp() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.5, 10.0.1.1, proxy.com';
		$_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.3';

		$this->assertEquals( '192.168.1.5', iwf_get_ip( false ) );

		$this->assertEquals( '192.168.1.2', iwf_get_ip() );

		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		$this->assertEquals( '192.168.1.2', iwf_get_ip() );

		unset( $_SERVER['HTTP_CLIENT_IP'] );

		$this->assertEquals( '192.168.1.3', iwf_get_ip() );
	}

	/**
	 * @covers iwf_request_is
	 */
	public function testRequestIs() {
		$this->assertFalse( iwf_request_is( 'undefined' ) );

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$this->assertTrue( iwf_request_is( 'get' ) );

		$_SERVER['REQUEST_METHOD'] = 'POST';

		$this->assertTrue( iwf_request_is( 'POST' ) );

		$_SERVER['REQUEST_METHOD'] = 'PUT';

		$this->assertTrue( iwf_request_is( 'put' ) );

		$this->assertFalse( iwf_request_is( 'get' ) );

		$_SERVER['REQUEST_METHOD'] = 'DELETE';

		$this->assertTrue( iwf_request_is( 'delete' ) );

		$_SERVER['REQUEST_METHOD'] = 'delete';

		$this->assertFalse( iwf_request_is( 'delete' ) );
	}

	/**
	 * @covers  iwf_get_document_root
	 */
	public function testGetDocumentRoot() {
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['DOCUMENT_ROOT'] = '/home/web_user/htdocs/www';
		$_SERVER['SCRIPT_FILENAME'] = '/home/web_user/htdocs/www/index.php';
		$docroot = iwf_get_document_root();
		$expected = '/home/web_user/htdocs/www';

		$this->assertEquals( $expected, $docroot );

		$_SERVER['DOCUMENT_ROOT'] = '/home/other_user/web_root/www';
		$docroot = iwf_get_document_root();
		$expected = '/home/web_user/htdocs/www';

		$this->assertEquals( $expected, $docroot );

		$_SERVER['DOCUMENT_ROOT'] = '';
		$docroot = iwf_get_document_root();
		$expected = '/home/web_user/htdocs/www';

		$this->assertEquals( $expected, $docroot );

		$_SERVER['PHP_SELF'] = '/test.com/index.php';
		$_SERVER['DOCUMENT_ROOT'] = '/home/other_user/web_root/www';
		$_SERVER['SCRIPT_FILENAME'] = '/home/web_user/htdocs/www/index.php';
		$docroot = iwf_get_document_root();
		$expected = '/home/web_user/htdocs/www';

		$this->assertEquals( $expected, $docroot );
	}

	/**
	 * @covers  iwf_url_to_path
	 * @require extension runkit
	 */
	public function testUrlToPath() {
		runkit_function_rename( 'realpath', '_realpath' );
		runkit_function_add( 'realpath', '$file_path', 'return ( strpos( $file_path, "/" ) === 0 ) ? "vfs:/" . $file_path : $file_path;' );

		vfsStream::setup( 'home' );
		$test_dir = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files';
		mkdir( $test_dir, 0777, true );
		file_put_contents( $test_dir . '/test_file.txt', 'this is test file.' );

		$_SERVER['HTTP_HOST'] = 'test.com';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www';
		$_SERVER['SCRIPT_FILENAME'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www/index.php';

		$path = iwf_url_to_path( 'http://test.com/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$path = iwf_url_to_path( 'https://test.com/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$_SERVER['HTTP_HOST'] = '192.168.1.1';
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/user/www';
		$_SERVER['PHP_SELF'] = '/test.com/index.php';
		$_SERVER['SCRIPT_FILENAME'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www/index.php';

		$path = iwf_url_to_path( 'http://test.com/files/test_file.txt' );

		$this->assertFalse( $path );

		$path = iwf_url_to_path( 'http://192.168.1.1/test.com/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$_SERVER['HTTP_HOST'] = 'test.com';
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['SCRIPT_FILENAME'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www/index.php';

		$path = iwf_url_to_path( 'http://test.com/home/web_user/htdocs/www/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$test_dir_2 = vfsStream::url( 'home' ) . '/web_user/htdocs/www/dummy/dir/media/files';
		mkdir( $test_dir_2, 0777, true );
		file_put_contents( $test_dir_2 . '/test_file.txt', 'this is test file 2.' );

		$_SERVER['HTTP_HOST'] = 'test.com';
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['SCRIPT_FILENAME'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www/dummy/dir/index.php';

		$path = iwf_url_to_path( 'http://test.com/dummy/files/test_file.txt' );

		$this->assertFalse( $path );

		$path = iwf_url_to_path( 'http://test.com/media/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/dummy/dir/media/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$path = iwf_url_to_path( 'http://test.com/dummy/dir/media/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/dummy/dir/media/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$_SERVER['HTTP_HOST'] = '192.168.1.1';
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/user/www';
		$_SERVER['PHP_SELF'] = '/test.com/index.php';
		$_SERVER['SCRIPT_FILENAME'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www/dummy/dir/index.php';

		$path = iwf_url_to_path( 'http://192.168.1.1/test.com/media/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/dummy/dir/media/files/test_file.txt';

		$this->assertEquals( $expected, $path );
	}

	/**
	 * @covers iwf_check_value_only
	 */
	public function testCheckValueOnly() {
		$array = array(
			'test_value',
			'test_value_2',
			'test_value_3'
		);

		$hash = array(
			'test_key' => 'test_value',
			'test_key_2' => 'test_value_2',
			'test_key_3' => 'test_value_3'
		);

		$mixin = array(
			'test_key' => 'test_value',
			'test_value_2',
			'test_key_2' => 'test_value_3'
		);

		$this->assertTrue( iwf_check_value_only( $array ) );

		$this->assertFalse( iwf_check_value_only( $hash ) );

		$this->assertFalse( iwf_check_value_only( $mixin ) );
	}

	/**
	 * @covers iwf_callback
	 */
	public function testCallback() {
		$string = 'test_value';
		$array = array( 'test_value', 'test_value_2' );

		$value = iwf_callback( $string, 'strtoupper' );
		$expected = 'TEST_VALUE';

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $string, array( 'substr' => array( 0, 4 ), 'ucfirst' ) );
		$expected = 'Test';

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $string, 'md5 strtoupper' );
		$expected = strtoupper( md5( $string ) );

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $array, array( 'array_map' => array( 'strtoupper', '%value%' ), 'array_reverse' ) );
		$expected = array( 'TEST_VALUE_2', 'TEST_VALUE' );

		$this->assertEquals( $expected, $value );
	}
}