<?php
/*
Plugin Name: BotSmasher
Plugin URI: http://www.joedolson.com/articles/botsmasher/
Description: BotSmasher smashes bots. 
Version: 1.1.0
Author: Joe Dolson
Author URI: http://www.joedolson.com/

    Copyright 2013 Joe Dolson (joe@joedolson.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

register_activation_hook(__FILE__,'bs_install');
add_action('admin_menu', 'add_bs_admin_menu');
load_plugin_textdomain( 'botsmasher',false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

//define( 'BS_DEBUG_TO', 'debug@joedolson.com' ); // use this to send debugging to Joe. Please, only if you contacted me first!
define( 'BS_DEBUG_TO', get_option( 'admin_email' ) );
define( 'BS_DEBUGGING', false );

$bs_api_url = 'https://www.botsmasher.com/api/index.php';
$bs_version = '1.1.0'; 

if ( !class_exists('botsmasherClient') ) {
	require_once( plugin_dir_path(__FILE__).'botsmasherClient.class.php' );
}

$bs_options = get_option( 'bs_options' );
require_once( plugin_dir_path( __FILE__ ).'bs-checking.php' );
if ( isset( $bs_options['bs_filter_comments'] ) && $bs_options['bs_filter_comments'] == 'on' ) {
	require_once( plugin_dir_path( __FILE__ ).'bs-comments.php' );
}
if ( isset( $bs_options['bs_filter_registrations'] ) && $bs_options['bs_filter_registrations'] == 'on' ) {
	require_once( plugin_dir_path( __FILE__ ).'bs-registrations.php' );
}
require_once( plugin_dir_path( __FILE__ ).'bs-contacts.php' );


add_action( 'init', 'bs_posttypes' );
function bs_posttypes() {
	$args = array( 'public' => false ); 
	register_post_type( 'bs_flags',$args );
	$value = array( 
			__( 'message','botsmasher' ),
			__( 'messages','botsmasher' ),
			__( 'Message','botsmasher' ),
			__( 'Messages','botsmasher' ),
		);
		$labels = array(
		'name' => $value[3],
		'singular_name' => $value[2],
		'add_new' => __( 'Add New' , 'botsmasher' ),
		'add_new_item' => sprintf( __( 'Create New %s','botsmasher' ), $value[2] ),
		'edit_item' => sprintf( __( 'Modify %s','botsmasher' ), $value[2] ),
		'new_item' => sprintf( __( 'New %s','botsmasher' ), $value[2] ),
		'view_item' => sprintf( __( 'View %s','botsmasher' ), $value[2] ),
		'search_items' => sprintf( __( 'Search %s','botsmasher' ), $value[3] ),
		'not_found' =>  sprintf( __( 'No %s found','botsmasher' ), $value[1] ),
		'not_found_in_trash' => sprintf( __( 'No %s found in Trash','botsmasher' ), $value[1] ), 
		'parent_item_colon' => ''
	);
	$args = array(
		'labels' => $labels,
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_icon' => 'dashicons-email',
		'supports' => array( 'title', 'custom-fields' ),
		'taxonomies' => array( 'bs_form_category' )
	); 
	register_post_type( 'bs_saved_post',$args );
}

add_action( 'admin_menu', 'bs_add_outer_box' );

// begin add boxes
function bs_add_outer_box() {
	add_meta_box( 'bs_custom_div',__('BotSmasher Submission','botsmasher'), 'bs_add_inner_box', 'bs_saved_post', 'normal','high' );			
}
function bs_add_inner_box() {
	global $post;
	$content = '';
	$data = json_decode( stripslashes( $post->post_content ) );
	foreach ( $data as $key => $value ) {
		$value = str_replace( 'rnrn', '<br /><br />', $value );
		$content .= "<p><strong>".ucfirst($key)."</strong>:<br /> $value</p>";
	}
	$content .= "<h2>".__('JSON Submission Data','botsmasher').":</h2><code>".stripslashes( $post->post_content )."</code>";
	echo '<div class="bs_post_fields">'.$content.'</div>';
}


function bs_column($cols) {
	$cols['bs_submitter'] = __('Sender','botsmasher');
	$cols['bs_form_id'] = __('Form ID','botsmasher');
	return $cols;
}

// Echo the ID for the new column
function bs_custom_column( $column_name, $id ) {
	switch ( $column_name ) {
		case 'bs_submitter' :
			$email = get_post_meta( $id, '_bs_submitter_email', true );		
			$name = get_post_meta( $id, '_bs_submitter_name',true );
			$link = "<a href='mailto:$email'>$name</a>";
			echo $link;
		break;
		case 'bs_form_id' :
			$form = get_post_meta( $id, '_bs_form_id', true );
			echo $form;
		break;
	}
}

function bs_return_value( $value, $column_name, $id ) {
	if ( $column_name == 'bs_form_id' || $column_name == 'bs_submitter' ) {
		$value = $id;
	}
	return $value;
}

// Actions/Filters for message tables and css output
add_action('admin_init', 'bs_add');
function bs_add() {
	add_filter( "manage_bs_saved_post_posts_columns", 'bs_column' );			
	add_action( "manage_bs_saved_post_posts_custom_column", 'bs_custom_column', 10, 2 );
}

/* 
* Everything in this file pertains to the WP BotSmasher UI. 
* All functional components are in the required files.
*/

