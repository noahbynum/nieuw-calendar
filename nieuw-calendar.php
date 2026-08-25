<?php
/**
 * Plugin Name:       Nieuw Calendar
 * Plugin URI:        https://nieuwark.com
 * Description:       A refined events calendar with month and list views, categories, color coding, and iCal export.
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Nieuw Ark
 * Author URI:        https://nieuwark.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       nieuw-calendar
 * Domain Path:       /languages
 *
 * @package NieuwCalendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NIEUW_CALENDAR_VERSION', '1.1.0' );
define( 'NIEUW_CALENDAR_FILE', __FILE__ );
define( 'NIEUW_CALENDAR_DIR', plugin_dir_path( __FILE__ ) );
define( 'NIEUW_CALENDAR_URL', plugin_dir_url( __FILE__ ) );

require_once NIEUW_CALENDAR_DIR . 'includes/helpers.php';
require_once NIEUW_CALENDAR_DIR . 'includes/class-post-type.php';
require_once NIEUW_CALENDAR_DIR . 'includes/class-meta.php';
require_once NIEUW_CALENDAR_DIR . 'includes/class-settings.php';
require_once NIEUW_CALENDAR_DIR . 'includes/class-shortcode.php';
require_once NIEUW_CALENDAR_DIR . 'includes/class-ical.php';
require_once NIEUW_CALENDAR_DIR . 'admin/class-admin.php';

register_activation_hook( __FILE__, 'nieuw_calendar_activate' );
register_deactivation_hook( __FILE__, 'nieuw_calendar_deactivate' );

/**
 * Activate: register CPT/tax and flush rewrites.
 */
function nieuw_calendar_activate() {
	Nieuw_Calendar_Post_Type::register();
	flush_rewrite_rules();
	if ( false === get_option( 'nieuw_calendar_settings' ) ) {
		add_option( 'nieuw_calendar_settings', nieuw_calendar_default_settings() );
	}
}

/**
 * Deactivate: flush rewrites.
 */
function nieuw_calendar_deactivate() {
	flush_rewrite_rules();
}

add_action( 'plugins_loaded', static function () {
	load_plugin_textdomain( 'nieuw-calendar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'init', array( 'Nieuw_Calendar_Post_Type', 'register' ) );
add_action( 'init', array( 'Nieuw_Calendar_Ical', 'register' ) );
add_action( 'init', array( 'Nieuw_Calendar_Shortcode', 'register' ) );
add_action( 'admin_init', array( 'Nieuw_Calendar_Settings', 'register' ) );
add_action( 'admin_init', array( 'Nieuw_Calendar_Admin', 'redirect_native_screens' ) );
add_action( 'admin_menu', array( 'Nieuw_Calendar_Admin', 'menu' ) );
add_action( 'add_meta_boxes', array( 'Nieuw_Calendar_Meta', 'boxes' ) );
add_action( 'save_post_nieuw_event', array( 'Nieuw_Calendar_Meta', 'save' ) );
add_action( 'admin_enqueue_scripts', array( 'Nieuw_Calendar_Admin', 'assets' ) );
add_action( 'wp_enqueue_scripts', array( 'Nieuw_Calendar_Shortcode', 'assets' ) );
add_action( 'nieuw_event_category_add_form_fields', array( 'Nieuw_Calendar_Meta', 'category_add_field' ) );
add_action( 'nieuw_event_category_edit_form_fields', array( 'Nieuw_Calendar_Meta', 'category_edit_field' ) );
add_action( 'created_nieuw_event_category', array( 'Nieuw_Calendar_Meta', 'save_category_color' ) );
add_action( 'edited_nieuw_event_category', array( 'Nieuw_Calendar_Meta', 'save_category_color' ) );
add_filter( 'get_edit_post_link', array( 'Nieuw_Calendar_Admin', 'edit_link' ), 10, 2 );
add_filter( 'parent_file', array( 'Nieuw_Calendar_Admin', 'parent_file' ) );
add_filter( 'submenu_file', array( 'Nieuw_Calendar_Admin', 'submenu_file' ) );
add_filter( 'use_block_editor_for_post_type', array( 'Nieuw_Calendar_Admin', 'disable_block_editor' ), 10, 2 );
