<?php
require_once 'wp-load.php';
require_once 'vfsStream/vfsStream.php';
require_once dirname( dirname( __FILE__ ) ) . '/iwf-functions.php';

class DummyClass {
	public $key_1 = 'value_1';

	protected $_key_2 = 'value_2';

	private $_key_3 = 'value_3';

	public function method_1() {
		return 'method_1';
	}

	protected function method_2() {
		return 'method_2';
	}

	private function method_3() {
		return 'method_3';
	}

	public function __toString() {
		return 'This is dummy class.';
	}
}

class DummyUtilityClass {
	public static function strtoupper( $text ) {
		return strtoupper( $text );
	}

	public static function array_slice( array $array, $length = 1 ) {
		return array_slice( $array, 0, $length );
	}
}

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
		$_SERVER['DOCUMENT_ROOT'] = '/home/web_user/htdocs/www';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
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

		$_SERVER['DOCUMENT_ROOT'] = '/home/other_user/web_root/www';
		$_SERVER['SCRIPT_NAME'] = '/test.com/index.php';
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
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['SCRIPT_FILENAME'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www/index.php';

		$path = iwf_url_to_path( 'http://test.com/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$path = iwf_url_to_path( 'https://test.com/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$_SERVER['HTTP_HOST'] = '192.168.1.1';
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/user/www';
		$_SERVER['SCRIPT_NAME'] = '/test.com/index.php';
		$_SERVER['SCRIPT_FILENAME'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www/index.php';

		$path = iwf_url_to_path( 'http://test.com/files/test_file.txt' );

		$this->assertFalse( $path );

		$path = iwf_url_to_path( 'http://192.168.1.1/test.com/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$_SERVER['HTTP_HOST'] = 'test.com';
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['SCRIPT_FILENAME'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www/index.php';

		$path = iwf_url_to_path( 'http://test.com/home/web_user/htdocs/www/files/test_file.txt' );
		$expected = vfsStream::url( 'home' ) . '/web_user/htdocs/www/files/test_file.txt';

		$this->assertEquals( $expected, $path );

		$test_dir_2 = vfsStream::url( 'home' ) . '/web_user/htdocs/www/dummy/dir/media/files';
		mkdir( $test_dir_2, 0777, true );
		file_put_contents( $test_dir_2 . '/test_file.txt', 'this is test file 2.' );

		$_SERVER['HTTP_HOST'] = 'test.com';
		$_SERVER['DOCUMENT_ROOT'] = vfsStream::url( 'home' ) . '/web_user/htdocs/www';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
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
		$_SERVER['SCRIPT_NAME'] = '/test.com/index.php';
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

		$value = iwf_callback( $string, array( 'DummyUtilityClass', 'strtoupper' ) );
		$expected = 'TEST_VALUE';

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $string, array( 'substr' => array( 0, 4 ), 'ucfirst' ) );
		$expected = 'Test';

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $string, 'md5 strtoupper' );
		$expected = strtoupper( md5( $string ) );

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $array, array( 'array_map' => array( 'strtoupper', '%value%' ) ) );
		$expected = array( 'TEST_VALUE', 'TEST_VALUE_2' );

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $array, array(
			array( 'array_map', 'strtoupper', '%value%' ),
			array( 'array_reverse' ),
			array( array( 'DummyUtilityClass', 'array_slice' ), 1 )
		) );
		$expected = array( 'TEST_VALUE_2' );

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $array, array(
			array( 'array_map', 'strtoupper', '%value%' ),
			'array_reverse',
			array( 'DummyUtilityClass', 'array_slice' )
		) );
		$expected = array( 'TEST_VALUE_2' );

		$this->assertEquals( $expected, $value );

		$value = iwf_callback( $array, array(
			array( 'array_map', 'strtoupper', '%value%' ),
			'array_pad' => array( 5, 'dummy' ),
			array( array( 'DummyUtilityClass', 'array_slice' ), 3 )
		) );
		$expected = array( 'TEST_VALUE', 'TEST_VALUE_2', 'dummy' );

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers iwf_convert
	 */
	public function testConvert() {
		$string = 'test_string';
		$array = array(
			'value',
			array(
				'nest_value',
				'nest_value_2'
			),
			(object)array(
				'obj_key' => 'obj_value',
				'obj_key_2' => array(
					'nest_obj_key' => 'nest_obj_value',
					'nest_obj_key_2' => 'nest_obj_value_2'
				)
			),
			new DummyClass()
		);
		$hash_array = array(
			'key' => 'value',
			'key_2' => array(
				'nest_key' => 'nest_value',
				'nest_key_2' => 'nest_value_2',
			),
			'key_3' => (object)array(
					'obj_key' => 'obj_value',
					'obj_key_2' => array(
						'nest_obj_key' => 'nest_obj_value',
						'nest_obj_key_2' => 'nest_obj_value_2'
					)
				),
			'key_4' => new DummyClass()
		);
		$bool = true;
		$int = 1;
		$std_class = (object)array(
			'member_string' => 'string',
			'member_array' => array(
				'key' => 'value',
				'key_2' => array(
					'nest_key' => 'nest_value',
					'nest_key_2' => 'nest_value_2'
				),
			),
			'member_bool' => false,
			'member_int' => 1
		);
		$dummy_class = new DummyClass();

		/**
		 * String
		 */
		$value = iwf_convert( $string, 'i' );
		$expected = 0;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $string, 'f' );
		$expected = 0;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $string, 'a' );
		$expected = array( 'test_string' );

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $string, 'b' );

		$this->assertTrue( $value );

		$value = iwf_convert( $string, 'o' );
		$expected = (object)$string;

		$this->assertEquals( $expected, $value );

		/**
		 * Array
		 */
		$value = iwf_convert( $array, 'i' );
		$expected = 1;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $array, 'f' );
		$expected = 1;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $array, 'b' );

		$this->assertTrue( $value );

		$value = iwf_convert( array(
			null,
			false,
			0
		), 'b' );

		$this->assertTrue( $value );

		$value = iwf_convert( array(), 'b' );

		$this->assertFalse( $value );

		$value = iwf_convert( $array, 's' );
		$expected = 'value, [nest_value, nest_value_2], (stdClass), This is dummy class.';

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $array, 'o' );
		$expected = new stdClass();

		$this->assertEquals( $expected, $value );

		/**
		 * Hash Array
		 */
		$value = iwf_convert( $hash_array, 'i' );
		$expected = 1;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $hash_array, 'f' );
		$expected = 1;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $hash_array, 'b' );

		$this->assertTrue( $value );

		$value = iwf_convert( array(
			'key_1' => null,
			'key_2' => false,
			'key_3' => 0
		), 'b' );

		$this->assertTrue( $value );

		$value = iwf_convert( $hash_array, 's' );
		$expected = 'key:value, key_2:[nest_key:nest_value, nest_key_2:nest_value_2], key_3:(stdClass), key_4:This is dummy class.';

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $hash_array, 'o' );
		$expected = (object)$hash_array;

		$this->assertEquals( $expected, $value );

		/**
		 * Boolean
		 */
		$value = iwf_convert( $bool, 'i' );
		$expected = 1;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $bool, 'f' );
		$expected = 1;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $bool, 's' );
		$expected = 'true';

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $bool, 'a' );
		$expected = array( $bool );

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $bool, 'o' );
		$expected = (object)$bool;

		$this->assertEquals( $expected, $value );

		/**
		 * Integer
		 */
		$value = iwf_convert( $int, 'f' );
		$expected = 1;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $int, 's' );
		$expected = '1';

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $int, 'b' );

		$this->assertTrue( $value );

		$value = iwf_convert( $int, 'a' );
		$expected = array( $bool );

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $int, 'o' );
		$expected = (object)$bool;

		$this->assertEquals( $expected, $value );

		/**
		 * stdClass
		 */
		$value = iwf_convert( $std_class, 'i' );
		$expected = 0;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $std_class, 'f' );
		$expected = 0;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $std_class, 's' );
		$expected = '(stdClass)';

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $std_class, 'b' );

		$this->assertTrue( $value );

		$value = iwf_convert( $std_class, 'a' );
		$expected = array(
			'member_string' => 'string',
			'member_array' => array(
				'key' => 'value',
				'key_2' => array(
					'nest_key' => 'nest_value',
					'nest_key_2' => 'nest_value_2'
				),
			),
			'member_bool' => false,
			'member_int' => 1
		);

		$this->assertEquals( $expected, $value );

		/**
		 * stdClass
		 */
		$value = iwf_convert( $dummy_class, 'i' );
		$expected = 0;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $dummy_class, 'f' );
		$expected = 0;

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $dummy_class, 's' );
		$expected = 'This is dummy class.';

		$this->assertEquals( $expected, $value );

		$value = iwf_convert( $dummy_class, 'b' );

		$this->assertTrue( $value );

		$value = iwf_convert( $dummy_class, 'a' );
		$expected = array(
			'key_1' => 'value_1'
		);

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers iwf_filter
	 */
	public function testFilter() {
		$value = iwf_filter( false );
		$expected = false;

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( 0 );
		$expected = 0;

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( '' );
		$expected = '';

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( null, 'default' );
		$expected = 'default';

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( 'test_value', array(
			'before' => 'before:',
			'after' => ':after',
		) );

		$expected = 'before:test_value:after';

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( '', array(
			'before' => 'before:',
			'after' => ':after',
			'empty_value' => false,
			'default' => 'default'
		) );

		$expected = 'default';

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( '', array(
			'before' => 'before:',
			'after' => ':after',
			'empty_value' => true,
			'default' => 'default'
		) );

		$expected = 'before::after';

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( array( 'test_value', 'test_value_2' ), array(
			'before' => 'before:',
			'after' => ':after',
		) );

		$expected = array( 'test_value', 'test_value_2' );

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( array( 'test_value', 'test_value_2' ), array(
			'before' => 'before:',
			'after' => ':after',
			'callback' => array( 'array_keys' )
		) );

		$expected = array( 0, 1 );

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( array( 'test_value', 'test_value_2' ), array(
			'before' => 'before:',
			'after' => ':after',
			'convert' => 's',
			'callback' => array( 'substr' => array( 0, 16 ), 'strtoupper' )
		) );

		$expected = 'before:TEST_VALUE, TEST:after';

		$this->assertEquals( $expected, $value );

		$value = iwf_filter( array( 'test_value', 'test_value_2', 'test_value_3' ), array(
			'before' => 'before:',
			'after' => ':after',
			'callback' => array( 'array_slice' => array( 0, 2 ), 'array_reverse' ),
			'convert' => 's',
		) );

		$expected = 'before:test_value_2, test_value:after';

		$this->assertEquals( $expected, $value );
	}
}