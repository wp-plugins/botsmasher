<?php
add_shortcode( 'botsmasher', 'bs_form' );
/* Save submissions as posts. */

add_action( 'bs_post_sanitize_contact','bs_save_submission', 10, 9 );
function bs_save_submission( $pd, $recipient, $fields, $labels, $required, $subject, $thanks, $form_id, $result ) {
	if ( $form_id == 'default' ) { $form_name = "Default Form"; } else { $form_name = $form_id; }
	$my_post = array(
		'post_title' => bs_draw_template( $pd, wp_strip_all_tags( $subject ) ),
		'post_content' => json_encode($pd),
		'post_status' => 'private',
		'post_author' => 1,
		'post_date' => date( 'Y-m-d H:i:00', current_time('timestamp') ),
		'post_type' => apply_filters( 'bs_saved_post_type', 'bs_saved_post', $form_id )
	);
	$post_id = wp_insert_post( $my_post );
	add_post_meta( $post_id, '_bs_status', $result );
	add_post_meta( $post_id, '_bs_form_id', $form_name );
	add_post_meta( $post_id, '_bs_submitter_name', $pd['name'] );
	add_post_meta( $post_id, '_bs_submitter_email', $pd['email'] );
	// for pushing data into custom fields
	do_action( 'bs_save_fields', $post_id, $pd );
	if ( $post_id ) {
		wp_update_post( array( 'ID'=>$post_id, 'post_name'	=> 'bs_'.$post_id ) );
	}
}

function bs_form( $atts, $content ) {
	extract( shortcode_atts( 
		array( 
			'recipient' => get_option( 'admin_email' ),
			'recipientname' => get_option( 'blogname' ),
			'submit'=> 'Send Now',
			// Name and Email are always present and required. Only reason to add them is to customize their labels.
			'fields'=> 'name,email,phone,subject,message',
			'labels'=> 'Name,Email,Telephone,Subject,Message',
			'required'=> 'message',
			'thanks'=> 'Thank you for contacting '.get_option('blogname').'. We\'ll get back to you as soon as possible!',
			'subject'=>'Submission from Contact Form by {name}',
			'form_id'=>'default'
		), $atts, 'botsmasher' ) );
		$template = apply_filters( 'bs_customize_template', $content, $atts ); 
	return bs_contact_form( $recipient, $submit, $fields, $labels, $required, $subject, $thanks, $template, $recipientname, $form_id );
}

function bs_generate_array( $fields ) {
	foreach ( $fields as $field ) {
		$array[$field] = ' ';
	}
	$array['name'] == '';
	$array['email'] == '';
	$array['message'] == '';
	return $array; 
}

