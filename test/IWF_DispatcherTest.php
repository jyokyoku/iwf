<?php
require_once 'wp-load.php';
require_once dirname( dirname( __FILE__ ) ) . '/iwf-dispatcher.php';
require_once dirname( dirname( __FILE__ ) ) . '/iwf-view.php';

class IWF_DispatcherTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var IWF_Dispatcher
	 */
	protected $object;

	protected function setUp() {
		$this->object = IWF_Dispatcher::instance( 'default' );
		$this->object->set_action_key( 'test' );
	}

	protected function tearDown() {
		IWF_Dispatcher::destroy( $this->object );
	}

	/**
	 * @covers IWF_Dispatcher::dispatch_action
	 */
	public function testDispatchAction() {
		// Create the mock of IWF_View_Template_Text
		$view_template_text = $this->getMock( 'IWF_View_Template_Text', array( 'render' ), array(), '', false );
		$view_template_text->expects( $this->any() )->method( 'render' )->will( $this->returnValue( 'rendered_template_html' ) );

		// Create the mock of IWF_View_Template_Php
		$view_template_php = $this->getMock( 'IWF_View_Template_Php', array( 'render' ), array(), '', false );
		$view_template_php->expects( $this->any() )->method( 'render' )->will( $this->returnValue( 'rendered_template_php' ) );

		// Create the mock of IWF_View_Callback
		$view_callback = $this->getMock( 'IWF_View_Callback', array( 'render' ), array(), '', false );
		$view_callback->expects( $this->any() )->method( 'render' )->will( $this->returnValue( 'rendered_template_html' ) );

		// Returns the IWF_View_Template_Text
		$this->object->add_action( 'test_action', function () use ( $view_template_text ) {
			return $view_template_text;
		} );

		// Returns the IWF_View_Template_Php
		$this->object->add_action( 'test_action', function () use ( $view_template_php ) {
			return $view_template_php;
		} );

		// Returns the IWF_View_Callback
		$this->object->add_action( 'test_action', function () use ( $view_callback ) {
			return $view_callback;
		} );

		// Returns the string
		$this->object->add_action( 'test_action', function () {
			return 'return_string';
		} );

		// Returns the integer
		$this->object->add_action( 'test_action', function () {
			return 1;
		} );

		// Returns the array (illegal response type)
		$this->object->add_action( 'test_action', function () {
			return array( 'return_array' );
		} );

		// Add the action of another action name (not processing)
		$this->object->add_action( 'ignore_action', function () {
			return 'return_ignore_string';
		} );

		$_GET['test'] = 'test_action';

		$this->assertEquals( array(
			$view_template_text,
			$view_template_php,
			$view_callback,
			'return_string',
			1
		), $this->object->dispatch_action() );
	}

	/**
	 * @covers IWF_Dispatcher::add_action
	 */
	public function testAddAction() {
		$action_1 = function () {
		};

		$action_2 = function () {
		};

		$action_3 = function () {
		};

		$action_4 = function () {
		};

		$this->object->add_action( 'test_action', $action_1, 10 );
		$this->object->add_action( 'test_action', $action_2, 10 );
		$this->object->add_action( 'test_action', $action_3, 20 );
		$this->object->add_action( 'test_action', $action_4, 20 );

		$this->assertEquals( array(
			$action_1,
			$action_2
		), array_values( $this->object->actions['test_action'][10] ) );

		$this->assertEquals( array(
			$action_3,
			$action_4
		), array_values( $this->object->actions['test_action'][20] ) );
	}

	/**
	 * @covers IWF_Dispatcher::remove_action
	 */
	public function testRemoveAction() {
		$action_1 = function () {
		};

		$action_2 = function () {
		};

		$this->object->add_action( 'test_action', $action_1, 10 );
		$this->object->add_action( 'test_action', $action_2, 10 );
		$this->object->remove_action( 'test_action', $action_1, 10 );

		$this->assertEquals( array(
			$action_2
		), array_values( $this->object->actions['test_action'][10] ) );
	}

	/**
	 * @covers IWF_Dispatcher::set_action_key
	 */
	public function testSetActionKey() {
		$this->object->set_action_key( 'test_2' );
		$this->assertEquals( 'test_2', $this->object->action_key );
	}
}
