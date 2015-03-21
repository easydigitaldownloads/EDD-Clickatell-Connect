<?php
/**
 * Plugin Name:     Easy Digital Downloads - Clickatell Connect
 * Plugin URI:      https://easydigitaldownloads.com/extensions/clickatell-connect/
 * Description:     Adds better handling for directly downloading free products to EDD
 * Version:         1.0.0
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-clickatell-connect
 *
 * @package         EDD\FreeDownloads
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


if( ! class_exists( 'EDD_Clickatell_Connect' ) ) {


    /**
     * Main EDD_Clickatell_Connect class
     *
     * @since       1.0.0
     */
    class EDD_Clickatell_Connect {


        /**
         * @var         EDD_Clickatell_Connect $instance The one true EDD_Clickatell_Connect
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      self::$instance The one true EDD_Clickatell_Connect
         */
        public static function instance() {
            if( ! self::$instance ) {
                self::$instance = new EDD_Clickatell_Connect();
                self::$instance->setup_constants();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_CLICKATELL_CONNECT_VER', '1.0.0' );
            
            // Plugin path
            define( 'EDD_CLICKATELL_CONNECT_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_CLICKATELL_CONNECT_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Add our extension settings
            add_filter( 'edd_settings_extensions', array( $this, 'add_settings' ) );

            // Build SMS message on purchase
            add_action( 'edd_complete_purchase', array( $this, 'build_sms' ), 100, 1 );

            // Handle licensing
            if( class_exists( 'EDD_License' ) ) {
                $license = new EDD_License( __FILE__, 'Clickatell Connect', EDD_CLICKATELL_CONNECT_VER, 'Daniel J Griffiths' );
            }
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing plugin settings
         * @return      array The modified plugin settings
         */
        public function add_settings( $settings ) {
            $new_settings = array(
                array(
                    'id'    => 'edd_clickatell_connect_settings',
                    'name'  => '<strong>' . __( 'Clickatell Connect Settings', 'edd-clickatell-connect' ) . '</strong>',
                    'desc'  => '',
                    'type'  => 'header'
                ),
                array(
                    'id'    => 'edd_clickatell_connect_username',
                    'name'  => __( 'Username', 'edd-clickatell-connect' ),
                    'desc'  => __( 'Enter your Clickatell username', 'edd-clickatell-connect' ),
                    'type'  => 'text'
                ),
                array(
                    'id'    => 'edd_clickatell_connect_password',
                    'name'  => __( 'Password', 'edd-clickatell-connect' ),
                    'desc'  => __( 'Enter your Clickatell password', 'edd-clickatell-connect' ),
                    'type'  => 'password'
                ),
                array(
                    'id'    => 'edd_clickatell_connect_api_id',
                    'name'  => __( 'API ID', 'edd-clickatell-connect' ),
                    'desc'  => __( 'Enter your Clickatell API ID', 'edd-clickatell-connect' ),
                    'type'  => 'text'
                ),
                array(
                    'id'    => 'edd_clickatell_connect_twoway_number',
                    'name'  => __( 'Two-way Phone Number', 'edd-clickatell-connect' ),
                    'desc'  => sprintf( __( 'Enter your Clickatell <a href="%s" target="_blank">two-way phone number</a>', 'edd-clickatell-connect' ), 'https://central.clickatell.com/twoway/ussb/' ),
                    'type'  => 'text'
                ),
                array(
                    'id'    => 'edd_clickatell_connect_phone_number',
                    'name'  => __( 'Phone Number', 'edd-clickatell-connect' ),
                    'desc'  => __( 'Enter the number(s) you want messages delivered to, comma separated', 'edd-clickatell-connect' ),
                    'type'  => 'text'
                ),
                array(
                    'id'    => 'edd_clickatell_connect_itemize',
                    'name'  => __( 'Itemized Notification', 'edd-clickatell-connect' ),
                    'desc'  => __( 'Select whether or not you want itemized SMS notifications', 'edd-clickatell-connect' ),
                    'type'  => 'checkbox'
                )
            );

            return array_merge( $settings, $new_settings );
        }


        /**
         * Build the message to be passed to Clickatell
         *
         * @access      public
         * @since       1.0.0
         * @param       string $payment_id
         * @return      void
         */
        public function build_sms( $payment_id ) {
            $username = edd_get_option( 'edd_clickatell_connect_username', false );
            $password = edd_get_option( 'edd_clickatell_connect_password', false );
            $api_id = edd_get_option( 'edd_clickatell_connect_api_id', false );

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

                $payment_meta   = edd_get_payment_meta( $payment_id );
                $user_info      = edd_get_payment_meta_user_info( $payment_id );

                $cart_items     = isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

                if( empty( $cart_items ) || ! $cart_items ) {
                    $cart_items = maybe_unserialize( $payment_meta['downloads'] );
                }

                if( $cart_items ) {
                    $i = 0;

                    $message = __( 'New Order', 'edd-clickatell-connect' ) . ' @ ' . get_bloginfo( 'name' ) . urldecode( '%0a' );

                    if( edd_get_option( 'edd_clickatell_connect_itemize' ) ) {
                        foreach( $cart_items as $key => $cart_item ) {
                            $id = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
                            $price_override = isset( $payment_meta['cart_details'] ) ? $cart_item['price'] : null;
                            $price = edd_get_download_final_price( $id, $user_info, $price_override );

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
                        $max = count( $messages );
                        $count = 1;

                        foreach( $messages as $message ) {
                            $message = $count . '/' . $max . urldecode( '%0a' ) . $message;
                            $error = $this->send_sms( $session_id, $message );
                        }
                    } else {
                        $error = $this->send_sms( $session_id, $message );
                    }
                }
            }
        }


        /**
         * Send an SMS
         *
         * @access      public
         * @since       1.0.0
         * @param       string $session_id The Clickatell session ID
         * @param       string $message The message to send
         * @return      void
         */
        public function send_sms( $session_id, $message ) {
            $phone_numbers  = edd_get_option( 'edd_clickatell_connect_phone_number', false );
            $two_way        = edd_get_option( 'edd_clickatell_connect_twoway_number', false );
            $api_base       = 'https://api.clickatell.com/http/';

            if( $phone_numbers && $two_way ) {
                $phone_numbers = preg_replace( '/[^0-9,.]/', '', $phone_numbers );

                $result = wp_remote_get( $api_base . 'sendmsg?session_id=' . $session_id . '&to=' . $phone_numbers . '&from=' . $two_way . '&mo=1&text=' . urlencode( $message ) );
                $result = wp_remote_retrieve_body( $result );

                if( substr( $result, 0, 4 ) === 'ERR:' ) {
                    edd_record_gateway_error( __( 'Clickatell Connect Error', 'edd-clickatell-connect' ), $result, 0 );
                    return;
                }
            }
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
            $lang_dir = apply_filters( 'edd_clickatell_connect_language_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), '' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-clickatell-connect', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-clickatell-connect/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-clickatell-connect/ folder
                load_textdomain( 'edd-clickatell-connect', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-clickatell-connect/ folder
                load_textdomain( 'edd-clickatell-connect', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-clickatell-connect', false, $lang_dir );
            }
        }
    }
}


/**
 * The main function responsible for returning the one true EDD_Clickatell_Connect
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Clickatell_Connect The one true EDD_Clickatell_Connect
 */
function edd_clickatell_connect() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'S214_EDD_Activation' ) ) {
            require_once 'includes/class.s214-edd-activation.php';
        }

        $activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
        
        return EDD_Clickatell_Connect::instance();
    } else {
        return EDD_Clickatell_Connect::instance();
    }
}
add_action( 'plugins_loaded', 'edd_clickatell_connect' );
