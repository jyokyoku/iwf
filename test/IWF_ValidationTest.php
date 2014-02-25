<?php
require_once 'wp-load.php';
require_once dirname( dirname( __FILE__ ) ) . '/iwf-validation.php';

class IWF_ValidationTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var IWF_Validation
	 */
	protected $object;

	protected function setUp() {
		$this->object = IWF_Validation::instance( array(
			'messages' => array(),
			'error_open' => '',
			'error_close' => ''
		) );
	}

	protected function tearDown() {
		IWF_Validation::destroy( $this->object );
	}

	/**
	 * @covers IWF_Validation::not_empty
	 */
	public function testNot_empty() {
		$value = null;

		$this->assertFalse( IWF_Validation::not_empty( $value ) );

		$value = '';

		$this->assertFalse( IWF_Validation::not_empty( $value ) );

		$value = false;

		$this->assertFalse( IWF_Validation::not_empty( $value ) );

		$value = 0;

		$this->assertTrue( IWF_Validation::not_empty( $value ) );
	}

	/**
	 * @covers IWF_Validation::not_empty_if
	 */
	public function testNot_empty_if() {
		$expr_value = false;
		$value = null;

		$this->assertTrue( IWF_Validation::not_empty_if( $value, $expr_value ) );

		$value = '';

		$this->assertTrue( IWF_Validation::not_empty_if( $value, $expr_value ) );

		$value = false;

		$this->assertTrue( IWF_Validation::not_empty_if( $value, $expr_value ) );

		$value = 0;

		$this->assertTrue( IWF_Validation::not_empty_if( $value, $expr_value ) );

		$expr_value = true;
		$value = null;

		$this->assertFalse( IWF_Validation::not_empty_if( $value, $expr_value ) );

		$value = '';

		$this->assertFalse( IWF_Validation::not_empty_if( $value, $expr_value ) );

		$value = false;

		$this->assertFalse( IWF_Validation::not_empty_if( $value, $expr_value ) );

		$value = 0;

		$this->assertTrue( IWF_Validation::not_empty_if( $value, $expr_value ) );
	}

	/**
	 * @covers IWF_Validation::valid_string
	 * @todo   Implement testValidString().
	 */
	public function testValidString() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers IWF_Validation::valid_email
	 */
	public function testValidEmail() {
		$value = 'test@test.com';

		$this->assertTrue( IWF_Validation::valid_email( $value ) );

		$value = 'test.test.com';

		$this->assertFalse( IWF_Validation::valid_email( $value ) );
	}

	/**
	 * @covers IWF_Validation::valid_url
	 */
	public function testValidUrl() {
		$value = 'http://www.test.com';

		$this->assertTrue( IWF_Validation::valid_url( $value ) );

		$value = 'www.test.com';

		$this->assertFalse( IWF_Validation::valid_url( $value ) );
	}

	/**
	 * @covers IWF_Validation::min_length
	 */
	public function testMinLength() {
		$value = 'abcde';

		$this->assertFalse( IWF_Validation::min_length( $value, 10 ) );

		$this->assertTrue( IWF_Validation::min_length( $value, 5 ) );
	}

	/**
	 * @covers IWF_Validation::max_length
	 */
	public function testMaxLength() {
		$value = 'abcdedefgh';

		$this->assertFalse( IWF_Validation::max_length( $value, 5 ) );

		$this->assertTrue( IWF_Validation::max_length( $value, 10 ) );
	}

	/**
	 * @covers IWF_Validation::exact_length
	 */
	public function testExactLength() {
		$value = 'abcdedefgh';

		$this->assertFalse( IWF_Validation::exact_length( $value, 5 ) );

		$this->assertTrue( IWF_Validation::exact_length( $value, 10 ) );

		$this->assertFalse( IWF_Validation::exact_length( $value, 15 ) );
	}

	/**
	 * @covers IWF_Validation::numeric_min
	 */
	public function testNumericMin() {
		$value = 10;

		$this->assertFalse( IWF_Validation::numeric_min( $value, 15 ) );

		$this->assertTrue( IWF_Validation::numeric_min( $value, 10 ) );
	}

	/**
	 * @covers IWF_Validation::numeric_max
	 */
	public function testNumericMax() {
		$value = 10;

		$this->assertFalse( IWF_Validation::numeric_max( $value, 5 ) );

		$this->assertTrue( IWF_Validation::numeric_max( $value, 10 ) );
	}

	/**
	 * @covers IWF_Validation::integer
	 */
	public function testInteger() {
		$value = 10;

		$this->assertTrue( IWF_Validation::integer( $value ) );

		$value = 10.5;

		$this->assertFalse( IWF_Validation::integer( $value ) );

		$value = 'abcde';

		$this->assertFalse( IWF_Validation::integer( $value ) );
	}

	/**
	 * @covers IWF_Validation::decimal
	 */
	public function testDecimal() {
		$value = 10;

		$this->assertTrue( IWF_Validation::decimal( $value ) );

		$value = 10.5;

		$this->assertTrue( IWF_Validation::decimal( $value ) );

		$value = 'abcde';

		$this->assertFalse( IWF_Validation::decimal( $value ) );
	}

	/**
	 * @covers IWF_Validation::match_value
	 */
	public function testMatchValue() {
		$value = 'abcde';

		$this->assertTrue( IWF_Validation::match_value( $value, 'abcde' ) );

		$this->assertFalse( IWF_Validation::match_value( $value, '12345' ) );
	}

	/**
	 * @covers IWF_Validation::match_pattern
	 */
	public function testMatchPattern() {
		$value = 'abcde';

		$this->assertTrue( IWF_Validation::match_pattern( $value, '|^[a-zA-Z]+$|' ) );

		$this->assertFalse( IWF_Validation::match_pattern( $value, '|^[0-9]+$|' ) );
	}

	/**
	 * @covers IWF_Validation::add_field
	 */
	public function testAddField() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );

		$this->assertEquals( array(
			'test_field' => array(
				'type' => 'text',
				'value' => 'default value',
				'attributes' => array( 'class' => 'test_form_class' )
			)
		), $this->object->forms );

		$this->assertEquals( array(
			'test_field' => 'Test Field'
		), $this->object->fields );

		$this->assertEquals( 'test_field', $this->object->current_field );

		$this->object->add_field( 'test_field_2', 'Test Field 2' );

		$this->assertEquals( array(
			'test_field' => array(
				'type' => 'text',
				'value' => 'default value',
				'attributes' => array( 'class' => 'test_form_class' )
			),
			'test_field_2' => array(
				'type' => null,
				'value' => null,
				'attributes' => array()
			)
		), $this->object->forms );

		$this->assertEquals( array(
			'test_field' => 'Test Field',
			'test_field_2' => 'Test Field 2'
		), $this->object->fields );

		$this->assertEquals( 'test_field_2', $this->object->current_field );
	}

	/**
	 * @covers IWF_Validation::add_rule
	 */
	public function testAddRule() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );
		$this->object->add_rule( 'not_empty' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => array(
					array( 'IWF_Validation', 'not_empty' )
				)
			)
		), $this->object->rules );

		$this->object->add_rule( 'is_string' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => array(
					array( 'IWF_Validation', 'not_empty' )
				),
				'is_string' => array(
					'is_string'
				)
			)
		), $this->object->rules );

		$closure = function () {
		};

		$this->object->add_rule( $closure, 'arg_1', 'arg_2' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => array(
					array( 'IWF_Validation', 'not_empty' )
				),
				'is_string' => array(
					'is_string'
				),
				'Closure::__invoke' => array(
					$closure,
					'arg_1',
					'arg_2'
				)
			)
		), $this->object->rules );

		$this->object->add_field( 'test_field_2', 'Test Field 2' );
		$this->object->add_rule( 'not_empty' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => array(
					array( 'IWF_Validation', 'not_empty' )
				),
				'is_string' => array(
					'is_string'
				),
				'Closure::__invoke' => array(
					$closure,
					'arg_1',
					'arg_2'
				)
			),
			'test_field_2' => array(
				'IWF_Validation::not_empty' => array(
					array( 'IWF_Validation', 'not_empty' )
				)
			),
		), $this->object->rules );

		$this->object->add_rule( 'not_empty' );
		$this->object->add_rule( 'not_empty' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => array(
					array( 'IWF_Validation', 'not_empty' )
				),
				'is_string' => array(
					'is_string'
				),
				'Closure::__invoke' => array(
					$closure,
					'arg_1',
					'arg_2'
				)
			),
			'test_field_2' => array(
				'IWF_Validation::not_empty' => array(
					array( 'IWF_Validation', 'not_empty' )
				),
				'IWF_Validation::not_empty(2)' => array(
					array( 'IWF_Validation', 'not_empty' )
				),
				'IWF_Validation::not_empty(3)' => array(
					array( 'IWF_Validation', 'not_empty' )
				)
			),
		), $this->object->rules );
	}

	/**
	 * @covers IWF_Validation::set_message
	 */
	public function testSetMessage() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );
		$this->object->add_rule( 'not_empty' );
		$this->object->set_message( 'This value is empty.' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => 'This value is empty.'
			)
		), $this->object->messages );

		$this->object->set_message( 'This value is empty again.' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => 'This value is empty again.'
			)
		), $this->object->messages );

		$this->object->add_rule( 'not_empty' );
		$this->object->set_message( 'This value is empty.' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => 'This value is empty again.',
				'IWF_Validation::not_empty(2)' => 'This value is empty.'
			)
		), $this->object->messages );

		$this->object->add_field( 'test_field_2', 'Test Field 2' );
		$this->object->add_rule( 'is_array' );
		$this->object->set_message( 'This value must be an array.' );

		$this->assertEquals( array(
			'test_field' => array(
				'IWF_Validation::not_empty' => 'This value is empty again.',
				'IWF_Validation::not_empty(2)' => 'This value is empty.'
			),
			'test_field_2' => array(
				'is_array' => 'This value must be an array.'
			)
		), $this->object->messages );
	}

	/**
	 * @covers IWF_Validation::form_field
	 */
	public function testFormField() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );

		$this->assertEquals(
			'<input class="test_form_class" name="test_field" value="default value" type="text" id="_test_field" />',
			$this->object->form_field( 'test_field' )
		);

		$this->object->set_data( array( 'test_field' => 'posted value' ) );

		$this->assertEquals(
			'<input class="test_form_class" name="test_field" value="posted value" type="text" id="_test_field" />',
			$this->object->form_field( 'test_field' )
		);

		$this->object->add_field( 'test_field_2', 'Test Field 2', 'checkbox', 1, array( 'label' => 'Test checkbox' ) );

		$this->assertEquals(
			'<input type="hidden" value="" name="test_field_2" id="_test_field_2_hidden" /><label for="_test_field_2"><input name="test_field_2" value="1" type="checkbox" id="_test_field_2" />&nbsp;Test checkbox</label>',
			$this->object->form_field( 'test_field_2' )
		);

		$this->object->set_data( array( 'test_field_2' => '1' ) );

		$this->assertEquals(
			'<input type="hidden" value="" name="test_field_2" id="_test_field_2_hidden" /><label for="_test_field_2"><input checked="checked" name="test_field_2" value="1" type="checkbox" id="_test_field_2" />&nbsp;Test checkbox</label>',
			$this->object->form_field( 'test_field_2' )
		);

		$this->object->add_field( 'test_field_3', 'Test Field 3', 'checkboxes', array( 1, 2, 3 ), array( 'separator' => "\n" ) );

		$this->assertEquals(
			'<input type="hidden" value="" name="test_field_3[0]" id="_test_field_3_0_hidden" /><label for="_test_field_3_0"><input id="_test_field_3_0" name="test_field_3[0]" value="1" type="checkbox" />&nbsp;1</label>' . "\n"
			. '<input type="hidden" value="" name="test_field_3[1]" id="_test_field_3_1_hidden" /><label for="_test_field_3_1"><input id="_test_field_3_1" name="test_field_3[1]" value="2" type="checkbox" />&nbsp;2</label>' . "\n"
			. '<input type="hidden" value="" name="test_field_3[2]" id="_test_field_3_2_hidden" /><label for="_test_field_3_2"><input id="_test_field_3_2" name="test_field_3[2]" value="3" type="checkbox" />&nbsp;3</label>',
			$this->object->form_field( 'test_field_3' )
		);

		$this->object->add_field( 'test_field_3', 'Test Field 3', 'checkboxes', array( 'label for 1' => 1, 'label for 2' => 2, 'label for 3' => 3 ), array( 'separator' => "\n" ) );

		$this->assertEquals(
			'<input type="hidden" value="" name="test_field_3[0]" id="_test_field_3_0_hidden" /><label for="_test_field_3_0"><input id="_test_field_3_0" name="test_field_3[0]" value="1" type="checkbox" />&nbsp;label for 1</label>' . "\n"
			. '<input type="hidden" value="" name="test_field_3[1]" id="_test_field_3_1_hidden" /><label for="_test_field_3_1"><input id="_test_field_3_1" name="test_field_3[1]" value="2" type="checkbox" />&nbsp;label for 2</label>' . "\n"
			. '<input type="hidden" value="" name="test_field_3[2]" id="_test_field_3_2_hidden" /><label for="_test_field_3_2"><input id="_test_field_3_2" name="test_field_3[2]" value="3" type="checkbox" />&nbsp;label for 3</label>',
			$this->object->form_field( 'test_field_3' )
		);

		$this->object->add_field( 'test_field_4', 'Test Field 4', 'checkboxes', array( 'label for 1' => 1, 'label for 2' => 2, 'label for 3' => 3 ), array( 'separator' => "\n" ) );

		$this->assertEquals(
			'<input type="hidden" value="" name="test_field_3[0]" id="_test_field_3_0_hidden" /><label for="_test_field_3_0"><input id="_test_field_3_0" name="test_field_3[0]" value="1" type="checkbox" />&nbsp;label for 1</label>' . "\n"
			. '<input type="hidden" value="" name="test_field_3[1]" id="_test_field_3_1_hidden" /><label for="_test_field_3_1"><input id="_test_field_3_1" name="test_field_3[1]" value="2" type="checkbox" />&nbsp;label for 2</label>' . "\n"
			. '<input type="hidden" value="" name="test_field_3[2]" id="_test_field_3_2_hidden" /><label for="_test_field_3_2"><input id="_test_field_3_2" name="test_field_3[2]" value="3" type="checkbox" />&nbsp;label for 3</label>',
			$this->object->form_field( 'test_field_3' )
		);

		$this->object->set_data( array( 'test_field_3' => array( '1', '3' ) ) );

		$this->assertEquals(
			'<input type="hidden" value="" name="test_field_3[0]" id="_test_field_3_0_hidden" /><label for="_test_field_3_0"><input checked="checked" id="_test_field_3_0" name="test_field_3[0]" value="1" type="checkbox" />&nbsp;label for 1</label>' . "\n"
			. '<input type="hidden" value="" name="test_field_3[1]" id="_test_field_3_1_hidden" /><label for="_test_field_3_1"><input id="_test_field_3_1" name="test_field_3[1]" value="2" type="checkbox" />&nbsp;label for 2</label>' . "\n"
			. '<input type="hidden" value="" name="test_field_3[2]" id="_test_field_3_2_hidden" /><label for="_test_field_3_2"><input checked="checked" id="_test_field_3_2" name="test_field_3[2]" value="3" type="checkbox" />&nbsp;label for 3</label>',
			$this->object->form_field( 'test_field_3' )
		);

		$this->object->add_field( 'test_field_4', 'Test Field 4', 'radio', array( 1, 2, 3 ), array( 'separator' => "\n" ) );

		$this->assertEquals(
			'<label for="_test_field_4_0"><input type="radio" id="_test_field_4_0" name="test_field_4" value="1" />&nbsp;1</label>' . "\n"
			. '<label for="_test_field_4_1"><input type="radio" id="_test_field_4_1" name="test_field_4" value="2" />&nbsp;2</label>' . "\n"
			. '<label for="_test_field_4_2"><input type="radio" id="_test_field_4_2" name="test_field_4" value="3" />&nbsp;3</label>',
			$this->object->form_field( 'test_field_4' )
		);

		$this->object->set_data( array( 'test_field_4' => 2 ) );

		$this->assertEquals(
			'<label for="_test_field_4_0"><input type="radio" id="_test_field_4_0" name="test_field_4" value="1" />&nbsp;1</label>' . "\n"
			. '<label for="_test_field_4_1"><input checked="checked" type="radio" id="_test_field_4_1" name="test_field_4" value="2" />&nbsp;2</label>' . "\n"
			. '<label for="_test_field_4_2"><input type="radio" id="_test_field_4_2" name="test_field_4" value="3" />&nbsp;3</label>',
			$this->object->form_field( 'test_field_4' )
		);
	}

	/**
	 * @covers IWF_Validation::set_validated
	 */
	public function testSetValidated() {
		$this->object->set_validated( 'test_field', 'Valid value' );
		$this->object->set_validated( 'test_field_2', 'Valid value 2' );

		$this->assertEquals( 'Valid value', $this->object->validated['test_field'] );

		$this->assertEquals( 'Valid value 2', $this->object->validated['test_field_2'] );

		$this->object->set_validated( array(
			'test_field_3' => 'Valid value 3',
			'test_field_4' => 'Valid value 4',
		) );

		$this->assertEquals( 'Valid value 3', $this->object->validated['test_field_3'] );

		$this->assertEquals( 'Valid value 4', $this->object->validated['test_field_4'] );
	}

	/**
	 * @covers  IWF_Validation::validated
	 */
	public function testValidated() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );
		$this->object->add_field( 'test_field_2', 'Test Field 2' );

		$this->object->set_validated( 'test_field', 'Valid value' );

		$this->assertEquals( 'Valid value', $this->object->validated( 'test_field' ) );

		$this->object->set_validated( 'test_field', 'Valid value 2' );

		$this->assertEquals( 'Valid value 2', $this->object->validated( 'test_field' ) );

		$this->object->set_validated( 'test_field_2', 'Valid value 3' );

		$this->assertEquals( 'Valid value 2', $this->object->validated( 'test_field' ) );
	}

	/**
	 * @covers IWF_Validation::validated_hidden_fields
	 */
	public function testValidatedHiddenFields() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );
		$this->object->add_field( 'test_field_2', 'Test Field 2' );
		$this->object->add_field( 'test_field_3', 'Test Field 3' );

		$this->object->set_validated( 'test_field', 'Valid value' );
		$this->object->set_validated( 'test_field_2', 'Valid value 2' );
		$this->object->set_validated( 'test_field_3', array(
			'valid_value_key_1' => 'Valid array value 1',
			'valid_value_key_2' => 'Valid array value 2'
		) );

		$this->assertEquals(
			'<input name="test_field" value="Valid value" type="hidden" id="_test_field" />' . "\n"
			. '<input name="test_field_2" value="Valid value 2" type="hidden" id="_test_field_2" />' . "\n"
			. '<input name="test_field_3[valid_value_key_1]" value="Valid array value 1" type="hidden" id="_test_field_3_valid_value_key_1" />' . "\n"
			. '<input name="test_field_3[valid_value_key_2]" value="Valid array value 2" type="hidden" id="_test_field_3_valid_value_key_2" />',
			$this->object->validated_hidden_fields()
		);
	}

	/**
	 * @covers IWF_Validation::set_error
	 */
	public function testSetError() {
		$this->object->set_error( 'test_field', 'Error message' );
		$this->object->set_error( 'test_field_2', 'Error message 2' );

		$this->assertEquals( 'Error message', $this->object->errors['test_field'] );

		$this->assertEquals( 'Error message 2', $this->object->errors['test_field_2'] );

		$this->object->set_error( array(
			'test_field_3' => 'Error message 3',
			'test_field_4' => 'Error message 4',
		) );

		$this->assertEquals( 'Error message 3', $this->object->errors['test_field_3'] );

		$this->assertEquals( 'Error message 4', $this->object->errors['test_field_4'] );
	}

	/**
	 * @covers IWF_Validation::error_message
	 */
	public function testErrorMessage() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );
		$this->object->add_field( 'test_field_2', 'Test Field 2' );

		$this->object->set_error( 'test_field', 'Error message' );
		$this->object->set_error( 'test_field_2', 'Error message 2' );

		$this->assertEquals(
			'Error message',
			$this->object->error_message( 'test_field' )
		);

		$this->assertEquals(
			'Error message 2',
			$this->object->error_message( 'test_field_2' )
		);

		$this->assertEquals(
			array( 'Error message', 'Error message 2' ),
			$this->object->error_message( 'test_field', 'test_field_2' )
		);
	}

	/**
	 * @covers IWF_Validation::error
	 */
	public function testError() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );
		$this->object->add_field( 'test_field_2', 'Test Field 2' );

		$this->object->set_error( 'test_field', 'Error message' );
		$this->object->set_error( 'test_field_2', 'Error message 2' );

		$this->assertEquals(
			'<span>Error message</span>',
			$this->object->error( 'test_field', '<span>', '</span>' )
		);

		$this->assertEquals(
			'<p>Error message 2</p>',
			$this->object->error( 'test_field_2', '<p>', '</p>' )
		);
	}

	/**
	 * @covers IWF_Validation::set_data
	 */
	public function testSetData() {
		$data = array(
			'test_key_1' => 'test_value_1',
			'test_key_2' => 'test_value_2',
			'test_key_3' => 'test_value_3',
		);

		$this->object->set_data( $data );

		$this->assertEquals( $data, $this->object->data );
	}

	/**
	 * @covers IWF_Validation::get_data
	 */
	public function testGetData() {
		$data = array(
			'test_key_1' => 'test_value_1',
			'test_key_2' => 'test_value_2',
			'test_key_3' => 'test_value_3',
		);

		$this->object->set_data( $data );

		$this->assertEquals( $data, $this->object->get_data() );
	}

	/**
	 * @covers IWF_Validation::set_default_message
	 */
	public function testSetDefaultMessage() {
		$this->object->set_default_message( 'is_scalar', 'This value must be a scalar.' );

		$this->assertEquals( array( 'is_scalar' => 'This value must be a scalar.' ), $this->object->default_messages );

		$this->object->set_default_message( array(
			'is_string' => 'This value must be a string.',
			'is_array' => 'This value must be an array.',
			'is_bool' => 'This value must be a boolean.'
		) );

		$this->assertEquals( array(
			'is_scalar' => 'This value must be a scalar.',
			'is_string' => 'This value must be a string.',
			'is_array' => 'This value must be an array.',
			'is_bool' => 'This value must be a boolean.'
		), $this->object->default_messages );
	}

	/**
	 * @covers IWF_Validation::get_default_message
	 */
	public function testGetDefaultMessage() {
		$this->object->set_default_message( 'is_scalar', 'This value must be a scalar.' );

		$this->assertEquals( 'This value must be a scalar.', $this->object->get_default_message( 'is_scalar' ) );

		$this->assertEquals( 'This value must be a scalar.', $this->object->get_default_message( 'is_scalar(2)' ) );

		$this->assertEquals( array( 'is_scalar' => 'This value must be a scalar.' ), $this->object->get_default_message() );

		$this->object->set_default_message( array(
			'is_string' => 'This value must be a string.',
			'is_array' => 'This value must be an array.',
			'is_bool' => 'This value must be a boolean.'
		) );

		$this->assertEquals( 'This value must be a string.', $this->object->get_default_message( 'is_string' ) );

		$this->assertEquals( array(
			'is_scalar' => 'This value must be a scalar.',
			'is_string' => 'This value must be a string.',
			'is_array' => 'This value must be an array.',
			'is_bool' => 'This value must be a boolean.'
		), $this->object->get_default_message() );
	}

	/**
	 * @covers IWF_Validation::validate_field
	 */
	public function testValidateField() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );
		$this->object->add_rule( 'not_empty' )->set_message( ':label is required' );
		$this->object->add_rule( 'is_string' )->set_message( ':label is must be a string' );
		$this->object->add_rule( 'in_array', array( 'hoge', 'fuga' ) )->set_message( ':label must match the next strings [ :param:1 ]' );

		$this->object->add_field( 'test_field_2', 'Test Field 2' );
		$this->object->add_rule( 'not_empty' )->set_message( '%label% is required' );
		$this->object->add_rule( 'is_numeric' )->set_message( '%label% is must be a number' );
		$this->object->add_rule( 'match_pattern', '|^[1-3]{3}[4-6]{3}$|' )->set_message( '%label% must match the next pattern [ %param:1% ]' );

		$this->object->add_field( 'test_field_3', 'Test Field 3' );
		$this->object->add_rule( 'match_value', ':test_field' )->set_message( ':label must same the Test Field value.' );

		$this->object->add_field( 'test_field_4', 'Test Field 4' );
		$this->object->add_rule( 'match_value', '%test_field_2%' )->set_message( '%label% must same the Test Field 2 value.' );

		$result = $this->object->validate_field( 'test_field' );
		$expected = 'Test Field is required';

		$this->assertEquals( $expected, $result );

		$this->object->set_data( array(
			'test_field' => false,
			'test_field_2' => false
		) );

		$result = $this->object->validate_field( 'test_field' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field' );
		$expected = 'Test Field is required';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_2' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field_2' );
		$expected = 'Test Field 2 is required';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_3' );

		$this->assertEmpty( $result );

		$this->object->set_data( array(
			'test_field' => 10,
			'test_field_2' => 'abcdef'
		) );

		$result = $this->object->validate_field( 'test_field' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field' );
		$expected = 'Test Field is must be a string';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_2' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field_2' );
		$expected = 'Test Field 2 is must be a number';

		$this->assertEquals( $expected, $result );

		$this->object->set_data( array(
			'test_field' => 'who',
			'test_field_2' => '456789'
		) );

		$result = $this->object->validate_field( 'test_field' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field' );
		$expected = 'Test Field must match the next strings [ hoge, fuga ]';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_2' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field_2' );
		$expected = 'Test Field 2 must match the next pattern [ |^[1-3]{3}[4-6]{3}$| ]';

		$this->assertEquals( $expected, $result );

		$this->object->set_data( array(
			'test_field' => 'what',
			'test_field_2' => '123456',
			'test_field_3' => 'foo',
			'test_field_4' => 'bar',
		) );

		$result = $this->object->validate_field( 'test_field' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field' );
		$expected = 'Test Field must match the next strings [ hoge, fuga ]';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_2' );
		$expected = '123456';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_3' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field_3' );
		$expected = 'Test Field 3 must same the Test Field value.';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_4' );
		$expected = 'IWF_Validation_Error';

		$this->assertInstanceOf( $expected, $result );

		$result = (string)$this->object->validate_field( 'test_field_4' );
		$expected = 'Test Field 4 must same the Test Field 2 value.';

		$this->assertEquals( $expected, $result );

		$this->object->set_data( array(
			'test_field' => 'hoge',
			'test_field_2' => '123456',
			'test_field_3' => 'hoge',
			'test_field_4' => '123456',
		) );

		$result = $this->object->validate_field( 'test_field' );
		$expected = 'hoge';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_2' );
		$expected = '123456';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_3' );
		$expected = 'hoge';

		$this->assertEquals( $expected, $result );

		$result = $this->object->validate_field( 'test_field_4' );
		$expected = '123456';

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @covers IWF_Validation::run
	 * @covers IWF_Validation::is_valid
	 */
	public function testRun() {
		$this->object->add_field( 'test_field', 'Test Field', 'text', 'default value', array( 'class' => 'test_form_class' ) );
		$this->object->add_rule( 'not_empty' )->set_message( ':label is required' );
		$this->object->add_rule( 'is_string' )->set_message( ':label is must be a string' );
		$this->object->add_rule( 'in_array', array( 'hoge', 'fuga' ) )->set_message( ':label must match the next strings [ :param:1 ]' );

		$this->object->add_field( 'test_field_2', 'Test Field 2' );
		$this->object->add_rule( 'not_empty' )->set_message( '%label% is required' );
		$this->object->add_rule( 'is_numeric' )->set_message( '%label% is must be a number' );
		$this->object->add_rule( 'match_pattern', '|^[1-3]{3}[4-6]{3}$|' )->set_message( '%label% must match the next pattern [ %param:1% ]' );

		$this->object->add_field( 'test_field_3', 'Test Field 3' );
		$this->object->add_rule( 'match_value', ':test_field' )->set_message( ':label must same the Test Field value.' );

		$this->object->add_field( 'test_field_4', 'Test Field 4' );
		$this->object->add_rule( 'match_value', '%test_field_2%' )->set_message( '%label% must same the Test Field 2 value.' );

		// Process the validation
		$this->object->run();

		$this->assertFalse( $this->object->is_valid() );

		$this->assertFalse( $this->object->validated( 'test_field' ) );

		$this->assertFalse( $this->object->validated( 'test_field_2' ) );

		$this->assertEquals( array(
			'test_field' => 'Test Field is required',
			'test_field_2' => 'Test Field 2 is required'
		), $this->object->error_message() );

		// Set the dummy data for validation
		$this->object->set_data( array(
			'test_field' => false,
			'test_field_2' => false
		) );

		// Process the validation
		$this->object->run();

		$this->assertFalse( $this->object->is_valid() );

		$this->assertFalse( $this->object->validated( 'test_field' ) );

		$this->assertFalse( $this->object->validated( 'test_field_2' ) );

		$this->assertEquals( array(
			'test_field' => 'Test Field is required',
			'test_field_2' => 'Test Field 2 is required'
		), $this->object->error_message() );

		// Set the dummy data for validation
		$this->object->set_data( array(
			'test_field' => 10,
			'test_field_2' => 'abcdef'
		) );

		// Process the validation
		$this->object->run();

		$this->assertFalse( $this->object->is_valid() );

		$this->assertFalse( $this->object->validated( 'test_field' ) );

		$this->assertFalse( $this->object->validated( 'test_field_2' ) );

		$this->assertEquals( array(
			'test_field' => 'Test Field is must be a string',
			'test_field_2' => 'Test Field 2 is must be a number'
		), $this->object->error_message() );

		// Set the dummy data for validation
		$this->object->set_data( array(
			'test_field' => 'who',
			'test_field_2' => '456789'
		) );

		// Process the validation
		$this->object->run();

		$this->assertFalse( $this->object->is_valid() );

		$this->assertFalse( $this->object->validated( 'test_field' ) );

		$this->assertFalse( $this->object->validated( 'test_field_2' ) );

		$this->assertEquals( array(
			'test_field' => 'Test Field must match the next strings [ hoge, fuga ]',
			'test_field_2' => 'Test Field 2 must match the next pattern [ |^[1-3]{3}[4-6]{3}$| ]'
		), $this->object->error_message() );

		// Set the dummy data for validation
		$this->object->set_data( array(
			'test_field' => 'what',
			'test_field_2' => '123456',
			'test_field_3' => 'foo',
			'test_field_4' => 'bar',
		) );

		// Process the validation
		$this->object->run();

		$this->assertFalse( $this->object->is_valid() );

		$this->assertFalse( $this->object->validated( 'test_field' ) );

		$this->assertEquals( '123456', $this->object->validated( 'test_field_2' ) );

		$this->assertFalse( $this->object->validated( 'test_field_3' ) );

		$this->assertFalse( $this->object->validated( 'test_field_4' ) );

		$this->assertEquals( array(
			'test_field' => 'Test Field must match the next strings [ hoge, fuga ]',
			'test_field_3' => 'Test Field 3 must same the Test Field value.',
			'test_field_4' => 'Test Field 4 must same the Test Field 2 value.',
		), $this->object->error_message() );

		// Set the dummy data for validation
		$this->object->set_data( array(
			'test_field' => 'hoge',
			'test_field_2' => '123456',
			'test_field_3' => 'hoge',
			'test_field_4' => '123456',
		) );

		// Process the validation
		$this->object->run();

		$this->assertTrue( $this->object->is_valid() );

		$this->assertEquals( 'hoge', $this->object->validated( 'test_field' ) );

		$this->assertEquals( '123456', $this->object->validated( 'test_field_2' ) );

		$this->assertEquals( 'hoge', $this->object->validated( 'test_field_3' ) );

		$this->assertEquals( '123456', $this->object->validated( 'test_field_4' ) );
	}
}
