<?php

/* ConTroll Form Handlers */

function controll_handler_buy_pass() {
	// Handle POST requests to implement the purchase
	if (!is_numeric(@$_REQUEST['pass-id']))
		return;
	
	$passid = $_REQUEST['pass-id'];
	$passname = @$_REQUEST['pass-name'];
	if (!$passname) {
		controll_set_error("חובה למלא שם בעל הכרטיס");
		return;
	}
	
	$email = controll_api()->getUserEmail();
	if (!$email) {
		controll_auth_with_data([
				'controll-action' => 'buy-pass',
				'controll-success-page' => @$_REQUEST['controll-success-page'],
				'pass-name' => $passname,
				'pass-id' => $passid,
		]);
	}
	
	$res = controll_api()->passes()->buy($passid, $passname);
	if ($res->status === false) {
		controll_set_error($res->error);
		return;
	}
	
	if (@$_REQUEST['controll-success-page']) {
		$url = get_permalink(get_page_by_path($atts['success-page']));
	} else {
		$url = ConTrollSettingsPage::get_shopping_cart_url();
	}
	controll_redirect_helper($url);
}

function controll_handler_cancel_ticket() {
	if (!is_numeric(@$_REQUEST['ticket-id']))
		return;
	
	controll_api()->tickets()->cancel(@$_REQUEST['ticket-id']);
	$ref = $_SERVER['HTTP_REFERER'];
	wp_redirect($ref);
	print($ref);
	exit();
}
