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
		'error_message' => get_option('pmprobb_option_error_message', __( 'This forum is for members only.', 'pmpro-bbpress' ) ),
		'member_links' => get_option('pmprobb_option_member_links', 0),
		'hide_member_forums' => get_option('pmprobb_option_hide_member_forums', 0),
		'hide_forum_roles' => get_option('pmprobb_option_hide_forum_roles', 0),
		'show_membership_levels' => get_option('pmprobb_option_show_membership_levels', 0),
		'levels' => get_option('pmprobb_options_levels', array()),
	);	
		
	return $pmprobb_options;
}

/**
 * Get the bbPress forum role configured for a given level.
 *
 * Returns an empty string when the level has no custom forum role set
 * ("Preserve Current Forum Role"), so callers can leave the user's
 * existing forum role untouched instead of forcing the default role.
 *
 * @param int $level_id The membership level ID.
 * @return string The configured forum role, or '' if none is set.
 */
function pmprobb_get_role_for_level( $level_id ) {
	$options = pmprobb_getOptions();
	if ( ! empty( $options['levels'] )
	  && ! empty( $options['levels'][$level_id] )
	  && ! empty( $options['levels'][$level_id]['role'] ) ) {
		return $options['levels'][$level_id]['role'];
	}

	return '';
}

/**
 * Get the highest-capability bbPress forum role assigned across a set of levels.
 *
 * Levels set to "Preserve Current Forum Role" assign no role and are skipped.
 * bbPress only supports one role per user, so when multiple levels assign a
 * role, the one with the most capabilities wins.
 *
 * @since TBD
 *
 * @param array $levels Array of level objects (each with an ->id property).
 * @return string The highest-capability forum role, or '' if no level assigns one.
 */
function pmprobb_get_highest_role_for_levels( $levels ) {
	if ( empty( $levels ) || ! is_array( $levels ) ) {
		return '';
	}

	// Collect the custom forum roles assigned by these levels.
	$role_options = array();
	foreach ( $levels as $level ) {
		$role = pmprobb_get_role_for_level( $level->id );
		if ( ! empty( $role ) ) {
			$role_options[] = $role;
		}
	}
	$role_options = array_unique( $role_options );

	// Find the role with the most capabilities.
	$highest_role = '';
	$highest_caps = 0;
	foreach ( $role_options as $role_option ) {
		$role = get_role( $role_option );
		if ( empty( $role ) ) {
			continue;
		}

		$role_caps = count( $role->capabilities );
		if ( $role_caps > $highest_caps ) {
			$highest_role = $role_option;
			$highest_caps = $role_caps;
		}
	}

	return $highest_role;
}
