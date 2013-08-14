<?php 

add_filter( 'registration_errors', 'bs_validate_user', 10, 3 );

function bs_validate_user( $errors, $user_login, $user_email ) {
	if ( !is_admin() ) { // if user is added via the admin, don't run this.
		$ip = $_SERVER['REMOTE_ADDR'];
		$args = array(
			'ip'=>$ip, 
			'email'=>$user_email, 
			'name'=>'', 
			'action'=>'check'
			);
		$result = bs_checker( $args );
		do_action( 'bs_handle_registration', $result, $ip, $user_email, 'check' );
		if ( $result ) {
			$errors->add( 'bs_registration', __('<strong>ERROR</strong>: BotSmasher has flagged you as a spammer. Please contact the site owner!', 'botsmasher' ) );
		}
	}
	return $errors;
}

// pre-define wp_new_user_notification to add text to message.
if ( !function_exists( 'wp_new_user_notification' ) ) {
	function wp_new_user_notification($user_id, $plaintext_pass = '') {
		$user = get_userdata( $user_id );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$flag_url = admin_url( "user-edit.php?user_id=$user_id&bsflag=true" );
		
		$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		$message .= sprintf(__('E-mail: %s'), $user->user_email) . "\r\n";
		$message .= sprintf(__('Flag as spam? %s'), $flag_url ) . "\r\n";
		
		@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

		if ( empty($plaintext_pass) )
			return;

		$message  = sprintf(__('Username: %s'), $user->user_login) . "\r\n";
		$message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
		$message .= wp_login_url() . "\r\n";
		
		wp_mail($user->user_email, sprintf(__('[%s] Your username and password'), $blogname), $message);
	}
}

add_action( 'show_user_profile', 'bs_profile' );
add_action( 'edit_user_profile', 'bs_profile' );
add_action( 'profile_update', 'bs_save_profile');

function bs_save_profile() {
	/*global $user_ID;
	get_currentuserinfo();
	if ( isset($_POST['user_id']) ) {
		$edit_id = (int) $_POST['user_id']; 
	} else {
		$edit_id = $user_ID;
	}
	$value = $_POST['value'];
	update_user_meta($edit_id ,'mf_files' , $value );*/
}

function bs_profile() {
	if ( isset( $_GET['bsflag'] ) && $_GET['bsflag'] == 'true' ) {
		global $user_ID;
		get_currentuserinfo();
		$user_edit = ( isset($_GET['user_id']) )?(int) $_GET['user_id']:$user_ID;
		$user = get_userdata( $user_edit );
		$name = $user->user_login;
		$email = $user->user_email;	
		$args = array( 'ip'=>'', 'name'=>$name, 'email'=>$email, 'action'=>'submit' );
		$result = bs_checker( $args );
		do_action( 'bs_profile', $result, $name, $email, 'submit' );
		if ( $result ) {
			echo "<div class='notice updated'>".sprintf(__('User blacklisted at BotSmasher <a href="%s">Delete user</a>','botsmasher'), admin_url( "users.php?acton=delete&user=$user_ID" ) )."</div>"; 
		} else {
			echo "<div class='notice error'>".__('Could not blacklist user at BotSmasher','botsmasher')."</div>"; 
		}
	}
}
