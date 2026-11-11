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
 * @since 1.9
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
 * @since 1.9
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
 * Applies whenever that page is configured, on BuddyPress or BuddyBoss.
 *
 * @since 1.9
 *
 * @param string $redirect_to Default redirect URL (forums archive + noaccess).
 * @param int    $forum_id    The restricted forum's ID.
 * @return string
 */
function pmprobb_bp_forum_redirect_url( $redirect_to, $forum_id ) {
	global $pmpro_pages;

	if ( ! empty( $pmpro_pages['pmprobp_restricted'] ) ) {
		// Guard against a deleted page: get_permalink() returns false then.
		$permalink = get_permalink( $pmpro_pages['pmprobp_restricted'] );
		if ( ! empty( $permalink ) ) {
			$redirect_to = $permalink;
		}
	}

	return $redirect_to;
}
add_filter( 'pmprobbp_check_forum_redirect_url', 'pmprobb_bp_forum_redirect_url', 10, 2 );

/**
 * Hide the content of forum topic and reply activity items from users who
 * can't access the forum.
 *
 * bbPress copies the topic or reply content into the BuddyPress activity item,
 * where it shows in activity streams, activity RSS feeds and the BuddyPress
 * REST API without checking the forum's membership restrictions.
 *
 * Users who can moderate BuddyPress still see the content, so that editing an
 * activity item in the admin doesn't erase it.
 *
 * @since TBD
 *
 * @param array $activities Activity results, with the activity items in the 'activities' key.
 * @return array
 */
function pmprobb_bp_hide_restricted_activity_content( $activities ) {
	// Make sure PMPro and bbPress are active and there are activity items to check.
	if ( empty( $activities['activities'] ) || ! function_exists( 'pmpro_has_membership_access' ) || ! function_exists( 'bbp_get_topic_forum_id' ) ) {
		return $activities;
	}

	// Site moderators can see and edit all activity in the admin, so don't hide it from them.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return $activities;
	}

	foreach ( $activities['activities'] as $activity ) {
		// Only check bbPress topic and reply activity items that have content.
		if ( ! is_object( $activity ) || empty( $activity->content ) || ! in_array( $activity->type, array( 'bbp_topic_create', 'bbp_reply_create' ), true ) ) {
			continue;
		}

		// Group forum activity stores the topic or reply ID as the secondary item ID.
		$post_id  = ( 'groups' === $activity->component ) ? $activity->secondary_item_id : $activity->item_id;
		$forum_id = ( 'bbp_topic_create' === $activity->type ) ? bbp_get_topic_forum_id( $post_id ) : bbp_get_reply_forum_id( $post_id );

		if ( ! pmpro_has_membership_access( $forum_id ) ) {
			$activity->content = '';
		}
	}

	return $activities;
}
add_filter( 'bp_activity_get', 'pmprobb_bp_hide_restricted_activity_content' );
add_filter( 'bp_activity_get_specific', 'pmprobb_bp_hide_restricted_activity_content' );
