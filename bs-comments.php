<?php

add_action( 'unspammed_comment','bs_clear_comment' );
add_action( 'spam_comment', 'bs_submit_comment' );
add_filter( 'pre_comment_approved', 'bs_check_comment', 5, 2 );

function bs_check_comment( $approved, $commentdata ) {
	extract($commentdata, EXTR_SKIP );
	if ( is_user_logged_in() && current_user_can('unfiltered_html') ) { return $approved; }
	$args = array( 
		'ip'=>$comment_author_IP, 
		'email'=>$comment_author_email, 
		'name'=>$comment_author, 
		'action'=>'check'
		);
	$result = bs_checker( $args );
	do_action( 'bs_check_comment', $result, $comment_author_IP, $comment_author_email, $comment_author, 'check' );	
	if ( $result ) {
		return 'spam'; 
	}
	return $approved;
}

function bs_submit_comment( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( !$comment ) return;
	update_comment_meta( $comment_id, 'botsmasher', 'reported' );	
	$ip = $comment->comment_author_IP;
	$email = $comment->comment_author_email;
	$author = $comment->comment_author;
	$args = array( 'ip'=>$ip, 'email'=>$email, 'name'=>$author, 'action'=>'submit' );
	bs_local_registry( 'true', $ip, $email, $author, 'submit' );
	$result = bs_checker( $args );
	do_action( 'bs_submit_comment', $result, $ip, $email, $author, 'submit' );
	return $result;
}

function bs_clear_comment( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( !$comment ) return;
	$current_status = get_comment_meta( $comment_id, 'botsmasher', true );
	update_comment_meta( $comment_id, 'botsmasher', 'notspam' );	
	$ip = $comment->comment_author_IP;
	$email = $comment->comment_author_email;
	$author = $comment->comment_author;
	$args = array( 'ip'=>$ip, 'email'=>$email, 'name'=>$author, 'action'=>'clear' );
	if ( $current_status == 'reported' ) {
		// if comment was reported by you, it can be cleared; otherwise, enter in local registry. 
		$result = bs_checker( $args ); 
	} else {
		$result = bs_local_registry( 'true', $ip, $email, $author, 'clear' );
	}
	do_action( 'bs_clear_comment', $result, $ip, $email, $author, 'clear' );
	return $result;
}

add_filter( 'manage_edit-comments_columns', 'bs_comment_columns' );
add_filter( 'manage_comments_custom_column', 'bs_comment_column', 10, 2 );	

function bs_comment_columns( $columns ) {
	$columns['botsmasher'] = __( 'BotSmasher' );
	return $columns;
}

function bs_comment_column( $column, $comment_ID ) {
	if ( 'botsmasher' == $column ) {
		if ( $meta = get_comment_meta( $comment_ID, $column, true ) ) {
			if ( $meta === 'spam' ) {
				$smashed = __('Flagged by BotSmasher','botsmasher');
			} else if ( $meta === 'notspam' ) {
				$smashed = __('Flagged by you as a false positive','botsmasher');
			} else if ( $meta === 'reported' ) {
				$smashed = __('Flagged by you as spam','botsmasher');
			}
		}
		echo "<span class='$meta'>$smashed</span>";		
	}
}