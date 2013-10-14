<?php
/**
 * Plugin Name: PMPro bbPress
 * Plugin URI: http://www.paidmembershipspro.com/pmpro-bbpress/
 * Description: Allow individual forums to be locked down for members.
 * Version: .1
 * Author: Stranger Studios, Scott Sousa
 * Author URI: http://www.strangerstudios.com
 */

/**
 * These functions add the PMPro Require Membership metabox to bbPress Forums.
 */
add_action( 'init', 'pmprobbpress_init', 20 );
function pmprobbp_add_meta_box() {
	add_meta_box( 'pmpro_page_meta', 'Require Membership', 'pmpro_page_meta', 'forum', 'side' );	
}
function pmprobbpress_init() {
	if ( is_admin() )
		add_action( 'admin_menu', 'pmprobbp_add_meta_box' );
}

/**
 * These next two functions work together to lock down bbPress forums based on PMPro membership level.
 */	

/**
 * Check that the current user has access to this forum.
 */
add_action( 'wp', 'pmprobbp_check_forum' );
function pmprobbp_check_forum() {
	global $current_user;

	$forum_id = bbp_get_forum_id();
	$restricted_forums[bbp_get_forum_id()] = array(1,2);
	
	// Is this even a forum page at all?
	if( ! bbp_is_forum_archive() && ! empty( $forum_id ) && pmpro_bbp_is_forum() ) {			
		// The current user does not have access to this forum, re-direct them away
		if( ! pmpro_has_membership_access( $forum_id ) ) {
			wp_redirect( get_post_type_archive_link( 'forum' ) );
			exit;
		}
	}

	return true;
}

/**
 * Function to tell if the current forum, topic, or reply is a subpost of the forum_id passed.
 * If no forum_id is passed, it will return true if it is any forum, topic, or reply.
 */
function pmpro_bbp_is_forum( $forum_id = NULL ) {
	global $post;
		
	if(bbp_is_forum($post->ID))
	{		
		if(!empty($forum_id) && $post->ID == $forum_id)
			return true;
		elseif(empty($forum_id))
			return true;
		else
			return false;
	}
	elseif(bbp_is_topic($post->ID))
	{		
		if(!empty($forum_id) && $post->post_parent == $forum_id)
			return true;
		elseif(empty($forum_id))
			return true;
		else
			return false;
	}
	elseif(bbp_is_reply($post->ID))
	{		
		if(!empty($forum_id) && in_array($forum_id, $post->ancestors))
			return true;
		elseif(empty($forum_id))
			return true;
		else
			return false;
	}
	else
		return false;
}