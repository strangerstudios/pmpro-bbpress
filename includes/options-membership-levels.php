<?php
/**
 * These functions add the PMPro Require Membership metabox to bbPress Forums.
 */
function pmprobbp_add_meta_box() {
	add_meta_box( 'pmpro_page_meta', 'Require Membership', 'pmpro_page_meta', 'forum', 'side' );	
}
add_action( 'admin_menu', 'pmprobbp_add_meta_box' );


/**
 * Add settings to membership levels.
 * Forum Role, Color 
*/
//show settings
function pmprobb_pmpro_membership_level_after_other_settings()
{
	if (!class_exists( 'bbPress' ))
		return;
	
	$level_id = intval($_REQUEST['edit']);	
	$options = pmprobb_getOptions();
		
	if(!empty($_REQUEST['forum_role']))
		$forum_role = sanitize_text_field($_REQUEST['forum_role']);
	elseif(!empty($options['levels']) && !empty($options['levels'][$level_id]['role']))
		$forum_role = $options['levels'][$level_id]['role'];
	else
		$forum_role = '';
	
	if(!empty($_REQUEST['forum_color']))
		$forum_color = preg_replace('/^0-9a-fA-F#/', '', $_REQUEST['forum_color']);
	elseif(!empty($options['levels']) && !empty($options['levels'][$level_id]['color']))
		$forum_color = $options['levels'][$level_id]['color'];
	else
		$forum_color = '';
	
?>
<h3 class="topborder">bbPress Settings</h3>
<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="forum_role"><?php _e('Forum Role', 'pmpro');?></label></th>
		<td>			
			<select id="forum_role" name="forum_role">
				<option value="" <?php selected($forum_role, '');?>><?php esc_html_e( 'Default Behavior', 'pmpro-bbpress' ); ?></option>
				<?php
					$roles = bbp_get_dynamic_roles();
					if(!empty($roles)) {
						foreach($roles as $value => $role) {
						?>
						<option value="<?php echo esc_attr($value);?>" <?php selected($forum_role, $value);?>><?php echo $role['name'];?></option>
						<?php
						}
					}
				?>
			</select>
			<p class="description"><?php esc_html_e( 'Leave as "Default Behavior" if you don\'t need to change roles by membership level.', 'pmpro-bbpress' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="forum_color"><?php _e('Background Color', 'pmpro');?></label></th>
		<td>			
			<input type="text" id="forum_color" name="forum_color" value="<?php echo esc_attr($forum_color);?>" />
			<p class="description"><?php printf( esc_html__( 'You can also add custom styles for %s via your CSS files.', 'pmpro-bbpress' ), "<code>.pmpro-level-" . intval( $level_id ) . "</code>" ); ?></p>
		</td>
	</tr>	
</tbody>
</table>
<script><!--
	jQuery(document).ready(function() {
		jQuery('#forum_color').wpColorPicker();
	});
--></script>
<?php	
}
add_action('pmpro_membership_level_after_other_settings', 'pmprobb_pmpro_membership_level_after_other_settings', 20);

//save settings
function pmprobb_pmpro_save_membership_level($level_id) {
	
	if ( class_exists( 'bbPress' ) ) {
		//get values
		$options = pmprobb_getOptions();
		
		//build array
		$options['levels'][$level_id] = array(
			'role' => sanitize_text_field($_REQUEST['forum_role']),
			'color' => preg_replace('/^0-9a-fA-F#/', '', $_REQUEST['forum_color'])
		);
	
		//save
		update_option('pmprobb_options_levels', $options['levels'], "no");
	}
}
add_action("pmpro_save_membership_level", "pmprobb_pmpro_save_membership_level");