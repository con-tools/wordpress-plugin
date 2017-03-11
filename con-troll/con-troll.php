<?php
/*
Plugin Name: ConTroll Integration
Plugin URI:  http://con-troll.org/plugins
Description: ConTroll convention management system integration
Version:     1.0
Author:      Oded Arbel
Author URI:  http://geek.co.il
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: con-troll
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include __DIR__.'/options.php';
include __DIR__.'/pages.php';

include __DIR__.'/inc/controll-api.php';
include __DIR__.'/inc/registration-functions.php';

function controll_scripts() {
	//wp_enqueue_script('controll-api', get_template_directory_uri() . '/js/controll.js', [ 'jquery' ]);
	wp_enqueue_script('controll-plugin-scripts', plugin_dir_url( __FILE__ ) . 'js/controll-tools.js');
}
add_action( 'wp_enqueue_scripts', 'controll_scripts' );

function controll_api() {
	global $controll;
	if ($controll)
		return $controll;
	$controll = new Controll(get_option('controll-api-key'), get_option('controll-api-secret'));
	$controll->checkAuthentication();
	return $controll;
}