function bs_contact_form( $recipient, $submit, $fields, $labels, $required, $subject, $thanks, $template, $recipientname, $form_id='default' ) {
	$return = $status = '';
	$errors = array();
	$options = get_option( 'bs_options' );
	$lr = trim($options['bs_required_label']);	
	$fields = array_map( 'trim', explode( ',', $fields ) );
	$labels = array_map( 'trim', explode( ',', $labels ) );
	$required = array_map( 'trim', explode( ',', $required ) );
	if ( count( $fields ) != count( $labels ) ) {
		return __('Field count and label count does not match. You must have a label specified for all defined fields.','botsmasher');
	}
	$labels = array_combine( $fields, $labels );
	$post = bs_generate_array( $fields ); 
	$return = bs_submit_form( $_POST, $recipient, $fields, $labels, $required, $subject, $thanks, $template, $recipientname, $form_id );
	$message = $return['message'];
	$message = ( $message ) ? "<div class='bs-notice'>$message</div>" : '';
	$message = apply_filters( 'bs_post_submit_message', $message, $return );
	$hash = md5( $recipient.$fields.$labels.$required.$subject.$thanks.$template );
	if ( is_array( $return['post'] ) ) {
		$post = $return['post'];
		$status = $post['status'];
		$errors = ( trim($status) == 'errors' ) ? $post['errors'] : array() ;
	}
	// special case fields
	if ( in_array( 'name', array_keys( $errors ) ) ) {
		$error_name = apply_filters( 'bs_filter_name_error', "<div class='bs-error' id='bs_name_error'>".sprintf( __('%s is a required field.', 'botsmasher' ), $labels['name'] )."</div>" );
		$aria_name = " aria-labelledby='bs_name_error'";
	} else {
		$error_name = $aria_name = '';
	}
	if ( in_array( 'email', array_keys( $errors ) ) ) {
		$error_email = apply_filters( 'bs_filter_email_error', "<div class='bs-error' id='bs_email_error'>".sprintf( __('%s is a required field.', 'botsmasher' ), $labels['email'] )."</div>" );
		$aria_email = " aria-labelledby='bs_email_error'";
	} else {
		$error_email = $aria_email = '';
	}	
	$output = "
<div class='bs-form $status'>
	<div class='bs-inner'>
	<div class='form'>
		<div class='header'>
			$message
		</div>
		<div class='body'>
			<form action='' method='post'>
				<div><input type='hidden' name='bs_contact_form' value='$hash' />".
				wp_nonce_field('bs_contact_form','bs_contact_form_nonce',false,false)."</div>
				<p>$error_name
					<label for='bs_name'>$labels[name] $lr</label> <input$aria_name aria-required='true' required type='text' name='bs_name' id='bs_name' value='".trim( esc_attr( stripslashes( $post['name'] ) ) )."' placeholder='Your name' />
				</p>
				<p>$error_email
					<label for='bs_email'>$labels[email] $lr</label> <input$aria_email aria-required='true' required type='text' name='bs_email' id='bs_email' value='".trim( esc_attr( stripslashes( $post['email'] ) ) )."' placeholder=\"Email Address\" />
				</p>";
				foreach ( $fields as $value ) {
					switch ($value) {
						case 'name':break;
						case 'email':break;
						case 'phone':
							$output .= apply_filters( 'bs_field_phone', bs_create_field( 'phone', $labels['phone'], $post['phone'], $required, $errors ) );
							break;
						case 'street':
							$output .= apply_filters( 'bs_field_street', bs_create_field( 'street', $labels['street'], $post['street'],  $required, $errors ) );
							break;
						case 'street2':
							$output .= apply_filters( 'bs_field_street2', bs_create_field( 'street2', $labels['street2'], $post['street2'],  $required, $errors ) );
							break;
						case 'city':
							$output .= apply_filters( 'bs_field_city', bs_create_field( 'city', $labels['city'], $post['city'],  $required, $errors ) );
							break;
						case 'state':
							$output .= apply_filters( 'bs_field_state', bs_create_field( 'state', $labels['state'], $post['state'],  $required, $errors ) );
							break;
						case 'zip':
							$output .= apply_filters( 'bs_field_zip', bs_create_field( 'zip', $labels['zip'], $post['zip'],  $required, $errors ) );	
							break;
						case 'country':
							$output .= apply_filters( 'bs_field_country', bs_create_field( 'country', $labels['country'], $post['country'],  $required, $errors ) );
							break;
						case 'date':
							$output .= apply_filters( 'bs_field_date', bs_create_field( 'date', $labels['date'], $post['date'],  $required, $errors ) );
							break;
						case 'number':
							$output .= apply_filters( 'bs_field_number', bs_create_field( 'number', $labels['number'], $post['number'],  $required, $errors ) );
							break;
						case 'message':
							$output .= apply_filters( 'bs_field_message', bs_create_field( 'message', $labels['message'], $post['message'],  $required, $errors ) );
							break;
						default: 
							$output .= apply_filters( 'bs_field_custom', bs_create_field( $value, $labels[$value], $post[$value],  $required, $errors ) ); 
					}
				}	

		$output .= "<p>
					<input type='submit' name='submit' value='$submit' />
				</p>
			</form>
		</div>
	</div>
	</div>
</div>";
	return $output;
}

