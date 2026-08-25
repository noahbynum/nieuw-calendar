<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default plugin settings.
 *
 * @return array<string, mixed>
 */
function nieuw_calendar_default_settings() {
	$tz = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC';
	return array(
		'primary'              => '#2f5d50',
		'primary_opacity'      => 100,
		'secondary'            => '#3d5a6c',
		'secondary_opacity'    => 100,
		'text'                 => '#1c1915',
		'text_opacity'         => 100,
		'background'           => '#f4efe6',
		'background_opacity'   => 100,
		'header'               => '#ece4d6',
		'header_opacity'       => 100,
		'header_text'          => '#6e675e',
		'header_text_opacity'  => 100,
		'border'               => '#e0d6c6',
		'border_opacity'       => 100,
		'button'               => '#1c1915',
		'button_opacity'       => 100,
		'button_text'          => '#f4efe6',
		'button_text_opacity'  => 100,
		'font_body'            => 'figtree',
		'font_heading'         => 'fraunces',
		'border_radius'        => 12,
		'timezone'             => $tz ? $tz : 'UTC',
		'first_day'            => 0,
		'time_format'          => '12',
		'show_past_events'     => 1,
	);
}

/**
 * Available fonts.
 *
 * @return array<string, array{label:string,family:string,google:string}>
 */
function nieuw_calendar_fonts() {
	return array(
		'figtree'      => array( 'label' => 'Figtree', 'family' => '"Figtree", sans-serif', 'google' => 'Figtree:wght@400;500;600' ),
		'poppins'      => array( 'label' => 'Poppins', 'family' => '"Poppins", sans-serif', 'google' => 'Poppins:wght@400;500;600' ),
		'montserrat'   => array( 'label' => 'Montserrat', 'family' => '"Montserrat", sans-serif', 'google' => 'Montserrat:wght@400;500;600' ),
		'roboto'       => array( 'label' => 'Roboto', 'family' => '"Roboto", sans-serif', 'google' => 'Roboto:wght@400;500;700' ),
		'open-sans'    => array( 'label' => 'Open Sans', 'family' => '"Open Sans", sans-serif', 'google' => 'Open+Sans:wght@400;500;600' ),
		'lato'         => array( 'label' => 'Lato', 'family' => '"Lato", sans-serif', 'google' => 'Lato:wght@400;700' ),
		'inter'        => array( 'label' => 'Inter', 'family' => '"Inter", sans-serif', 'google' => 'Inter:wght@400;500;600' ),
		'source-serif' => array( 'label' => 'Source Serif', 'family' => '"Source Serif 4", Georgia, serif', 'google' => 'Source+Serif+4:opsz,wght@8..60,400;8..60,600' ),
		'playfair'     => array( 'label' => 'Playfair Display', 'family' => '"Playfair Display", Georgia, serif', 'google' => 'Playfair+Display:wght@500;600' ),
		'fraunces'     => array( 'label' => 'Fraunces', 'family' => '"Fraunces", Georgia, serif', 'google' => 'Fraunces:opsz,wght@9..144,500;9..144,600' ),
	);
}

/**
 * Hex + opacity to CSS color.
 *
 * @param string $hex Hex.
 * @param int    $opacity 0-100.
 * @return string
 */
function nieuw_calendar_rgba( $hex, $opacity = 100 ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return '#1c1915';
	}
	$opacity = max( 0, min( 100, absint( $opacity ) ) );
	$r       = hexdec( substr( $hex, 0, 2 ) );
	$g       = hexdec( substr( $hex, 2, 2 ) );
	$b       = hexdec( substr( $hex, 4, 2 ) );
	if ( 100 === $opacity ) {
		return '#' . $hex;
	}
	return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . ( $opacity / 100 ) . ')';
}

/**
 * Get merged settings.
 *
 * @return array<string, mixed>
 */
function nieuw_calendar_get_settings() {
	$saved = get_option( 'nieuw_calendar_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, nieuw_calendar_default_settings() );
}

/**
 * Contrast text for a hex background.
 *
 * @param string $hex Hex color.
 * @return string
 */
function nieuw_calendar_contrast_text( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return '#1c1915';
	}
	$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
	$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
	$b = hexdec( substr( $hex, 4, 2 ) ) / 255;
	$lin = static function ( $c ) {
		return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	};
	$l = 0.2126 * $lin( $r ) + 0.7152 * $lin( $g ) + 0.0722 * $lin( $b );
	return $l > 0.55 ? '#1c1915' : '#fbf8f2';
}

/**
 * Resolve event color: event > first category > global primary.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function nieuw_calendar_event_color( $post_id ) {
	$event_color = get_post_meta( $post_id, '_nieuw_event_color', true );
	if ( $event_color ) {
		return $event_color;
	}
	$terms = get_the_terms( $post_id, 'nieuw_event_category' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$c = get_term_meta( $term->term_id, 'nieuw_category_color', true );
			if ( $c ) {
				return $c;
			}
		}
	}
	$settings = nieuw_calendar_get_settings();
	return $settings['primary'];
}

/**
 * Allowed event statuses (demo labels → WP post_status).
 *
 * @return array<string, string>
 */
