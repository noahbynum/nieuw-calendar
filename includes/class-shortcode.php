<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nieuw_Calendar_Shortcode {
	/**
	 * Whether event JSON has been printed this request.
	 *
	 * @var bool
	 */
	private static $localized = false;

	public static function register() {
		add_shortcode( 'nieuw_calendar', array( __CLASS__, 'render' ) );
		add_filter( 'the_content', array( __CLASS__, 'unwrap' ), 9 );
	}

	/**
	 * Keep WordPress from wrapping the shortcode in a paragraph.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function unwrap( $content ) {
		return preg_replace(
			'/<p>\s*(\[nieuw_calendar[^\]]*\])\s*<\/p>/',
			'$1',
			$content
		);
	}

	/**
	 * Whether the current view likely contains the calendar.
	 *
	 * @return bool
	 */
	private static function page_has_calendar() {
		global $post;
		if ( $post instanceof WP_Post ) {
			if ( has_shortcode( $post->post_content, 'nieuw_calendar' ) ) {
				return true;
			}
			$elementor = get_post_meta( $post->ID, '_elementor_data', true );
			if ( is_string( $elementor ) && false !== strpos( $elementor, 'nieuw_calendar' ) ) {
				return true;
			}
		}
		return false;
	}

	public static function assets() {
		wp_register_style(
			'nieuw-calendar',
			NIEUW_CALENDAR_URL . 'public/css/calendar.css',
			array(),
			NIEUW_CALENDAR_VERSION
		);
		wp_register_script(
			'nieuw-calendar',
			NIEUW_CALENDAR_URL . 'public/js/calendar.js',
			array(),
			NIEUW_CALENDAR_VERSION,
			true
		);
		if ( self::page_has_calendar() ) {
			self::enqueue_fonts( nieuw_calendar_get_settings() );
			wp_enqueue_style( 'nieuw-calendar' );
		}
	}

	/**
	 * Load Google Fonts for the chosen pair.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private static function enqueue_fonts( $settings ) {
		$fonts     = nieuw_calendar_fonts();
		$body      = isset( $fonts[ $settings['font_body'] ] ) ? $fonts[ $settings['font_body'] ] : $fonts['figtree'];
		$head      = isset( $fonts[ $settings['font_heading'] ] ) ? $fonts[ $settings['font_heading'] ] : $fonts['fraunces'];
		$families  = array_unique( array( $body['google'], $head['google'] ) );
		$gurl      = 'https://fonts.googleapis.com/css2?' . implode(
			'&',
			array_map(
				static function ( $g ) {
					return 'family=' . $g;
				},
				$families
			)
		) . '&display=swap';
		wp_enqueue_style( 'nieuw-calendar-fonts', $gurl, array(), null );
	}

	/**
	 * Shared payload + asset enqueue.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private static function enqueue( $settings ) {
		self::enqueue_fonts( $settings );
		wp_enqueue_style( 'nieuw-calendar' );
		wp_enqueue_script( 'nieuw-calendar' );
		if ( self::$localized ) {
			return;
		}
		self::$localized = true;

		$events = nieuw_calendar_get_events();
		$terms  = get_terms(
			array(
				'taxonomy'   => 'nieuw_event_category',
				'hide_empty' => false,
			)
		);
		$cats = array();
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_color = (string) get_term_meta( $term->term_id, 'nieuw_category_color', true );
				$cats[]     = array(
					'id'    => $term->term_id,
					'slug'  => $term->slug,
					'name'  => $term->name,
					'color' => $term_color ? $term_color : $settings['primary'],
				);
			}
		}

		$payload = array(
			'events'     => $events,
			'categories' => $cats,
			'settings'   => $settings,
			'ical'       => home_url( '/?nieuw_calendar_ical=1' ),
			'i18n'       => array(
				'month'      => __( 'Month', 'nieuw-calendar' ),
				'list'       => __( 'List', 'nieuw-calendar' ),
				'all'        => __( 'All', 'nieuw-calendar' ),
				'prev'       => __( 'Previous month', 'nieuw-calendar' ),
				'next'       => __( 'Next month', 'nieuw-calendar' ),
				'close'      => __( 'Close', 'nieuw-calendar' ),
				'allDay'     => __( 'All day', 'nieuw-calendar' ),
				'more'       => __( 'more', 'nieuw-calendar' ),
				'times'      => __( 'Times shown in', 'nieuw-calendar' ),
				'custom'     => __( 'Custom color', 'nieuw-calendar' ),
				'emptyTitle' => __( 'No events in this view', 'nieuw-calendar' ),
				'emptyBody'  => __( 'Try another category, or add an event from the dashboard.', 'nieuw-calendar' ),
			),
		);

		wp_add_inline_script(
			'nieuw-calendar',
			'window.NieuwCalendar = ' . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . ';',
			'before'
		);
	}

	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'view' => 'month',
			),
			$atts,
			'nieuw_calendar'
		);
		$view     = 'list' === $atts['view'] ? 'list' : 'month';
		$settings = nieuw_calendar_get_settings();
		$fonts    = nieuw_calendar_fonts();
		$body     = isset( $fonts[ $settings['font_body'] ] ) ? $fonts[ $settings['font_body'] ] : $fonts['figtree'];
		$head     = isset( $fonts[ $settings['font_heading'] ] ) ? $fonts[ $settings['font_heading'] ] : $fonts['fraunces'];

		self::enqueue( $settings );

		$style = sprintf(
			'--nc-primary:%1$s;--nc-secondary:%2$s;--nc-text:%3$s;--nc-bg:%4$s;--nc-header:%5$s;--nc-header-text:%6$s;--nc-border:%7$s;--nc-button:%8$s;--nc-button-text:%9$s;--nc-radius:%10$s;--nc-font:%11$s;--nc-font-heading:%12$s;--nc-surface:#fbf8f2;--nc-muted:#6e675e;',
			esc_attr( nieuw_calendar_rgba( $settings['primary'], $settings['primary_opacity'] ) ),
			esc_attr( nieuw_calendar_rgba( $settings['secondary'], $settings['secondary_opacity'] ) ),
			esc_attr( nieuw_calendar_rgba( $settings['text'], $settings['text_opacity'] ) ),
			esc_attr( nieuw_calendar_rgba( $settings['background'], $settings['background_opacity'] ) ),
			esc_attr( nieuw_calendar_rgba( $settings['header'], $settings['header_opacity'] ) ),
			esc_attr( nieuw_calendar_rgba( $settings['header_text'], $settings['header_text_opacity'] ) ),
			esc_attr( nieuw_calendar_rgba( $settings['border'], $settings['border_opacity'] ) ),
			esc_attr( nieuw_calendar_rgba( $settings['button'], $settings['button_opacity'] ) ),
			esc_attr( nieuw_calendar_rgba( $settings['button_text'], $settings['button_text_opacity'] ) ),
			esc_attr( (string) $settings['border_radius'] ) . 'px',
			esc_attr( $body['family'] ),
			esc_attr( $head['family'] )
		);

		return sprintf(
			'<div class="nieuw-calendar" data-view="%1$s" style="%2$s"></div>',
			esc_attr( $view ),
			$style
		);
	}
}
