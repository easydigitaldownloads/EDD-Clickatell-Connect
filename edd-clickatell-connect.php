<?php
/**
 * Plugin Name:     Easy Digital Downloads - Clickatell Connect
 * Plugin URI:      https://section214.com/product/edd-clickatell-connect/
 * Description:     Get real-time SMS notifications from Clickatell when you make sales!
 * Version:         1.0.2
 * Author:          Daniel J Griffiths
 * Author URI:      https://section214.com
 * Text Domain:     edd-clickatell-connect
 *
 * @package         EDD\ClickatellConnect
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
		 * @var         bool $debugging Whether or not debugging is available
		 * @since       1.0.2
		 */
		public $debugging = false;


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
				self::$instance->includes();
				self::$instance->hooks();

				if( class_exists( 'S214_Debug' ) ) {
					if( edd_get_option( 'edd_clickatell_connect_enable_debug', false ) ) {
						self::$instance->debugging = true;
					}
				}
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
			define( 'EDD_CLICKATELL_CONNECT_VER', '1.0.2' );

			// Plugin path
			define( 'EDD_CLICKATELL_CONNECT_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'EDD_CLICKATELL_CONNECT_URL', plugin_dir_url( __FILE__ ) );
        }


		/**
		 * Include required files
		 *
		 * @access      public
		 * @since       1.0.1
		 * @return      void
		 */
		public function includes() {
			require_once EDD_CLICKATELL_CONNECT_DIR . 'includes/functions.php';

			if( is_admin() ) {
				require_once EDD_CLICKATELL_CONNECT_DIR . 'includes/admin/settings/register.php';
				require_once EDD_CLICKATELL_CONNECT_DIR . 'includes/libraries/S214_License_Field.php';
			}

			if( ! class_exists( 'S214_Plugin_Updater' ) ) {
				require_once EDD_CLICKATELL_CONNECT_DIR . 'includes/libraries/S214_Plugin_Updater.php';
			}
		}


		/**
		 * Run action and filter hooks
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function hooks() {
			// Handle licensing
			$license = edd_get_option( 'edd_clickatell_connect_license', false );

			if( $license ) {
				$update = new S214_Plugin_Updater( 'https://section214.com', __FILE__, array(
					'version' => EDD_CLICKATELL_CONNECT_VER,
					'license' => $license,
					'item_id' => 842,
					'author'  => 'Daniel J Griffiths'
				) );
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
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-clickatell-connect/' . $mofile;

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
