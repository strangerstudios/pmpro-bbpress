<?php
/**
 * BuddyBoss compatibility.
 *
 * Adds a "Forum Access" admin page (Memberships > Forum Access) for setting the
 * required membership levels per bbPress forum, styled after the PMPro MailChimp
 * Add On settings. Reads/writes the same pmpro_memberships_pages table PMPro
 * uses, so the standard pmpro-bbpress access checks enforce it automatically.
 *
 * Only loads for BuddyBoss Platform 3.0+.
 *
 * @package PMPro_bbPress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the Forum Access page should load.
 *
 * Requires PMPro (for membership levels) and BuddyBoss Platform 3.0+ (the
 * version this integration is built and tested against).
 *
 * @since TBD
 * 
 * @return bool
 */
function pmprobb_buddyboss_is_supported() {
	return (
		function_exists( 'pmpro_getAllLevels' ) &&
		defined( 'BP_PLATFORM_VERSION' ) &&
		version_compare( BP_PLATFORM_VERSION, '3.0', '>=' )
	);
}

/**
 * Register the submenu under PMPro's "Memberships" menu (slug: pmpro-dashboard).
 *
 * Hooked late so the PMPro parent menu is registered first.
 * 
 * @since TBD
 */
function pmprobb_buddyboss_admin_menu() {
	if ( ! pmprobb_buddyboss_is_supported() ) {
		return;
	}

	$hook = add_submenu_page(
		'pmpro-dashboard',
		__( 'Forum Access', 'pmpro-bbpress' ),
		__( 'Forum Access', 'pmpro-bbpress' ),
		'manage_options',
		'pmpro-forum-access',
		'pmprobb_buddyboss_render_page'
	);

	// Enqueue the page styles only on the Forum Access screen.
	if ( $hook ) {
		add_action( 'admin_print_styles-' . $hook, 'pmprobb_buddyboss_enqueue_styles' );
	}
}
add_action( 'admin_menu', 'pmprobb_buddyboss_admin_menu', 20 );

/**
 * Enqueue the Forum Access page styles.
 *
 * Registers an inline-only style handle and attaches the page CSS via
 * wp_add_inline_style(), rather than echoing a <style> block from the
 * render callback.
 *
 * @since TBD
 */
function pmprobb_buddyboss_enqueue_styles() {
	$css = '
		input[type=checkbox] + label.pmprobb-checkbox-label {
			cursor: pointer;
			display: inline;
			width: auto;
		}
		.pmprobb-checkbox-list-scrollable {
			height: 100px;
			width: 300px;
			background: #FFFFFF;
			border: 1px solid #CCC;
			overflow: auto;
		}';

	wp_register_style( 'pmprobb-buddyboss-admin', false );
	wp_enqueue_style( 'pmprobb-buddyboss-admin' );
	wp_add_inline_style( 'pmprobb-buddyboss-admin', $css );
}

/**
 * Get all membership levels (id + name), including hidden, sorted by admin order.
 *
 * @since TBD
 * 
 * @return array[] List of array( 'id' => int, 'name' => string ).
 */
function pmprobb_buddyboss_get_levels() {
	if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
		return array();
	}

	$levels = pmpro_getAllLevels( true, true );
	if ( function_exists( 'pmpro_sort_levels_by_order' ) ) {
		$levels = pmpro_sort_levels_by_order( $levels );
	}

	$out = array();
	foreach ( (array) $levels as $level ) {
		$out[] = array(
			'id'   => (int) $level->id,
			'name' => $level->name,
		);
	}
	return $out;
}

/**
 * Get all forums ordered hierarchically (parents followed by their children),
 * each annotated with a depth for indentation.
 *
 * @since TBD
 * 
 * @return WP_Post[] Forum posts, each with a ->bb_depth int property.
 */
