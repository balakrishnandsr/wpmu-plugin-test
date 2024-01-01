<?php
/**
 * Post Maintenance block.
 *
 * @link  https://wpmudev.com/
 * @since 1.0.0
 *
 * @author  WPMUDEV (https://wpmudev.com)
 * @package WPMUDEV\PluginTest
 *
 * @copyright (c) 2023, Incsub (http://incsub.com)
 */


namespace WPMUDEV\PluginTest\App\Admin_Pages;

// Abort if called directly.


use WPMUDEV\PluginTest\Core\Base;

defined( 'WPINC' ) || die;

class Post_Maintenance extends Base {

	/**
	 * The page title.
	 *
	 * @var string
	 */
	private $page_title;

	/**
	 * The page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'wpmudev_plugintest_post_maintenance';


	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name = 'wpmudev_test_last_scan';


	/**
	 * Initializes the page.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function init() {
		$this->page_title = __( 'Post Maintenance', 'wpmudev-plugin-test' );

		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'daily_posts_maintenance', array( $this, 'schedule_scan_and_update' ) );
		add_action( 'post_maintenance_update', array( $this, 'post_maintenance_update' ) );

		if (  class_exists('WP_CLI') ) {
			// Register the custom WP-CLI command
			WP_CLI::add_command( 'wpmu-scan-posts', array( $this, 'wpmu_scan_posts' ) );
		}

	}

	/**
	 * Register Admin Page
	 *
	 * @return void
	 */
	public function register_admin_page() {
		add_menu_page(
			__( 'Post Maintenance setup', 'wpmudev-plugin-test' ),
			$this->page_title,
			'manage_options',
			$this->page_slug,
			array( $this, 'view' ),
			'dashicons-admin-tools',
			6
		);
	}


	/**
	 * Post Maintenance Page UI
	 *
	 * @return void
	 */
	public function view() {

		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Posts Maintenance', 'wpmudev-plugin-test' ); ?></h2>
			<form method="post">
		<?php //phpcs:ignore
		if ( isset( $_POST['scan_posts'] ) ) { //ignore:phpcs
			// Perform the scan and update post_meta
			wp_schedule_event( time(), 2, 'post_maintenance_update' );

			echo '<div class="updated"><p>' . esc_html__( 'Posts scanning  started successfully. The next scheduled scan will take place in 24 hours.', 'wpmudev-plugin-test' ) . '</p></div>';
		}
		?>
				<p><?php esc_html_e( 'Click the button below to scan all public posts and pages:', 'wpmudev-plugin-test' ); ?></p>
				<input type="submit" class="button button-primary" name="scan_posts" value="<?php esc_html_e( 'Scan Posts', 'wpmudev-plugin-test' ); ?>">
			</form>
		</div>
		<?php
	}

	/**
	 * Scan all public posts types and update wpmudev_test_last_scan post_meta.
	 *
	 * ## OPTIONS
	 *
	 * [--post-types=<post_types>]
	 * : Comma-separated list of post types to include in the scan.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpmu-scan-posts
	 *     wp wpmu-scan-posts --post-types=post,page,custom_post_type --time-interval=daily
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 *
	 * @return void
	 */
	private function wpmu_scan_posts( $args = array(), $assoc_args = array() ) {
		$this->schedule_scan_and_update( $assoc_args );
		if (  class_exists('WP_CLI') ) {
			WP_CLI::success('Posts scanning started successfully and the next cron is scheduled.');
		}
	}

	/**
	 * Scan and update posts.
	 *
	 * @param array $assoc_args data.
	 *
	 * @return void
	 */
	public function schedule_scan_and_update( $assoc_args = array() ) {
		$post_types         = ! empty( $assoc_args['post-types'] ) ? explode( ',', $assoc_args['post-types'] ) : get_post_types();
		$assoc_args         = wp_parse_args(
			$assoc_args,
			array(
				'time-interval' => 'daily',
				'post-type'     => $post_types,
			)
		);
		$post_types_to_scan = $assoc_args['post-type'];
		$post_per_page = 1000;


		$args = array(
			'post_type'      => $post_types_to_scan,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		$posts = get_posts( $args );

		if(!empty($posts)){
			$post_count = count($posts);
			if( $post_count > 1000 ){
				$total_pages = ceil( $post_count / $post_per_page );
				for ( $page = 1; $page <= $total_pages; $page++ ) {
					$args = array(
						'post_type'      => $post_types_to_scan,
						'post_status'    => 'publish',
						'posts_per_page' => $post_per_page,
						'paged'			 => $page,
						'offset'         => ( $page - 1 ) * $post_per_page,
					);

					$posts = get_posts( $args );
					if(!empty($posts)){
						foreach ( $posts as $post ) {
							// Update post_meta with the current timestamp
							update_post_meta( $post->ID, $this->option_name, current_time( 'timestamp' ) ); //phpcs:ignore
						}
					}

				}
			}else{
				foreach ( $posts as $post ) {
					// Update post_meta with the current timestamp
					update_post_meta( $post->ID, $this->option_name, current_time( 'timestamp' ) ); //phpcs:ignore
				}
			}

		}

		$this->schedule_update( $assoc_args['time-interval'] );
	}

	/**
	 * Schedule event.
	 *
	 * @param string $interval data.
	 *
	 * @return void
	 */
	private function schedule_update( $interval = 'daily' ) {
		if ( ! wp_next_scheduled( 'daily_posts_maintenance' ) ) {
			wp_schedule_event( time(), $interval, 'daily_posts_maintenance' );
		}
	}

	/**
	 * Post maintenance update call.
	 *
	 * @return void
	 */
	public function post_maintenance_update()
	{
		$this->schedule_scan_and_update();
		wp_clear_scheduled_hook( 'post_maintenance_update' );
	}
}
