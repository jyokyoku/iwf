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
}