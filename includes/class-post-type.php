<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nieuw_Calendar_Post_Type {
	public static function register() {
		register_post_type(
			'nieuw_event',
			array(
				'labels'             => array(
					'name'               => __( 'Events', 'nieuw-calendar' ),
					'singular_name'      => __( 'Event', 'nieuw-calendar' ),
					'add_new'            => __( 'Add Event', 'nieuw-calendar' ),
					'add_new_item'       => __( 'Add New Event', 'nieuw-calendar' ),
					'edit_item'          => __( 'Edit Event', 'nieuw-calendar' ),
					'new_item'           => __( 'New Event', 'nieuw-calendar' ),
					'view_item'          => __( 'View Event', 'nieuw-calendar' ),
					'search_items'       => __( 'Search Events', 'nieuw-calendar' ),
					'not_found'          => __( 'No events found', 'nieuw-calendar' ),
					'not_found_in_trash' => __( 'No events found in Trash', 'nieuw-calendar' ),
					'all_items'          => __( 'Events', 'nieuw-calendar' ),
					'menu_name'          => __( 'Nieuw Calendar', 'nieuw-calendar' ),
				),
				'public'             => true,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_admin_bar'  => false,
				'show_in_rest'       => true,
				// No archive at /events/ — that collides with a normal Events page.
				// Front-end calendar is rendered via [nieuw_calendar] shortcode.
				'has_archive'        => false,
				'rewrite'            => array( 'slug' => 'nieuw-event' ),
				'supports'           => array( 'title', 'editor', 'thumbnail' ),
				'menu_icon'          => 'dashicons-calendar-alt',
				'capability_type'    => 'post',
			)
		);

		register_taxonomy(
			'nieuw_event_category',
			'nieuw_event',
			array(
				'labels'            => array(
					'name'          => __( 'Event Categories', 'nieuw-calendar' ),
					'singular_name' => __( 'Event Category', 'nieuw-calendar' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'event-category' ),
			)
		);
	}
}
