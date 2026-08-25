<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nieuw_Calendar_Settings {
	public static function register() {
		register_setting(
			'nieuw_calendar',
			'nieuw_calendar_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => nieuw_calendar_default_settings(),
			)
		);
	}

	/**
	 * @param mixed $input Raw settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		$defaults = nieuw_calendar_default_settings();
		if ( ! is_array( $input ) ) {
			return $defaults;
		}
		$out                     = $defaults;
		$fonts                   = array_keys( nieuw_calendar_fonts() );
		$out['primary']          = self::hex( $input['primary'] ?? '', $defaults['primary'] );
		$out['secondary']        = self::hex( $input['secondary'] ?? '', $defaults['secondary'] );
		$out['text']             = self::hex( $input['text'] ?? '', $defaults['text'] );
		$out['background']       = self::hex( $input['background'] ?? '', $defaults['background'] );
		$out['header']           = self::hex( $input['header'] ?? '', $defaults['header'] );
		$out['header_text']      = self::hex( $input['header_text'] ?? '', $defaults['header_text'] );
		$out['border']           = self::hex( $input['border'] ?? '', $defaults['border'] );
		$out['button']           = self::hex( $input['button'] ?? '', $defaults['button'] );
		$out['button_text']      = self::hex( $input['button_text'] ?? '', $defaults['button_text'] );
		foreach ( array( 'primary', 'secondary', 'text', 'background', 'header', 'header_text', 'border', 'button', 'button_text' ) as $key ) {
			$out[ $key . '_opacity' ] = max( 0, min( 100, absint( $input[ $key . '_opacity' ] ?? 100 ) ) );
		}
		$body = sanitize_key( $input['font_body'] ?? $defaults['font_body'] );
		$head = sanitize_key( $input['font_heading'] ?? $defaults['font_heading'] );
		$out['font_body']        = in_array( $body, $fonts, true ) ? $body : $defaults['font_body'];
		$out['font_heading']     = in_array( $head, $fonts, true ) ? $head : $defaults['font_heading'];
		$out['border_radius']    = max( 0, min( 28, absint( $input['border_radius'] ?? 12 ) ) );
		$tz                      = sanitize_text_field( $input['timezone'] ?? $defaults['timezone'] );
		$out['timezone']         = $tz ? $tz : $defaults['timezone'];
		$out['first_day']        = ( isset( $input['first_day'] ) && '1' === (string) $input['first_day'] ) ? 1 : 0;
		$out['time_format']      = ( isset( $input['time_format'] ) && '24' === (string) $input['time_format'] ) ? '24' : '12';
		$out['show_past_events'] = empty( $input['show_past_events'] ) ? 0 : 1;
		return $out;
	}

	private static function hex( $value, $fallback ) {
		$clean = sanitize_hex_color( $value );
		return $clean ? $clean : $fallback;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = nieuw_calendar_get_settings();
		$zones = timezone_identifiers_list();
		?>
		<div class="wrap nieuw-admin">
			<h1><?php esc_html_e( 'Nieuw Calendar Settings', 'nieuw-calendar' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'nieuw_calendar' ); ?>
				<h2><?php esc_html_e( 'Typography', 'nieuw-calendar' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="nc_font_body"><?php esc_html_e( 'Body font', 'nieuw-calendar' ); ?></label></th>
						<td>
							<select id="nc_font_body" name="nieuw_calendar_settings[font_body]">
								<?php foreach ( nieuw_calendar_fonts() as $id => $font ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['font_body'], $id ); ?>><?php echo esc_html( $font['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="nc_font_heading"><?php esc_html_e( 'Heading font', 'nieuw-calendar' ); ?></label></th>
						<td>
							<select id="nc_font_heading" name="nieuw_calendar_settings[font_heading]">
								<?php foreach ( nieuw_calendar_fonts() as $id => $font ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['font_heading'], $id ); ?>><?php echo esc_html( $font['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Appearance', 'nieuw-calendar' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::color_row( $s, 'primary', __( 'Primary', 'nieuw-calendar' ) );
					self::color_row( $s, 'secondary', __( 'Secondary', 'nieuw-calendar' ) );
					self::color_row( $s, 'text', __( 'Text', 'nieuw-calendar' ) );
					self::color_row( $s, 'background', __( 'Background', 'nieuw-calendar' ) );
					self::color_row( $s, 'header', __( 'Calendar header', 'nieuw-calendar' ) );
					self::color_row( $s, 'header_text', __( 'Header text', 'nieuw-calendar' ) );
					self::color_row( $s, 'border', __( 'Borders', 'nieuw-calendar' ) );
					self::color_row( $s, 'button', __( 'Buttons', 'nieuw-calendar' ) );
					self::color_row( $s, 'button_text', __( 'Button text', 'nieuw-calendar' ) );
					?>
					<tr>
						<th><label for="nc_radius"><?php esc_html_e( 'Border radius', 'nieuw-calendar' ); ?></label></th>
						<td>
							<input type="range" id="nc_radius" name="nieuw_calendar_settings[border_radius]" min="0" max="28" value="<?php echo esc_attr( $s['border_radius'] ); ?>" />
							<span><?php echo esc_html( (string) $s['border_radius'] ); ?>px</span>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Timezone & regional', 'nieuw-calendar' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="nc_tz"><?php esc_html_e( 'Timezone', 'nieuw-calendar' ); ?></label></th>
						<td>
							<select id="nc_tz" name="nieuw_calendar_settings[timezone]">
								<?php foreach ( $zones as $zone ) : ?>
									<option value="<?php echo esc_attr( $zone ); ?>" <?php selected( $s['timezone'], $zone ); ?>><?php echo esc_html( $zone ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="nc_first"><?php esc_html_e( 'First day of week', 'nieuw-calendar' ); ?></label></th>
						<td>
							<select id="nc_first" name="nieuw_calendar_settings[first_day]">
								<option value="0" <?php selected( (int) $s['first_day'], 0 ); ?>><?php esc_html_e( 'Sunday', 'nieuw-calendar' ); ?></option>
								<option value="1" <?php selected( (int) $s['first_day'], 1 ); ?>><?php esc_html_e( 'Monday', 'nieuw-calendar' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="nc_tf"><?php esc_html_e( 'Time format', 'nieuw-calendar' ); ?></label></th>
						<td>
							<select id="nc_tf" name="nieuw_calendar_settings[time_format]">
								<option value="12" <?php selected( $s['time_format'], '12' ); ?>><?php esc_html_e( '12-hour', 'nieuw-calendar' ); ?></option>
								<option value="24" <?php selected( $s['time_format'], '24' ); ?>><?php esc_html_e( '24-hour', 'nieuw-calendar' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Past events', 'nieuw-calendar' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nieuw_calendar_settings[show_past_events]" value="1" <?php checked( ! empty( $s['show_past_events'] ) ); ?> />
								<?php esc_html_e( 'Show past events on the public calendar', 'nieuw-calendar' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p class="description"><?php esc_html_e( 'Place the calendar with the shortcode [nieuw_calendar]. Use [nieuw_calendar view="list"] for list view.', 'nieuw-calendar' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Color + opacity row.
	 *
	 * @param array<string, mixed> $s Settings.
	 * @param string               $key Field key.
	 * @param string               $label Label.
	 */
	private static function color_row( $s, $key, $label ) {
		$hex = isset( $s[ $key ] ) ? $s[ $key ] : '#000000';
		$op  = isset( $s[ $key . '_opacity' ] ) ? (int) $s[ $key . '_opacity' ] : 100;
		?>
		<tr>
			<th><label for="nc_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="color" id="nc_<?php echo esc_attr( $key ); ?>" name="nieuw_calendar_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $hex ); ?>" />
				<label>
					<?php esc_html_e( 'Opacity', 'nieuw-calendar' ); ?>
					<input type="range" min="0" max="100" name="nieuw_calendar_settings[<?php echo esc_attr( $key ); ?>_opacity]" value="<?php echo esc_attr( (string) $op ); ?>" />
					<?php echo esc_html( (string) $op ); ?>%
				</label>
			</td>
		</tr>
		<?php
	}
}
