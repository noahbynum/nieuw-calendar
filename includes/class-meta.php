<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nieuw_Calendar_Meta {
	public static function boxes() {
		add_meta_box(
			'nieuw_event_details',
			__( 'Event details', 'nieuw-calendar' ),
			array( __CLASS__, 'render' ),
			'nieuw_event',
			'normal',
			'high'
		);
	}

	public static function render( $post ) {
		wp_nonce_field( 'nieuw_event_save', 'nieuw_event_nonce' );
		$all_day    = (bool) get_post_meta( $post->ID, '_nieuw_event_all_day', true );
		$start_date = get_post_meta( $post->ID, '_nieuw_event_start_date', true );
		$start_time = get_post_meta( $post->ID, '_nieuw_event_start_time', true );
		$end_date   = get_post_meta( $post->ID, '_nieuw_event_end_date', true );
		$end_time   = get_post_meta( $post->ID, '_nieuw_event_end_time', true );
		$location   = get_post_meta( $post->ID, '_nieuw_event_location', true );
		$color      = get_post_meta( $post->ID, '_nieuw_event_color', true );
		?>
		<p>
			<label>
				<input type="checkbox" name="nieuw_event_all_day" id="nieuw_event_all_day" value="1" <?php checked( $all_day ); ?> />
				<?php esc_html_e( 'All-day event', 'nieuw-calendar' ); ?>
			</label>
		</p>
		<div class="nieuw-grid">
			<p>
				<label for="nieuw_event_start_date"><?php esc_html_e( 'Start date', 'nieuw-calendar' ); ?></label>
				<input type="date" class="widefat" id="nieuw_event_start_date" name="nieuw_event_start_date" value="<?php echo esc_attr( $start_date ); ?>" required />
			</p>
			<p class="nieuw-time-field">
				<label for="nieuw_event_start_time"><?php esc_html_e( 'Start time', 'nieuw-calendar' ); ?></label>
				<input type="time" class="widefat" id="nieuw_event_start_time" name="nieuw_event_start_time" value="<?php echo esc_attr( $start_time ); ?>" />
			</p>
			<p>
				<label for="nieuw_event_end_date"><?php esc_html_e( 'End date', 'nieuw-calendar' ); ?></label>
				<input type="date" class="widefat" id="nieuw_event_end_date" name="nieuw_event_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
			</p>
			<p class="nieuw-time-field">
				<label for="nieuw_event_end_time"><?php esc_html_e( 'End time', 'nieuw-calendar' ); ?></label>
				<input type="time" class="widefat" id="nieuw_event_end_time" name="nieuw_event_end_time" value="<?php echo esc_attr( $end_time ); ?>" />
			</p>
		</div>
		<p>
			<label for="nieuw_event_location"><?php esc_html_e( 'Location / venue', 'nieuw-calendar' ); ?></label>
			<input type="text" class="widefat" id="nieuw_event_location" name="nieuw_event_location" value="<?php echo esc_attr( $location ); ?>" />
		</p>
		<p>
			<label for="nieuw_event_color"><?php esc_html_e( 'Event color (optional override)', 'nieuw-calendar' ); ?></label>
			<input type="color" id="nieuw_event_color" name="nieuw_event_color" value="<?php echo esc_attr( $color ? $color : '#2f5d50' ); ?>" />
			<label>
				<input type="checkbox" name="nieuw_event_color_clear" value="1" <?php checked( ! $color ); ?> />
				<?php esc_html_e( 'Use category color', 'nieuw-calendar' ); ?>
			</label>
		</p>
		<p class="description"><?php esc_html_e( 'Leave end date empty for a single-day event. All-day events hide times.', 'nieuw-calendar' ); ?></p>
		<?php
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST['nieuw_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nieuw_event_nonce'] ) ), 'nieuw_event_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$all_day    = isset( $_POST['nieuw_event_all_day'] ) ? '1' : '';
		$start_date = isset( $_POST['nieuw_event_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['nieuw_event_start_date'] ) ) : '';
		$end_date   = isset( $_POST['nieuw_event_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['nieuw_event_end_date'] ) ) : '';
		$start_time = isset( $_POST['nieuw_event_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['nieuw_event_start_time'] ) ) : '';
		$end_time   = isset( $_POST['nieuw_event_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['nieuw_event_end_time'] ) ) : '';
		$location   = isset( $_POST['nieuw_event_location'] ) ? sanitize_text_field( wp_unslash( $_POST['nieuw_event_location'] ) ) : '';

		if ( $end_date && $start_date && $end_date < $start_date ) {
			$end_date = $start_date;
		}
		if ( $all_day ) {
			$start_time = '';
			$end_time   = '';
		} elseif ( $end_date === $start_date && $end_time && $start_time && $end_time < $start_time ) {
			$end_time = $start_time;
		}

		update_post_meta( $post_id, '_nieuw_event_all_day', $all_day );
		update_post_meta( $post_id, '_nieuw_event_start_date', $start_date );
		update_post_meta( $post_id, '_nieuw_event_end_date', $end_date );
		update_post_meta( $post_id, '_nieuw_event_start_time', $start_time );
		update_post_meta( $post_id, '_nieuw_event_end_time', $end_time );
		update_post_meta( $post_id, '_nieuw_event_location', $location );

		if ( ! empty( $_POST['nieuw_event_color_clear'] ) ) {
			delete_post_meta( $post_id, '_nieuw_event_color' );
		} elseif ( isset( $_POST['nieuw_event_color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['nieuw_event_color'] ) );
			if ( $color ) {
				update_post_meta( $post_id, '_nieuw_event_color', $color );
			}
		}
	}

	public static function category_add_field() {
		?>
		<div class="form-field">
			<label for="nieuw_category_color"><?php esc_html_e( 'Color', 'nieuw-calendar' ); ?></label>
			<input type="color" name="nieuw_category_color" id="nieuw_category_color" value="#2f5d50" />
		</div>
		<?php
	}

	public static function category_edit_field( $term ) {
		$color = get_term_meta( $term->term_id, 'nieuw_category_color', true );
		?>
		<tr class="form-field">
			<th><label for="nieuw_category_color"><?php esc_html_e( 'Color', 'nieuw-calendar' ); ?></label></th>
			<td>
				<input type="color" name="nieuw_category_color" id="nieuw_category_color" value="<?php echo esc_attr( $color ? $color : '#2f5d50' ); ?>" />
			</td>
		</tr>
		<?php
	}

	public static function save_category_color( $term_id ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		if ( isset( $_POST['nieuw_category_color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['nieuw_category_color'] ) );
			if ( $color ) {
				update_term_meta( $term_id, 'nieuw_category_color', $color );
			}
		}
	}
}
