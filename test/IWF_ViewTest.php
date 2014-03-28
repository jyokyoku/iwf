<?php
require_once 'wp-load.php';
require_once 'vfsStream/vfsStream.php';
require_once dirname( dirname( __FILE__ ) ) . '/iwf-view.php';

class IWF_ViewTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var IWF_View
	 */
	protected $object;

	protected function setUp() {
		$this->object = IWF_View::instance();
	}

	protected function tearDown() {
		IWF_View::destroy( $this->object );
	}

	/**
	 * @covers IWF_View::set
	 */
	public function testSet() {
		$this->object->set( 'test_key_1', 'test_value_1' );

		$this->assertEquals( array( 'test_key_1' => 'test_value_1' ), $this->object->vars );

		$this->object->set( array(
			'test_key_2' => 'test_value_2',
			'test_key_3' => 'test_value_3'
		) );

		$this->assertEquals( array(
			'test_key_1' => 'test_value_1',
			'test_key_2' => 'test_value_2',
			'test_key_3' => 'test_value_3'
		), $this->object->vars );
	}

	/**
	 * @covers IWF_View::get
	 */
	public function testGet() {
		$this->object->set( array(
			'test_key_1' => 'test_value_1',
			'test_key_2' => 'test_value_2',
			'test_key_3' => 'test_value_3'
		) );

		$this->assertEquals( 'test_value_1', $this->object->get( 'test_key_1' ) );

		$this->assertEquals( 'test_value_2', $this->object->get( 'test_key_2' ) );

		$this->assertEquals( array(
			'test_key_1' => 'test_value_1',
			'test_key_2' => 'test_value_2'
		), $this->object->get( array( 'test_key_1', 'test_key_2' ) ) );
	}

	/**
	 * @covers IWF_View::set_template_dir
	 */
	public function testSetTemplateDir() {
		$this->object->set_template_dir( dirname( __FILE__ ) );

		$this->assertEquals( dirname( __FILE__ ) . '/', $this->object->template_dir );
	}

	/**
	 * @covers IWF_View::set_template_ext
	 */
	public function testSetTemplateExt() {
		$this->object->set_template_ext( 'test' );

		$this->assertEquals( 'test', $this->object->template_ext );

		$this->object->set_template_ext( '.test' );

		$this->assertEquals( 'test', $this->object->template_ext );
	}

	/**
	 * @covers IWF_View::set_template_suffix
	 */
	public function testSetTemplateSuffix() {
		$this->object->set_template_suffix( 'suffix' );

		$this->assertEquals( 'suffix', $this->object->template_suffix );
	}

	/**
	 * @covers IWF_View::set_template_prefix
	 */
	public function testSetTemplatePrefix() {
		$this->object->set_template_prefix( 'prefix' );

		$this->assertEquals( 'prefix', $this->object->template_prefix );
	}

	/**
	 * @covers IWF_View::set_bound
	 */
	public function testSetBounds() {
		$this->object->set_bound( '#' );

		$this->assertEquals( '#', $this->object->bound );
	}

	/**
	 * @covers IWF_View::template_php
	 */
	public function testTemplatePhp() {
		// Setup the vsfStream
		vfsStream::setup( 'php-templates' );
		$template_dir_path = vfsStream::url( 'php-templates' ) . '/php-template-test.php';

		// Create the virtual template file
		file_put_contents( $template_dir_path, 'This is template file. <?php echo $var_name ?> is a variable value' );

		// Create the IWF_View_Template_Php
		$template = $this->object->template_php( $template_dir_path );

		// Set the view variable
		$this->object->set( 'var_name', 'test_value' );

		$this->assertInstanceOf( 'IWF_View_Template_Php', $template );

		$this->assertEquals(
			'This is template file. test_value is a variable value',
			$template->render()
		);

		// Set the view variable with the same name
		$this->object->set( array( 'var_name' => 'test_new_value_1' ) );

		$this->assertEquals(
			'This is template file. test_new_value_1 is a variable value',
			$template->render()
		);

		$this->assertEquals(
			'This is template file. test_overwrite_value_1 is a variable value',
			$template->render( array( 'var_name' => 'test_overwrite_value_1' ) )
		);
	}

	/**
	 * @covers IWF_View::callback
	 */
	public function testCallback() {
		$this->object->set( 'var_name', 'test_value' );

		// Create the IWF_View_Callback
		$callback = $this->object->callback( function ( $view, $arg_1, $arg_2 ) {
			echo 'I am callback function. ' . $view->get( 'var_name' ) . ' is value of $var_name. Arguments 1 is ' . $arg_1 . ', arguments 2 is ' . $arg_2;
		}, array( 'arg_1' => 'test_value_1', 'arg_2' => 'test_value_2' ) );

		$this->assertInstanceOf( 'IWF_View_Callback', $callback );

		$this->assertEquals(
			'I am callback function. test_value is value of $var_name. Arguments 1 is test_value_1, arguments 2 is test_value_2',
			$callback->render()
		);

		// Set the view variable
		$this->object->set( 'var_name', 'overwrite_value' );

		$this->assertEquals(
			'I am callback function. overwrite_value is value of $var_name. Arguments 1 is test_new_value_1, arguments 2 is test_value_2',
			$callback->render( array( 'arg_1' => 'test_new_value_1' ) )
		);
	}

	/**
	 * @covers IWF_View::template_text
	 */
	public function testTemplateText() {
		// Setup the vsfStream
		vfsStream::setup( 'text-templates' );
		$template_dir_path = vfsStream::url( 'text-templates' ) . '/text-template-test.php';

		// Create the virtual template file
		file_put_contents( $template_dir_path, 'This is template file. %var_name%, #var_name#, |var_name|' );

		// Set the view variable
		$this->object->set( 'var_name', 'test_value' );

		// Create IWF_View_Template_Text
		$template = $this->object->template_text( $template_dir_path );

		$this->assertInstanceOf( 'IWF_View_Template_Text', $template );

		$this->assertEquals(
			'This is template file. test_value, #var_name#, |var_name|',
			$template->render()
		);

		// Set the view variable with the same name
		$this->object->set( array( 'var_name' => 'test_new_value_1' ) );

		$this->assertEquals(
			'This is template file. test_new_value_1, #var_name#, |var_name|',
			$template->render()
		);

		$this->assertEquals(
			'This is template file. test_new_value_2, #var_name#, |var_name|',
			$template->render( array( 'var_name' => 'test_new_value_2' ) )
		);

		$this->assertEquals(
			'This is template file. %var_name%, test_new_value_3, |var_name|',
			$template->render( array( 'var_name' => 'test_new_value_3' ), '#' )
		);

		// Set the bounds
		$this->object->set_bound( '#' );

		// Renew IWF_View_Template_Text
		$template = $this->object->template_text( $template_dir_path );

		$this->assertEquals(
			'This is template file. %var_name%, test_new_value_4, |var_name|',
			$template->render( array( 'var_name' => 'test_new_value_4' ) )
		);

		// Renew IWF_View_Template_Text with bounds
		$template = $this->object->template_text( $template_dir_path, '|' );

		$this->assertEquals(
			'This is template file. %var_name%, #var_name#, test_new_value_5',
			$template->render( array( 'var_name' => 'test_new_value_5' ) )
		);
	}

	/**
	 * @covers IWF_View::replace
	 */
	public function testReplace() {
		// Replace text
		$text = 'This is template file. %var_name%, #var_name#, |var_name|';

		// Set the view variable
		$this->object->set( 'var_name', 'test_value' );

		$this->assertEquals(
			'This is template file. test_value, #var_name#, |var_name|',
			$this->object->replace( $text )
		);

		// Set the view variable with the same name
		$this->object->set( array( 'var_name' => 'test_new_value_1' ) );

		$this->assertEquals(
			'This is template file. test_new_value_1, #var_name#, |var_name|',
			$this->object->replace( $text )
		);

		$this->assertEquals(
			'This is template file. %var_name%, test_new_value_1, |var_name|',
			$this->object->replace( $text, '#' )
		);

		// Set the bounds
		$this->object->set_bound( '#' );

		$this->assertEquals(
			'This is template file. %var_name%, test_new_value_1, |var_name|',
			$this->object->replace( $text )
		);
	}
}
