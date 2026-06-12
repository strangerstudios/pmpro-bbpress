<?php
/**
 * The Memberships > Forums settings page.
 *
 * Consolidates all PMPro bbPress settings in one place: the general forum
 * settings (previously registered into the bbPress > Settings screen) and
 * per-forum membership level restrictions. The same screen is used whether
 * forums are powered by bbPress or BuddyBoss Platform.
 *
 * @package PMPro_bbPress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Forums submenu under the Memberships menu.
 *
 * @since TBD
 */
function pmprobb_admin_menu() {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return;
	}

	add_submenu_page(
		'pmpro-dashboard',
		__( 'Forums', 'pmpro-bbpress' ),
		__( 'Forums', 'pmpro-bbpress' ),
		'manage_options',
		'pmpro-bbpress',
		'pmprobb_settings_page'
	);
}
add_action( 'admin_menu', 'pmprobb_admin_menu', 20 );

/**
 * Get all forums ordered hierarchically (parents followed by their children),
 * each annotated with a pmprobb_depth property for indentation.
 *
 * @since TBD
 *
 * @return WP_Post[] Forum posts, each with a ->pmprobb_depth int property.
 */
function pmprobb_get_forums() {
	if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
		return array();
	}

	$forums = get_posts(
		array(
			'post_type'      => bbp_get_forum_post_type(),
			'post_status'    => array( 'publish', 'private', 'hidden' ),
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);

	// Bucket by parent so we can emit a depth-first, indented ordering.
	$by_parent = array();
	foreach ( $forums as $forum ) {
		$by_parent[ (int) $forum->post_parent ][] = $forum;
	}

	$ordered = array();
	$walk    = function ( $parent_id, $depth ) use ( &$walk, &$ordered, $by_parent ) {
		if ( empty( $by_parent[ $parent_id ] ) ) {
			return;
		}
		foreach ( $by_parent[ $parent_id ] as $forum ) {
			$forum->pmprobb_depth = $depth;
			$ordered[]            = $forum;
			$walk( (int) $forum->ID, $depth + 1 );
		}
	};
	$walk( 0, 0 );

	// Append any orphans whose parent is not in the result set.
	if ( count( $ordered ) !== count( $forums ) ) {
		$seen = wp_list_pluck( $ordered, 'ID' );
		foreach ( $forums as $forum ) {
			if ( ! in_array( $forum->ID, $seen, true ) ) {
				$forum->pmprobb_depth = 0;
				$ordered[]            = $forum;
			}
		}
	}

	return $ordered;
}

/**
 * Get a map of forum_id => array of required level ids, in one query.
 *
 * @since TBD
 *
 * @param int[] $forum_ids Forum post IDs.
 * @return array<int,int[]>
 */
function pmprobb_get_forum_restrictions( $forum_ids ) {
	global $wpdb;

	$map       = array();
	$forum_ids = array_map( 'intval', (array) $forum_ids );
	if ( empty( $forum_ids ) ) {
		return $map;
	}

	$placeholders = implode( ',', array_fill( 0, count( $forum_ids ), '%d' ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders are built from a counted int array.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT page_id, membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id IN ($placeholders)",
			$forum_ids
		)
	);

	foreach ( (array) $rows as $row ) {
		$map[ (int) $row->page_id ][] = (int) $row->membership_id;
	}

	return $map;
}

/**
 * Persist the required levels for a single forum into pmpro_memberships_pages.
 *
 * @since TBD
 *
 * @param int   $forum_id  Forum post ID (stored as page_id).
 * @param int[] $level_ids Required membership level ids. An empty array clears all restrictions.
 */
function pmprobb_update_forum_restrictions( $forum_id, $level_ids ) {
	$forum_id  = (int) $forum_id;
	$level_ids = array_map( 'intval', (array) $level_ids );

	if ( function_exists( 'pmpro_update_post_level_restrictions' ) ) {
		// Preferred: official helper (diffs rows + fires its action hook).
		pmpro_update_post_level_restrictions( $forum_id, $level_ids );
		return;
	}

	// Fallback for older PMPro: replace all rows for this page_id.
	global $wpdb;
	$wpdb->delete( $wpdb->pmpro_memberships_pages, array( 'page_id' => $forum_id ), array( '%d' ) );
	foreach ( $level_ids as $level_id ) {
		$wpdb->insert(
			$wpdb->pmpro_memberships_pages,
			array(
				'membership_id' => $level_id,
				'page_id'       => $forum_id,
			),
			array( '%d', '%d' )
		);
	}
}

/**
 * Render the Memberships > bbPress settings page (and handle the form submission).
 *
 * @since TBD
 */
