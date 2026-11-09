<?php
/*
	bbPress shortcode for member activity
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbp_user_activity_shortcode($atts, $content = null) {
	// Make sure bbPress is active.
	if ( ! class_exists( 'bbPress' ) ) {
		return '';
	}

	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// examples: [bbp-user-activity activity_type="topic" show_date="true" title="My Recent Topics"]
	$atts = shortcode_atts( array(
		'activity_type' => 'topic', //set to topic or reply
		'bbp_user_id' => get_current_user_id(), //can set attribute to a specific user ID or omit for the current_user
		'count' => 5, //number of entries to show
		'show_date' => false, //optionally show the entry date
		'show_excerpt' => false, //optionally show an excerpt of the entry
		'title' => __( 'My Recent Activity', 'pmpro-bbpress' ), //an optional title, h2 class="widgettitle" format
	), $atts, 'bbp-user-activity' );

	$activity_type = ( 'reply' === $atts['activity_type'] ) ? bbp_get_reply_post_type() : bbp_get_topic_post_type();
	$bbp_user_id   = absint( $atts['bbp_user_id'] );
	$show_date     = wp_validate_boolean( $atts['show_date'] );
	$show_excerpt  = wp_validate_boolean( $atts['show_excerpt'] );
	$title         = $atts['title'];

	// No user to show activity for (e.g. a logged-out visitor).
	if ( empty( $bbp_user_id ) ) {
		return '';
	}

	$shortcode_query = new WP_Query( array(
		'post_type'           => $activity_type,
		'post_status'         => array( bbp_get_public_status_id(), bbp_get_closed_status_id() ),
		'posts_per_page'      => (int) $atts['count'],
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'author'		  		  => $bbp_user_id,
	) );

	// Bail if no replies
	if ( ! $shortcode_query->have_posts() ) {
		return '';
	}

	$items = '';
	while ( $shortcode_query->have_posts() ) : $shortcode_query->the_post();
		$post_id = $shortcode_query->post->ID;

		if ( bbp_get_reply_post_type() === $activity_type ) {
			$forum_id   = bbp_get_reply_forum_id( $post_id );
			$item_url   = bbp_get_reply_url( $post_id );
			$item_title = bbp_get_reply_topic_title( $post_id );
			$excerpt    = bbp_get_reply_excerpt( $post_id, 50 );
		} else {
			$forum_id   = bbp_get_topic_forum_id( $post_id );
			$item_url   = bbp_get_topic_permalink( $post_id );
			$item_title = bbp_get_topic_title( $post_id );
			$excerpt    = bbp_get_topic_excerpt( $post_id, 50 );
		}

		// Skip entries in forums the viewer can't access.
		if ( function_exists( 'pmpro_has_membership_access' ) && ! empty( $forum_id ) && ! pmpro_has_membership_access( $forum_id ) ) {
			continue;
		}

		$items .= '<li>';

		$item_link = '<a class="bbp-reply-topic-title" href="' . esc_url( $item_url ) . '" title="' . esc_attr( $excerpt ) . '">' . esc_html( $item_title ) . '</a>';

		if ( $show_excerpt ) :
			$items .= esc_html( $excerpt ) . '&nbsp;';
		endif;

		// Reply link and timestamp
		if ( $show_date ) :
			// translators: 1: reply link, 2: reply timestamp
			$items .= sprintf(_x( '%1$s %2$s', 'widgets', 'bbpress' ), $item_link, '<em>' . esc_html( bbp_get_time_since( get_the_time( 'U' ) ) ) . '</em>');
		// Only the reply title
		else :
			// translators: 1: reply link
			$items .= sprintf(_x( '%1$s', 'widgets', 'bbpress' ), $item_link);
		endif;

		$items .= '</li>';

		endwhile;

	// Reset the $post global
	wp_reset_postdata();

	// Nothing the viewer can see.
	if ( empty( $items ) ) {
		return '';
	}

	$r = '<div class="widget widget_display_topics">';
	if ( ! empty( $title ) ) {
		$r .= '<h2 class="widgettitle">' . esc_html( $title ) . '</h2>';
	}
	$r .= '<ul>' . $items . '</ul>';
	$r .= '</div>';

	return $r;
}
add_shortcode('bbp-user-activity', 'bbp_user_activity_shortcode');