function bs_create_field( $field, $label, $value, $required, $errors=array() ) {
	$type = bs_set_type( $field );
	$custom = apply_filters( 'bs_custom_field', false, $field, $label, $value, $required, $errors, $type );
	if ( $custom ) { return $custom; }	
	$options = get_option( 'bs_options' );
	$value = trim( $value );
	$is_required = in_array( $field, $required );
	if ( in_array( $field, array_keys( $errors ) ) ) {
		$error = apply_filters( 'bs_filter_error', "<div class='bs-error' id='bs_$field".'_error'."'>".sprintf( __('%s is a required field.', 'botsmasher' ), $label )."</div>", $field, $value );
		$aria = " aria-labelledby='bs_$field".'_error'."'";
	} else {
		$error = $aria = '';
	}
	if ( $is_required ) { 
		$required = "aria-required='true' required"; $lr = trim($options['bs_required_label']);
	} else { 
		$required = ''; $lr = '';
	}
	if ( $type == 'textarea' ) {
		$output = "<p class='$field'>$error<label for='bs_$field'>$label $lr</label> <textarea$aria name='bs_$field' id='bs_$field' $required>".esc_textarea( stripslashes( $value ) )."</textarea></p>";	
	} else if ( $type == 'number' ) {
		$output = "<p class='$field'>$error<label for='bs_$field'>$label $lr</label> <input$aria type='$type' name='bs_$field' id='bs_$field' value='".esc_attr( stripslashes( $value ) )."' $required /></p>";
	} else {
		$output = "<p class='$field'>$error<label for='bs_$field'>$label $lr</label> <input$aria type='$type' name='bs_$field' id='bs_$field' value='".esc_attr( stripslashes( $value ) )."' placeholder='$label' $required /></p>";	
	}
	return $output;
}

function bs_set_type( $field ) {
// phone,street,street2,city,state,zip,country,date,number,message
	switch ($field) {
		case "street":
		case "street2":
		case "city":
		case "state":
		case "zip":
		case "country":$type = 'text';break;
		case "phone": 
		case "tel" : 
		case "telephone" : $type = 'tel';break;		
		case "date":$type = 'date';break;
		case "number":$type = 'number';break;
		case "message":
		case "notes" : 
		case "textarea" : 
		case "description" : $type = 'textarea';break;
		case "datetime":$type = 'datetime';break;
		case "email":$type = 'email'; break;
		case "password": $type = 'password'; break;
		case "month" : $type = 'month'; break;
		case "url" : $type = 'url'; break;
		default:$type = 'text';
	}
	return apply_filters( 'bs_set_type', $type, $field );
}

