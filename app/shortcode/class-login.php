<?php
/**
 * Shortcode.
 *
 * @link  https://wpmudev.com/
 * @since 1.0.0
 *
 * @author  WPMUDEV (https://wpmudev.com)
 * @package WPMUDEV\PluginTest
 *
 * @copyright (c) 2023, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\App\Shortcode;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Core\Base;
use WPMUDEV\PluginTest\Core\Google_Auth\Auth;

/**
 * Login Class.
 */
class Login extends Base {

	/**
	 * Initializes the page.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function init() {
		add_shortcode( 'wpmu_google_login_form', array( $this, 'wpmu_google_login_form' ) );
	}

	/**
	 * Google Login Form.
	 *
	 * @return mixed|null
	 */
	public function wpmu_google_login_form() {
		ob_start();?>
		<div class="wpmu-form-container">
		<div class="wpmu-form-row">
		<?php
		$user = wp_get_current_user();
		Auth::instance()->client();
		Auth::instance()->set_up();
		$url = esc_url( Auth::instance()->get_auth_url() );
		if ( is_object( $user ) && $user->exists() ) {
			$user_name         = isset( $user->user_login ) ? $user->user_login : '';
			$user_display_name = isset( $user->display_name ) ? $user->display_name : '';

			if ( ! empty( $user_display_name ) ) {
				?>
				<p>
				<?php
					/* translators: %s user display name */
					printf( __( 'Welcome <strong>  %s </strong>', 'wpmudev-plugin-test' ), esc_html( $user_display_name ) );
				?>
					</p>
				<?php
			} else {
				?>
				<p>
				<?php
					/* translators: %s user name */
					printf( __( 'Welcome <strong> %s </strong>', 'wpmudev-plugin-test' ), esc_html( $user_name ) );
				?>
					</p>
				<?php
			}
		} else {
			?>
				<p><a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Login By Google', 'wpmudev-plugin-test' ); ?></a></p>
									<?php
		}
		?>

		</div>
		</div>
		<?php
		$wpmu_form_html = apply_filters( 'wpmudev_plugin_test_login_by_google_content', ob_get_contents(), $url );
		ob_end_clean();
		return $wpmu_form_html;
	}
}
