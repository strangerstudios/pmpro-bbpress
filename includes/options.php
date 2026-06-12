<?php
/*
	General Forum Settings
	- Error message when trying to access a forum.
	- Show links to member forums on the Membership Account page.
	- Hide member forums from forum lists and search.
	- Hide forum roles in replies.
	- Show membership level in replies and on the bbpress profile page.

	These fields are rendered on the Memberships > bbPress settings page.
	See includes/admin-settings.php.
*/

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
	<p class="description"><?php esc_html_e( "This message is shown when users attempt to view a forum or thread they don't have access to.", 'pmpro-bbpress' ); ?></p>
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
	<label for="pmprobb_option_hide_member_forums"><?php esc_html_e( 'Hide member forums content from forums list and search results.', 'pmpro-bbpress' ); ?></label>
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
	<label for="pmprobb_option_member_links"><?php esc_html_e( 'Add links to member forums in the Member Links section of the Membership Account page.', 'pmpro-bbpress' ); ?></label>
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
	<label for="pmprobb_option_hide_forum_roles"><?php esc_html_e( 'Hide forum roles in replies.', 'pmpro-bbpress' ); ?></label>
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
	<label for="pmprobb_option_show_membership_levels"><?php esc_html_e( 'Show membership levels in replies and on the bbPress profile page.', 'pmpro-bbpress' ); ?></label>
	<?php
}
