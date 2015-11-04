<?php
/**
 * Plugin Name: Paid Memberships Pro - bbPress Add On
 * Plugin URI: http://www.paidmembershipspro.com/pmpro-bbpress/
 * Description: Allow individual forums to be locked down for members.
 * Version: 1.4
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

	//make sure pmpro and bbpress are active
	if ( !defined('PMPRO_VERSION') || !class_exists('bbPress') )
		return;
	
	if ( is_admin() )
		add_action( 'admin_menu', 'pmprobbp_add_meta_box' );

	//apply search filter to bbpress searches
	$filterqueries = pmpro_getOption("filterqueries");
	if(!empty($filterqueries))
	    add_filter( 'pre_get_posts', 'pmprobb_pre_get_posts' );
}

/**
 * These next two functions work together to lock down bbPress forums based on PMPro membership level.
 */	

/**
 * Check that the current user has access to this forum.
 */
add_action( 'template_redirect', 'pmprobbp_check_forum' );
function pmprobbp_check_forum() {
	//make sure pmpro and bbpress are active
	if ( !defined('PMPRO_VERSION') || !class_exists('bbPress') )
		return;
	
	global $current_user;

	$forum_id = bbp_get_forum_id();
	$restricted_forums[bbp_get_forum_id()] = array(1,2);
	
	// Is this even a forum page at all?
	if( ! bbp_is_forum_archive() && ! empty( $forum_id ) && pmpro_bbp_is_forum() ) {
		// The current user does not have access to this forum, re-direct them away
		if( ! pmpro_has_membership_access( $forum_id ) ) {
			wp_redirect(add_query_arg('noaccess', 1, get_post_type_archive_link( 'forum' )));
			exit;
		}
	}
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

/* Add membership level required message if user does not have access */
function pmpro_bbp_membership_msg() 
{
    $pmpro_bbp_error_msg = apply_filters('pmpro_bbp_error_msg', 'You do not have the required membership level to access that forum.'); // error message to display

    if (bbp_is_forum_archive() && !empty($_REQUEST['noaccess'])) 
	{
        echo '<p class="pmpro_bbp_membership_msg">' . $pmpro_bbp_error_msg . '</p>';
    }
}
add_action('bbp_template_before_forums_index','pmpro_bbp_membership_msg');

/*
 * Add topics and forums to pmpro_search_query
 */
function pmprobb_pre_get_posts($query) {

    global $wpdb;
		
	//only filter front end searches
	if(is_admin() || !$query->is_search || bbp_is_single_topic())
		return $query;
	
    //get all member forums
    $sqlQuery = "SELECT ID FROM $wpdb->posts WHERE post_type LIKE 'forum'";
    $all_forums = $wpdb->get_col($sqlQuery);
	
	//no forums?
	if(empty($all_forums))
		return $query;
	
    //add restricted forums to array
    $restricted_forum_ids = array();
    foreach($all_forums as $forum_id) {
        if(!pmpro_has_membership_access($forum_id))
            $restricted_forum_ids[] = $forum_id;
    }

	//if there are restricted forums, find topics and exclude them all from searches
	if(!empty($restricted_forum_ids))
	{	
		//get topics belonging to restricted forums
		$sqlQuery = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key LIKE '_bbp_forum_id' AND meta_value IN(" . implode(',', $restricted_forum_ids) . ")";
		$restricted_topic_ids = $wpdb->get_col($sqlQuery);

		//exclude restricted topics and posts
		$query->set('post__not_in', array_merge($query->get('post__not_in'), $restricted_topic_ids, $restricted_forum_ids));		
	}
	
    return $query;
}

/*
Function to add links to the plugin row meta
*/
function pmprobb_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-bbpress.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plugins-wordpress-repository/pmpro-bbpress/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprobb_plugin_row_meta', 10, 2);