// ADMIN MENU
function add_bs_admin_menu() {
	add_action( 'admin_print_footer_scripts', 'bs_write_js' );
	add_options_page('BotSmasher', 'BotSmasher', 'manage_options', __FILE__, 'bs_admin_menu');
}

// ACTIVATION
function bs_install() {
	global $bs_version;
	if ( get_option('bs_installed') != 'true' ) {
		add_option( 'bs_options', array(
			'bs_api_key'=>'',
			'bs_required_label'=>'<span>Required</span>',
			'bs_html_email'=>'',
			'bs_filter_comments'=>'on',
			'bs_filter_registrations'=>'on',
			'bs_daily_api_queries'=>0,
			'bs_total_api_queries'=>0,
			'bs_total_thwarts'=>0
			) 
		);
	} else {
		bs_check_version();
		update_option( 'bs_version', $bs_version );
	}
}

function bs_check_version() {
	return true; // not needed yet
}

function bs_plugin_action($links, $file) {
	if ( $file == plugin_basename(dirname( __FILE__).'/botsmasher.php') ) {
		$admin_url = admin_url('options-general.php?page=botsmasher/botsmasher.php');
		$links[] = "<a href='$admin_url'>" . __('BotSmasher Settings', 'botsmasher', 'botsmasher') . "</a>";
	}
	return $links;
}
//Add Plugin Actions to WordPress
add_filter( 'plugin_action_links', 'bs_plugin_action', -10, 2 );
add_action( 'admin_enqueue_scripts', 'bs_register_scripts' );
add_action( 'wp_head', 'bs_stylesheet' );

