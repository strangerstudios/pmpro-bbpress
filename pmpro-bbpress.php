<?php
/**
 * Plugin Name: Paid Memberships Pro - bbPress Add On
 * Plugin URI: http://www.paidmembershipspro.com/pmpro-bbpress/
 * Description: Allow individual forums to be locked down for members.
 * Version: 1.3
 * Author: Stranger Studios, Scott Sousa
 * Author URI: http://www.strangerstudios.com
 */

/**
 * Include the TGM_Plugin_Activation class.
 */
require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';

/**
 * Register the required plugins for this theme.
 */
add_action( 'tgmpa_register', 'pmprobbp_tgmpa_register' );
function pmprobbp_tgmpa_register() {
	/**
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(
		// Paid Memberships Pro
		array(
			'name' 		=> 'Paid Memberships Pro',
			'slug' 		=> 'paid-memberships-pro',
			'required' 	=> true
		),
		// bbPress
		array(
			'name' 		=> 'bbPress',
			'slug' 		=> 'bbpress',
			'required' 	=> true
		)
	);

	// Change this to your theme text domain, used for internationalising strings
	$theme_text_domain = 'pmpro';

	$config = array(
		'domain'       		=> $theme_text_domain,         	// Text domain - likely want to be the same as your theme.
		'default_path' 		=> '',                         	// Default absolute path to pre-packaged plugins
		'parent_menu_slug' 	=> 'themes.php', 				// Default parent menu slug
		'parent_url_slug' 	=> 'themes.php', 				// Default parent URL slug
		'menu'         		=> 'install-required-plugins', 	// Menu slug
		'has_notices'      	=> true,                       	// Show admin notices or not
		'is_automatic'    	=> false,					   	// Automatically activate plugins after installation or not
		'message' 			=> '',							// Message to output right before the plugins table
		'strings'      		=> array(
			'page_title'                       			=> __( 'Install Required Plugins', $theme_text_domain ),
			'menu_title'                       			=> __( 'Install Plugins', $theme_text_domain ),
			'installing'                       			=> __( 'Installing Plugin: %s', $theme_text_domain ), // %1$s = plugin name
			'oops'                             			=> __( 'Something went wrong with the plugin API.', $theme_text_domain ),
			'notice_can_install_required'     			=> _n_noop( 'PMPro bbPress requires the following plugin: %1$s.', 'PMPro bbPress requires the following plugins: %1$s.' ), // %1$s = plugin name(s)
			'notice_can_install_recommended'			=> _n_noop( 'PMPro bbPress recommends the following plugin: %1$s.', 'PMPro bbPress recommends the following plugins: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_install'  					=> _n_noop( 'PMPro bbPress: Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'PMPro bbPress: Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.' ), // %1$s = plugin name(s)
			'notice_can_activate_required'    			=> _n_noop( 'PMPro bbPress: The following required plugin is currently inactive: %1$s.', 'PMPro bbPress: The following required plugins are currently inactive: %1$s.' ), // %1$s = plugin name(s)
			'notice_can_activate_recommended'			=> _n_noop( 'PMPro bbPress: The following recommended plugin is currently inactive: %1$s.', 'PMPro bbPress: The following recommended plugins are currently inactive: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_activate' 					=> _n_noop( 'PMPro bbPress: Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'PMPro bbPress: Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.' ), // %1$s = plugin name(s)
			'notice_ask_to_update' 						=> _n_noop( 'PMPro bbPress: The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'PMPro bbPress: The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_update' 						=> _n_noop( 'PMPro bbPress: Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'PMPro bbPress: Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.' ), // %1$s = plugin name(s)
			'install_link' 					  			=> _n_noop( 'Begin installing plugin', 'Begin installing plugins' ),
			'activate_link' 				  			=> _n_noop( 'Activate installed plugin', 'Activate installed plugins' ),
			'return'                           			=> __( 'Return to Required Plugins Installer', $theme_text_domain ),
			'plugin_activated'                 			=> __( 'Plugin activated successfully.', $theme_text_domain ),
			'complete' 									=> __( 'All plugins installed and activated successfully. %s', $theme_text_domain ), // %1$s = dashboard link
			'nag_type'									=> 'updated' // Determines admin notice type - can only be 'updated' or 'error'
		)
	);

	tgmpa( $plugins, $config );
}




/**
 * These functions add the PMPro Require Membership metabox to bbPress Forums.
 */
add_action( 'init', 'pmprobbpress_init', 20 );
function pmprobbp_add_meta_box() {
	add_meta_box( 'pmpro_page_meta', 'Require Membership', 'pmpro_page_meta', 'forum', 'side' );	
}
function pmprobbpress_init() {

	//make sure PMPro is activated
	if(!function_exists("pmpro_getOption"))
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
add_action( 'wp', 'pmprobbp_check_forum' );
function pmprobbp_check_forum() {
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
