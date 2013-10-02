<?php
if ( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
} else {
	delete_option( 'bs_exceptions' );
	delete_option( 'bs_options' );
	delete_option( 'bs_version' );
}