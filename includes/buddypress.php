<?php
/**
 * BuddyPress / BuddyBoss compatibility.
 *
 * Improves the frontend experience for restricted forums when BuddyBoss
 * Platform is active.
 *
 * @package PMPro_bbPress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether BuddyBoss Platform is active.
 *
 * @since TBD
 *
 * @return bool
 */
function pmprobb_is_buddyboss() {
	return defined( 'BP_PLATFORM_VERSION' );
}

/**
 * Hide the no access message for restricted forums on the forum archive,
 * since some forums in the list may be locked and others not. Direct access
 * to a restricted forum still redirects via pmprobbp_check_forum().
 *
 * @since TBD
 *
 * @param mixed  $override  False to keep default behavior, or content to return.
 * @param string $content   The original post content.
 * @param bool   $hasaccess Whether the current user has access to the post.
 * @return mixed
 */
function pmprobb_bp_suppress_archive_no_access_msg( $override, $content, $hasaccess ) {
	// Already overridden by another filter, or the user has access: leave as-is.
	if ( false !== $override || $hasaccess ) {
		return $override;
	}

	if ( pmprobb_is_buddyboss() && function_exists( 'bbp_is_forum_archive' ) && bbp_is_forum_archive() ) {
		return $content; // Return original content; skip the no access message.
	}

	return $override;
}
add_filter( 'pmpro_membership_content_filter', 'pmprobb_bp_suppress_archive_no_access_msg', 10, 3 );

/**
 * Redirect restricted-forum visitors to the PMPro BuddyPress/BuddyBoss
 * "Access Restricted" page if it is set, instead of the forums archive.
 *
 * Requires the PMPro BuddyPress Add On, which registers the page setting.
 *
 * @since TBD
 *
 * @param string $redirect_to Default redirect URL (forums archive + noaccess).
 * @param int    $forum_id    The restricted forum's ID.
 * @return string
 */
function pmprobb_bp_forum_redirect_url( $redirect_to, $forum_id ) {
	global $pmpro_pages;

	if ( pmprobb_is_buddyboss() && ! empty( $pmpro_pages['pmprobp_restricted'] ) ) {
		$redirect_to = get_permalink( $pmpro_pages['pmprobp_restricted'] );
	}

	return $redirect_to;
}
add_filter( 'pmprobbp_check_forum_redirect_url', 'pmprobb_bp_forum_redirect_url', 10, 2 );
