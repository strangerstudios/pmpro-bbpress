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