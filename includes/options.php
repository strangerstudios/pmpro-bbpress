<?php
/*
	General Forum Settings
	- Error message when trying to access a forum.
	- Give non-member users bbpress role. [none, spectator, participant]
	- Hide forum roles in replies.
	- Show membership level in replies.
	- Show membership level on bbpress profile page.
*/

function pmprobb_bbp_admin_get_settings_sections($sections) {
	$sections['bbp_settings_pmpro'] = array(
		'title'    => __( 'Paid Memberships Pro', 'pmpro' ),
		'callback' => 'pmprobb_section_general',
		'page'     => 'pmprobb',
	);

	return $sections;
}
add_filter('bbp_admin_get_settings_sections', 'pmprobb_bbp_admin_get_settings_sections');

function pmprobb_bbp_admin_get_settings_fields($fields) {
	$fields['bbp_settings_pmpro'] = array(
		'pmprobb_option_error_message' => array(
			'title'             => __( 'Error Message', 'pmprobb' ),
			'callback'          => 'pmprobb_option_error_message',
			'sanitize_callback' => 'sanitize_text_field',
			'args'              => array()
		),
		'pmprobb_option_member_links' => array(
			'title'             => __( 'Member Links', 'pmprobb' ),
			'callback'          => 'pmprobb_option_member_links',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),
		'pmprobb_option_hide_member_forums' => array(
			'title'             => __( 'Hide Member Forums', 'pmprobb' ),
			'callback'          => 'pmprobb_option_hide_member_forums',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),
		'pmprobb_option_hide_forum_roles' => array(
			'title'             => __( 'Hide Forum Roles', 'pmprobb' ),
			'callback'          => 'pmprobb_option_hide_forum_roles',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),
		'pmprobb_option_show_membership_levels' => array(
			'title'             => __( 'Show Membership Levels', 'pmprobb' ),
			'callback'          => 'pmprobb_option_show_membership_levels',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),
	);

	return $fields;
}
add_filter('bbp_admin_get_settings_fields', 'pmprobb_bbp_admin_get_settings_fields');

function pmprobb_bbp_map_settings_meta_caps($caps, $cap, $user_id, $args) {
	if($cap == 'bbp_settings_pmpro') {
		$caps = array( bbpress()->admin->minimum_capability );
	}

	return $caps;
}
add_filter('bbp_map_settings_meta_caps', 'pmprobb_bbp_map_settings_meta_caps', 10, 4);

/**
 * Options section.
 */
function pmprobb_section_general() {
?>
<?php
}

/**
 * Error Message Option
 */
function pmprobb_option_error_message() {
	$options = pmprobb_getOptions();
	if(isset($options['error_message']))
		$error_message = $options['error_message'];
	else
		$error_message = "";
	?>
	<input id='pmprobb_option_error_message' name='pmprobb_option_error_message' size='40' type='text' value='<?php echo esc_attr($error_message);?>' />
	<small>This message is shown when users attempt to view a forum or thread they don't have access to.</small>
	<?php
}

/**
 * Hide member forums from search and lists.
 */
function pmprobb_option_hide_member_forums() {
	$options = pmprobb_getOptions();
	if(isset($options['hide_member_forums']))
		$hide_member_forums = $options['hide_member_forums'];
	else
		$hide_member_forums = 0;
	?>
	<input type="checkbox" id="pmprobb_option_hide_member_forums" name="pmprobb_option_hide_member_forums" value="1" <?php checked($hide_member_forums, 1);?> />
	<label for="pmprobb_option_hide_member_forums">Hide member forums content from forums list and search results.</label>
	<?php
}

/**
 * Show member forums in member links section of membership account page.
 */
function pmprobb_option_member_links() {
	$options = pmprobb_getOptions();
	if(isset($options['member_links']))
		$member_links = $options['member_links'];
	else
		$member_links = 0;
	?>
	<input type="checkbox" id="pmprobb_option_member_links" name="pmprobb_option_member_links" value="1" <?php checked($member_links, 1);?> />
	<label for="pmprobb_option_member_links">Add links to member forums in the Member Links section of the Membership Account page.</label>
	<?php
}

/**
 * Hide Forum Roles
 */
function pmprobb_option_hide_forum_roles() {
	$options = pmprobb_getOptions();
	if(isset($options['hide_forum_roles']))
		$hide_forum_roles = $options['hide_forum_roles'];
	else
		$hide_forum_roles = 0;
	?>
	<input type="checkbox" id="pmprobb_option_hide_forum_roles" name="pmprobb_option_hide_forum_roles" value="1" <?php checked($hide_forum_roles, 1);?> />
	<label for="pmprobb_option_hide_forum_roles">Hide forum roles in replies.</label>
	<?php
}

/**
 * Show Membership Levels
 */
function pmprobb_option_show_membership_levels() {
	$options = pmprobb_getOptions();
	if(isset($options['show_membership_levels']))
		$show_membership_levels = $options['show_membership_levels'];
	else
		$show_membership_levels = 0;
	?>
	<input type="checkbox" id="pmprobb_option_show_membership_levels" name="pmprobb_option_show_membership_levels" value="1" <?php checked($show_membership_levels, 1);?> />
	<label for="pmprobb_option_show_membership_levels">Show membership levels in replies and on the bbPress profile page.</label>
	<?php
}
