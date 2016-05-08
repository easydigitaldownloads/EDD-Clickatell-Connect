<?php
/**
 * Settings
 *
 * @package         EDD\ClickatellConnect\Functions
 * @since           1.1.1
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Build the message to be passed to Clickatell
 *
 * @since       1.0.0
 * @param       string $payment_id
 * @return      void
 */
function edd_clickatell_connect_build_sms( $payment_id ) {
	$username = edd_get_option( 'edd_clickatell_connect_username', false );
	$password = edd_get_option( 'edd_clickatell_connect_password', false );
	$api_id   = edd_get_option( 'edd_clickatell_connect_api_id', false );

	if( $username && $password && $api_id ) {
		$api_base = 'https://api.clickatell.com/http/';

		// Get a session ID from Clickatell
		$session_id = wp_remote_get( $api_base . 'auth?api_id=' . $api_id . '&user=' . $username . '&password=' . $password );
		$session_id = wp_remote_retrieve_body( $session_id );

		if( substr( $session_id, 0, 3 ) === 'OK:' ) {
			$session_id = str_replace( 'OK: ', '', $session_id );
		} else {
			edd_record_gateway_error( __( 'Clickatell Connect Error', 'edd-clickatell-connect' ), $session_id, 0 );
			return;
		}

		$payment_meta = edd_get_payment_meta( $payment_id );
		$user_info    = edd_get_payment_meta_user_info( $payment_id );
		$cart_items   = isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

		if( empty( $cart_items ) || ! $cart_items ) {
			$cart_items = maybe_unserialize( $payment_meta['downloads'] );
		}

		if( $cart_items ) {
			$i = 0;

			$message = __( 'New Order', 'edd-clickatell-connect' ) . ' @ ' . get_bloginfo( 'name' ) . urldecode( '%0a' );

			if( edd_get_option( 'edd_clickatell_connect_itemize' ) ) {
				foreach( $cart_items as $key => $cart_item ) {
					$id             = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
					$price_override = isset( $payment_meta['cart_details'] ) ? $cart_item['price'] : null;
					$price          = edd_get_download_final_price( $id, $user_info, $price_override );

					$message .= get_the_title( $id );

					if( isset( $cart_items[$key]['item_number'] ) ) {
						$price_options = $cart_items[$key]['item_number']['options'];

						if( isset( $price_options['price_id'] ) ) {
							$message .= ' - ' . edd_get_price_option_name( $id, $price_options['price_id'], $payment_id );
						}
					}

					$message .= ' - ' . html_entity_decode( edd_currency_filter( edd_format_amount( $price ) ) ) . urldecode( '%0a' );
				}
			}

			$message .= __( 'TOTAL', 'edd-clickatell-connect' ) . ' - ' . html_entity_decode( edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment_id ) ) ) );

			if( strlen( $message ) > 160 ) {
				$messages = str_split( $message, 140 );
				$max      = count( $messages );
				$count    = 1;

				foreach( $messages as $message ) {
					$message = $count . '/' . $max . urldecode( '%0a' ) . $message;
					$error   = edd_clickatell_connect_send_sms( $session_id, $message );
				}
			} else {
				$error = edd_clickatell_connect_send_sms( $session_id, $message );
			}
		}
	}
}
add_action( 'edd_complete_purchase', 'edd_clickatell_connect_build_sms', 100, 1 );


/**
 * Send an SMS
 *
 * @since       1.0.0
 * @param       string $session_id The Clickatell session ID
 * @param       string $message The message to send
 * @return      void
 */
function edd_clickatell_connect_send_sms( $session_id, $message ) {
	$phone_numbers = edd_get_option( 'edd_clickatell_connect_phone_number', false );
	$two_way       = edd_get_option( 'edd_clickatell_connect_twoway_number', false );
	$api_base      = 'https://api.clickatell.com/http/';

	if( $phone_numbers && $two_way ) {
		$phone_numbers = preg_replace( '/[^0-9,.]/', '', $phone_numbers );
		$result        = wp_remote_get( $api_base . 'sendmsg?session_id=' . $session_id . '&to=' . $phone_numbers . '&from=' . $two_way . '&mo=1&text=' . urlencode( $message ) );
		$result        = wp_remote_retrieve_body( $result );

		if( substr( $result, 0, 4 ) === 'ERR:' ) {
			edd_record_gateway_error( __( 'Clickatell Connect Error', 'edd-clickatell-connect' ), $result, 0 );
			return;
		}
	}
}