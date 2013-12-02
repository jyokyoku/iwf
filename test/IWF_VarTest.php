<?php
require_once dirname( dirname( __FILE__ ) ) . '/iwf-var.php';

/**
 * Class IWF_VarTest
 *
 * @property IWF_Var $var
 */
class IWF_VarTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->var = IWF_Var::instance();
	}

	protected function tearDown() {
		$this->var->clear( true );
		$this->var->ns( 'default' );
	}

	public function testNs() {
		$this->var->ns( 'default' );

		$this->assertTrue( $this->var->is( 'default' ) );

		$this->var->ns( 'testNamespace' );

		$this->assertTrue( $this->var->is( 'testNamespace' ) );
	}

	public function testSet() {
		$this->var->set( 'testKey', 'testValue' );

		$this->assertEquals( array( 'testKey' => 'testValue' ), $this->var->get() );

		$this->var->set( 'deepKey.deepTestKey', 'deepTestValue' );

		$this->assertEquals( array(
			'testKey' => 'testValue',
			'deepKey' => array(
				'deepTestKey' => 'deepTestValue'
			)
		), $this->var->get() );

		$this->var->ns( 'testNamespace' );

		$this->var->set( 'testKey3', 'testValue3' );

		$this->assertEquals( array( 'testKey3' => 'testValue3' ), $this->var->get() );

		$this->var->set( array(
			'testKey4' => 'testValue4',
			'testKey5' => 'testValue5'
		) );

		$this->assertEquals( array(
			'testKey3' => 'testValue3',
			'testKey4' => 'testValue4',
			'testKey5' => 'testValue5',
		), $this->var->get() );
	}

	public function testGet() {
		$this->var->set( 'testKey', 'testValue' );

		$this->assertEquals( 'testValue', $this->var->get( 'testKey' ) );

		$this->assertNull( $this->var->get( 'testKeyUndefined' ) );

		$this->assertEquals( 'default', $this->var->get( 'testKeyUndefined', 'default' ) );

		$this->var->set( 'deepKey.deepTestKey', 'deepTestValue' );

		$this->assertEquals( array( 'deepTestKey' => 'deepTestValue' ), $this->var->get( 'deepKey' ) );

		$this->assertEquals( 'deepTestValue', $this->var->get( 'deepKey.deepTestKey' ) );

		$this->var->ns( 'testNamespace' );

		$this->var->set( 'testKey2', 'testValue2' );

		$this->assertEquals( 'testValue2', $this->var->get( 'testKey2' ) );

		$this->assertNull( $this->var->get( 'testKey' ) );

		$this->var->ns( 'default' );

		$this->assertEquals( 'testValue', $this->var->get( 'testKey' ) );

		$this->assertNull( $this->var->get( 'testKey2' ) );
	}

	public function testSetAs() {
		IWF_Var::set_as( 'testKey', 'testValue' );

		$this->assertEquals( array( 'testKey' => 'testValue' ), $this->var->get() );

		IWF_Var::set_as( 'deepKey.deepTestKey', 'deepTestValue' );

		$this->assertEquals( array(
			'testKey' => 'testValue',
			'deepKey' => array(
				'deepTestKey' => 'deepTestValue'
			)
		), $this->var->get() );

		IWF_Var::set_as( 'testKey3', 'testValue3', 'testNamespace' );

		$this->assertTrue( $this->var->is( 'testNamespace' ) );

		$this->assertEquals( array( 'testKey3' => 'testValue3' ), $this->var->get() );

		IWF_Var::set_as( array(
			'testKey4' => 'testValue4',
			'testKey5' => 'testValue5'
		), 'testNamespace' );

		$this->assertEquals( array(
			'testKey3' => 'testValue3',
			'testKey4' => 'testValue4',
			'testKey5' => 'testValue5',
		), $this->var->get() );

		IWF_Var::set_as( 'testNamespace2\testKey6', 'testValue6' );

		$this->assertTrue( $this->var->is( 'testNamespace2' ) );

		$this->assertEquals( array( 'testKey6' => 'testValue6' ), $this->var->get() );
	}

	public function testGetAs() {
		$this->var->set( 'testKey', 'testValue' );

		$this->assertEquals( 'testValue', IWF_Var::get_as( 'testKey' ) );

		$this->assertNull( IWF_Var::get_as( 'testKeyUndefined' ) );

		$this->assertEquals( 'default', IWF_Var::get_as( 'testKeyUndefined', 'default' ) );

		$this->var->set( 'deepKey.deepTestKey', 'deepTestValue' );

		$this->assertEquals( array( 'deepTestKey' => 'deepTestValue' ), IWF_Var::get_as( 'deepKey' ) );

		$this->assertEquals( 'deepTestValue', IWF_Var::get_as( 'deepKey.deepTestKey' ) );

		$this->var->ns( 'testNamespace' );

		$this->var->set( 'testKey2', 'testValue2' );

		$this->assertEquals( 'testValue2', IWF_Var::get_as( 'testKey2', null, 'testNamespace' ) );

		$this->assertTrue( $this->var->is( 'testNamespace' ) );

		$this->assertNull( IWF_Var::get_as( 'testKey' ) );

		$this->assertEquals( 'testValue', IWF_Var::get_as( 'testKey', null, 'default' ) );

		$this->assertTrue( $this->var->is( 'default' ) );

		$this->assertNull( IWF_Var::get_as( 'testKey2' ) );

		$this->assertEquals( 'testValue2', IWF_Var::get_as( 'testNamespace\testKey2' ) );

		$this->assertTrue( $this->var->is( 'testNamespace' ) );

		$this->var->ns( 'testNamespace2' );

		$this->var->set( array(
			'testKey3' => 'testValue3',
			'testKey4' => 'testValue4',
			'testKey5' => 'testValue5',
		) );

		$this->assertEquals( array(
			'testKey3' => 'testValue3',
			'testKey4' => 'testValue4',
			'testKey6' => null,
		), IWF_Var::get_as( array( 'testKey3', 'testKey4', 'testKey6' ) ) );
	}
}