function bs_submit_form( $pd, $recipient, $fields, $labels, $required, $subject, $thanks, $template, $recipientname, $form_id ) {
	// hash ensures that forms are unique (widget won't submit main, etc.)
	if ( isset( $pd['bs_contact_form'] ) ) {
		$options = get_option( 'bs_options' );
		$hash = md5( $recipient.$fields.$labels.$required.$subject.$thanks.$template );
		$return = $default_template = '';
		$is_error = false;
		$post = array( 'status'=>'', 'name'=>'', 'email'=>'', 'message'=>'' );
		if ( isset($pd['bs_contact_form']) && $pd['bs_contact_form'] == $hash ) {
			if ( !wp_verify_nonce($pd['bs_contact_form_nonce'],'bs_contact_form') ) { wp_die(); }
			do_action( 'bs_pre_filter_contact', $pd, $recipient, $fields, $labels, $required, $subject, $thanks, $form_id );
			$post['email'] = sanitize_email( $pd['bs_email'] );
			$post['name'] = stripslashes( sanitize_text_field( $pd['bs_name'] ) );
			if ( !$post['email'] || !$post['name'] ) {
				if ( empty( $post['name'] ) ) {
					$is_error = true;
					$errors['name'] = array( 'label'=>$labels['name'], 'name'=>'name', 'post'=>'' );
				}
				if ( empty( $post['email'] ) ) {
					$is_error = true;
					$errors['email'] = array( 'label'=>$labels['email'], 'name'=>'email', 'post'=>'' );
				}
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
				$result = bs_checker( array( 'ip'=>$ip, 'email'=>$post['email'], 'name'=>$post['name'], 'action'=>'check' ) );
				do_action( 'bs_post_filter_contact', $pd, $recipient, $fields, $labels, $required, $subject, $thanks, $form_id );
			}			
			
			if ( $result ) { // this is spam!
				return array( 'message'=>__( 'BotSmasher thinks you\'re a spammer. Please contact us if you\'re a real person!', 'botsmasher' ), 'post'=>$post );
			} else {
				foreach ( $fields as $value ) {
					switch ( $value ) {
						case 'number':
							$val = ( is_numeric( $pd["bs_$value"] ) ) ? $pd["bs_$value"] : false;
							$val = apply_filters( "bs_sanitize_$value", $val, $labels[$value] );
							if ( $val ) {
								$post[$value] = $val;
								$default_template .= "\n".ucfirst($value).": {".$value."}";
							} else {
								if ( in_array( $value, $required ) ) {
									$is_error = true; 
									$errors[$value] = array( 'label'=>$labels[$value], 'name'=>$value, 'post'=>$pd["bs_$value"] );
								}
							}
							break;					
						default:
							if ( $options['bs_html_email'] == 'on' ) {
								$val = apply_filters( "bs_sanitize_$value", htmlspecialchars( $pd["bs_$value"] ), $labels[$value] );
							} else {
								$val = apply_filters( "bs_sanitize_$value", $pd["bs_$value"], $labels[$value] );
							}
							if ( $val ) {
								$post[$value] = $val;
								$default_template .= "\n".ucfirst($value).": {".$value."}";
							} else {
								if ( in_array( $value, $required ) ) {
									$is_error = true; 
									$errors[$value] = array( 'label'=>$labels[$value], 'name'=>$value, 'post'=>$pd["bs_$value"] );
								}
							}
							break;
					}
				}
				
				if ( $is_error ) {
					$post['status'] = ' errors';
					$post['errors'] = $errors;
					$error_message = '';
					foreach ( $errors as $error ) {
						$error_message .= "<li>".sprintf( __( 'The provided information for <strong>%1$s</strong> did not validate.', 'botsmasher' ), $error['label'] ). "</li>";
					}
					$return = "
					<div class='bs-errors'>
						<ul>
						".stripslashes( $error_message )."
						</ul>
					</div>";
					return array( 'message'=>$return, 'post'=>$post );
				} else {
					$post['status'] = ' submitted';
					$post['errors'] = '';
				}
				
				do_action( 'bs_post_sanitize_contact', $post, $recipient, $fields, $labels, $required, $subject, $thanks, $form_id, $result );
				
				if ( !$template ) { $template = apply_filters( 'bs_custom_template', $default_template, $post, $recipient ); }
				
				$message = bs_draw_template( $post, apply_filters( 'bs_draw_message', $template, $post ) );
				$subject = bs_draw_template( $post, $subject );
				$senderfrom = "From: \"".stripslashes( $recipientname )."\" <$recipient>";
				$recipientfrom = "From: \"".stripslashes( $post['name'] )."\" <$post[email]>";
				
				if ( $options['bs_html_email'] == 'on' ) {
					add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
				}
				wp_mail( $post['email'], stripslashes( $subject ), $message, $senderfrom );
				wp_mail( $recipient, stripslashes( $subject ), $message, $recipientfrom );
				if ( $options['bs_html_email'] == 'on' ) {
					remove_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
				}			
				$return = "<div class='bs-thanks'>".stripslashes( $thanks )."</div>";
			}
		}
		return array( 'message'=>$return, 'post'=>$post );
	} else {
		return false;
	}
}

function bs_generate_params( $instance ) {
	if ( !empty( $instance ) ) {
		$recipient = 	apply_filters( 'bs_widget_recipient', $instance['recipient'] );
		$submit = 		apply_filters( 'bs_widget_submit', $instance['submit'] );
		$the_fields = 	apply_filters( 'bs_widget_fields', $instance['fields'] );
		$subject = 		apply_filters( 'bs_widget_subject', $instance['subject'] );
		$thanks = 		apply_filters( 'bs_widget_thanks', $instance['thanks'] );
		$template = 	apply_filters( 'bs_widget_template', $instance['template'] );
	} else {
		$the_fields = array();
		$recipient = $submit = $subject = $thanks = $template = '';
	}
	
	$fields = $labels = $required = array(); 
	$fields[] = 'name'; 
	$fields[] = 'email';
	$labels[] = ( isset( $instance['name_label'] ) )?$instance['name_label']:'';
	$labels[] = ( isset( $instance['email_label'] ) )?$instance['email_label']:'';
	
	foreach ( $the_fields as $field => $value  ) {
		$labels[] = $value['label'];
		if ( isset($value['required']) ) {
			$required[] = $field;
		}
	}
	$labels = implode( ',', $labels );
	$required = implode( ',', $required );
	$fields = implode( ',', array_merge( $fields, (array) array_keys( $the_fields ) ) );
	if ( !empty( $the_fields ) ) {
		return array( 'recipient'=>$recipient, 'submit'=>$submit, 'fields'=>$fields, 'labels'=>$labels, 'required'=>$required, 'subject'=>$subject, 'thanks'=>$thanks, 'template'=>$template );
	}
}