function bs_write_js() {
	if ( isset($_GET['page']) && $_GET['page']=='botsmasher/botsmasher.php' ) {
		// no written scripts required at this time.
	}
}
function bs_register_scripts() {
	global $current_screen;
	if ( $current_screen->id == 'widgets' ) {
		wp_register_script( 'bs.addfields', plugins_url( 'js/jquery.addfields.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'bs.addfields' );
	}
}

function bs_stylesheet() {
	// styles are enqueued where appropriate.
	if ( file_exists( get_stylesheet_directory() . '/bs-form.css' ) ) {
		$file =  get_stylesheet_directory_uri() . '/bs-form.css';
	} else {
		$file = plugins_url( 'css/bs-form.css',__FILE__);
	}
	wp_register_style( 'bs-form', $file );
	wp_enqueue_style( 'bs-form' );
}

register_activation_hook( __FILE__, 'bs_activation' );
function bs_activation() {
	wp_schedule_event( current_time( 'timestamp' ), 'daily', 'bs_daily_event' );
}

// clear today's queries, increment total.
add_action( 'bs_daily_event', 'bs_clear_api_queries' );
function bs_clear_api_queries() {
	$opts = get_option( 'bs_options' );
	$day_count = $opts['bs_daily_api_queries']; 
	$total_count = $opts['bs_total_api_queries'];
	$opts['bs_daily_api_queries'] = '';
	$opts['bs_total_api_queries'] = $day_count + $total_count;
	update_option( 'bs_options', $opts );
}

// clear hook on deactivation
register_deactivation_hook( __FILE__, 'bs_deactivation' );
function bs_deactivation() {
	wp_clear_scheduled_hook( 'bs_daily_event' );
}

function bs_update_settings() {
	bs_check_version();
	$opts = get_option( 'bs_options' );
	if ( !empty($_POST) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'bs-nonce') ) die("Security check failed");  
		if ( isset($_POST['action']) && $_POST['action'] == 'bss' ) {
			$bs_api_key = ( isset( $_POST['bs_api_key'] ) )?$_POST['bs_api_key']:'';
			$prev_key = $opts['bs_api_key'];
			$bs_required_label = ( isset( $_POST['bs_required_label'] ) )?$_POST['bs_required_label']:'';
			$bs_html_email = ( isset( $_POST['bs_html_email'] ) )?'on':'';
			$bs_filter_comments = ( isset( $_POST['bs_filter_comments'] ) )?'on':'';
			$bs_filter_registrations = ( isset( $_POST['bs_filter_registrations'] ) )?'on':'';
			$options = array_merge( $opts, array( 'bs_api_key'=> $bs_api_key, 'bs_required_label'=>$bs_required_label, 'bs_html_email'=>$bs_html_email, 'bs_filter_comments'=>$bs_filter_comments, 'bs_filter_registrations'=>$bs_filter_registrations ) );
			update_option( 'bs_options', $options );
			$message = __("BotSmasher Settings Updated",'botsmasher');
			return "<div class='updated'><p>".$message."</p></div>";
		}
	} else {
		return;
	}
}


add_action( "admin_head", 'bs_admin_styles' );
function bs_admin_styles() {
	global $current_screen; 
	if (  isset($_GET['page']) && ($_GET['page'] == 'botsmasher/botsmasher.php' ) || $current_screen->id == 'edit-comments' || $current_screen->id == 'widgets' ) {
		echo '<link type="text/css" rel="stylesheet" href="'.plugins_url( 'css/bs-styles.css', __FILE__ ).'" />';
	}
}

