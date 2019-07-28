<?php
namespace PMPro\Bbpress\Tests\Includes;

use PMPro\Tests\Base;

class Options extends Base {

	/**
	 * @covers ::pmprobb_bbp_admin_get_settings_sections
	 */
	public function test_pmprobb_bbp_admin_get_settings_sections() {

		$sections = pmprobb_bbp_admin_get_settings_sections( [] );
		$expects  = [
			'bbp_settings_pmpro' => [
				'title'    => 'Paid Memberships Pro',
				'callback' => 'pmprobb_section_general',
				'page'     => 'pmprobb',
			],
		];

		$this->assertSame( $expects, $sections );

	}

	/**
	 * @covers ::pmprobb_bbp_admin_get_settings_fields
	 */
	public function test_pmprobb_bbp_admin_get_settings_fields() {
		$fields  = pmprobb_bbp_admin_get_settings_fields( [] );
		$expects = [
			'bbp_settings_pmpro' => [
				'pmprobb_option_error_message' => [
					'title'             => 'Error Message',
					'callback'          => 'pmprobb_option_error_message',
					'sanitize_callback' => 'sanitize_text_field',
					'args'              => []
				],
				'pmprobb_option_member_links' => [
					'title'             => 'Member Links',
					'callback'          => 'pmprobb_option_member_links',
					'sanitize_callback' => 'intval',
					'args'              => []
				],
				'pmprobb_option_hide_member_forums' => [
					'title'             => 'Hide Member Forums',
					'callback'          => 'pmprobb_option_hide_member_forums',
					'sanitize_callback' => 'intval',
					'args'              => []
				],
				'pmprobb_option_hide_forum_roles' => [
					'title'             => 'Hide Forum Roles',
					'callback'          => 'pmprobb_option_hide_forum_roles',
					'sanitize_callback' => 'intval',
					'args'              => []
				],
				'pmprobb_option_show_membership_levels' => [
					'title'             => 'Show Membership Levels',
					'callback'          => 'pmprobb_option_show_membership_levels',
					'sanitize_callback' => 'intval',
					'args'              => []
				],
			],
		];

		$this->assertSame( $expects, $fields );

	}

	/**
	 * @covers ::pmprobb_bbp_map_settings_meta_caps
	 * @group foobar
	 */
	public function test_pmprobb_bbp_map_settings_meta_caps() {

		$user_id = $this->factory->user->create( [
			'role' => 'editor',
		] );

		wp_set_current_user( $user_id );
		set_current_screen( 'edit.php' );

		\bbpress::instance()->includes_dir;

		// Simulate admin setup in bbPress.
		$bb_dir = \bbpress::instance()->includes_dir;
		require_once $bb_dir . 'admin/admin.php';
		require_once $bb_dir . 'admin/actions.php';
		bbp_admin();

		//@todo this function only requires 2 arguments, should adjust function/filter.
		$caps = pmprobb_bbp_map_settings_meta_caps( [], '', 0, [] );
		$this->assertEmpty( $caps );

		$caps = pmprobb_bbp_map_settings_meta_caps( [], 'bbp_settings_pmpro', $user_id, [] );
		$this->assertSame( [ 'keep_gate' ], $caps );

		// Reset
		set_current_screen( 'front' );
		wp_set_current_user( 0 );

	}

}

//EOF