function bs_generate_shortcode( $params ) {
	extract($params);
	$shortcode = "<div class='shortcode'><strong>This form via shortcode:</strong><br />[botsmasher recipient=\"$recipient\" submit=\"$submit\" fields=\"$fields\" labels=\"$labels\" required=\"$required\" subject=\"$subject\" thanks=\"$thanks\"]";
	if ( $template ) {
		$shortcode .= $template."[/botsmasher]";
	}
	$shortcode .= "</div>";
	return $shortcode;
}

class bs_quick_contact extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'bs_quick_contact', 'description' => __('BotSmasher Contact Form'));
		$control_ops = array('width' => 500, 'height' => 350);
		parent::__construct('quick-contact', __('Quick Contact'), $widget_ops, $control_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$params = bs_generate_params( $instance );

		$title = 		apply_filters( 'bs_widget_title', $instance['title'] );
		$widget_title = ( $title!='' ) ? $before_title . $title . $after_title : '';
		$after_widget = apply_filters( 'bs_widget_after', $after_widget );
		$before_widget = apply_filters( 'bs_widget_before', $before_widget );
		$recipientname = apply_filters( 'bs_recipient_name', get_option( 'blogname' ) );
		extract( $params );
		
		echo $before_widget;
		echo $widget_title;
		echo bs_contact_form( $recipient, $submit, $fields, $labels, $required, $subject, $thanks, $template, $recipientname );
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		foreach ( $new_instance['fields'] as $new_field ) {
			if ( isset( $new_field['label'] ) && trim($new_field['label']) != '' ) {
				$name = sanitize_title($new_field['label']);
				$fields[$name] = $new_field;
			}
		}
		$new_instance['fields'] = $fields;
		$new_instance['title'] = strip_tags( $new_instance['title'] );
		return $new_instance;
	}

	function form( $instance ) {
		$params = bs_generate_params( $instance );
		$shortcode = '';
		if ( $params ) {
			$shortcode = bs_generate_shortcode( $params );
		}
		$defaults = array( 'message'=>array( 'label'=>'Message', 'required'=>1, 'type'=>'textarea' ) );
		$fields = ( empty( $instance['fields'] ) )?$defaults:$instance['fields'];
		$tags = array( '{name}','{email}' );
		foreach ( $fields as $field => $value ) {
			$tags[] = "{".$field."}";
		}
		$tags = "<code>".implode( '</code>, <code>',$tags )."</code>";
		$title = ( isset( $instance['title'] ) )?$instance['title']:'';
		$recipient = ( isset( $instance['recipient'] ) )?$instance['recipient']:'';
		$submit = ( isset( $instance['submit'] ) )?$instance['submit']:'';
		$subject = ( isset( $instance['subject'] ) )?$instance['subject']:'';
		$thanks = ( isset( $instance['thanks'] ) )?$instance['thanks']:'';
		$template = ( isset( $instance['template'] ) )?$instance['template']:'';
		$name_label = ( isset( $instance['name_label'] ) )?$instance['name_label']:'';
		$email_label = ( isset( $instance['email_label'] ) )?$instance['email_label']:'';

		echo $shortcode;
?>
<div class='bs-widget'>
<div class='prime'>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'botsmasher' ); ?>:</label>
<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'recipient' ); ?>"><?php _e( 'Recipient', 'botsmasher' ); ?>:</label>
<input type="text" id="<?php echo $this->get_field_id( 'recipient' ); ?>" name="<?php echo $this->get_field_name( 'recipient' ); ?>" value="<?php echo esc_attr( $recipient ); ?>" class="widefat" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'submit' ); ?>"><?php _e( 'Submit Text', 'botsmasher' ); ?>:</label>
<input type="text" id="<?php echo $this->get_field_id( 'submit' ); ?>" name="<?php echo $this->get_field_name( 'submit' ); ?>" value="<?php echo esc_attr( $submit ); ?>" class="widefat" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'subject' ); ?>"><?php _e( 'Email Subject', 'botsmasher' ); ?>:</label>
<input type="text" id="<?php echo $this->get_field_id( 'subject' ); ?>" name="<?php echo $this->get_field_name( 'subject' ); ?>" value="<?php echo esc_attr( $subject ); ?>" class="widefat" aria-labelledby="<?php  echo $this->get_field_id( 'subject' ); echo $this->get_field_id( 'subject' ); ?>_label" />
<span id="<?php echo $this->get_field_id( 'subject' ); ?>_label"><?php _e('Available template tags: ', 'botsmasher' ); echo $tags; ?></span>
</p>
</div>
<p>
<label for="<?php echo $this->get_field_id( 'thanks' ); ?>"><?php _e( 'Thank you message', 'botsmasher' ); ?>:</label>
<textarea cols="40" rows="2" id="<?php echo $this->get_field_id( 'thanks' ); ?>" name="<?php echo $this->get_field_name( 'thanks' ); ?>" class="widefat"><?php echo esc_attr( $thanks ); ?></textarea>
</p>
<p>
<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Message Template', 'botsmasher' ); ?>:</label>
<textarea cols="40" rows="4" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>" class="widefat" aria-labelledby="<?php  echo $this->get_field_id( 'template' ); echo $this->get_field_id( 'subject' ); ?>_label" ><?php echo esc_attr( $template ); ?></textarea>
<span id="<?php echo $this->get_field_id( 'template' ); ?>_label"><?php _e('Available template tags: ', 'botsmasher' ); echo $tags; ?></span>
</p>
<fieldset>
<legend><?php _e('BotSmasher Form Fields','botsmasher'); ?></legend>
<div class="prime">
<p>
<label for="<?php echo $this->get_field_id( 'name_label' ); ?>"><?php _e( 'Name field label', 'botsmasher' ); ?>:</label>
<input type="text" id="<?php echo $this->get_field_id( 'name_label' ); ?>" name="<?php echo $this->get_field_name( 'name_label' ); ?>" value="<?php echo esc_attr( $name_label ); ?>" class="widefat" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'email_label' ); ?>"><?php _e( 'Email field label', 'botsmasher' ); ?>:</label>
<input type="text" id="<?php echo $this->get_field_id( 'email_label' ); ?>" name="<?php echo $this->get_field_name( 'email_label' ); ?>" value="<?php echo esc_attr( $email_label ); ?>" class="widefat" />
</p>
</div>
<table class="widefat botsmasher">
<thead>
	<tr>
	<th scope="col"><?php _e('Label','botsmasher'); ?></th>
	<th scope="col"><?php _e('Required','botsmasher'); ?></th>
	<th scope="col"><?php _e('Type','botsmasher'); ?></th>
	<th scope="col"><?php _e('Order','botsmasher'); ?></th>	
	</tr>
