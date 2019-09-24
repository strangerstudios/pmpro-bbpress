<?php
namespace PMPro\Bbpress\Tests\Includes;

use PMPro\Tests\Base;

class Shortcodes extends Base {

	public function data_bbp_user_activity_shortcode() {

		return [
			[ // #0
				[
					'activity_type' => 'topic',
				],
				null,
				[
					'<div class="widget widget_display_topics">',
					'<h2 class="widgettitle">My Recent Activity</h2>'
				],
				[
					'<li>Post excerpt',
					'</em></li>',
				],
			],
			[ // #1
				[
					'activity_type' => 'reply',
					'title'         => 'My Recent Unit Test',
					'show_excerpt'  => true,
					'show_date'     => true,
				],
				null,
				[
					'<div class="widget widget_display_topics">',
					'<h2 class="widgettitle">My Recent Unit Test</h2>',
					'<li>Post excerpt',
					'</em></li>',
				],
				[
					'<h2 class="widgettitle">My Recent Activity</h2>'
				],

			]
		];

	}

	/**
	 * @covers ::bbp_user_activity_shortcode
	 * @dataProvider data_bbp_user_activity_shortcode
	 */
	public function test_bbp_user_activity_shortcode( $atts, $content, $contains, $not_contains ) {

		global $current_user;

		$this->factory->post->create_many(
			5,
			[
				'post_type' => 'topic',
				'author'    => $current_user->ID,
			]
		);

		$this->factory->post->create_many(
			5,
			[
				'post_type' => 'reply',
				'author'    => $current_user->ID,
			]
		);

		$output = bbp_user_activity_shortcode( $atts, $content );

		foreach ( $contains as $value ) {
			$this->assertContains( $value, $output );
		}

		foreach ( $not_contains as $value ) {
			$this->assertNotContains( $value, $output );
		}

	}

	/**
	 * @covers ::bbp_user_activity_shortcode
	 */
	public function test_bbp_user_activity_shortcode__empty() {

		_delete_all_data();
		self::flush_cache();

		$output = bbp_user_activity_shortcode( [], null );

		$this->assertEmpty( $output );

	}
}

// EOF