function bs_admin_menu() { ?>
<?php echo bs_update_settings(); ?>
<?php $bs_options = get_option( 'bs_options' );
if ( !$bs_options || !isset($bs_options['bs_api_key'] ) || $bs_options['bs_api_key'] == '' ) {
	$message = sprintf(__("You must <a href='%s'>enter a BotSmasher API key</a> to use BotSmasher.", 'botsmasher'), admin_url('options-general.php?page=botsmasher/botsmasher.php'));
	add_action('admin_notices', create_function( '', "if ( ! current_user_can( 'manage_options' ) ) { return; } else { echo \"<div class='error'><p>$message</p></div>\";}" ) );
} ?>
<div class="wrap">
<h2><?php _e('BotSmasher: Settings','botsmasher' ); ?></h2>
<div id="bs_settings_page" class="postbox-container" style="width: 70%">
	<div class="metabox-holder">
		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h3><?php _e('BotSmasher Options','botsmasher'); ?></h3>
				<div class="inside">
				<form method="post" action="<?php echo admin_url('options-general.php?page=botsmasher/botsmasher.php'); ?>">
				<fieldset>
					<legend><?php _e('BotSmasher Options','botsmasher'); ?></legend>
					<ul>
						<li><label for="bs_api_key"><?php _e('BotSmasher API Key','botsmasher'); ?></label> (<span id="bsak_label"><a href="http://www.botsmasher.com/register.php"><?php _e('Get an API key','botsmasher'); ?></a>) <input type="text" id="bs_api_key" name="bs_api_key" class="widefat" aria-labelledby="bs_api_key bsak_label" value="<?php echo esc_attr( $bs_options['bs_api_key'] ); ?>" /></li>
						<li><label for="bs_required_label"><?php _e('Required Label Text','botsmasher'); ?></label> <input type="text" id="bs_required_label" name="bs_required_label" class="widefat" value="<?php echo esc_attr( $bs_options['bs_required_label'] ); ?>" /></li>
						<li><input type="checkbox" id="bs_html_email" name="bs_html_email" <?php if ( $bs_options['bs_html_email'] == "on") { echo 'checked="checked" '; } ?>/> <label for="bs_html_email"><?php _e('Send HTML Email','botsmasher'); ?></label></li>
						<li><input type="checkbox" id="bs_filter_comments" name="bs_filter_comments" <?php if ( $bs_options['bs_filter_comments'] == "on") { echo 'checked="checked" '; } ?>/> <label for="bs_filter_comments"><?php _e('Filter Comments','botsmasher'); ?></label></li>
						<li><input type="checkbox" id="bs_filter_registrations" name="bs_filter_registrations" <?php if ( $bs_options['bs_filter_registrations'] == "on") { echo 'checked="checked" '; } ?>/> <label for="bs_filter_registrations"><?php _e('Filter Registrations','botsmasher'); ?></label></li>
					</ul>
				</fieldset>
					<p>
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('bs-nonce'); ?>" />
						<input type="hidden" name="action" value="bss" />
					</p>
					<p><input type="submit" name="bs-settings" class="button-primary" value="<?php _e('Update Settings','botsmasher') ?>" /></p>
				</form>
				</div>
			</div>
			<div class="postbox" id="error-log">
				<h3><?php _e('BotSmasher API Errors (Last 20)','botsmasher'); ?></h3>
				<div class="inside">
					<p>
					<?php _e('This is a record of errors returned by the BotSmasher API or by WordPress when smashing a bot. If you\'re having trouble with BotSmasher, this is useful information for debugging.','botsmasher'); ?>
					</p>
					<div class="bs_error_log">
						<?php
							$exceptions = get_option( 'bs_exceptions' );
							$return = '';
							if ( is_array( $exceptions ) ) {
								foreach ( $exceptions as $exception ) {
									$date = date_i18n( 'M d, H:i', $exception['date'] );
									$response = $exception['message'];
									$report = ( isset( $exception['report'] ) )?$exception['report']:"N/A";
									if ( strpos( $response, 'request limit' ) && date( 'Y-m-d', $exception['date'] ) == date( 'Y-m-d', current_time( 'timestamp' ) ) ) {
										$target_link = "http://www.botsmasher.com/contact.php";
										echo "<div class='updated'><p>".sprintf( __('You have reached your BotSmasher API check limit for today. <a href="%s">Raise your limit!</a>'), $target_link )."</p></div>";
									}
									$return .= "<tr><th scope='row'>$date</th><td>$response</td><td>$report</td></tr>";
								}
								echo "
									<table class='widefat'>
										<thead>
											<tr>
												<th scope='col'>".__('Date','botsmasher')."</th>
												<th scope='col'>".__('Message','botsmasher')."</th>
												<th scope='col'>".__('Reported by','botsmasher')."</th>
											</tr>
										</thead>
										<tbody>
										".$return."
										</tbody>
									</table>";
							} else {
								_e('No errors reported.', 'botsmasher' );
							}
						?>
					</div>				
				</div>			
			</div>
			
			<div class="postbox" id="bs-shortcode">
			<h3><?php _e('BotSmasher Shortcode','botsmasher'); ?></h3>
				<div class="inside">
				<h4><?php _e('Simple Usage', 'botsmasher' ); ?></h4>
				<p><code>[botsmasher]</code></p>
				<p><?php _e( 'The basic BotSmasher shortcode produces a simple contact form with name, email, telephone, subject, and message. The name, email, and message fields are required. Messages will be sent to the administrator email set in WordPress general settings.', 'botsmasher' ); ?></p>
				<h4><?php _e('Shortcode Attributes (defaults shown)', 'botsmasher' ); ?></h4>
				<p><textarea disabled cols="50" rows="4" style="padding: 10px;border: none; background: transparent; width: 100%; font-family: monospace;">[botsmasher recipient="$admin_email" recipientname="$blogname" submit="Send Now" fields="name,email,phone,subject,message" labels="Name, Email, Telephone, Subject, Message" required="message" thanks="Thank you for contacting $blogname. We'll get back to you as soon as possible!" subject="Submission from Contact Form by {name}"]</textarea></p>
				<p><?php _e( 'None of these attributes are required, but if you use them, there are particular things you will need to know:', 'botsmasher' ); ?></p>
				<p><code>recipient</code>: <?php _e('Email or comma-separated string of emails to send messages to.', 'botsmasher' ); ?></p>
				<p><code>recipientname</code>: <?php _e('Name of recipient. (Shown as email "from" name.)', 'botsmasher' ); ?></p>
				<p><code>submit</code>: <?php _e('Text of submit button.', 'botsmasher' ); ?></p>
				<p><code>fields</code>: <?php _e('Fields to include. Whatever you want, but "name" and "email" are always included, whether in this list or not. Include them in the list if you wish to customize their labels. Some keywords will trigger particular input types: e.g., "number", "phone" or "tel", "date", etc. "Message", "Notes", "Textarea" or "Description" will trigger textarea elements.', 'botsmasher' ); ?><strong><?php _e( "Fields listed must be unique.", 'botsmasher' ); ?></strong></p>
				<p><code>labels</code>: <?php _e('Text labels for the above fields. Must be one label for every field listed.', 'botsmasher' ); ?></p>
				<p><code>required</code>: <?php _e('Comma-separated list of required fields. Name and email need not be included, they are always required.', 'botsmasher' ); ?></p>
				<p><code>thanks</code>: <?php _e('Thank you message displayed after form submission.', 'botsmasher' ); ?></p>
				<p><code>subject</code>: <?php _e('Subject line of sent message. Note that the default value includes a template tag "{name}" - this will be replaced by the value submitted in the "name" field. Any included field is available as a template tag using the name of the field wrapped in curly braces.', 'botsmasher' ); ?></p>
				<h4><?php _e( 'Customizing the Email message sent', 'botsmasher' ); ?></h4>
				<p><?php _e( 'The <code>[botsmasher]</code> shortcode is a containing shortcode. Whatever you wrap inside the shortcode container will be used as the template for your email message sent. Alternately, you can use the <code>bs_customize_template</code> filter to generate a custom template.', 'botsmasher' ); ?></p>
<textarea disabled cols="50" rows="9" style="padding: 10px;border: none; background: transparent; font-family: monospace; ">
[botsmasher]
Contact from my website: 

Subject: {subject}
From : {name} ({email}) 

{message}
[/botsmasher]
</textarea>
				</div>
			</div>			
			
			<div class="postbox" id="get-support">
			<h3><?php _e('Get Plug-in Support','botsmasher'); ?></h3>
				<div class="inside">
				<?php bs_get_support_form(); ?>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="postbox-container" style="width:20%">
	<div class="metabox-holder">
		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h3><?php _e('Support this Plugin','botsmasher'); ?></h3>
				<div class="inside">
					<p class='botsmasher'><a href="http://www.botsmasher.com"><img src="<?php echo plugins_url('imgs/logo.png', __FILE__ ); ?>" alt="BotSmasher" /></a></p>
					<p>
					<a href="https://twitter.com/intent/tweet?screen_name=joedolson&text=BotSmasher%20rocks!" class="twitter-mention-button" data-size="large" data-related="joedolson">Tweet to @joedolson</a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
					</p>
					<p><?php _e("If you've found BotSmasher useful, then please consider <a href='http://wordpress.org/extend/plugins/botsmasher/'>rating it five stars</a>, <a href='http://www.joedolson.com/donate.php'>making a donation</a>, or <a href='http://translate.joedolson.com/projects/botsmasher'>helping with translation</a>.",'botsmasher'); ?></p>
							<div>
					<p><?php _e('<a href="http://www.joedolson.com/donate.php">Make a donation today!</a> Every donation counts - donate $5, $20, or $100 and help me keep this plug-in running!','wp-to-twitter'); ?></p>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<div>
						<input type="hidden" name="cmd" value="_s-xclick" />
						<input type="hidden" name="hosted_button_id" value="QK9MXYGQKYUZY" />
						<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="Donate" />
						<img alt="" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
						</div>
					</form>
					</div>
				</div>
			</div>
		</div>
		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h3><?php _e('Your API Usage','botsmasher'); ?></h3>
				<?php
					$day_count = $bs_options['bs_daily_api_queries'];
					$total_count = "<strong>".( $bs_options['bs_total_api_queries'] + $day_count )."</strong>";
					$day_count = ( $day_count != '' )?"<strong>$day_count</strong>":"<strong>0</strong>";
					$thwart_count = "<strong>".$bs_options['bs_total_thwarts']."</strong>";
				?>
				<div class="inside">
				<p>
				<?php printf( __( 'Total Checks: %1$s<br />Bots Smashed: %2$s<br />Today\'s Checks: %3$s', 'botsmasher' ), $total_count, $thwart_count, $day_count ); ?>
				</p>
				<p>					
				<?php _e( 'The <a href="http://www.botsmasher.com">BotSmasher API</a> was created and is supported by <a href="http://www.karlgroves.com">Karl Groves</a>.','botsmasher'); ?>
				</p>
				</div>
			</div>
		</div>
	</div>
</div>

</div><?php
}