function pmprobb_settings_page() {
	global $msg, $msgt;

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'pmpro-bbpress' ) );
	}

	// Get all levels (including hidden, in admin order) and all forums.
	$levels = pmpro_getAllLevels( true, true );
	if ( function_exists( 'pmpro_sort_levels_by_order' ) ) {
		$levels = pmpro_sort_levels_by_order( $levels );
	}
	$forums = pmprobb_get_forums();

	// Save settings.
	if ( ! empty( $_POST['pmprobb_save_settings'] ) ) {
		check_admin_referer( 'pmprobb_save_settings', 'pmprobb_settings_nonce' );

		// General settings. Option names are unchanged from the old bbPress > Settings screen.
		$error_message = isset( $_POST['pmprobb_option_error_message'] ) ? sanitize_text_field( wp_unslash( $_POST['pmprobb_option_error_message'] ) ) : '';
		update_option( 'pmprobb_option_error_message', $error_message );
		update_option( 'pmprobb_option_member_links', empty( $_POST['pmprobb_option_member_links'] ) ? 0 : 1 );
		update_option( 'pmprobb_option_hide_member_forums', empty( $_POST['pmprobb_option_hide_member_forums'] ) ? 0 : 1 );
		update_option( 'pmprobb_option_hide_forum_roles', empty( $_POST['pmprobb_option_hide_forum_roles'] ) ? 0 : 1 );
		update_option( 'pmprobb_option_show_membership_levels', empty( $_POST['pmprobb_option_show_membership_levels'] ) ? 0 : 1 );

		// Forum access. Unchecked forums are saved with an empty set, which clears their restrictions.
		$posted = isset( $_POST['pmprobb_forum_levels'] ) && is_array( $_POST['pmprobb_forum_levels'] )
			? wp_unslash( $_POST['pmprobb_forum_levels'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ints extracted below.
			: array();
		$valid_level_ids = array_map( 'intval', wp_list_pluck( $levels, 'id' ) );
		foreach ( $forums as $forum ) {
			$checked   = isset( $posted[ $forum->ID ] ) && is_array( $posted[ $forum->ID ] ) ? array_map( 'intval', $posted[ $forum->ID ] ) : array();
			$level_ids = array_values( array_intersect( $checked, $valid_level_ids ) );
			pmprobb_update_forum_restrictions( $forum->ID, $level_ids );
		}

		// Refresh the cached options.
		pmprobb_getOptions( true );

		$msg  = true;
		$msgt = __( 'Your settings have been updated.', 'pmpro-bbpress' );
	}

	$restrictions = pmprobb_get_forum_restrictions( wp_list_pluck( $forums, 'ID' ) );

	require_once PMPRO_DIR . '/adminpages/admin_header.php';
	?>
	<form action="" method="post">
		<?php wp_nonce_field( 'pmprobb_save_settings', 'pmprobb_settings_nonce' ); ?>
		<hr class="wp-header-end">
		<h1><?php esc_html_e( 'Forums Integration', 'pmpro-bbpress' ); ?></h1>
		<p>
			<?php
				$pmprobb_docs_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - bbPress Add On Documentation', 'pmpro-bbpress' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/add-ons/pmpro-bbpress/?utm_source=plugin&utm_medium=pmpro-bbpress-settings&utm_campaign=add-ons">' . esc_html__( 'bbPress Integration', 'pmpro-bbpress' ) . '</a>';
				// translators: %s: Link to the bbPress Add On documentation.
				printf( esc_html__( 'Restrict access to forums by membership level. These settings apply to forums powered by bbPress or BuddyBoss. Learn more about %s.', 'pmpro-bbpress' ), $pmprobb_docs_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</p>

		<?php if ( ! function_exists( 'bbp_get_forum_post_type' ) ) { ?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'bbPress or BuddyBoss Platform must be active to use this Add On.', 'pmpro-bbpress' ); ?></p></div>
		<?php } ?>

		<div id="pmprobb-forum-access" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Forum Access', 'pmpro-bbpress' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php esc_html_e( 'Choose the membership levels required to view each forum. A forum is restricted to members as soon as it is assigned one or more levels; leave all levels unchecked to keep a forum public. These settings can also be managed in the "Require Membership" box when editing a single forum.', 'pmpro-bbpress' ); ?></p>
				<?php if ( empty( $levels ) ) { ?>
					<p><strong><?php esc_html_e( 'No membership levels found.', 'pmpro-bbpress' ); ?></strong> <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels' ) ); ?>"><?php esc_html_e( 'Create a membership level to get started.', 'pmpro-bbpress' ); ?></a></p>
				<?php } elseif ( empty( $forums ) ) { ?>
					<p><strong><?php esc_html_e( 'No forums found.', 'pmpro-bbpress' ); ?></strong></p>
				<?php } else { ?>
					<?php
						// Build the selectors for the checkbox list based on number of levels.
						$classes = array();
						$classes[] = 'pmpro_checkbox_box';
						if ( count( $levels ) > 5 ) {
							$classes[] = 'pmpro_scrollable';
						}
						$class = implode( ' ', array_unique( $classes ) );
					?>
					<table class="form-table">
						<tbody>
							<?php
							foreach ( $forums as $forum ) {
								$forum_restrictions = isset( $restrictions[ $forum->ID ] ) ? $restrictions[ $forum->ID ] : array();
								?>
								<tr>
									<th scope="row" valign="top">
										<?php
										echo esc_html( str_repeat( '— ', (int) $forum->pmprobb_depth ) );
										$edit_link = get_edit_post_link( $forum->ID );
										if ( ! empty( $edit_link ) ) {
											echo '<a href="' . esc_url( $edit_link ) . '" target="_blank">' . esc_html( get_the_title( $forum ) ) . '</a>';
										} else {
											echo esc_html( get_the_title( $forum ) );
										}
										?>
									</th>
									<td>
										<div class="<?php echo esc_attr( $class ); ?>">
											<?php
											foreach ( $levels as $level ) {
												$field_id = 'pmprobb_forum_' . (int) $forum->ID . '_level_' . (int) $level->id;
												?>
												<div class="pmpro_clickable">
													<input type="checkbox" id="<?php echo esc_attr( $field_id ); ?>" name="pmprobb_forum_levels[<?php echo (int) $forum->ID; ?>][]" value="<?php echo (int) $level->id; ?>" <?php checked( in_array( (int) $level->id, $forum_restrictions, true ) ); ?>>
													<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $level->name ); ?></label>
												</div>
											<?php } ?>
										</div>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->

		<div id="pmprobb-general-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'General Settings', 'pmpro-bbpress' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="pmprobb_option_error_message"><?php esc_html_e( 'Error Message', 'pmpro-bbpress' ); ?></label></th>
							<td><?php pmprobb_option_error_message(); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="pmprobb_option_member_links"><?php esc_html_e( 'Member Links', 'pmpro-bbpress' ); ?></label></th>
							<td><?php pmprobb_option_member_links(); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="pmprobb_option_hide_member_forums"><?php esc_html_e( 'Hide Member Forums', 'pmpro-bbpress' ); ?></label></th>
							<td><?php pmprobb_option_hide_member_forums(); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="pmprobb_option_hide_forum_roles"><?php esc_html_e( 'Hide Forum Roles', 'pmpro-bbpress' ); ?></label></th>
							<td><?php pmprobb_option_hide_forum_roles(); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="pmprobb_option_show_membership_levels"><?php esc_html_e( 'Show Membership Levels', 'pmpro-bbpress' ); ?></label></th>
							<td><?php pmprobb_option_show_membership_levels(); ?></td>
						</tr>
					</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->

		<div id="pmprobb-level-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Membership Level Settings', 'pmpro-bbpress' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php esc_html_e( 'Assign a forum role or background color to members of a specific level. These settings are managed in the "bbPress Settings" section when editing a membership level.', 'pmpro-bbpress' ); ?></p>
				<?php if ( empty( $levels ) ) { ?>
					<p><strong><?php esc_html_e( 'No membership levels found.', 'pmpro-bbpress' ); ?></strong> <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels' ) ); ?>"><?php esc_html_e( 'Create a membership level to get started.', 'pmpro-bbpress' ); ?></a></p>
				<?php } else { ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Level', 'pmpro-bbpress' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Forum Role', 'pmpro-bbpress' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Background Color', 'pmpro-bbpress' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Actions', 'pmpro-bbpress' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$options    = pmprobb_getOptions();
							$roles      = function_exists( 'bbp_get_dynamic_roles' ) ? bbp_get_dynamic_roles() : array();
							foreach ( $levels as $level ) {
								$level_settings = isset( $options['levels'][ $level->id ] ) ? $options['levels'][ $level->id ] : array();
								$role           = ! empty( $level_settings['role'] ) ? $level_settings['role'] : '';
								if ( ! empty( $role ) && isset( $roles[ $role ]['name'] ) ) {
									$role_name = $roles[ $role ]['name'];
								} elseif ( ! empty( $role ) ) {
									$role_name = $role;
								} else {
									$role_name = __( 'Default Behavior', 'pmpro-bbpress' );
								}
								$color    = ! empty( $level_settings['color'] ) ? $level_settings['color'] : '';
								$edit_url = admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . (int) $level->id );
								?>
								<tr>
									<td><a href="<?php echo esc_url( $edit_url ); ?>" target="_blank"><?php echo esc_html( $level->name ); ?></a></td>
									<td><?php echo esc_html( $role_name ); ?></td>
									<td>
										<?php if ( ! empty( $color ) ) {
											$color_css = ( 0 === strpos( $color, '#' ) ) ? $color : '#' . $color;
											?>
											<span style="display: inline-block; width: 1em; height: 1em; vertical-align: middle; border: 1px solid #ccc; background-color: <?php echo esc_attr( $color_css ); ?>;"></span>
											<code><?php echo esc_html( $color ); ?></code>
										<?php } else {
											esc_html_e( 'None', 'pmpro-bbpress' );
										} ?>
									</td>
									<td><a href="<?php echo esc_url( $edit_url ); ?>" target="_blank"><?php esc_html_e( 'Edit Level', 'pmpro-bbpress' ); ?></a></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->

		<?php submit_button( __( 'Save Settings', 'pmpro-bbpress' ), 'primary', 'pmprobb_save_settings' ); ?>
	</form>
	<?php
	require_once PMPRO_DIR . '/adminpages/admin_footer.php';
}
