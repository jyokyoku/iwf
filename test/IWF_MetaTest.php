<?php
require_once 'wp-load.php';
require_once 'wp-admin/includes/user.php';
require_once dirname( dirname( __FILE__ ) ) . '/iwf-meta.php';

class IWF_MetaTest extends PHPUnit_Framework_TestCase {
	protected $backupGlobalsBlacklist = array( 'wpdb' );

	protected static $post_id;

	protected static $user_id;

	protected static $comment_id;

	public static function setUpBeforeClass() {
		self::$post_id = wp_insert_post( array(
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_content' => 'IWF_Meta test post content.',
			'post_title' => 'IWF_Meta Test Post'
		) );

		self::$user_id = wp_insert_user( array(
			'user_login' => 'iwf_meta_test_user',
			'user_pass' => '12345678',
			'user_email' => 'iwf_meta_test_user@iwf_meta_test_user.com',
			'user_nickname' => 'IWF_Meta Test User'
		) );

		self::$comment_id = wp_insert_comment( array(
			'comment_post_ID' => 0,
			'comment_author' => 'IWF_Meta Test User',
			'comment_author_email' => 'test@test.com',
			'comment_author_url' => 'http://test.com',
			'comment_content' => 'IWF_Meta test comment content.',
			'user_id' => 0
		) );
	}

	protected function setUp() {
	}

