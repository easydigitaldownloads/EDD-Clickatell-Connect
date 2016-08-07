<?php
/**
 * Settings
 *
 * @package         EDD\ClickatellConnect\Admin\Settings
 * @since           1.0.1
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add settings section
 *
 * @since       1.0.1
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_clickatell_connect_add_settings_section( $sections ) {
	$sections['clickatell-connect'] = __( 'Clickatell Connect', 'edd-clickatell-connect' );

	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_clickatell_connect_add_settings_section' );


/**
 * Add settings
 *
 * @since       1.0.0
 * @param       array $settings the existing plugin settings
 * @return      array
 */
function edd_clickatell_connect_register_settings( $settings ) {
	$new_settings = array(
		'clickatell-connect' => apply_filters( 'edd_clickatell_connect_settings', array(
			array(
				'id'   => 'edd_clickatell_connect_settings',
				'name' => '<strong>' . __( 'Clickatell Connect Settings', 'edd-clickatell-connect' ) . '</strong>',
				'desc' => '',
				'type' => 'header'
			),
			array(
				'id'   => 'edd_clickatell_connect_username',
				'name' => __( 'Username', 'edd-clickatell-connect' ),
				'desc' => __( 'Enter your Clickatell username', 'edd-clickatell-connect' ),
				'type' => 'text'
			),
			array(
				'id'   => 'edd_clickatell_connect_password',
				'name' => __( 'Password', 'edd-clickatell-connect' ),
				'desc' => __( 'Enter your Clickatell password', 'edd-clickatell-connect' ),
				'type' => 'password'
			),
			array(
				'id'   => 'edd_clickatell_connect_api_id',
				'name' => __( 'API ID', 'edd-clickatell-connect' ),
				'desc' => __( 'Enter your Clickatell API ID', 'edd-clickatell-connect' ),
				'type' => 'text'
			),
			array(
				'id'   => 'edd_clickatell_connect_twoway_number',
				'name' => __( 'Two-way Phone Number', 'edd-clickatell-connect' ),
				'desc' => sprintf( __( 'Enter your Clickatell <a href="%s" target="_blank">two-way phone number</a>', 'edd-clickatell-connect' ), 'https://central.clickatell.com/twoway/ussb/' ),
				'type' => 'text'
			),
			array(
				'id'   => 'edd_clickatell_connect_phone_number',
				'name' => __( 'Phone Number', 'edd-clickatell-connect' ),
				'desc' => __( 'Enter the number(s) you want messages delivered to, comma separated', 'edd-clickatell-connect' ),
				'type' => 'text'
			),
			array(
				'id'   => 'edd_clickatell_connect_itemize',
				'name' => __( 'Itemized Notification', 'edd-clickatell-connect' ),
				'desc' => __( 'Select whether or not you want itemized SMS notifications', 'edd-clickatell-connect' ),
				'type' => 'checkbox'
			)
		) )
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_extensions', 'edd_clickatell_connect_register_settings', 1 );


/**
 * Add debug option if the S214 Debug plugin is enabled
 *
 * @since       1.0.2
 * @param       array $settings The current settings
 * @return      array $settings The updated settings
 */
function edd_clickatell_connect_add_debug( $settings ) {
	if( class_exists( 'S214_Debug' ) ) {
		$debug_setting[] = array(
			'id'   => 'edd_clickatell_connect_debugging',
			'name' => '<strong>' . __( 'Debugging', 'edd-clickatell-connect' ) . '</strong>',
			'desc' => '',
			'type' => 'header'
		);

		$debug_setting[] = array(
			'id'   => 'edd_clickatell_connect_enable_debug',
			'name' => __( 'Enable Debug', 'edd-clickatell-connect' ),
			'desc' => sprintf( __( 'Log plugin errors. You can view errors %s.', 'edd-clickatell-connect' ), '<a href="' . admin_url( 'tools.php?page=s214-debug-logs' ) . '">' . __( 'here', 'edd-clickatell-connect' ) . '</a>' ),
			'type' => 'checkbox'
		);

		$settings = array_merge( $settings, $debug_setting );
	}

	return $settings;
}
add_filter( 'edd_clickatell_connect_settings', 'edd_clickatell_connect_add_debug' );


/**
 * Add license setting
 *
 * @since       1.0.1
 * @param       array $settings The current settings
 * @return      array $settings The updated settings
 */
function edd_clickatell_connect_register_license_settings( $settings ) {
	$new_settings = array(
		'edd_clickatell_connect_license' => array(
			'id'   => 'edd_clickatell_connect_license',
			'name' => __( 'Clickatell Connect', 'edd-clickatell-connect' ),
			'desc' => sprintf( __( 'Enter your Clickatell Connect license key. This is required for automatic updates and <a href="%s">support</a>.' ), 'https://section214.com/contact' ),
			'type' => 's214_license_key'
		)
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_licenses', 'edd_clickatell_connect_register_license_settings', 1 );


/**
 * License key sanitization
 *
 * @since       1.0.0
 * @param       mixed $value The value of the field
 * @param       string $key The key we are sanitizing
 * @return      mixed $value The sanitized value
 */
function edd_clickatell_connect_license_key_sanitize( $value, $key ) {
	$current_value = edd_get_option( 'edd_clickatell_connect_license', false );

	if( ( $value && $value !== $current_value ) || ! $value ) {
		delete_option( 'edd_clickatell_connect_license_status' );
	}

	if( isset( $_POST['edd_clickatell_connect_license_activate'] ) && $value ) {
		edd_clickatell_connect_activate_license( $value );
	} elseif( isset( $_POST['edd_clickatell_connect_license_deactivate'] ) ) {
		edd_clickatell_connect_deactivate_license( $value );
		$value = '';
	}

	return $value;
}
add_filter( 'edd_settings_sanitize_s214_license_key', 'edd_clickatell_connect_license_key_sanitize', 10, 2 );


/**
 * License activation
 *
 * @since       1.0.0
 * @param       string $value The license key to activate
 * @return      void
 */
function edd_clickatell_connect_activate_license( $license ) {
	if( ! check_admin_referer( 'edd_clickatell_connect_license-nonce', 'edd_clickatell_connect_license-nonce' ) ) {
		return;
	}

	$license = trim( $license );

	if( $license ) {
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => 'EDD Clickatell Connect',
			'url'        => home_url()
		);

		// Call the API
		$response = wp_remote_post( 'https://section214.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if( is_wp_error( $response ) ) {
			return false;
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		update_option( 'edd_clickatell_connect_license_status', $license_data );
	}
}


/**
 * License deactivation
 *
 * @since       1.0.0
 * @return      void
 */
function edd_clickatell_connect_deactivate_license( $license ) {
	if( ! check_admin_referer( 'edd_clickatell_connect_license-nonce', 'edd_clickatell_connect_license-nonce' ) ) {
		return;
	}

	$license = trim( $license );
	$status  = get_option( 'edd_clickatell_connect_license_status', false );

	if( $license && is_object( $status ) && $status->license == 'valid' ) {
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( 'EDD Clickatell Connect' ),
			'url'        => home_url()
		);

		$response = wp_remote_post( 'https://section214.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if( is_wp_error( $response ) ) {
			return false;
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if( $license_data->license == 'deactivated' ) {
			delete_option( 'edd_clickatell_connect_license_status' );
		}
	}
}