<?php

/**
 * Class Post_MaintenanceTest
 *
 * @package Wpmudev_Plugin_Test
 */

/**
* Post_Maintenance test case.
*/
class Post_MaintenanceTest extends WP_UnitTestCase {

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
}
