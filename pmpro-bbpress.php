<?php
/**
 * Plugin Name: Paid Memberships Pro - bbPress Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-bbpress/
 * Description: Allow individual forums to be locked down for members.
 * Version: 1.7.1
 * Author: Paid Memberships Pro, Scott Sousa
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-bbpress
 * Domain Path: /languages
 */

//includes
define('PMPROBB_DIR', dirname(__FILE__));
require_once(PMPROBB_DIR . '/includes/functions.php');
require_once(PMPROBB_DIR . '/includes/options.php'); 
require_once(PMPROBB_DIR . '/includes/options-membership-levels.php');
require_once(PMPROBB_DIR . '/includes/shortcodes.php'); 

/**
 * Load text domain
 */
function pmprobb_load_plugin_text_domain() {
	load_plugin_textdomain( 'pmpro-bbpress', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'pmprobb_load_plugin_text_domain' );

/**
 * Load search filters if set.
 */
function pmprobb_init() {

	// Make sure PMPro and bbPress is installed.
	if ( !defined('PMPRO_VERSION') || !class_exists('bbPress') ) {
		return;
	}

	// bail if we're in the admin.
	if ( is_admin() ) {
		return;
	}

	$filterqueries = pmpro_getOption( "filterqueries" );
	if ( apply_filters( 'pmprobb_filter_forum_queries', true ) && ! empty( $filterqueries ) ) {
		add_filter( 'pre_get_posts', 'pmprobb_pre_get_posts' );
	}

	// Try to filter the search results too, just in case.
	$options = pmprobb_getOptions();
	if(!empty($options['hide_member_forums']) && function_exists('bbp_is_forum_archive') && bbp_is_forum_archive() ) {
		add_filter( 'pre_get_posts', 'pmpro_search_filter' );
		add_filter( 'pmpro_search_filter_post_types', 'pmprobb_pmpro_search_filter_post_types' );
	}

}
add_action('init', 'pmprobb_init', 50);
 
/**
 * Filter the search results for forums/topics.
 * @since 1.7
 */
function pmprobb_filter_forum_search_results( $query ) {
	if ( apply_filters( 'pmprobb_filter_topic_queries', true ) && function_exists('bbp_is_search_results') && bbp_is_search_results() ) {
		add_filter( 'pre_get_posts', 'pmprobb_pre_get_posts' );
	}
	return $query;
}
add_filter( 'pre_get_posts', 'pmprobb_filter_forum_search_results' );

/**
 * Admin init
 */
function pmprobb_admin_init() {
	//if on the edit level page, enqueue color picker
	if(!empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-membershiplevels' && isset($_REQUEST['edit'])) {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}	
}
add_action('admin_init', 'pmprobb_admin_init');

/**
 * These next two functions work together to lock down bbPress forums based on PMPro membership level.
 */	

/**
 * Check that the current user has access to this forum.
 */
add_action( 'template_redirect', 'pmprobbp_check_forum' );
function pmprobbp_check_forum() {
	// Make sure pmpro and bbpress are active.
	if ( ! defined( 'PMPRO_VERSION' ) || ! class_exists( 'bbPress' ) ) {
		return;
	}
	
	global $current_user;

	$forum_id = bbp_get_forum_id();
	// Is this even a forum page at all?
	if( ! bbp_is_forum_archive() && ! empty( $forum_id ) && pmpro_bbp_is_forum() ) {
		// The current user does not have access to this forum, re-direct them away
		if( ! pmpro_has_membership_access( $forum_id ) ) {
			// save to session in case we want to redirect later on
			$_SESSION['pmpro_bbp_redirected_from'] = $_SERVER['REQUEST_URI'];
			$redirect_to = add_query_arg( 'noaccess', 1, get_post_type_archive_link( 'forum' ) );
			$redirect_to = apply_filters( 'pmprobbp_check_forum_redirect_url', $redirect_to, $forum_id );
			wp_redirect( $redirect_to );
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
	
	//avoid notices on 404 pages
	if(!$post)
		return false;

	//if bbPress is not active, no forum
	if(!function_exists('bbp_is_forum'))
		return false;

	//check bbpress tests
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
function pmpro_bbp_membership_msg() {
  // Make sure bbpress is active.
	if ( ! class_exists( 'bbPress' ) ) {
		return;
	}

  if (bbp_is_forum_archive() && !empty($_REQUEST['noaccess'])) {
      $pmpro_bbp_error_msg = apply_filters('pmpro_bbp_error_msg', __( 'You do not have the required membership level to access that forum.', 'pmpro-bbpress' ) );
      echo '<p class="pmpro_bbp_membership_msg">' . esc_html( $pmpro_bbp_error_msg ) . '</p>';
  }
}
add_action('bbp_template_before_forums_index','pmpro_bbp_membership_msg');

/*
 * Add topics and forums to pmpro_search_query
 */
function pmprobb_pre_get_posts($query) {

    global $wpdb;
		
  	// Make sure pmpro and bbpress are active.
	if ( ! defined( 'PMPRO_VERSION' ) || ! class_exists( 'bbPress' ) ) {
		return $query;
	}

	// Get option to see if we need to hide Membership forum from front-end.
	$options = pmprobb_getOptions();
	if(empty($options['hide_member_forums'])) {
		return $query;
	}

	//only filter front end queries for forums/topics.
	if( is_admin() || ! isset( $query->query_vars['post_type'] ) || ! pmprobb_check_values( $query->query_vars['post_type'], array( 'forum', 'topic', 'reply' ) ) ) {
		return $query;
	}

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
	Adds "pmpro-level-ID" to the forum topic replies post class where ID
	is the membership level of the reply author. Useful for styling
	forum replies based on membership level.
	
	Style the replies using this post class in the following format:
	#bbpress-forums li.bbp-body div.pmpro-level-1 { } 
	#bbpress-forums li.bbp-body div.pmpro-level-2 { }
	Add this code to your active theme's functions.php or a custom plugin.
*/
function pmprobb_pmpro_reply_post_class($classes) {
	// Make sure pmpro and bbpress are active.
	if ( ! defined( 'PMPRO_VERSION' ) || ! class_exists( 'bbPress' ) ) {
		return $classes;
	}

	$reply_author_id = bbp_get_reply_author_id();
	$reply_author_membership_level = pmpro_getMembershipLevelForUser($reply_author_id);
	if(!empty($reply_author_membership_level)) {
		$classes[] = 'pmpro-level-' . $reply_author_membership_level->id;
	}
	return $classes;
}
add_filter( 'bbp_get_reply_class', 'pmprobb_pmpro_reply_post_class');

/*
	Generates CSS to color member posts.
*/
function pmprobb_forum_color_css() {
	//only on forum pages
	if(!pmpro_bbp_is_forum())
		return;
	
	//get color options and build rules
	$options = pmprobb_getOptions();	
	$rule = array();
	if(!empty($options['levels'])) {
		foreach($options['levels'] as $level_id => $level) {
			if(!empty($level['color']))
				$rules[] = ".topic.pmpro-level-" . $level_id . ", .reply.pmpro-level-" . $level_id . " {background-color: " . $level['color'] . " !important; }";
		}
	}
	
	//no rules?
	if(empty($rules))
		return false;
	
	//show rules
	?>
<style type="text/css" media="screen">
	<?php echo implode("\n", $rules) . "\n";?>
</style>
	<?php
}
add_action('wp_head', 'pmprobb_forum_color_css');

/*
	Add links to the top of the member links
*/
function pmprobb_pmpro_member_links_top() {
  // Make sure pmpro and bbpress are active.
	if ( ! defined( 'PMPRO_VERSION' ) || ! class_exists( 'bbPress' ) ) {
		return;
	}

	$options = pmprobb_getOptions();
	if(empty($options['member_links']))
		return;
	
	$forums = get_posts(array('post_type'=>'forum', 'post_status'=>'publish'));	
	foreach($forums as $forum) {
		//show in member links?	
		if(pmpro_has_membership_access($forum->ID)) {
		?>
		<li><a href="<?php echo get_permalink($forum->ID);?>"><?php echo $forum->post_title;?></a></li>
		<?php
		}
	}
}
add_filter('pmpro_member_links_top','pmprobb_pmpro_member_links_top');

/*
	Hide forums from list and search results
*/
function pmprobb_pmpro_search_filter_post_types($post_types)
{
	$options = pmprobb_getOptions();
	if(!empty($options['hide_member_forums'])) {
		$post_types[] = 'forum';
		$post_types[] = 'topic';
		$post_types[] = 'reply';
		array_unique($post_types);	
	}
	return $post_types;
}

/**
 * Change error message for PMPro bbPress	
 */
function pmprobb_pmpro_bbp_error_msg()
{
	$options = pmprobb_getOptions();
	return $options['error_message'];
}
add_filter('pmpro_bbp_error_msg', 'pmprobb_pmpro_bbp_error_msg');

/*
	Hide the forum role from the bbPress forums replies author link.	
*/	
function pmprobb_pmpro_hide_role($args) {
	$options = pmprobb_getOptions();
	if(!empty($options['hide_forum_roles']))
		$args['show_role'] = false;
	return $args;
}
add_filter ('bbp_before_get_reply_author_link_parse_args', 'pmprobb_pmpro_hide_role' );

/*
    Change user's forum role when they change levels.
    TODO: For MMPU compatibility, we need to get all of the user's Levels
          and use the highest role found.
*/
function pmprobb_pmpro_after_change_membership_level( $level_id, $user_id, $cancel_level_id ) {
    // Make sure bbPress is active.
    if ( ! function_exists( 'bbp_set_user_role' ) ) {
        return;
    }
    
    // Ignore admins.
    if ( user_can( $user_id, 'manage_options' ) ) {
        return;
    }
    
    if ( $level_id > 0 ) {
        // Give them the role for their level.
        $bbp_new_role = pmprobb_get_role_for_level( $level_id );
        bbp_set_user_role( $user_id, $bbp_new_role );
    } else {
        // Cancelling. Give them the default role back
        // if their old level was a non-default role.
        $bbp_old_role = pmprobb_get_role_for_level( $cancel_level_id );
        $bbp_default_role = get_option( '_bbp_default_role', 'bbp_participant' );
        if ( $bbp_old_role != $bbp_default_role ) {
            bbp_set_user_role( $user_id, $bbp_default_role );
        }
    }
}
add_action( 'pmpro_after_change_membership_level', 'pmprobb_pmpro_after_change_membership_level', 10, 3 );

/*
	Adds a Section "Membership Level" and displays the user's level
	on the bbPress User Profile page.	
*/
function pmprobb_pmpro_bbp_template_before_user_profile() 
{
  // Make sure pmpro and bbpress are active.
  if ( ! defined( 'PMPRO_VERSION' ) || ! class_exists( 'bbPress' ) ) {
    return;
  }

	$options = pmprobb_getOptions();
	if(empty($options['show_membership_levels']))
		return;
	
	$profile_user = new stdClass();
	$profile_user->membership_level = pmpro_getMembershipLevelForUser(bbp_get_user_id( 0, true, false ));
	if(!empty($profile_user->membership_level))
	{
		?>
		<div id="bbp-user-profile" class="bbp-user-profile">
			<h2 class="entry-title"><?php esc_html_e('Membership Level','pmpro-bbpress');?></h2>
			<div class="bbp-user-section">
				<?php echo esc_html( $profile_user->membership_level->name ); ?>
			</div>
		</div>
		<?php
	}
};
add_action( 'bbp_template_before_user_profile', 'pmprobb_pmpro_bbp_template_before_user_profile', 10, 0 );

/*
	Display the Membership Level of the reply author 
	in your bbPress forum replies.	
*/
function pmprobb_pmpro_bbp_theme_after_reply_author_details() 
{
  // Make sure pmpro and bbpress are active.
  if ( ! defined( 'PMPRO_VERSION' ) || ! class_exists( 'bbPress' ) ) {
    return;
  }

	$options = pmprobb_getOptions();
	if(empty($options['show_membership_levels']))
		return;
		
	$displayed_user = bbp_get_reply_author_id(bbp_get_reply_id());
	$membership_level = pmpro_getMembershipLevelForUser($displayed_user);
	if(!empty($membership_level))
	{
	  echo '<br /><div class="bbp-author-role">' . esc_html( $membership_level->name ) . '</div>';
	}
}
add_action('bbp_theme_after_reply_author_details','pmprobb_pmpro_bbp_theme_after_reply_author_details', 10, 0);

/*
	Block the reply content if non-members try to access it directly
*/
function pmprobb_auth_reply_view($content, $reply_id)
{	
	//make sure PMPro is active
	if(!function_exists('pmpro_has_membership_access'))
		return $content;
	
	$has_access = pmpro_has_membership_access(bbp_get_reply_forum_id($reply_id), NULL, true);	
	if(!$has_access[0] || (!empty($has_access[1]) && !is_user_logged_in())) {
		$content = __( 'Replies viewable by members only', 'pmpro-bbpress' );
	}
	
	return $content;

}
add_filter( 'bbp_get_reply_content', 'pmprobb_auth_reply_view', 10, 2 );

/**
 * This will check if values are inside the haystack to check. Wrapper function for pre_get_posts sometimes passing array sometimes passes string.
 * @param $needle string/array. Checks whether values are in array via array to array comparison or string to array.
 * @param $haystack array. Array of values to compare $needle to.
 * @param $r boolean. Returns true or false if arrays match.
 */
function pmprobb_check_values( $needle, $haystack ) {
	if ( is_array( $needle ) ) {
		$r = array_intersect( $needle, $haystack );
	} else {
		$r = in_array( $needle, $haystack );
	}
	
	return $r;
}


/**
 * Function to stop non-members from receiving member only emails/notifications to subscribed topics
 * @since 1.7
 */
function pmprobb_non_member_reply_notifications( $user_ids, $reply_id, $topic_id ) {

	// If no user ID's are subscribed to a topic, just bail.
	if ( empty( $user_ids ) ) {
		return $user_ids;
	}
	
	// Make sure PMPro is active.
	if ( ! function_exists( 'pmpro_has_membership_access' ) ) {
		return $user_ids;
	}

	// Check if topic id belongs to restricted forum.
	$forum_id = bbp_get_topic_forum_id( $topic_id );

	if ( is_array( $user_ids ) ) {
		$allowed_users = array();
		foreach( $user_ids as $user_id ) {
			if (  pmpro_has_membership_access( $forum_id, $user_id, false ) ) {
				$allowed_users[] = $user_id;
			}
		}

		$user_ids = $allowed_users;
	}

	// Check if users have access to that forum or has an active membership level, if not remove them.
	return $user_ids;
}
add_filter( 'bbp_topic_subscription_user_ids', 'pmprobb_non_member_reply_notifications', 10, 3 );

/*
Function to add links to the plugin row meta
*/
function pmprobb_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-bbpress.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/pmpro-bbpress/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-bbpress' ) ) . '">' . esc_html__( 'Docs', 'pmpro-bbpress' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-bbpress' ) ) . '">' . esc_html__( 'Support', 'pmpro-bbpress' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprobb_plugin_row_meta', 10, 2);
