<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nieuw_Calendar_Admin {
	public static function menu() {
		add_menu_page(
			__( 'Nieuw Calendar', 'nieuw-calendar' ),
			__( 'Nieuw Calendar', 'nieuw-calendar' ),
			'edit_posts',
			'edit.php?post_type=nieuw_event',
			'',
			'dashicons-calendar-alt',
			26
		);
		add_submenu_page(
			'edit.php?post_type=nieuw_event',
			__( 'Events', 'nieuw-calendar' ),
			__( 'Events', 'nieuw-calendar' ),
			'edit_posts',
			'edit.php?post_type=nieuw_event'
		);
		add_submenu_page(
			'edit.php?post_type=nieuw_event',
			__( 'Add Event', 'nieuw-calendar' ),
			__( 'Add Event', 'nieuw-calendar' ),
			'edit_posts',
			'post-new.php?post_type=nieuw_event'
		);
		add_submenu_page(
			'edit.php?post_type=nieuw_event',
			__( 'Categories', 'nieuw-calendar' ),
			__( 'Categories', 'nieuw-calendar' ),
			'manage_categories',
			'edit-tags.php?taxonomy=nieuw_event_category&post_type=nieuw_event'
		);
		add_submenu_page(
			'edit.php?post_type=nieuw_event',
			__( 'Settings', 'nieuw-calendar' ),
			__( 'Settings', 'nieuw-calendar' ),
			'manage_options',
			'nieuw-calendar-settings',
			array( 'Nieuw_Calendar_Settings', 'render' )
		);
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$ok = in_array( $screen->post_type, array( 'nieuw_event' ), true ) || false !== strpos( $hook, 'nieuw-calendar' );
		if ( ! $ok ) {
			return;
		}
		wp_enqueue_style(
			'nieuw-calendar-admin',
			NIEUW_CALENDAR_URL . 'admin/css/admin.css',
			array(),
			NIEUW_CALENDAR_VERSION
		);
		wp_enqueue_script(
			'nieuw-calendar-admin',
			NIEUW_CALENDAR_URL . 'admin/js/admin.js',
			array(),
			NIEUW_CALENDAR_VERSION,
			true
		);
	}
}

add_action( 'admin_footer-edit.php', static function () {
	$screen = get_current_screen();
	if ( $screen && 'nieuw_event' === $screen->post_type ) {
		echo '<p class="nieuw-branding">' . esc_html__( 'Nieuw Calendar by Nieuw Ark', 'nieuw-calendar' ) . '</p>';
	}
} );
