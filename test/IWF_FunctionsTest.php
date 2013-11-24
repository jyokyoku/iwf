<?php
require_once dirname( dirname( __FILE__ ) ) . '/iwf-functions.php';

class IWF_FunctionsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
	}

	protected function tearDown() {
	}

	public function testGetArray() {
		$array = array(
			'testKey' => 'testValue',
			'valueOnly',
			'deep' => array(
				'key' => 'value'
			),
		);

		$this->assertEquals( 'testValue', iwf_get_array( $array, 'testKey' ) );

		$this->assertEquals( array(
			'testKey' => 'testValue',
			'valueOnly',
			'deep' => array(
				'key' => 'value'
			),
		), $array );

		$this->assertEquals( 'testValue', iwf_get_array( $array, 'testKey' ) );

		$this->assertEquals( 'valueOnly', iwf_get_array( $array, 0 ) );

		$this->assertEquals( array(
			'key' => 'value'
		), iwf_get_array( $array, 'deep' ) );

		$this->assertEquals( 'value', iwf_get_array( $array, 'deep.key' ) );

		$this->assertEquals( array(
			'testKey' => 'testValue',
			'valueOnly'
		), iwf_get_array( $array, array( 'testKey', 0 ) ) );

		$this->assertNull( iwf_get_array( $array, 'testKey2' ) );

		$this->assertNull( iwf_get_array( $array, 'deep.key.none' ) );

		$this->assertEquals( 'default', iwf_get_array( $array, 'testKey2', 'default' ) );

		$this->assertEquals( array(
			'testKey' => 'testValue',
			'testKey2' => 'default',
			'testKey3' => null,
			1 => null,
			0 => 'valueOnly'
		), iwf_get_array( $array, array(
			'testKey',
			'testKey2' => 'default',
			'testKey3',
			1,
			0
		) ) );

		$this->assertEquals( array( 'default' => null ), iwf_get_array( $array, array( 0 => 'default' ) ) );
	}

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
}