function nieuw_calendar_statuses() {
	return array(
		'publish' => __( 'Published', 'nieuw-calendar' ),
		'draft'   => __( 'Draft', 'nieuw-calendar' ),
		'pending' => __( 'Pending', 'nieuw-calendar' ),
		'private' => __( 'Private', 'nieuw-calendar' ),
	);
}

/**
 * Format a wall-clock time for admin lists.
 *
 * @param string $time H:i.
 * @param string $format 12 or 24.
 * @return string
 */
function nieuw_calendar_format_time( $time, $format = '12' ) {
	if ( ! $time ) {
		return '';
	}
	$parts = explode( ':', $time );
	$h     = isset( $parts[0] ) ? (int) $parts[0] : 0;
	$m     = isset( $parts[1] ) ? (int) $parts[1] : 0;
	if ( '24' === (string) $format ) {
		return sprintf( '%02d:%02d', $h, $m );
	}
	$suffix = $h >= 12 ? 'PM' : 'AM';
	$hour   = $h % 12;
	if ( 0 === $hour ) {
		$hour = 12;
	}
	return $hour . ':' . sprintf( '%02d', $m ) . ' ' . $suffix;
}

/**
 * Human "when" string matching the demo list.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function nieuw_calendar_format_when( $post_id ) {
	$settings   = nieuw_calendar_get_settings();
	$all_day    = (bool) get_post_meta( $post_id, '_nieuw_event_all_day', true );
	$start_date = (string) get_post_meta( $post_id, '_nieuw_event_start_date', true );
	$end_date   = (string) get_post_meta( $post_id, '_nieuw_event_end_date', true );
	$start_time = (string) get_post_meta( $post_id, '_nieuw_event_start_time', true );
	$end_time   = (string) get_post_meta( $post_id, '_nieuw_event_end_time', true );
	if ( ! $start_date ) {
		return '—';
	}
	if ( ! $end_date ) {
		$end_date = $start_date;
	}
	$start_ts = strtotime( $start_date . ' 00:00:00' );
	$end_ts   = strtotime( $end_date . ' 00:00:00' );
	if ( ! $start_ts ) {
		return '—';
	}
	$start_label = wp_date( 'M j, Y', $start_ts );
	$end_label   = $end_ts ? wp_date( 'M j, Y', $end_ts ) : $start_label;
	$multi       = $end_date !== $start_date;
	if ( $all_day ) {
		return $multi ? $start_label . ' – ' . $end_label : $start_label . ' · ' . __( 'All day', 'nieuw-calendar' );
	}
	$start_t = nieuw_calendar_format_time( $start_time, $settings['time_format'] );
	$end_t   = nieuw_calendar_format_time( $end_time, $settings['time_format'] );
	if ( $multi ) {
		return trim( $start_label . ' ' . $start_t . ' – ' . $end_label . ' ' . $end_t );
	}
	if ( $start_t && $end_t ) {
		return $start_label . ' · ' . $start_t . ' – ' . $end_t;
	}
	if ( $start_t ) {
		return $start_label . ' · ' . $start_t;
	}
	return $start_label;
}

/**
 * Build a public event payload.
 *
 * @param WP_Post $post Post.
 * @return array<string, mixed>|null
 */
function nieuw_calendar_event_payload( $post ) {
	if ( 'publish' !== $post->post_status ) {
		return null;
	}
	$all_day    = (bool) get_post_meta( $post->ID, '_nieuw_event_all_day', true );
	$start_date = (string) get_post_meta( $post->ID, '_nieuw_event_start_date', true );
	$end_date   = (string) get_post_meta( $post->ID, '_nieuw_event_end_date', true );
	if ( ! $start_date ) {
		return null;
	}
	if ( ! $end_date ) {
		$end_date = $start_date;
	}
	$terms = get_the_terms( $post->ID, 'nieuw_event_category' );
	$cats  = array();
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$cats[] = array(
				'id'    => $term->term_id,
				'slug'  => $term->slug,
				'name'  => $term->name,
				'color' => get_term_meta( $term->term_id, 'nieuw_category_color', true ),
			);
		}
	}
	$thumb = get_the_post_thumbnail_url( $post, 'medium_large' );
	return array(
		'id'          => $post->ID,
		'title'       => get_the_title( $post ),
		'description' => wp_strip_all_tags( $post->post_content ),
		'startDate'   => $start_date,
		'startTime'   => (string) get_post_meta( $post->ID, '_nieuw_event_start_time', true ),
		'endDate'     => $end_date,
		'endTime'     => (string) get_post_meta( $post->ID, '_nieuw_event_end_time', true ),
		'allDay'      => $all_day,
		'location'    => (string) get_post_meta( $post->ID, '_nieuw_event_location', true ),
		'color'       => nieuw_calendar_event_color( $post->ID ),
		'url'         => get_permalink( $post ),
		'image'       => $thumb ? $thumb : '',
		'categories'  => $cats,
	);
}

/**
 * Query published events.
 *
 * @return array<int, array<string, mixed>>
 */
function nieuw_calendar_get_events() {
	$q = new WP_Query(
		array(
			'post_type'      => 'nieuw_event',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'meta_key'       => '_nieuw_event_start_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		)
	);
	$out = array();
	foreach ( $q->posts as $post ) {
		$payload = nieuw_calendar_event_payload( $post );
		if ( $payload ) {
			$out[] = $payload;
		}
	}
	return $out;
}
