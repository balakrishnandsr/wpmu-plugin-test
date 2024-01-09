<?php

/**
 * Class Post_MaintenanceTest
 *
 * @package Wpmudev_Plugin_Test
 */

 use WPMUDEV\PluginTest\App\Admin_Pages\Post_Maintenance;

/**
* Post_Maintenance test case.
*/
class Post_MaintenanceTest extends WP_UnitTestCase {

	/**
	* Test hooks init
	*/
	public function test_init() {
		$post_maintenance = Post_Maintenance::instance();
		$cron_schedules   = has_action( 'daily_posts_maintenance', array( $post_maintenance, 'schedule_scan_and_update' ) );
		$scan_and_update  = has_action( 'post_maintenance_update', array( $post_maintenance, 'post_maintenance_update' ) );

		$this->assertTrue( 10 === $cron_schedules && 10 === $scan_and_update );
	}

	/**
	* A schedule_scan_and_update test.
	*/
	public function test_schedule_scan_and_update() {
		// Replace this with some actual testing code.
		//$this->assertTrue( true );
		global $wp_query;
		// Create a dummy post using the 'WP_UnitTest_Factory_For_Post' class
		$post_id = $this->factory->post->create( [
			'post_status' => 'publish',
			'post_title'  => 'Test 1',
			'post_content' => 'Test Content',
		] );
		$post_maintenance = new Post_Maintenance();
		$post_maintenance->schedule_scan_and_update();
		// Reset the $wp_query global post variable and create a new WP Query.
		$wp_query = new WP_Query( [
			'post__in' => [ $post_id ],
			'posts_per_page' => 1,
		] );

		// Run the WordPress loop through this query and check the post meta.
		if ( $wp_query->have_posts() ) {
			while( $wp_query->have_posts() ) {
				$wp_query->the_post();
				$meta = get_post_meta( get_the_ID(), 'wpmudev_test_last_scan', true); //phpcs:ignore
				$result = !empty($meta);
				$this->assertTrue( $result );

			}
		}

	}

	/**
	 * Test cron is scheduled
	 */
	public function test_schedule_update() {
		$post_maintenance = Post_Maintenance::instance();
		$post_maintenance->schedule_update();
		$next_scheduled = wp_next_scheduled( 'daily_posts_maintenance' );

		$this->assertTrue( false !== $next_scheduled );
	}
}