	protected function tearDown() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id = " . self::$post_id );
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE user_id = " . self::$user_id );
		$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id = " . self::$comment_id );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'iwf_meta_test%'" );
	}

	public static function tearDownAfterClass() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID = " . self::$post_id );
		$wpdb->query( "DELETE FROM {$wpdb->users} WHERE ID = " . self::$user_id );
		$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_ID = " . self::$comment_id );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->posts}" );
		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );
		$wpdb->query( "OPTIMIZE TABLE {$wpdb->users}" );
		$wpdb->query( "OPTIMIZE TABLE {$wpdb->usermeta}" );
		$wpdb->query( "OPTIMIZE TABLE {$wpdb->comments}" );
		$wpdb->query( "OPTIMIZE TABLE {$wpdb->commentmeta}" );
	}

	/**
	 * @covers IWF_Meta::post
	 */
	public function testPost() {
		update_post_meta( self::$post_id, 'test_post_meta_key', 'test_post_meta_value' );
		update_post_meta( self::$post_id, 'test_post_meta_key_2', array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		) );

		$value = IWF_Meta::post( self::$post_id, 'test_post_meta_key' );
		$expected = 'test_post_meta_value';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post( self::$post_id, 'test_post_meta_key_not_exists', 'default_value' );
		$expected = 'default_value';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post( self::$post_id, 'test_post_meta_key', array(
			'callback' => 'strtoupper',
			'before' => 'before:',
			'after' => ':after'
		) );

		$expected = 'before:TEST_POST_META_VALUE:after';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post( self::$post_id, 'test_post_meta_key', array(
			'callback' => array( 'substr' => array( 0, 4 ), 'ucfirst' ),
		) );

		$expected = 'Test';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post( self::$post_id, 'test_post_meta_key_2' );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post( self::$post_id, 'test_post_meta_key_2.test_nested_key' );
		$expected = 'test_nested_value';

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers IWF_Meta::post_iteration
	 */
	public function testPostIteration() {
		update_post_meta( self::$post_id, 'test_post_meta_key_1', 'test_post_meta_value_1' );
		update_post_meta( self::$post_id, 'test_post_meta_key_2', 'test_post_meta_value_2' );
		update_post_meta( self::$post_id, 'test_post_meta_key_3', 'test_post_meta_value_3' );
		update_post_meta( self::$post_id, 'test_post_1_meta_key', 'test_post_meta_value_4' );
		update_post_meta( self::$post_id, 'test_post_2_meta_key', 'test_post_meta_value_5' );
		update_post_meta( self::$post_id, 'test_post_3_meta_key', 'test_post_meta_value_6' );
		update_post_meta( self::$post_id, 'test_post_meta_key_array', array(
			'test_nested_key_1' => 'test_nested_value_1',
			'test_nested_key_2' => 'test_nested_value_2',
			'test_nested_key_3' => 'test_nested_value_3',
		) );

		$value = IWF_Meta::post_iteration( self::$post_id, 'test_post_meta_key_', 1, 3 );
		$expected = array(
			1 => 'test_post_meta_value_1',
			2 => 'test_post_meta_value_2',
			3 => 'test_post_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post_iteration( self::$post_id, 'test_post_meta_key_:index', 1, 3 );
		$expected = array(
			1 => 'test_post_meta_value_1',
			2 => 'test_post_meta_value_2',
			3 => 'test_post_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post_iteration( self::$post_id, 'test_post_meta_key_%index%', 1, 3 );
		$expected = array(
			1 => 'test_post_meta_value_1',
			2 => 'test_post_meta_value_2',
			3 => 'test_post_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post_iteration( self::$post_id, 'test_post_:index_meta_key', 1, 3 );
		$expected = array(
			1 => 'test_post_meta_value_4',
			2 => 'test_post_meta_value_5',
			3 => 'test_post_meta_value_6',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::post_iteration( self::$post_id, 'test_post_meta_key_array.test_nested_key_:index', 1, 3 );
		$expected = array(
			1 => 'test_nested_value_1',
			2 => 'test_nested_value_2',
			3 => 'test_nested_value_3',
		);

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers IWF_Meta::update_post
	 */
	public function testUpdatePost() {
		$result = IWF_Meta::update_post( null, 'test_post_meta_key', 'test_post_meta_value' );

		$this->assertFalse( $result );

		$result = IWF_Meta::update_post( self::$post_id, 'test_post_meta_key', 'test_post_meta_value' );

		$this->assertNotEmpty( $result );

		$value = get_post_meta( self::$post_id, 'test_post_meta_key', true );
		$expected = 'test_post_meta_value';

		$this->assertEquals( $expected, $value );

		IWF_Meta::update_post( self::$post_id, 'test_post_meta_key_2', array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		) );

		$value = get_post_meta( self::$post_id, 'test_post_meta_key_2', true );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$result = IWF_Meta::update_post( self::$post_id, array(
			'test_nested_value_5',
			'test_nested_value_6',
			'test_nested_value_7',
		) );

		$this->assertFalse( $result );

		$result = IWF_Meta::update_post( self::$post_id, array(
			'test_post_meta_key_3' => 'test_post_meta_value_3',
			'test_post_meta_key_4' => array(
				'test_nested_key' => 'test_nested_value',
				'test_nested_key_2' => 'test_nested_value_2',
			),
			'test_post_meta_key_5' => array(
				'test_nested_key_3' => 'test_nested_value_3',
				'test_nested_key_4' => 'test_nested_value_4',
			),
		) );

		$this->assertNotEmpty( $result );

		$value = get_post_meta( self::$post_id, 'test_post_meta_key_3', true );
		$expected = 'test_post_meta_value_3';

		$this->assertEquals( $expected, $value );

		$value = get_post_meta( self::$post_id, 'test_post_meta_key_4', true );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$value = get_post_meta( self::$post_id, 'test_post_meta_key_5', true );
		$expected = array(
			'test_nested_key_3' => 'test_nested_value_3',
			'test_nested_key_4' => 'test_nested_value_4',
		);

		$this->assertEquals( $expected, $value );

		$result = IWF_Meta::update_post( self::$post_id, 'test_post_meta_key_6.', 'test_post_meta_value_5' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers IWF_Meta::user
	 */
	public function testUser() {
		update_user_meta( self::$user_id, 'test_user_meta_key', 'test_user_meta_value' );
		update_user_meta( self::$user_id, 'test_user_meta_key_2', array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		) );

		$value = IWF_Meta::user( self::$user_id, 'test_user_meta_key' );
		$expected = 'test_user_meta_value';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user( self::$user_id, 'test_user_meta_key_not_exists', 'default_value' );
		$expected = 'default_value';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user( self::$user_id, 'test_user_meta_key', array(
			'callback' => 'strtoupper',
			'before' => 'before:',
			'after' => ':after'
		) );

		$expected = 'before:TEST_USER_META_VALUE:after';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user( self::$user_id, 'test_user_meta_key', array(
			'callback' => array( 'substr' => array( 0, 4 ), 'ucfirst' ),
		) );

		$expected = 'Test';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user( self::$user_id, 'test_user_meta_key_2' );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user( self::$user_id, 'test_user_meta_key_2.test_nested_key' );
		$expected = 'test_nested_value';

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers IWF_Meta::user_iteration
	 */
	public function testUserIteration() {
		update_user_meta( self::$user_id, 'test_user_meta_key_1', 'test_user_meta_value_1' );
		update_user_meta( self::$user_id, 'test_user_meta_key_2', 'test_user_meta_value_2' );
		update_user_meta( self::$user_id, 'test_user_meta_key_3', 'test_user_meta_value_3' );
		update_user_meta( self::$user_id, 'test_user_1_meta_key', 'test_user_meta_value_4' );
		update_user_meta( self::$user_id, 'test_user_2_meta_key', 'test_user_meta_value_5' );
		update_user_meta( self::$user_id, 'test_user_3_meta_key', 'test_user_meta_value_6' );
		update_user_meta( self::$user_id, 'test_user_meta_key_array', array(
			'test_nested_key_1' => 'test_nested_value_1',
			'test_nested_key_2' => 'test_nested_value_2',
			'test_nested_key_3' => 'test_nested_value_3',
		) );

		$value = IWF_Meta::user_iteration( self::$user_id, 'test_user_meta_key_', 1, 3 );
		$expected = array(
			1 => 'test_user_meta_value_1',
			2 => 'test_user_meta_value_2',
			3 => 'test_user_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user_iteration( self::$user_id, 'test_user_meta_key_:index', 1, 3 );
		$expected = array(
			1 => 'test_user_meta_value_1',
			2 => 'test_user_meta_value_2',
			3 => 'test_user_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user_iteration( self::$user_id, 'test_user_meta_key_%index%', 1, 3 );
		$expected = array(
			1 => 'test_user_meta_value_1',
			2 => 'test_user_meta_value_2',
			3 => 'test_user_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user_iteration( self::$user_id, 'test_user_:index_meta_key', 1, 3 );
		$expected = array(
			1 => 'test_user_meta_value_4',
			2 => 'test_user_meta_value_5',
			3 => 'test_user_meta_value_6',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::user_iteration( self::$user_id, 'test_user_meta_key_array.test_nested_key_:index', 1, 3 );
		$expected = array(
			1 => 'test_nested_value_1',
			2 => 'test_nested_value_2',
			3 => 'test_nested_value_3',
		);

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers IWF_Meta::update_user
	 */
	public function testUpdateUser() {
		$result = IWF_Meta::update_user( null, 'test_user_meta_key', 'test_user_meta_value' );

		$this->assertFalse( $result );

		$result = IWF_Meta::update_user( self::$user_id, 'test_user_meta_key', 'test_user_meta_value' );

		$this->assertNotEmpty( $result );

		$value = get_user_meta( self::$user_id, 'test_user_meta_key', true );
		$expected = 'test_user_meta_value';

		$this->assertEquals( $expected, $value );

		IWF_Meta::update_user( self::$user_id, 'test_user_meta_key_2', array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		) );

		$value = get_user_meta( self::$user_id, 'test_user_meta_key_2', true );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$result = IWF_Meta::update_user( self::$user_id, array(
			'test_nested_value_5',
			'test_nested_value_6',
			'test_nested_value_7',
		) );

		$this->assertFalse( $result );

		$result = IWF_Meta::update_user( self::$user_id, array(
			'test_user_meta_key_3' => 'test_user_meta_value_3',
			'test_user_meta_key_4' => array(
				'test_nested_key' => 'test_nested_value',
				'test_nested_key_2' => 'test_nested_value_2',
			),
			'test_user_meta_key_5' => array(
				'test_nested_key_3' => 'test_nested_value_3',
				'test_nested_key_4' => 'test_nested_value_4',
			),
		) );

		$this->assertNotEmpty( $result );

		$value = get_user_meta( self::$user_id, 'test_user_meta_key_3', true );
		$expected = 'test_user_meta_value_3';

		$this->assertEquals( $expected, $value );

		$value = get_user_meta( self::$user_id, 'test_user_meta_key_4', true );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$value = get_user_meta( self::$user_id, 'test_user_meta_key_5', true );
		$expected = array(
			'test_nested_key_3' => 'test_nested_value_3',
			'test_nested_key_4' => 'test_nested_value_4',
		);

		$this->assertEquals( $expected, $value );

		$result = IWF_Meta::update_user( self::$user_id, 'test_user_meta_key_6.', 'test_user_meta_value_5' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers IWF_Meta::comment
	 */
	public function testComment() {
		update_comment_meta( self::$comment_id, 'test_comment_meta_key', 'test_comment_meta_value' );
		update_comment_meta( self::$comment_id, 'test_comment_meta_key_2', array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		) );

		$value = IWF_Meta::comment( self::$comment_id, 'test_comment_meta_key' );
		$expected = 'test_comment_meta_value';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment( self::$comment_id, 'test_comment_meta_key_not_exists', 'default_value' );
		$expected = 'default_value';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment( self::$comment_id, 'test_comment_meta_key', array(
			'callback' => 'strtoupper',
			'before' => 'before:',
			'after' => ':after'
		) );

		$expected = 'before:TEST_COMMENT_META_VALUE:after';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment( self::$comment_id, 'test_comment_meta_key', array(
			'callback' => array( 'substr' => array( 0, 4 ), 'ucfirst' ),
		) );

		$expected = 'Test';

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment( self::$comment_id, 'test_comment_meta_key_2' );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment( self::$comment_id, 'test_comment_meta_key_2.test_nested_key' );
		$expected = 'test_nested_value';

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers IWF_Meta::comment_iteration
	 */
	public function testCommentIteration() {
		update_comment_meta( self::$comment_id, 'test_comment_meta_key_1', 'test_comment_meta_value_1' );
		update_comment_meta( self::$comment_id, 'test_comment_meta_key_2', 'test_comment_meta_value_2' );
		update_comment_meta( self::$comment_id, 'test_comment_meta_key_3', 'test_comment_meta_value_3' );
		update_comment_meta( self::$comment_id, 'test_comment_1_meta_key', 'test_comment_meta_value_4' );
		update_comment_meta( self::$comment_id, 'test_comment_2_meta_key', 'test_comment_meta_value_5' );
		update_comment_meta( self::$comment_id, 'test_comment_3_meta_key', 'test_comment_meta_value_6' );
		update_comment_meta( self::$comment_id, 'test_comment_meta_key_array', array(
			'test_nested_key_1' => 'test_nested_value_1',
			'test_nested_key_2' => 'test_nested_value_2',
			'test_nested_key_3' => 'test_nested_value_3',
		) );

		$value = IWF_Meta::comment_iteration( self::$comment_id, 'test_comment_meta_key_', 1, 3 );
		$expected = array(
			1 => 'test_comment_meta_value_1',
			2 => 'test_comment_meta_value_2',
			3 => 'test_comment_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment_iteration( self::$comment_id, 'test_comment_meta_key_:index', 1, 3 );
		$expected = array(
			1 => 'test_comment_meta_value_1',
			2 => 'test_comment_meta_value_2',
			3 => 'test_comment_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment_iteration( self::$comment_id, 'test_comment_meta_key_%index%', 1, 3 );
		$expected = array(
			1 => 'test_comment_meta_value_1',
			2 => 'test_comment_meta_value_2',
			3 => 'test_comment_meta_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment_iteration( self::$comment_id, 'test_comment_:index_meta_key', 1, 3 );
		$expected = array(
			1 => 'test_comment_meta_value_4',
			2 => 'test_comment_meta_value_5',
			3 => 'test_comment_meta_value_6',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::comment_iteration( self::$comment_id, 'test_comment_meta_key_array.test_nested_key_:index', 1, 3 );
		$expected = array(
			1 => 'test_nested_value_1',
			2 => 'test_nested_value_2',
			3 => 'test_nested_value_3',
		);

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers IWF_Meta::update_comment
	 */
	public function testUpdateComment() {
		$result = IWF_Meta::update_comment( null, 'test_comment_meta_key', 'test_comment_meta_value' );

		$this->assertFalse( $result );

		$result = IWF_Meta::update_comment( self::$comment_id, 'test_comment_meta_key', 'test_comment_meta_value' );

		$this->assertNotEmpty( $result );

		$value = get_comment_meta( self::$comment_id, 'test_comment_meta_key', true );
		$expected = 'test_comment_meta_value';

		$this->assertEquals( $expected, $value );

		IWF_Meta::update_comment( self::$comment_id, 'test_comment_meta_key_2', array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		) );

		$value = get_comment_meta( self::$comment_id, 'test_comment_meta_key_2', true );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$result = IWF_Meta::update_comment( self::$comment_id, array(
			'test_nested_value_5',
			'test_nested_value_6',
			'test_nested_value_7',
		) );

		$this->assertFalse( $result );

		$result = IWF_Meta::update_comment( self::$comment_id, array(
			'test_comment_meta_key_3' => 'test_comment_meta_value_3',
			'test_comment_meta_key_4' => array(
				'test_nested_key' => 'test_nested_value',
				'test_nested_key_2' => 'test_nested_value_2',
			),
			'test_comment_meta_key_5' => array(
				'test_nested_key_3' => 'test_nested_value_3',
				'test_nested_key_4' => 'test_nested_value_4',
			),
		) );

		$this->assertNotEmpty( $result );

		$value = get_comment_meta( self::$comment_id, 'test_comment_meta_key_3', true );
		$expected = 'test_comment_meta_value_3';

		$this->assertEquals( $expected, $value );

		$value = get_comment_meta( self::$comment_id, 'test_comment_meta_key_4', true );
		$expected = array(
			'test_nested_key' => 'test_nested_value',
			'test_nested_key_2' => 'test_nested_value_2',
		);

		$this->assertEquals( $expected, $value );

		$value = get_comment_meta( self::$comment_id, 'test_comment_meta_key_5', true );
		$expected = array(
			'test_nested_key_3' => 'test_nested_value_3',
			'test_nested_key_4' => 'test_nested_value_4',
		);

		$this->assertEquals( $expected, $value );

		$result = IWF_Meta::update_comment( self::$comment_id, 'test_comment_meta_key_6.', 'test_comment_meta_value_5' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers IWF_Meta::option
	 */
	public function testOption() {
		update_option( 'iwf_meta_test', array(
			'test_key' => 'test_value',
			'test_key_2' => 'test_value_2',
			'test_key_3' => array(
				'nested_test_key' => 'nested_test_value',
				'nested_test_key_2' => 'nested_test_value_2',
				'test_key_3' => 'test_value_3',
			)
		) );

		$options = IWF_Meta::option( 'iwf_meta_test.test_key' );
		$expected = 'test_value';

		$this->assertEquals( $expected, $options );

		$options = IWF_Meta::option( 'iwf_meta_test.test_key_3.nested_test_key' );
		$expected = 'nested_test_value';

		$this->assertEquals( $expected, $options );

		$options = IWF_Meta::option( array(
			'iwf_meta_test.test_key',
			'iwf_meta_test.test_key_2',
			'iwf_meta_test.test_key_3.nested_test_key'
		) );

		$expected = array(
			'test_key' => 'test_value',
			'test_key_2' => 'test_value_2',
			'nested_test_key' => 'nested_test_value'
		);

		$this->assertEquals( $expected, $options );

		$options = IWF_Meta::option( array(
			'iwf_meta_test.test_key_3',
			'iwf_meta_test.test_key_3.test_key_3'
		) );

		$expected = array(
			'test_key_3' => 'test_value_3'
		);

		$this->assertEquals( $expected, $options );
	}

	/**
	 * @covers IWF_Meta::option_iteration
	 */
	public function testOptionIteration() {
		update_option( 'iwf_meta_test_option_key_1', 'test_option_value_1' );
		update_option( 'iwf_meta_test_option_key_2', 'test_option_value_2' );
		update_option( 'iwf_meta_test_option_key_3', 'test_option_value_3' );
		update_option( 'iwf_meta_test_1_option_key', 'test_option_value_4' );
		update_option( 'iwf_meta_test_2_option_key', 'test_option_value_5' );
		update_option( 'iwf_meta_test_3_option_key', 'test_option_value_6' );
		update_option( 'iwf_meta_test_option_key_array', array(
			'test_nested_key_1' => 'test_nested_value_1',
			'test_nested_key_2' => 'test_nested_value_2',
			'test_nested_key_3' => 'test_nested_value_3',
		) );

		$value = IWF_Meta::option_iteration( 'iwf_meta_test_option_key_', 1, 3 );
		$expected = array(
			1 => 'test_option_value_1',
			2 => 'test_option_value_2',
			3 => 'test_option_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::option_iteration( 'iwf_meta_test_option_key_:index', 1, 3 );
		$expected = array(
			1 => 'test_option_value_1',
			2 => 'test_option_value_2',
			3 => 'test_option_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::option_iteration( 'iwf_meta_test_option_key_%index%', 1, 3 );
		$expected = array(
			1 => 'test_option_value_1',
			2 => 'test_option_value_2',
			3 => 'test_option_value_3',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::option_iteration( 'iwf_meta_test_:index_option_key', 1, 3 );
		$expected = array(
			1 => 'test_option_value_4',
			2 => 'test_option_value_5',
			3 => 'test_option_value_6',
		);

		$this->assertEquals( $expected, $value );

		$value = IWF_Meta::option_iteration( 'iwf_meta_test_option_key_array.test_nested_key_:index', 1, 3 );
		$expected = array(
			1 => 'test_nested_value_1',
			2 => 'test_nested_value_2',
			3 => 'test_nested_value_3',
		);

		$this->assertEquals( $expected, $value );
	}

	/**
	 * @covers IWF_Meta::update_option
	 */
	public function testUpdateOption() {
		IWF_Meta::update_option( 'iwf_meta_test.test_key', 'test_value' );

		$options = get_option( 'iwf_meta_test' );
		$expected = array( 'test_key' => 'test_value' );

		$this->assertEquals( $expected, $options );

		IWF_Meta::update_option( 'iwf_meta_test.test_key_2', 'test_value_2' );

		$options = get_option( 'iwf_meta_test' );
		$expected = array(
			'test_key' => 'test_value',
			'test_key_2' => 'test_value_2'
		);

		$this->assertEquals( $expected, $options );

		IWF_Meta::update_option( array(
			'iwf_meta_test.test_key_3' => 'test_value_3',
			'iwf_meta_test_2.test_key_4' => 'test_value_4'
		) );

		$options = get_option( 'iwf_meta_test' );
		$expected = array(
			'test_key' => 'test_value',
			'test_key_2' => 'test_value_2',
			'test_key_3' => 'test_value_3'
		);

		$this->assertEquals( $expected, $options );

		$options = get_option( 'iwf_meta_test_2' );
		$expected = array(
			'test_key_4' => 'test_value_4'
		);

		$this->assertEquals( $expected, $options );

		IWF_Meta::update_option( 'iwf_meta_test.test_key_5.test_nested_key', 'test_nested_value' );

		$options = get_option( 'iwf_meta_test' );
		$expected = array(
			'test_key' => 'test_value',
			'test_key_2' => 'test_value_2',
			'test_key_3' => 'test_value_3',
			'test_key_5' => array(
				'test_nested_key' => 'test_nested_value'
			)
		);

		$this->assertEquals( $expected, $options );
	}
}
