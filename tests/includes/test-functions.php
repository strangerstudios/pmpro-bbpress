<?php
namespace PMPro\Bbpress\Tests\Includes;

use PMPro\Tests\Base;

class Functions extends Base {

	/**
	 * @covers ::pmprobb_getOptions()
	 */
	public function test_pmprobb_getOptions() {

		$expected = [
			'error_message'          => 'This forum is for members only.',
			'member_links'           => 0,
			'hide_member_forums'     => 0,
			'hide_forum_roles'       => 0,
			'show_membership_levels' => 0,
			'levels'                 => [],
		];

		$actual = pmprobb_getOptions( true );

		$this->assertSame( $expected, $actual );

		$actual = pmprobb_getOptions( false );

		$this->assertSame( $expected, $actual );

		$expected = [
			'error_message'          => 'This forum is for unit test members only.',
			'member_links'           => 1,
			'hide_member_forums'     => 1,
			'hide_forum_roles'       => 1,
			'show_membership_levels' => 1,
			'levels'                 => [ 'unit', 'test' ],
		];

		foreach ( $expected as $key => $option ) {
			$prefix = ( 'levels' !== $key ) ? 'pmprobb_option_' : 'pmprobb_options_';

			update_option( $prefix . $key, $option );
		}

		$actual = pmprobb_getOptions( true );

		$this->assertSame( $expected, $actual );

		$actual = pmprobb_getOptions( false );

		$this->assertSame( $expected, $actual );

	}

}

//EOF
