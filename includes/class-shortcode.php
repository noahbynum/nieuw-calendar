<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nieuw_Calendar_Shortcode {
	public static function register() {
		add_shortcode( 'nieuw_calendar', array( __CLASS__, 'render' ) );
	}

	public static function assets() {
		if ( ! is_singular() && ! is_post_type_archive( 'nieuw_event' ) ) {
			return;
		}
		global $post;
		$has = $post && has_shortcode( $post->post_content, 'nieuw_calendar' );
		if ( ! $has && ! is_post_type_archive( 'nieuw_event' ) ) {
			return;
		}
		wp_enqueue_style(
			'nieuw-calendar',
			NIEUW_CALENDAR_URL . 'public/css/calendar.css',
			array(),
			NIEUW_CALENDAR_VERSION
		);
		wp_enqueue_script(
			'nieuw-calendar',
			NIEUW_CALENDAR_URL . 'public/js/calendar.js',
			array(),
			NIEUW_CALENDAR_VERSION,
			true
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
		$view = 'list' === $atts['view'] ? 'list' : 'month';
		$settings = nieuw_calendar_get_settings();
		$events   = nieuw_calendar_get_events();
		$terms    = get_terms(
			array(
				'taxonomy'   => 'nieuw_event_category',
				'hide_empty' => false,
			)
		);
		$cats = array();
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
		$ical = home_url( '/?nieuw_calendar_ical=1' );
		$fonts = nieuw_calendar_fonts();
		$body  = isset( $fonts[ $settings['font_body'] ] ) ? $fonts[ $settings['font_body'] ] : $fonts['figtree'];
		$head  = isset( $fonts[ $settings['font_heading'] ] ) ? $fonts[ $settings['font_heading'] ] : $fonts['fraunces'];
		$families = array_unique( array( $body['google'], $head['google'] ) );
		$gurl = 'https://fonts.googleapis.com/css2?' . implode( '&', array_map( static function ( $g ) {
			return 'family=' . $g;
		}, $families ) ) . '&display=swap';
		wp_enqueue_style( 'nieuw-calendar-fonts', $gurl, array(), NIEUW_CALENDAR_VERSION );
		wp_enqueue_style( 'nieuw-calendar', NIEUW_CALENDAR_URL . 'public/css/calendar.css', array( 'nieuw-calendar-fonts' ), NIEUW_CALENDAR_VERSION );
		wp_enqueue_script( 'nieuw-calendar', NIEUW_CALENDAR_URL . 'public/js/calendar.js', array(), NIEUW_CALENDAR_VERSION, true );
		wp_localize_script(
			'nieuw-calendar',
			'NieuwCalendar',
			array(
				'events'   => $events,
				'categories' => $cats,
				'settings' => $settings,
				'ical'     => $ical,
				'i18n'     => array(
					'month' => __( 'Month', 'nieuw-calendar' ),
					'list'  => __( 'List', 'nieuw-calendar' ),
					'all'   => __( 'All', 'nieuw-calendar' ),
					'sub'   => __( 'Subscribe .ics', 'nieuw-calendar' ),
				),
			)
		);
		ob_start();
		?>
		<div class="nieuw-calendar" data-view="<?php echo esc_attr( $view ); ?>" style="
			--nc-primary: <?php echo esc_attr( nieuw_calendar_rgba( $settings['primary'], $settings['primary_opacity'] ) ); ?>;
			--nc-secondary: <?php echo esc_attr( nieuw_calendar_rgba( $settings['secondary'], $settings['secondary_opacity'] ) ); ?>;
			--nc-text: <?php echo esc_attr( nieuw_calendar_rgba( $settings['text'], $settings['text_opacity'] ) ); ?>;
			--nc-bg: <?php echo esc_attr( nieuw_calendar_rgba( $settings['background'], $settings['background_opacity'] ) ); ?>;
			--nc-header: <?php echo esc_attr( nieuw_calendar_rgba( $settings['header'], $settings['header_opacity'] ) ); ?>;
			--nc-header-text: <?php echo esc_attr( nieuw_calendar_rgba( $settings['header_text'], $settings['header_text_opacity'] ) ); ?>;
			--nc-border: <?php echo esc_attr( nieuw_calendar_rgba( $settings['border'], $settings['border_opacity'] ) ); ?>;
			--nc-button: <?php echo esc_attr( nieuw_calendar_rgba( $settings['button'], $settings['button_opacity'] ) ); ?>;
			--nc-button-text: <?php echo esc_attr( nieuw_calendar_rgba( $settings['button_text'], $settings['button_text_opacity'] ) ); ?>;
			--nc-radius: <?php echo esc_attr( (string) $settings['border_radius'] ); ?>px;
			--nc-font: <?php echo esc_attr( $body['family'] ); ?>;
			--nc-font-heading: <?php echo esc_attr( $head['family'] ); ?>;
		"></div>
		<?php
		return ob_get_clean();
	}
}