</thead>
<tbody>	
<?php
		$i = 0;
		$default_options = bs_options();
		$return = '';
		foreach ( $fields as $field ) {
			if ( $field['label'] == '' ) continue; 
			$checked = ( isset( $field['required'] ) && $field['required'] == 1 )?"checked='checked'":'';
			$options = bs_options( $field['type'] );
			$return .= "
			<tr>
				<th scope='row'><label for='".$this->get_field_id( 'fields' )."$i'>Label</label> 
					<input type='text' value='".esc_attr(trim($field['label']))."' name='".$this->get_field_name( 'fields' )."[$i][label]' id='".$this->get_field_id( 'fields' )."$i' /></th>
				<td><label for='".$this->get_field_id( 'fields' )."$i'>Required</label> 
					<input type='checkbox' value='1' $checked name='".$this->get_field_name( 'fields' )."[$i][required]' id='".$this->get_field_id( 'fields' )."$i' /></td>
				<td><label for='".$this->get_field_id( 'fields' )."$i'>Type</label> 
					<select name='".$this->get_field_name( 'fields' )."[$i][type]' id='".$this->get_field_id( 'fields' )."$i'>$options</select></td>
				<td><a href='#' class='up'><span>".__('Up','botsmasher')."</span></a> / <a href='#' class='down'><span>".__('Down','botsmasher')."</span></a></td>
			</tr>\n";
			$i++;
		}
		echo $return;