function pmprobb_buddyboss_get_forums() {
	if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
		return array();
	}

	$forums = get_posts(
		array(
			'post_type'      => bbp_get_forum_post_type(),
			'post_status'    => array( 'publish', 'private', 'hidden' ),
			'posts_per_page' => 250,
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
			$forum->bb_depth = $depth;
			$ordered[]       = $forum;
			$walk( (int) $forum->ID, $depth + 1 );
		}
	};
	$walk( 0, 0 );

	// Append any orphans whose parent is not in the result set (defensive).
	if ( count( $ordered ) !== count( $forums ) ) {
		$seen = wp_list_pluck( $ordered, 'ID' );
		foreach ( $forums as $forum ) {
			if ( ! in_array( $forum->ID, $seen, true ) ) {
				$forum->bb_depth = 0;
				$ordered[]       = $forum;
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
function pmprobb_buddyboss_get_restrictions( $forum_ids ) {
	global $wpdb;

	$map = array();
	$forum_ids = array_map( 'intval', $forum_ids );
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
 * @param int   $forum_id  Forum post ID (stored as page_id).
 * @param int[] $level_ids Required membership level ids.
 */
function pmprobb_buddyboss_save_forum_levels( $forum_id, $level_ids ) {
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
 * Render the Forum Access admin page (and handle the form submission).
 */
function pmprobb_buddyboss_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'pmpro-bbpress' ) );
	}

	// Get all levels and all forums.
	$levels = pmprobb_buddyboss_get_levels();
	$forums = pmprobb_buddyboss_get_forums();

	$msg  = ''; // The message text.
	$msgt = ''; // The notice class: 'updated fade' on success, 'error' on failure.

	// Run on save.
	if ( isset( $_POST['pmprobb_buddyboss_submit'] ) ) {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'pmprobb_buddyboss_save' ) ) {
			$msg  = __( 'Are you sure you want to do that? Please try again.', 'pmpro-bbpress' );
			$msgt = 'error';
		} else {
			$valid_level_ids = wp_list_pluck( $levels, 'id' );
			$valid_forum_ids = wp_list_pluck( $forums, 'ID' );

			$posted = isset( $_POST['pmprobb_buddyboss'] ) && is_array( $_POST['pmprobb_buddyboss'] )
				? wp_unslash( $_POST['pmprobb_buddyboss'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ints extracted below.
				: array();

			// Seed every forum with an empty level set so unchecked forums get cleared.
			$forum_levels = array_fill_keys( $valid_forum_ids, array() );

			foreach ( $valid_level_ids as $level_id ) {
				if ( empty( $posted[ $level_id ] ) || ! is_array( $posted[ $level_id ] ) ) {
					continue;
				}
				$checked_forums = array_intersect( array_map( 'intval', $posted[ $level_id ] ), $valid_forum_ids );
				foreach ( $checked_forums as $forum_id ) {
					$forum_levels[ $forum_id ][] = (int) $level_id;
				}
			}

			foreach ( $forum_levels as $forum_id => $level_ids ) {
				pmprobb_buddyboss_save_forum_levels( $forum_id, array_values( array_unique( $level_ids ) ) );
			}

			$msg  = __( 'Forum access settings saved.', 'pmpro-bbpress' );
			$msgt = 'updated fade';
		}
	}

	// --- Current state (forum_id => required level ids) --------------------
	$restrictions = pmprobb_buddyboss_get_restrictions( wp_list_pluck( $forums, 'ID' ) );
	$scrollable   = count( $forums ) > 5; // Match MailChimp: scrollable list when >5 items.
	?>
	<?php if ( ! empty( $msg ) ) : ?>
		<div id="message" class="<?php echo esc_attr( $msgt ); ?>"><p><?php echo esc_html( $msg ); ?></p></div>
	<?php endif; ?>
	<div class="wrap pmprobb-buddyboss-wrap">
		<h1><?php esc_html_e( 'Forum Access', 'pmpro-bbpress' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'For each membership level below, choose the forums that should be restricted to that level. A forum becomes members-only as soon as it is assigned to one or more levels; leave a forum unchecked everywhere for no restriction. Access is enforced by the PMPro bbPress add-on.', 'pmpro-bbpress' ); ?>
		</p>

		<?php if ( empty( $levels ) ) : ?>
			<div role="alert" class="pmpro_message pmpro_alert"><p><?php esc_html_e( 'No membership levels found. Create a level in PMPro first.', 'pmpro-bbpress' ); ?></p></div>
		<?php elseif ( empty( $forums ) ) : ?>
			<div role="alert" class="pmpro_message pmpro_alert"><p><?php esc_html_e( 'No forums found.', 'pmpro-bbpress' ); ?></p></div>
		<?php else : ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'pmprobb_buddyboss_save' ); ?>

				<h2><?php esc_html_e( 'Membership Levels and Forums', 'pmpro-bbpress' ); ?></h2>
				<p><?php esc_html_e( 'For each level below, choose the forum(s) that should be restricted to that level.', 'pmpro-bbpress' ); ?></p>
				<table class="form-table" role="presentation">
					<tbody>
						<?php foreach ( $levels as $level ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $level['name'] ); ?></th>
								<td>
									<div <?php echo $scrollable ? 'class="pmprobb-checkbox-list-scrollable"' : ''; ?>>
										<?php
										foreach ( $forums as $forum ) :
											$current  = isset( $restrictions[ $forum->ID ] ) ? $restrictions[ $forum->ID ] : array();
											$checked  = in_array( $level['id'], $current, true ) ? ' checked' : '';
											$field_id = 'pmprobb_level_' . (int) $level['id'] . '_forum_' . (int) $forum->ID;
											echo '<input type="checkbox" name="pmprobb_buddyboss[' . (int) $level['id'] . '][]" value="' . (int) $forum->ID . '" id="' . esc_attr( $field_id ) . '"' . esc_attr( $checked ) . '>';
											echo '<label for="' . esc_attr( $field_id ) . '" class="pmprobb-checkbox-label">' . esc_html( get_the_title( $forum ) ) . '</label><br>';
										endforeach;
										?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php submit_button( __( 'Save Changes', 'pmpro-bbpress' ), 'primary', 'pmprobb_buddyboss_submit' ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Hide the Forum Archive No Access Message as some may be locked and others not.
 * Direct Access to a restricted forum will redirect to the BuddyPress no access page if it's set.
 * 
 * @param mixed  $override  False to keep default behavior, or content to return.
 * @param string $content   The original post content.
 * @param bool   $hasaccess Whether the current user has access to the post.
 * @return mixed
 */
function pmprobb_buddyboss_suppress_archive_no_access_msg( $override, $content, $hasaccess ) {
	// Already overridden by another filter, or the user has access: leave as-is.
	if ( false !== $override || $hasaccess ) {
		return $override;
	}

	if ( function_exists( 'bbp_is_forum_archive' ) && bbp_is_forum_archive() ) {
		return $content; // Return original content; skip the no-access message.
	}

	return $override;
}
add_filter( 'pmpro_membership_content_filter', 'pmprobb_buddyboss_suppress_archive_no_access_msg', 10, 3 );

/**
 * Redirect restricted-forum visitors to the PMPro BuddyPress/BuddyBoss "No Access" page if it's set.
 *
 * @param string $redirect_to Default redirect URL (forums archive + noaccess).
 * @param int    $forum_id    The restricted forum's ID.
 * @return string
 */
function pmprobb_buddyboss_forum_redirect_url( $redirect_to, $forum_id ) {
	if ( ! pmprobb_buddyboss_is_supported() ) {
		return $redirect_to;
	}

	// If the PMPro BuddyPress/BuddyBoss "No Access" page is set, redirect there instead of the forums archive.
	global $pmpro_pages;
	if ( ! empty( $pmpro_pages['pmprobp_restricted'] ) ) {
		$redirect_to = get_permalink( $pmpro_pages['pmprobp_restricted'] );
	}

	return $redirect_to;
}
add_filter( 'pmprobbp_check_forum_redirect_url', 'pmprobb_buddyboss_forum_redirect_url', 10, 2 );

