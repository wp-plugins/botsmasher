<?php
/*
* return the result from botsmasher
* 
* @param array $args Argument array
* @return boolean True if spam, false if OK.
* 
* $args array( 'ip'='string', 'email'=>'string', 'name'=>'string', 'action'=>'string' )
*/
function bs_checker( $args=array() ) {
	apply_filters("debug", "botsmasher: begin check");
	extract($args);
	$result = false; 
	$bs_options = get_option( 'bs_options' );
	$today = $bs_options['bs_daily_api_queries'];
	$thwarts = $bs_options['bs_total_thwarts'];
	global $bs_api_url;
	// check the local registry (clears and blacklists) before querying BotSmasher
	if ( $action = 'check' ) {
		apply_filters("debug", "botsmasher: begin local check");
		$result = bs_check_local_registry( array( 'ip'=>$ip,'email'=>$email,'name'=>$name ) );
		apply_filters("debug", "botsmasher: complete local check");
	}
	if ( !$result ) {
		$bs = new botsmasherClient( $bs_api_url, $bs_options['bs_api_key'] );
		$bs->setOpts( array( 'ip'=>$ip,'email'=>$email,'name'=>$name,'action'=>$action ) );
		$bs->query();
		$result = $bs->smash();
		if ( $action == 'check' ) {
			$bs_options['bs_daily_api_queries'] = $today+1;
			if ( $result === 1 || $result === true ) {
				$bs_options['bs_total_thwarts'] = $thwarts+1;
			}
			update_option( 'bs_options', $bs_options );			
		}		
	}
	do_action( 'bs_handle_results', $result, $ip, $email, $name, $action );
	apply_filters("debug", "botsmasher: complete check");
	return ( $result === 1 || $result === true ) ? true : false;
}

add_action( 'bs_handle_results', 'bs_local_registry', 10, 5 );
function bs_local_registry( $result, $ip, $email, $name, $action ) {
	if ( $action != 'check' ) {
		// insert into DB
		$args = array( 'ip'=>$ip, 'email'=>$email, 'name'=>$name );
		$title = md5($args['ip'].$args['email'].$args['name']);
		$post = array( 'post_title'=>$title, 'post_status'=>'publish', 'post_type'=>'bs_flags' );
		$id = wp_insert_post( $post );
		add_post_meta( $id, 'bs_flag_data', json_encode($args) );
		add_post_meta( $id, 'bs_flag_action', $action );
		return true;
	}
	return false;
}

function bs_check_local_registry( $args ) {
	if ( isset( $args['result'] ) ) {
		unset( $args['result'] );
	}
	$posts = get_posts( array( 'meta_key'=>'bs_flag_data', 'meta_value'=>json_encode($args) ) );
	$count = count($posts);
	if ( $count > 0 ) {
		return true;
	} else {
		return false;
	}
	return false;
}