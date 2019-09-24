<?php
namespace PMPro\Bbpress\Tests\Includes;

use PMPro\Tests\Base;

class Options_Membership_Levels extends Base {

	/**
	 * @covers ::pmprobbp_add_meta_box
	 */
	public function test_pmprobbp_add_meta_box() {

		pmprobbp_add_meta_box();

		global $wp_meta_boxes;

		$expected = [
			'pmpro_page_meta' => [
				'id'       => 'pmpro_page_meta',
				'title'    => 'Require Membership',
				'callback' => 'pmpro_page_meta',
				'args'     => null,
			],
		];

		$this->assertContains( $expected, $wp_meta_boxes['forum']['side'] );

	}

}

//EOF
