<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nieuw_Calendar_Admin {
	/** @var string */
	private static $form_error = '';

	public static function menu() {
		$list = add_menu_page(
			__( 'Nieuw Calendar', 'nieuw-calendar' ),
			__( 'Nieuw Calendar', 'nieuw-calendar' ),
			'edit_posts',
			'nieuw-calendar',
			array( __CLASS__, 'render_events' ),
			'dashicons-calendar-alt',
			26
		);
		add_submenu_page(
			'nieuw-calendar',
			__( 'Events', 'nieuw-calendar' ),
			__( 'Events', 'nieuw-calendar' ),
			'edit_posts',
			'nieuw-calendar',
			array( __CLASS__, 'render_events' )
		);
		$form = add_submenu_page(
			'nieuw-calendar',
			__( 'Add Event', 'nieuw-calendar' ),
			__( 'Add Event', 'nieuw-calendar' ),
			'edit_posts',
			'nieuw-calendar-event',
			array( __CLASS__, 'render_event_form' )
		);
		add_submenu_page(
			'nieuw-calendar',
			__( 'Categories', 'nieuw-calendar' ),
			__( 'Categories', 'nieuw-calendar' ),
			'manage_categories',
			'edit-tags.php?taxonomy=nieuw_event_category&post_type=nieuw_event'
		);
		add_submenu_page(
			'nieuw-calendar',
			__( 'Settings', 'nieuw-calendar' ),
			__( 'Settings', 'nieuw-calendar' ),
			'manage_options',
			'nieuw-calendar-settings',
			array( 'Nieuw_Calendar_Settings', 'render' )
		);

		add_action( 'load-' . $list, array( __CLASS__, 'load_list' ) );
		add_action( 'load-' . $form, array( __CLASS__, 'load_form' ) );
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$ok = false !== strpos( $hook, 'nieuw-calendar' )
			|| ( isset( $screen->post_type ) && 'nieuw_event' === $screen->post_type )
			|| ( isset( $screen->taxonomy ) && 'nieuw_event_category' === $screen->taxonomy );
		if ( ! $ok ) {
			return;
		}

		wp_enqueue_style(
			'nieuw-calendar-admin-fonts',
			'https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap',
			array(),
			NIEUW_CALENDAR_VERSION
		);
		wp_enqueue_style(
			'nieuw-calendar-admin',
			NIEUW_CALENDAR_URL . 'admin/css/admin.css',
			array( 'nieuw-calendar-admin-fonts' ),
			NIEUW_CALENDAR_VERSION
		);

		$deps = array();
		if ( false !== strpos( $hook, 'nieuw-calendar-event' ) ) {
			wp_enqueue_media();
			$deps[] = 'jquery';
		}
		wp_enqueue_script(
			'nieuw-calendar-admin',
			NIEUW_CALENDAR_URL . 'admin/js/admin.js',
			$deps,
			NIEUW_CALENDAR_VERSION,
			true
		);
		wp_localize_script(
			'nieuw-calendar-admin',
			'NieuwCalendarAdmin',
			array(
				'chooseImage' => __( 'Choose image', 'nieuw-calendar' ),
				'useImage'    => __( 'Use image', 'nieuw-calendar' ),
			)
		);
	}

	public static function redirect_native_screens() {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}
		global $pagenow;
		if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'nieuw_event' === $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( admin_url( 'admin.php?page=nieuw-calendar-event' ) );
			exit;
		}
		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post = get_post( absint( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $post && 'nieuw_event' === $post->post_type ) {
				wp_safe_redirect( admin_url( 'admin.php?page=nieuw-calendar-event&event_id=' . $post->ID ) );
				exit;
			}
		}
	}

	public static function edit_link( $link, $post_id ) {
		$post = get_post( $post_id );
		if ( $post && 'nieuw_event' === $post->post_type ) {
			return admin_url( 'admin.php?page=nieuw-calendar-event&event_id=' . $post->ID );
		}
		return $link;
	}

	public static function parent_file( $parent ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && isset( $screen->taxonomy ) && 'nieuw_event_category' === $screen->taxonomy ) {
			return 'nieuw-calendar';
		}
		return $parent;
	}

	public static function submenu_file( $submenu ) {
		if ( isset( $_GET['taxonomy'] ) && 'nieuw_event_category' === $_GET['taxonomy'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 'edit-tags.php?taxonomy=nieuw_event_category&post_type=nieuw_event';
		}
		if ( isset( $_GET['page'] ) && 'nieuw-calendar-event' === $_GET['page'] && ! empty( $_GET['event_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 'nieuw-calendar';
		}
		return $submenu;
	}

	public static function disable_block_editor( $use, $type ) {
		if ( 'nieuw_event' === $type ) {
			return false;
		}
		return $use;
	}

	public static function load_list() {
		if ( empty( $_GET['nieuw_delete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$id = absint( $_GET['nieuw_delete'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $id || ! current_user_can( 'delete_post', $id ) ) {
			wp_die( esc_html__( 'You cannot delete this event.', 'nieuw-calendar' ) );
		}
		check_admin_referer( 'nieuw_delete_' . $id );
		wp_trash_post( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=nieuw-calendar&deleted=1' ) );
		exit;
	}

	public static function load_form() {
		if ( empty( $_POST['nieuw_event_nonce'] ) ) {
			return;
		}
		$result = self::save_from_request();
		if ( is_wp_error( $result ) ) {
			self::$form_error = $result->get_error_message();
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=nieuw-calendar&saved=1' ) );
		exit;
	}

	/**
	 * Search event title, description, or venue.
	 *
	 * @param string   $search Search SQL.
	 * @param WP_Query $query Query.
	 * @return string
	 */
	public static function search_title_or_venue( $search, $query ) {
		if ( ! $query->get( 'nieuw_calendar_search' ) ) {
			return $search;
		}
		$term = $query->get( 's' );
		if ( ! $term ) {
			return $search;
		}
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $term ) . '%';
		return $wpdb->prepare(
			" AND ( {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = {$wpdb->posts}.ID AND pm.meta_key = '_nieuw_event_location' AND pm.meta_value LIKE %s ) ) ",
			$like,
			$like,
			$like
		);
	}

	public static function render_events() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$q     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $q ) {
			add_filter( 'posts_search', array( __CLASS__, 'search_title_or_venue' ), 10, 2 );
		}
		$query = new WP_Query(
			array(
				'post_type'              => 'nieuw_event',
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => 20,
				'paged'                  => $paged,
				's'                      => $q,
				'meta_key'               => '_nieuw_event_start_date',
				'orderby'                => 'meta_value',
				'order'                  => 'ASC',
				'nieuw_calendar_search'  => 1,
			)
		);
		if ( $q ) {
			remove_filter( 'posts_search', array( __CLASS__, 'search_title_or_venue' ), 10 );
		}
		$statuses = nieuw_calendar_statuses();
		?>
		<div class="wrap nieuw-admin">
			<div class="nieuw-admin-head">
				<h1><?php esc_html_e( 'Events', 'nieuw-calendar' ); ?></h1>
				<p class="nieuw-branding"><?php esc_html_e( 'Nieuw Calendar by Nieuw Ark', 'nieuw-calendar' ); ?></p>
			</div>

			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="nieuw-notice"><?php esc_html_e( 'Event saved.', 'nieuw-calendar' ); ?></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="nieuw-notice"><?php esc_html_e( 'Event moved to trash.', 'nieuw-calendar' ); ?></div>
			<?php endif; ?>

			<div class="nieuw-toolbar">
				<form method="get" class="nieuw-search">
					<input type="hidden" name="page" value="nieuw-calendar" />
					<input type="search" name="s" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'Search title or venue', 'nieuw-calendar' ); ?>" />
				</form>
				<a class="nieuw-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=nieuw-calendar-event' ) ); ?>">
					<?php esc_html_e( 'Add event', 'nieuw-calendar' ); ?>
				</a>
			</div>

			<div class="nieuw-table-wrap">
				<div class="nieuw-table-head">
					<span><?php esc_html_e( 'Title', 'nieuw-calendar' ); ?></span>
					<span><?php esc_html_e( 'When', 'nieuw-calendar' ); ?></span>
					<span><?php esc_html_e( 'Venue', 'nieuw-calendar' ); ?></span>
					<span><?php esc_html_e( 'Status', 'nieuw-calendar' ); ?></span>
					<span></span>
				</div>
				<?php if ( ! $query->have_posts() ) : ?>
					<p class="nieuw-empty"><?php esc_html_e( 'No events yet. Create the first one.', 'nieuw-calendar' ); ?></p>
				<?php else : ?>
					<ul class="nieuw-rows">
						<?php
						foreach ( $query->posts as $post ) :
							$color    = nieuw_calendar_event_color( $post->ID );
							$location = (string) get_post_meta( $post->ID, '_nieuw_event_location', true );
							$terms    = get_the_terms( $post->ID, 'nieuw_event_category' );
							$edit     = admin_url( 'admin.php?page=nieuw-calendar-event&event_id=' . $post->ID );
							$del      = wp_nonce_url( admin_url( 'admin.php?page=nieuw-calendar&nieuw_delete=' . $post->ID ), 'nieuw_delete_' . $post->ID );
							$status   = isset( $statuses[ $post->post_status ] ) ? $statuses[ $post->post_status ] : $post->post_status;
							?>
							<li class="nieuw-row">
								<div class="nieuw-row-title">
									<a href="<?php echo esc_url( $edit ); ?>">
										<span class="nieuw-dot" style="background:<?php echo esc_attr( $color ); ?>"></span>
										<strong><?php echo esc_html( get_the_title( $post ) ); ?></strong>
									</a>
									<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
										<span class="nieuw-pills">
											<?php foreach ( $terms as $term ) : ?>
												<?php $tc = get_term_meta( $term->term_id, 'nieuw_category_color', true ); ?>
												<span class="nieuw-pill-cat" style="background:<?php echo esc_attr( $tc ? $tc : '#3d5a6c' ); ?>;color:<?php echo esc_attr( nieuw_calendar_contrast_text( $tc ? $tc : '#3d5a6c' ) ); ?>">
													<?php echo esc_html( $term->name ); ?>
												</span>
											<?php endforeach; ?>
										</span>
									<?php endif; ?>
								</div>
								<p class="nieuw-muted"><?php echo esc_html( nieuw_calendar_format_when( $post->ID ) ); ?></p>
								<p class="nieuw-muted"><?php echo esc_html( $location ? $location : '—' ); ?></p>
								<span class="nieuw-status nieuw-status-<?php echo esc_attr( $post->post_status ); ?>"><?php echo esc_html( $status ); ?></span>
								<div class="nieuw-row-actions">
									<a class="nieuw-btn-ghost" href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'Edit', 'nieuw-calendar' ); ?></a>
									<a class="nieuw-btn-ghost is-danger" href="<?php echo esc_url( $del ); ?>" data-confirm="<?php echo esc_attr( sprintf( /* translators: %s event title */ __( 'Delete “%s”?', 'nieuw-calendar' ), get_the_title( $post ) ) ); ?>"><?php esc_html_e( 'Delete', 'nieuw-calendar' ); ?></a>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<p class="nieuw-count">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d event count */
						_n( '%d event', '%d events', (int) $query->found_posts, 'nieuw-calendar' ),
						(int) $query->found_posts
					)
				);
				?>
			</p>
			<?php
			if ( $query->max_num_pages > 1 ) {
				echo '<div class="nieuw-pager">';
				echo wp_kses_post(
					paginate_links(
						array(
							'base'    => add_query_arg( 'paged', '%#%' ),
							'format'  => '',
							'current' => $paged,
							'total'   => $query->max_num_pages,
						)
					)
				);
				echo '</div>';
			}
			?>
		</div>
		<?php
	}

	public static function render_event_form() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $event_id && ! current_user_can( 'edit_post', $event_id ) ) {
			wp_die( esc_html__( 'You cannot edit this event.', 'nieuw-calendar' ) );
		}

		$form = ! empty( $_POST['nieuw_event_nonce'] ) ? self::posted_form() : self::form_from_event( $event_id );
		$terms = get_terms(
			array(
				'taxonomy'   => 'nieuw_event_category',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
		$thumb_url = $form['thumbnail_id'] ? wp_get_attachment_image_url( (int) $form['thumbnail_id'], 'medium' ) : '';
		$settings  = nieuw_calendar_get_settings();
		?>
		<div class="wrap nieuw-admin nieuw-admin-form">
			<div class="nieuw-admin-head">
				<h1><?php echo $event_id ? esc_html__( 'Edit event', 'nieuw-calendar' ) : esc_html__( 'New event', 'nieuw-calendar' ); ?></h1>
				<p class="nieuw-branding"><?php echo esc_html( sprintf( /* translators: %s timezone */ __( 'Dates are stored in the calendar timezone (%s) from Settings.', 'nieuw-calendar' ), $settings['timezone'] ) ); ?></p>
			</div>

			<?php if ( self::$form_error ) : ?>
				<div class="nieuw-notice is-error"><?php echo esc_html( self::$form_error ); ?></div>
			<?php endif; ?>

			<form method="post" class="nieuw-event-form">
				<?php wp_nonce_field( 'nieuw_event_save', 'nieuw_event_nonce' ); ?>
				<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event_id ); ?>" />

				<label class="nieuw-field">
					<span><?php esc_html_e( 'Title', 'nieuw-calendar' ); ?></span>
					<input type="text" name="title" value="<?php echo esc_attr( $form['title'] ); ?>" placeholder="<?php esc_attr_e( 'Evening jazz', 'nieuw-calendar' ); ?>" required />
				</label>

				<label class="nieuw-field">
					<span><?php esc_html_e( 'Description', 'nieuw-calendar' ); ?></span>
					<textarea name="description" rows="4" placeholder="<?php esc_attr_e( 'Optional details for guests', 'nieuw-calendar' ); ?>"><?php echo esc_textarea( $form['description'] ); ?></textarea>
				</label>

				<label class="nieuw-check">
					<input type="checkbox" name="all_day" id="nieuw_event_all_day" value="1" <?php checked( $form['all_day'] ); ?> />
					<?php esc_html_e( 'All-day event', 'nieuw-calendar' ); ?>
				</label>

				<div class="nieuw-grid">
					<label class="nieuw-field">
						<span><?php esc_html_e( 'Start date', 'nieuw-calendar' ); ?></span>
						<input type="date" name="start_date" value="<?php echo esc_attr( $form['start_date'] ); ?>" required />
					</label>
					<label class="nieuw-field nieuw-time-field">
						<span><?php esc_html_e( 'Start time', 'nieuw-calendar' ); ?></span>
						<input type="time" name="start_time" value="<?php echo esc_attr( $form['start_time'] ); ?>" />
					</label>
					<label class="nieuw-field">
						<span><?php esc_html_e( 'End date', 'nieuw-calendar' ); ?></span>
						<input type="date" name="end_date" value="<?php echo esc_attr( $form['end_date'] ); ?>" />
					</label>
					<label class="nieuw-field nieuw-time-field">
						<span><?php esc_html_e( 'End time', 'nieuw-calendar' ); ?></span>
						<input type="time" name="end_time" value="<?php echo esc_attr( $form['end_time'] ); ?>" />
					</label>
				</div>

				<label class="nieuw-field">
					<span><?php esc_html_e( 'Location / venue', 'nieuw-calendar' ); ?></span>
					<input type="text" name="location" value="<?php echo esc_attr( $form['location'] ); ?>" placeholder="<?php esc_attr_e( 'Nieuw Hall courtyard', 'nieuw-calendar' ); ?>" />
				</label>

				<fieldset class="nieuw-field">
					<span><?php esc_html_e( 'Categories', 'nieuw-calendar' ); ?></span>
					<div class="nieuw-cat-chips">
						<?php foreach ( $terms as $term ) : ?>
							<?php $tc = get_term_meta( $term->term_id, 'nieuw_category_color', true ); ?>
							<label class="nieuw-chip">
								<input type="checkbox" name="categories[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php checked( in_array( $term->term_id, $form['categories'], true ) ); ?> />
								<span class="nieuw-chip-face">
									<i style="background:<?php echo esc_attr( $tc ? $tc : '#3d5a6c' ); ?>"></i>
									<?php echo esc_html( $term->name ); ?>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
					<div class="nieuw-new-cat">
						<input type="text" name="new_category" value="" placeholder="<?php esc_attr_e( 'New category', 'nieuw-calendar' ); ?>" />
						<span class="nieuw-hint"><?php esc_html_e( 'Type a name and save — it will be created with a default color.', 'nieuw-calendar' ); ?></span>
					</div>
				</fieldset>

				<div class="nieuw-grid">
					<label class="nieuw-field">
						<span><?php esc_html_e( 'Status / visibility', 'nieuw-calendar' ); ?></span>
						<select name="status">
							<?php foreach ( nieuw_calendar_statuses() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $form['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="nieuw-field">
						<span><?php esc_html_e( 'Event color (optional override)', 'nieuw-calendar' ); ?></span>
						<div class="nieuw-color-row">
							<input type="color" name="color" id="nieuw_event_color" value="<?php echo esc_attr( $form['color'] ? $form['color'] : '#2f5d50' ); ?>" />
							<label class="nieuw-check">
								<input type="checkbox" name="color_clear" id="nieuw_event_color_clear" value="1" <?php checked( ! $form['color'] ); ?> />
								<?php esc_html_e( 'Use category color', 'nieuw-calendar' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="nieuw-field">
					<span><?php esc_html_e( 'Featured image', 'nieuw-calendar' ); ?></span>
					<div class="nieuw-media" data-nieuw-media>
						<input type="hidden" name="thumbnail_id" value="<?php echo esc_attr( (string) $form['thumbnail_id'] ); ?>" />
						<div class="nieuw-media-preview">
							<?php if ( $thumb_url ) : ?>
								<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" />
							<?php endif; ?>
						</div>
						<div class="nieuw-media-actions">
							<button type="button" class="nieuw-btn-ghost" data-nieuw-media-choose><?php esc_html_e( 'Choose image', 'nieuw-calendar' ); ?></button>
							<button type="button" class="nieuw-btn-ghost" data-nieuw-media-remove><?php esc_html_e( 'Remove', 'nieuw-calendar' ); ?></button>
						</div>
					</div>
				</div>

				<div class="nieuw-form-actions">
					<a class="nieuw-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=nieuw-calendar' ) ); ?>"><?php esc_html_e( 'Cancel', 'nieuw-calendar' ); ?></a>
					<button type="submit" class="nieuw-btn"><?php esc_html_e( 'Save event', 'nieuw-calendar' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function form_from_event( $event_id ) {
		$defaults = array(
			'title'         => '',
			'description'   => '',
			'all_day'       => false,
			'start_date'    => '',
			'start_time'    => '18:00',
			'end_date'      => '',
			'end_time'      => '21:00',
			'location'      => '',
			'color'         => '',
			'categories'    => array(),
			'status'        => 'publish',
			'thumbnail_id'  => 0,
		);
		if ( ! $event_id ) {
			return $defaults;
		}
		$post = get_post( $event_id );
		if ( ! $post || 'nieuw_event' !== $post->post_type ) {
			return $defaults;
		}
		$all_day = (bool) get_post_meta( $event_id, '_nieuw_event_all_day', true );
		$terms   = wp_get_post_terms( $event_id, 'nieuw_event_category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
		$terms = array_map( 'intval', $terms );
		$status = $post->post_status;
		if ( ! isset( nieuw_calendar_statuses()[ $status ] ) ) {
			$status = 'publish';
		}
		$start_time = (string) get_post_meta( $event_id, '_nieuw_event_start_time', true );
		$end_time   = (string) get_post_meta( $event_id, '_nieuw_event_end_time', true );
		return array(
			'title'        => $post->post_title,
			'description'  => wp_strip_all_tags( $post->post_content ),
			'all_day'      => $all_day,
			'start_date'   => (string) get_post_meta( $event_id, '_nieuw_event_start_date', true ),
			'start_time'   => $start_time ? $start_time : '18:00',
			'end_date'     => (string) get_post_meta( $event_id, '_nieuw_event_end_date', true ),
			'end_time'     => $end_time ? $end_time : '21:00',
			'location'     => (string) get_post_meta( $event_id, '_nieuw_event_location', true ),
			'color'        => (string) get_post_meta( $event_id, '_nieuw_event_color', true ),
			'categories'   => $terms,
			'status'       => $status,
			'thumbnail_id' => (int) get_post_thumbnail_id( $event_id ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function posted_form() {
		$cats = array();
		if ( isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$cats = array_map( 'absint', wp_unslash( $_POST['categories'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		return array(
			'title'        => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'description'  => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'all_day'      => ! empty( $_POST['all_day'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'start_date'   => isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'start_time'   => isset( $_POST['start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['start_time'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'end_date'     => isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'end_time'     => isset( $_POST['end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['end_time'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'location'     => isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'color'        => isset( $_POST['color'] ) ? (string) sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'categories'   => $cats,
			'status'       => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'publish', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'thumbnail_id' => isset( $_POST['thumbnail_id'] ) ? absint( $_POST['thumbnail_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}

	/**
	 * @return int|WP_Error
	 */
	private static function save_from_request() {
		if ( ! isset( $_POST['nieuw_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nieuw_event_nonce'] ) ), 'nieuw_event_save' ) ) {
			return new WP_Error( 'nonce', __( 'The form expired. Please try again.', 'nieuw-calendar' ) );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'cap', __( 'You cannot save events.', 'nieuw-calendar' ) );
		}

		$event_id    = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$all_day     = ! empty( $_POST['all_day'] );
		$start_date  = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date    = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
		$start_time  = isset( $_POST['start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['start_time'] ) ) : '';
		$end_time    = isset( $_POST['end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['end_time'] ) ) : '';
		$location    = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';
		$status      = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'publish';
		$thumb       = isset( $_POST['thumbnail_id'] ) ? absint( $_POST['thumbnail_id'] ) : 0;

		if ( ! isset( nieuw_calendar_statuses()[ $status ] ) ) {
			$status = 'publish';
		}
		if ( '' === $title ) {
			return new WP_Error( 'title', __( 'A title is required.', 'nieuw-calendar' ) );
		}
		if ( ! $start_date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			return new WP_Error( 'start', __( 'Start date is required.', 'nieuw-calendar' ) );
		}
		if ( $end_date && $end_date < $start_date ) {
			return new WP_Error( 'end', __( 'End date cannot be before the start date.', 'nieuw-calendar' ) );
		}
		if ( ! $end_date ) {
			$end_date = $start_date;
		}
		if ( $all_day ) {
			$start_time = '';
			$end_time   = '';
		} else {
			if ( $end_date === $start_date && $end_time && $start_time && $end_time < $start_time ) {
				return new WP_Error( 'time', __( 'End time cannot be before the start time.', 'nieuw-calendar' ) );
			}
		}

		if ( $event_id && ! current_user_can( 'edit_post', $event_id ) ) {
			return new WP_Error( 'cap', __( 'You cannot edit this event.', 'nieuw-calendar' ) );
		}

		$postarr = array(
			'post_type'    => 'nieuw_event',
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => $status,
		);
		if ( $event_id ) {
			$postarr['ID'] = $event_id;
		}
		$saved = wp_insert_post( $postarr, true );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		update_post_meta( $saved, '_nieuw_event_all_day', $all_day ? '1' : '' );
		update_post_meta( $saved, '_nieuw_event_start_date', $start_date );
		update_post_meta( $saved, '_nieuw_event_end_date', $end_date );
		update_post_meta( $saved, '_nieuw_event_start_time', $start_time );
		update_post_meta( $saved, '_nieuw_event_end_time', $end_time );
		update_post_meta( $saved, '_nieuw_event_location', $location );

		if ( ! empty( $_POST['color_clear'] ) ) {
			delete_post_meta( $saved, '_nieuw_event_color' );
		} elseif ( isset( $_POST['color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['color'] ) );
			if ( $color ) {
				update_post_meta( $saved, '_nieuw_event_color', $color );
			}
		}

		$cat_ids = array();
		if ( isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) {
			$cat_ids = array_filter( array_map( 'absint', wp_unslash( $_POST['categories'] ) ) );
		}
		if ( ! empty( $_POST['new_category'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['new_category'] ) );
			if ( $name ) {
				$term = wp_insert_term( $name, 'nieuw_event_category' );
				if ( is_wp_error( $term ) && isset( $term->error_data['term_exists'] ) ) {
					$cat_ids[] = (int) $term->error_data['term_exists'];
				} elseif ( ! is_wp_error( $term ) ) {
					update_term_meta( (int) $term['term_id'], 'nieuw_category_color', '#3d5a6c' );
					$cat_ids[] = (int) $term['term_id'];
				}
			}
		}
		wp_set_object_terms( $saved, $cat_ids, 'nieuw_event_category' );

		if ( $thumb ) {
			set_post_thumbnail( $saved, $thumb );
		} else {
			delete_post_thumbnail( $saved );
		}

		return (int) $saved;
	}
}
