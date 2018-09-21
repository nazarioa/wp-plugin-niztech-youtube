<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 9/9/18
 * Time: 2:55 PM
 */

/**
 * Class Niztech_YoutubeTest
 *
 * @package Niztech_Youtube
 */
class Niztech_YoutubeTest extends WP_UnitTestCase {
	private $validUrlHttps = 'https://www.youtube.com/watch?v=VIDEO_A1&list=PLAYLIST_A';
	private $validUrlHttp = 'http://www.youtube.com/watch?v=VIDEO_B1&list=PLAYLIST_B';
	private $notValidUrl = 'http://www.notYuetube.com/v/ABC123';
	private $today;

	private $fakeVideos = array(
		array(
			'post_id'     => '1111',
			'code'        => 'YTCODE00001',
			'playlist_id' => '0',
			'data'        => array(
				'snippet' => array(
					'title'       => 'Title A',
					'description' => 'Description A',
					'thumbnails'  => array()
				)
			)
		),
		array(
			'post_id'     => '1112',
			'code'        => 'YTCODE00002',
			'playlist_id' => '0',
			'data'        => array(
				'snippet' => array(
					'title'       => 'Title B',
					'description' => 'Description B',
					'thumbnails'  => array()
				)
			)
		),
		array(
			'post_id'     => '1113',
			'code'        => 'YTCODE00003',
			'playlist_id' => '0',
			'data'        => array(
				'snippet' => array(
					'title'       => 'Title C',
					'description' => 'Description C',
					'thumbnails'  => array()
				)
			)
		),
		array(
			'post_id'     => '2221',
			'code'        => 'YTCODE00021',
			'playlist_id' => '1',
			'data'        => array(
				'snippet' => array(
					'title'       => 'Title 2A',
					'description' => 'Description 2A',
					'thumbnails'  => array()
				)
			)
		),
		array(
			'post_id'     => '2222',
			'code'        => 'YTCODE00022',
			'playlist_id' => '1',
			'data'        => array(
				'snippet' => array(
					'title'       => 'Title 2B',
					'description' => 'Description 2B',
					'thumbnails'  => array()
				)
			)
		)
	);