?>
	<tr class='bs-row'>
		<th scope='row'><label for='<?php echo $this->get_field_id( 'fields' ).$i; ?>'><?php _e('Label','botsmasher'); ?></label> 
			<input type='text' value='' name='<?php echo $this->get_field_name( 'fields' )."[$i][label]"; ?>' id='<?php echo $this->get_field_id( 'fields' ).$i; ?>' /></th>
		<td><label for='<?php echo $this->get_field_id( 'fields' ).$i; ?>'><?php _e('Required','botsmasher'); ?></label> 
			<input type='checkbox' value='1' name='<?php echo $this->get_field_name( 'fields' )."[$i][required]"; ?>' id='<?php echo $this->get_field_id( 'fields' ).$i; ?>' /></td>
		<td><label for='<?php echo $this->get_field_id( 'fields' ).$i; ?>'><?php _e('Type','botsmasher'); ?></label> 
			<select name='<?php echo $this->get_field_name( 'fields' )."[$i][type]"; ?>' id='<?php echo $this->get_field_id( 'fields' ).$i; ?>'><?php echo $default_options; ?></select></td>
		<td></td> 
	</tr>
</tbody>
</table>
</fieldset>
</div>
<?php
//$fields, $labels, $required
	}
}

add_action('widgets_init', create_function('', 'return register_widget("bs_quick_contact");') );
function bs_options( $selected='text' ) {
	$options = array( 'text','email','date','number','textarea' );
	$return = '<option value=""> -- </option>';
	foreach ( $options as $option ) {
		$checked = ( $option == $selected )?"selected='selected'":'';
		$return .= "<option value='$option' $checked>$option</option>\n";
	}
	return $return;
}

function bs_draw_template( $array=array(),$template='' ) {
	//1st argument: array of details
	//2nd argument: template to print details into
	$template = stripcslashes( $template );
	if ( is_array( $array ) ) {
		foreach ($array as $key=>$value) {
			if ( !is_object($value) ) {
				if ( strpos( $template, "{".$key ) !== false ) { // only check for tag parts that exist
					preg_match_all('/{'.$key.'\b(?>\s+(?:before="([^"]*)"|after="([^"]*)"|fallback="([^"]*)")|[^\s]+|\s+){0,2}}/', $template, $matches, PREG_PATTERN_ORDER );
					if ( $matches ) {
						$before = ( isset( $matches[1][0] ) )?$matches[1][0]:'';
						$after = ( isset( $matches[2][0] ) )?$matches[2][0]:'';
						$fallback = @$matches[3][0];
						$fb = ( $fallback != '' && $value == '' )?$before.$fallback.$after:'';
						$value = ( $value == '' )?$fb:$before.$value.$after;
						$whole_thang = ( isset( $matches[0][0] ) )?$matches[0][0]:'';
						$template = str_replace( $whole_thang, $value, $template );
					}
				}
			} 
		}
	}
	return stripslashes( trim( bs_clean_template($template) ) );
}
// function cleans unreplaced template tags out of the template. 
// Necessary for custom fields, which do not exist in array if empty.
function bs_clean_template( $template ) {
	preg_match_all('/{[\w]*\b(?>\s+(?:before="([^"]*)"|after="([^"]*)")|[^\s]+|\s+){0,2}}/', $template, $matches, PREG_PATTERN_ORDER );

	if ( $matches ) {
		foreach ( $matches[0] as $match ) {
			$template = str_replace( $match, '', $template );
		}
	}
	return $template;
}