<?php
/*
	bbPress shortcode for member activity
*/

function bbp_user_activity_shortcode($atts, $content = null) {
	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// examples: [bbp-user-activity activity_type="topic" show_date="true" title="My Recent Topics"]
    extract(shortcode_atts(array(
		'activity_type' => 'topic', //set to topic or reply
		'bbp_user_id' => $current_user->ID, //can set attribute to a specific user ID or omit for the current_user
		'count' => 5, //number of entries to show
		'show_date' => false, //optionally show the entry date
		'show_excerpt' => false, //optionally show an excerpt of the entry
		'title' => 'My Recent Activity', //an optional title, h2 class="widgettitle" format 
    ), $atts));
	
	global $current_user;

	$shortcode_query = new WP_Query( array(
		'post_type'           => $activity_type,
		'post_status'         => array( bbp_get_public_status_id(), bbp_get_closed_status_id() ),
		'posts_per_page'      => (int) $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'author'		  		  => $bbp_user_id,
	) );

	// Bail if no replies
	if ( ! $shortcode_query->have_posts() ) {
		return;
	}

	$r = '<div class="widget widget_display_topics">';
	$r .= '<h2 class="widgettitle">' . __($title, 'pmprobb') . '</h2>';
	$r .= '<ul>';
	
	while ( $shortcode_query->have_posts() ) : $shortcode_query->the_post();
		$r .= '<li>';

		// Verify the reply ID
		$reply_id   = bbp_get_reply_id( $shortcode_query->post->ID );
		$reply_link = '<a class="bbp-reply-topic-title" href="' . esc_url( bbp_get_reply_url( $reply_id ) ) . '" title="' . esc_attr( bbp_get_reply_excerpt( $reply_id, 50 ) ) . '">' . bbp_get_reply_topic_title( $reply_id ) . '</a>';
		
		if ( ! empty( $show_excerpt ) ) :
			$r .= esc_attr( bbp_get_reply_excerpt( $reply_id, 50 ) ) . '&nbsp;';
		endif;
		
		// Reply link and timestamp
		if ( ! empty( $show_date ) ) :
			// translators: 1: reply link, 2: reply timestamp
			$r .= sprintf(_x( '%1$s %2$s', 'widgets', 'bbpress' ), $reply_link, '<em>' . bbp_get_time_since( get_the_time( 'U' ) ) . '</em>');
		// Only the reply title
		else :
			// translators: 1: reply link
			$r .= sprintf(_x( '%1$s', 'widgets', 'bbpress' ), $reply_link);
		endif;
		
		$r .= '</li>';
		
		endwhile;
	
	$r .= '</ul>';
	$r .= '</div>';
	// Reset the $post global	
	wp_reset_postdata();
	
	return $r;
}
add_shortcode('bbp-user-activity', 'bbp_user_activity_shortcode'); 