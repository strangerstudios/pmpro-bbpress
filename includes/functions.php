<?php
/**
 * Function to get PMPro bbPress Options
 */
function pmprobb_getOptions($force = false)
{
	global $pmprobb_options;
	
	if(empty($force) && isset($pmprobb_options))
		return $pmprobb_options;
	
	//the bbpress settings are saved as individual settings, pulling them together
	$pmprobb_options = array(
		'error_message' => get_option('pmprobb_option_error_message', 'This forum is for members only.'),
		'member_links' => get_option('pmprobb_option_member_links', 0),
		'hide_member_forums' => get_option('pmprobb_option_hide_member_forums', 0),
		'hide_forum_roles' => get_option('pmprobb_option_hide_forum_roles', 0),
		'show_membership_levels' => get_option('pmprobb_option_show_membership_levels', 0),
		'levels' => get_option('pmprobb_options_levels', array()),
	);	
		
	return $pmprobb_options;
}

/**
 * Get the bbPress role for a given level.
 */
function pmprobb_get_role_for_level( $level_id ) {
	$options = pmprobb_getOptions();
    if ( ! empty( $options['levels'] ) 
      && ! empty( $options['levels'][$level_id] )
      && ! empty( $options['levels'][$level_id]['role'] ) ) {
		  return $options['levels'][$level_id]['role'];
	} else {
		return get_option( '_bbp_default_role', 'bbp_participant' );
	}
}