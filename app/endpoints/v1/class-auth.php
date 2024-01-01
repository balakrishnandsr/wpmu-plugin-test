<?php
/**
 * Google Auth Shortcode.
 *
 * @package   WPMUDEV\PluginTest
 * @author    WPMUDEV <username@example.com>
 * @link      https://wpmudev.com/
 * @copyright (c) 2023, Incsub (http://incsub.com)
 *
 * @since 1.0.0
 */

namespace WPMUDEV\PluginTest\App\Endpoints\V1;

// Abort if called directly.
defined( 'WPINC' ) || die;

use phpseclib3\Common\Functions\Strings;
use WPMUDEV\PluginTest\Core\Endpoint;
use WP_REST_Response;

/**
 * Class for handling Endpoints.
 *
 * @category WPMUDEV_PluginTest
 * @package  WPMUDEV\PluginTest
 * @author   WPMUDEV <username@example.com>
 * @license  GPL-2.0-or-later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @link     https://wpmudev.com/
 *
 * @since 1.0.0
 */
class Auth extends Endpoint {

	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @var string $endpoint
	 */
	protected $endpoint = 'auth';

	/**
	 * Register the routes for handling auth functionality.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function register_routes() {
		// TODO
		// Add a new Route to logout.

		// Route to get auth url.
		register_rest_route(
			$this->get_namespace(),
			$this->get_endpoint() . '/auth-url',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_credentials' ),
					'permission_callback' => array( $this, 'edit_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'args'                => array(
						'client_id'     => array(
							'required'    => true,
							'description' => __( 'The client ID from Google API project.', 'wpmudev-plugin-test' ),
							'type'        => 'string',
						),
						'client_secret' => array(
							'required'    => true,
							'description' => __( 'The client secret from Google API project.', 'wpmudev-plugin-test' ),
							'type'        => 'string',
						),
					),
					'callback'            => array( $this, 'save_credentials' ),
					'permission_callback' => array( $this, 'edit_permission' ),
				),
			)
		);

		// Route to establish the return url.
		register_rest_route(
			$this->get_namespace(),
			$this->get_endpoint() . '/confirm',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'return_url_setup' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Save the client id and secret.
	 *
	 * @param object $request data.
	 *
	 * @return WP_REST_Response
	 */
	public function save_credentials( $request ) {
		$response_data = $this->get_response( array(
			'code' 	   => 400,
			'message'  => __('Sorry, you are not allowed to save data as this user.', 'wpmudev-plugin-test'),
		), false );

		if( ! $this->edit_permission( $request ) ){
			 return $response_data;
		}

		$client_id     = ! empty( $request->get_param( 'client_id' ) ) ? sanitize_text_field( $request->get_param( 'client_id' ) ) : '';
		$client_secret = ! empty( $request->get_param( 'client_secret' ) ) ? sanitize_text_field( $request->get_param( 'client_secret' ) ) : '';

		$response_data = $this->get_response( array(
			'code' 	   => 400,
			'message'  => __('Sorry, client id or client secret should not be empty.', 'wpmudev-plugin-test'),
		), false );

		if(empty($client_id) || empty($client_secret)){
			return $response_data;
		}

		$data = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		);

		update_option(WPMUDEV_PLUGINTEST_SETTINGS, $data );

		$data['message'] = __('Data saved successfully!', 'wpmudev-plugin-test');
		$data['code'] = 200;

		return $this->get_response( $data, true );
	}

	/**
	 * Get Credentials
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function get_credentials($request) {
		$response_data = $this->get_response( array(
			'code' 	   => 400,
			'message'  => __('Sorry, you are not allowed to get data as this user.', 'wpmudev-plugin-test'),
		), false );
		if( ! $this->edit_permission( $request ) ){
			return $response_data;
		}
		$result = get_option( WPMUDEV_PLUGINTEST_SETTINGS );
		return $this->get_response( $result );
	}

	/**
	 * Return Url Setup.
	 *
	 * @param object $request data.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|WP_REST_Response
	 */
	public function return_url_setup( $request ) {

		// Redirect to the home page
		$redirect_url = home_url();

		$code   = ! empty( $request->get_param( 'code' ) ) ? $request->get_param( 'code' ) : '';
		$client = \WPMUDEV\PluginTest\Core\Google_Auth\Auth::instance()->client();
		\WPMUDEV\PluginTest\Core\Google_Auth\Auth::instance()->set_up();
		$token = $client->fetchAccessTokenWithAuthCode( $code );
		if ( ! empty( $token['access_token'] ) ) {
			$client->setAccessToken( $token['access_token'] );
		}

		// get profile info
		$google_oauth        = new \Google_Service_Oauth2( $client );
		$user = ! empty($google_oauth->userinfo->get() ) ? $this->get_user( $google_oauth->userinfo->get() ) : '';

		if ( is_object( $user ) ) {
			$user_name = ! empty( $user->data->user_login ) ? $user->data->user_login : '';
			$user_pass = ! empty( $user->data->user_pass ) ? $user->data->user_pass : '';
			wp_set_password( $user_pass, $user->ID );

			// Log in the user
			$user_login_credentials = array(
				'user_login'    => $user_name,
				'user_password' => $user_pass,
				'remember'      => true,
			);

			$user = wp_signon( $user_login_credentials, false );

			if ( is_wp_error( $user ) ) {
				// Handle login error if any
				error_log( $user->get_error_message() ); //PHPCS:IGNORE
			} else {
				// Check if the user has the 'administrator' role
				if ( in_array( 'administrator', (array) $user->roles, true ) ) {
					return rest_ensure_response( $this->redirect_user( admin_url() ) );
				}
				return rest_ensure_response( $this->redirect_user( $redirect_url ) );
			}
		}
		return rest_ensure_response( $this->redirect_user( $redirect_url ) );
	}

	/**
	 * Get User.
	 *
	 * @param object $account_info data.
	 *
	 * @return false|\WP_User
	 */
	private function get_user( $account_info = object ) {
		$email = ! empty( $account_info->email ) ? $account_info->email : '';

		$user = get_user_by( 'email', $email );

		if ( is_object( $user ) ) {
			return $user;
		}

		$name        = ! empty( $account_info->name ) ? $account_info->name : '';
		$first_name  = ! empty( $account_info->givenName ) ? $account_info->givenName : ''; //phpcs:ignore
		$second_name = ! empty( $account_info->familyName ) ? $account_info->familyName : ''; //phpcs:ignore

		// Define user information
		$user_data = array(
			'user_login' => $name,
			'user_pass'  => wp_generate_password(),
			'user_email' => $email,
			'first_name' => $first_name,
			'last_name'  => $second_name,
			'role'       => 'subscriber', // You can set the role as needed (subscriber, contributor, author, editor, administrator)
		);

		// Insert the user into the database
		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			// Handle error if any
			error_log( $user_id->get_error_message() ); //phpcs:ignore
			return false;
		}

		return get_user_by( 'id', $user_id );
	}

	/**
	 * Redirect User.
	 *
	 * @param Strings $redirect_url URL.
	 *
	 * @return WP_REST_Response
	 */
	private function redirect_user( $redirect_url = '' ): WP_REST_Response {
		// Prepare the response
		$response = new WP_REST_Response( array( 'message' => 'Redirecting to home page...' ) );
		$response->set_status( 302 ); // Found - Redirect

		// Add the 'Location' header with the redirect URL
		$response->header( 'Location', $redirect_url );

		// Send the response
		return $response;
	}
}
