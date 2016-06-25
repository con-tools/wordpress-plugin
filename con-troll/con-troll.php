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

function controll_api() {
	global $controll;
	if ($controll)
		return $controll;
	$controll = new Controll(get_option('controll-api-key'), get_option('controll-api-secret'));
	$controll->checkAuthentication();
	return $controll;
}
