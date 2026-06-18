<?php
/*
	General Forum Settings
	- Error message when trying to access a forum.
	- Show links to member forums on the Membership Account page.
	- Hide member forums from forum lists and search.
	- Hide forum roles in replies.
	- Show membership level in replies and on the bbpress profile page.

	These fields are rendered on the Memberships > Forums settings page.
	See includes/admin-settings.php.
*/

/**
 * Add a Paid Memberships Pro section to the bbPress > Settings screen that
 * links to the Memberships > Forums settings page, since users may expect
 * to find these settings here.
 *
 * @since 1.9
 *
 * @param array $sections bbPress settings sections.
 * @return array
 */
function pmprobb_bbp_admin_get_settings_sections( $sections ) {
	$sections['bbp_settings_pmpro'] = array(
		'title'    => esc_html__( 'Paid Memberships Pro', 'pmpro-bbpress' ),
		'callback' => 'pmprobb_bbp_settings_section_link',
		'page'     => 'pmprobb',
	);

	return $sections;
}
add_filter( 'bbp_admin_get_settings_sections', 'pmprobb_bbp_admin_get_settings_sections' );

/**
 * Register a single display-only field for the section.
 *
 * bbPress skips sections that have no fields, so the link is rendered as a
 * field. Nothing is saved for it.
 *
 * @since 1.9
 *
 * @param array $fields bbPress settings fields.
 * @return array
 */
function pmprobb_bbp_admin_get_settings_fields( $fields ) {
	$fields['bbp_settings_pmpro'] = array(
		'pmprobb_settings' => array(
			'title'             => esc_html__( 'Forum Settings', 'pmpro-bbpress' ),
			'callback'          => 'pmprobb_bbp_settings_field_link',
			'sanitize_callback' => '__return_false',
			'args'              => array(),
		),
	);

	return $fields;
}
add_filter( 'bbp_admin_get_settings_fields', 'pmprobb_bbp_admin_get_settings_fields' );

/**
 * Map the capability for the Paid Memberships Pro section.
 *
 * Uses manage_options to match the Memberships > Forums page the section
 * links to, so users never see a link to a page they cannot access.
 *
 * @since 1.9
 *
 * @param array  $caps    Capabilities for meta capability.
 * @param string $cap     Capability name.
 * @param int    $user_id User id.
 * @param array  $args    Arguments.
 * @return array
 */
function pmprobb_bbp_map_settings_meta_caps( $caps, $cap, $user_id, $args ) {
	if ( 'bbp_settings_pmpro' === $cap ) {
		$caps = array( 'manage_options' );
	}

	return $caps;
}
add_filter( 'bbp_map_settings_meta_caps', 'pmprobb_bbp_map_settings_meta_caps', 10, 4 );

/**
 * Section description for the Paid Memberships Pro section.
 *
 * @since 1.9
 */
function pmprobb_bbp_settings_section_link() {
	?>
	<p><?php esc_html_e( 'Restrict access to forums by membership level with the Paid Memberships Pro - bbPress Add On.', 'pmpro-bbpress' ); ?></p>
	<?php
}

/**
 * Display-only field linking to the Memberships > Forums settings page.
 *
 * @since 1.9
 */
function pmprobb_bbp_settings_field_link() {
	?>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-bbpress' ) ); ?>" class="button"><?php esc_html_e( 'Manage Forum Membership Settings', 'pmpro-bbpress' ); ?></a>
	<p class="description"><?php esc_html_e( 'Membership settings for forums are managed on the Memberships > Forums screen.', 'pmpro-bbpress' ); ?></p>
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