function bs_get_support_form() {
global $current_user, $bs_version;
get_currentuserinfo();
	$request = '';
	$version = $bs_version;
	// send fields for all plugins
	$wp_version = get_bloginfo('version');
	$home_url = home_url();
	$wp_url = site_url();
	$language = get_bloginfo('language');
	$charset = get_bloginfo('charset');
	// server
	$php_version = phpversion();
	
	// theme data
	if ( function_exists( 'wp_get_theme' ) ) {
	$theme = wp_get_theme();
		$theme_name = $theme->Name;
		$theme_uri = $theme->ThemeURI;
		$theme_parent = $theme->Template;
		$theme_version = $theme->Version;	
	} else {
	$theme_path = get_stylesheet_directory().'/style.css';
	$theme = get_theme_data($theme_path);
		$theme_name = $theme['Name'];
		$theme_uri = $theme['ThemeURI'];
		$theme_parent = $theme['Template'];
		$theme_version = $theme['Version'];
	}
	// plugin data
	$plugins = get_plugins();
	$plugins_string = '';
		foreach( array_keys($plugins) as $key ) {
			if ( is_plugin_active( $key ) ) {
				$plugin =& $plugins[$key];
				$plugin_name = $plugin['Name'];
				$plugin_uri = $plugin['PluginURI'];
				$plugin_version = $plugin['Version'];
				$plugins_string .= "$plugin_name: $plugin_version; $plugin_uri\n";
			}
		}
	$data = "
================ Installation Data ====================
==BotSmasher==
Version: $version

==WordPress:==
Version: $wp_version
URL: $home_url
Install: $wp_url
Language: $language
Charset: $charset
User Email: $current_user->user_email

==Extra info:==
PHP Version: $php_version
Server Software: $_SERVER[SERVER_SOFTWARE]
User Agent: $_SERVER[HTTP_USER_AGENT]

==Theme:==
Name: $theme_name
URI: $theme_uri
Parent: $theme_parent
Version: $theme_version

==Active Plugins:==
$plugins_string
";
	if ( isset($_POST['wpt_support']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'bs-nonce') ) die("Security check failed");	
		$request = ( !empty($_POST['support_request']) )?stripslashes($_POST['support_request']):false;
		$has_donated = ( $_POST['has_donated'] == 'on')?"Donor":"No donation";
		$has_read_faq = ( $_POST['has_read_faq'] == 'on')?"Read FAQ":false;
		$subject = "BotSmasher support request. $has_donated";
		$message = $request ."\n\n". $data;
		$from = "From: \"$current_user->display_name\" <$current_user->user_email>\r\n";

		if ( !$has_read_faq ) {
			echo "<div class='message error'><p>".__('Please read the FAQ and other Help documents before making a support request.','botsmasher')."</p></div>";
		} else if ( !$request ) {
			echo "<div class='message error'><p>".__('Please describe your problem. I\'m not psychic.','botsmasher')."</p></div>";
		} else {
			wp_mail( "plugins@joedolson.com",$subject,$message,$from );
			if ( $has_donated == 'Donor' || $has_purchased == 'Purchaser' ) {
				echo "<div class='message updated'><p>".__('Thank you for supporting the continuing development of this plug-in! I\'ll get back to you as soon as I can.','botsmasher')."</p></div>";		
			} else {
				echo "<div class='message updated'><p>".__('I cannot provide free support, but will treat your request as a bug report, and will incorporate any permanent solutions I discover into the plug-in.','botsmasher')."</p></div>";				
			}
		}
	}
	$admin_url = admin_url('options-general.php?page=botsmasher/botsmasher.php');

	echo "
	<form method='post' action='$admin_url'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('bs-nonce')."' /></div>
		<div>";
		echo "
		<p>".
		__('<strong>Please note</strong>: I do keep records of those who have donated, but if your donation came from somebody other than your account at this web site, you must note this in your message.','botsmasher')
		."</p>";
		echo "
		<p>
		<code>".__('From:','botsmasher')." \"$current_user->display_name\" &lt;$current_user->user_email&gt;</code>
		</p>
		<p>
		<input type='checkbox' name='has_read_faq' id='has_read_faq' value='on' required='required' aria-required='true' /> <label for='has_read_faq'>".sprintf(__('I have read <a href="%1$s">the FAQ for this plug-in</a> <span>(required)</span>','botsmasher'),'http://wordpress.org/plugins/botsmasher/faq/')."</label>
        </p>
        <p>
        <input type='checkbox' name='has_donated' id='has_donated' value='on' /> <label for='has_donated'>".sprintf(__('I have <a href="%1$s">made a donation to help support this plug-in</a>','botsmasher'),'http://www.joedolson.com/donate.php')."</label>
        </p>
        <p>
        <label for='support_request'>".__('Support Request:','botsmasher')."</label><br /><textarea name='support_request' required aria-required='true' id='support_request' cols='80' rows='10'>".stripslashes($request)."</textarea>
		</p>
		<p>
		<input type='submit' value='".__('Send Support Request','botsmasher')."' name='wpt_support' class='button-primary' />
		</p>
		<p>".
		__('The following additional information will be sent with your support request:','botsmasher')
		."</p>
		<div class='mc_support'>
		".wpautop($data)."
		</div>
		</div>
	</form>";
}

function bs_handle_exception( $e, $response ) {
	if ( defined ( 'BS_DEBUGGING' ) && BS_DEBUGGING == true ) {
		wp_mail( BS_DEBUG_TO, 'BotSmasher: Handled Exception', print_r( $response, 1 )."\n\n".print_r( $e, 1 ) );
	}
	$exceptions = get_option( 'bs_exceptions' );
	if ( !is_array( $exceptions ) ) {
		$exceptions = array();
	}
	if ( count( $exceptions ) > 20 ) { // only track the latest 20 issues.
		array_shift( $exceptions );
	}	
	if ( $response == 'is_wp_error' ) {
		$message = $e->get_error_message();
		$report = "WordPress";
	} else {
		$message = $e->getMessage();
		$report = "BotSmasher";
	}
	$exceptions[] = array( 'date'=>current_time( 'timestamp' ), 'message'=> $message, 'report'=>$report );
	update_option( 'bs_exceptions', $exceptions );
}