	public function setUp() {
		parent::setUp();
		$this->today          = new DateTime();
		$this->class_instance = new Niztech_Youtube();
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		$this->class_instance->create_table_playlist();
		$this->class_instance->create_table_video();
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	public function tearDown() {
		parent::tearDown();
	}

	/*
	public function testV2_delete_video_by_post_playlist() {

	}

	public function testInit_hooks() {

	}
	*/

	/**
	 * @group is_youtube_url
	 */
	public function testIs_youtube_url_acceptsValidUrlWithHttps() {
		$result = $this->class_instance->is_youtube_url( $this->validUrlHttps );
		$this->assertTrue( $result );
	}

	/**
	 * @group is_youtube_url
	 */
	public function testIs_youtube_url_acceptsValidUrlWithHttp() {
		$result = $this->class_instance->is_youtube_url( $this->validUrlHttp );
		$this->assertTrue( $result );
	}

	/**
	 * @group is_youtube_url
	 */
	public function testIs_youtube_url_acceptsNoneValidUrl() {
		$result = $this->class_instance->is_youtube_url( $this->notValidUrl );
		$this->assertFalse( $result );
	}

	/*
	public function testGet_local_playlist_row() {

	}

	public function testGet_video_info_for() {

	}
	*/

	/**
	 * @group commit_playlist_data_to_wp
	 */
	public function testCommit_playlist_data_to_wpShouldReturnFalseIfPlaylistIdIsNull() {
		$result = $this->class_instance->commit_playlist_data_to_wp( null, 'fake',
			array( $this->fakeVideos[0], $this->fakeVideos[1] ) );
		// TODO: Improve test by spying on other methods and asserting that they aren't called.
		$this->assertNull( $result );
	}

	/*
	public function testPlugin_deactivation() {

	}

	public function testCreate_empty_local_playlist_row() {

	}
	*/

	/**
	 * @group extract_youtube_code
	 */
	public function testExtract_youtube_code_withValidHttpsUrlForVideo() {
		$result = $this->class_instance->extract_youtube_code( $this->validUrlHttps,
			Niztech_Youtube::TYPE_OPTION_VIDEO );
		$this->assertEquals( $result, 'VIDEO_A1' );
	}

	/**
	 * @group extract_youtube_code
	 */
	public function testExtract_youtube_code_withValidHttpUrlForVideo() {
		$result = $this->class_instance->extract_youtube_code( $this->validUrlHttp,
			Niztech_Youtube::TYPE_OPTION_VIDEO );
		$this->assertEquals( $result, 'VIDEO_B1' );
	}

	/**
	 * @group extract_youtube_code
	 * @expectedException \Exception
	 */
	public function testExtract_youtube_code_withNoneValidUrlForVideo() {
		$this->class_instance->extract_youtube_code( $this->notValidUrl, Niztech_Youtube::TYPE_OPTION_VIDEO );
	}

	/**
	 * @group extract_youtube_code
	 */
	public function testExtract_youtube_code_withValidHttpsUrlForPlaylist() {
		$result = $this->class_instance->extract_youtube_code( $this->validUrlHttps,
			Niztech_Youtube::TYPE_OPTION_PLAYLIST );
		$this->assertEquals( $result, 'PLAYLIST_A' );
	}

	/**
	 * @group extract_youtube_code
	 */
	public function testExtract_youtube_code_withValidHttpUrlForPlaylist() {
		$result = $this->class_instance->extract_youtube_code( $this->validUrlHttp,
			Niztech_Youtube::TYPE_OPTION_PLAYLIST );
		$this->assertEquals( $result, 'PLAYLIST_B' );
	}

	/**
	 * @group extract_youtube_code
	 * @expectedException \Exception
	 */
	public function testExtract_youtube_code_withNoneValidUrlForPlaylist() {
		$this->class_instance->extract_youtube_code( $this->notValidUrl, Niztech_Youtube::TYPE_OPTION_PLAYLIST );
	}

	/*
	public function testVideo_source_get_meta() {

	}

	public function testQuery_video_data_from_youtube() {

	}

	public function testInit() {

	}

	public function testQuery_playlist_data_from_youtube() {

	}
	*/

	/**
	 * @group V2_delete_video_by_id
	 */
	public function testV2_delete_video_by_id() {
		// Add a new row and check that is went in
		$this->class_instance->commit_video_data_to_wp(
			$this->fakeVideos[0]['post_id'],
			$this->fakeVideos[0]['code'],
			$this->fakeVideos[0]['playlist_id'],
			$this->fakeVideos[0]['data']['snippet']['title'],
			$this->fakeVideos[0]['data']['snippet']['description'],
			$this->fakeVideos[0]['data']['snippet']['thumbnails']
		);

		global $wpdb;
		$table_name = $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS;

		$inital_row = $wpdb->get_row( 'select id from ' . $table_name . ' where post_id = "' . $this->fakeVideos[0]['post_id'] . '";' );
		$this->assertNotNull( $inital_row );

		// Now delete it
		$this->class_instance->V2_delete_video_by_id( $inital_row->id );

		// Assert that it has been removed
		$removed_row = $wpdb->get_row( 'select id from ' . $table_name . ' where post_id = "' . $this->fakeVideos[0]['post_id'] . '";' );
		$this->assertNull( $removed_row );
	}

	/*
	public function testEnter_api_key() {

	}

	public function testSet_youtube_api_key() {

	}
	*/

	/**
	 * @group commit_video_data_to_wp
	 */
	public function testCommit_video_data_to_wp() {

		$this->class_instance->commit_video_data_to_wp(
			$this->fakeVideos[0]['post_id'],
			$this->fakeVideos[0]['code'],
			$this->fakeVideos[0]['playlist_id'],
			$this->fakeVideos[0]['data']['snippet']['title'],
			$this->fakeVideos[0]['data']['snippet']['description'],
			$this->fakeVideos[0]['data']['snippet']['thumbnails']
		);

		global $wpdb;
		$post_id    = $this->fakeVideos[0]['post_id'];
		$tableName  = $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS;
		$actual_row = $wpdb->get_row( "select post_id, playlist_id, youtube_video_code, title, description, last_update from $tableName where post_id = '$post_id'" );

		$expected_row = (object) array(
			'post_id'            => $this->fakeVideos[0]['post_id'],
			'playlist_id'        => $this->fakeVideos[0]['playlist_id'],
			'youtube_video_code' => $this->fakeVideos[0]['code'],
			'title'              => $this->fakeVideos[0]['data']['snippet']['title'],
			'description'        => $this->fakeVideos[0]['data']['snippet']['description'],
			'last_update'        => $this->today->format( 'Y-m-d H:i:s' ),
		);
		$this->assertEquals( $expected_row, $actual_row );
	}

	/*
	public function testV2_delete_playlist_by_id() {

	}

	public function testV2_delete_playlist_by_post_id() {

	}

	public function testSetup_youtube_google_client() {

	}

	public function testGet_video_or_playlist_code_and_foreign_key() {

	}

	public function testCreate_table_video() {

	}

	public function testGet_playlist_info_for() {

	}

	public function testPlugin_activation() {

	}

	public function testProcess_Google_Service_YouTube_ThumbnailDetails() {

	}

	public function testCreate_table_playlist() {

	}

	public function testGet_youtube_api_key() {

	}

	public function testVerify_key() {

	}
	*/
}
