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
		controll_authorize([
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

/**
 * Handle the registration process
 * This function never returns
 */
function controll_handler_registration() {
	$timeslot = controll_api()->timeslots()->get(@$_REQUEST['timeslot-id']);
	if (!$timeslot) {
		controll_set_error("No schedule found!");
		return;
	}

	if (controll_api()->usesPasses())
		controll_handle_pass_registration($timeslot);
		else
			controll_handle_ticket_registration($timeslot);
}

/**
 * Register a ticket to an existing pass, or reserve a new pass, under a convention where users buy daily passes
 * This function never returns
 */
function controll_handle_pass_registration($timeslot) {
	if (!array_key_exists('pass', $_REQUEST)) {
		controll_set_error("Missing pass ID!");
		return;
	}
	$pass = $_REQUEST['pass'];
	if (is_numeric($pass)) { // user submitted an existing pass to register a ticket for
		$res = controll_api()->passes()->register($pass, $timeslot->id);
		if (!$res->status) {
			controll_set_error($ticket->error);
			return;
		}
		controll_redirect_helper(ConTrollSettingsPage::get_my_page_url());
	} else { // user requests a new pass
		if (!array_key_exists('pass-type', $_REQUEST))
			controll_set_error("Missing pass type ID to purchase!");
		if (!array_key_exists('pass-name', $_REQUEST))
			controll_set_error("Missing pass owner name!");
		$res = controll_api()->passes()->buy($_REQUEST['pass-type'], $_REQUEST['pass-name']);
		if ($res->error)
			controll_set_error($res->error);
		$pass = $res->id;
		$res = controll_api()->passes()->register($pass, $timeslot->id);
		if (!$res->status)
			controll_set_error($res->error);
		controll_redirect_helper(ConTrollSettingsPage::get_shopping_cart_url());
	}
}

/**
 * Reserve a ticket for the user, under a convention were users buy tickets
 * This function never returns
 */
function controll_handle_ticket_registration($timeslot) {
	$ticket = controll_api()->tickets()->create($timeslot->id);
	if ($ticket->status)
		controll_redirect_helper(ConTrollSettingsPage::get_shopping_cart_url());
	controll_set_error($ticket->error);
}

function controll_handler_cancel_pass() {
	$res = controll_api()->passes()->delete((int)$_REQUEST['id']);
	controll_verify_pass_package();
	wp_redirect(get_permalink(), 302);
}

function controll_handler_pass_package() {
	$pass_type = @$_REQUEST['pass-type'];
	$day_passes = @$_REQUEST['family-pass'];
	
	$email = controll_api()->getUserEmail();
	if (!$email) {
		controll_authorize([
				'controll-action' => 'pass-package',
				'pass-type' => $pass_type,
				'family-pass' => $day_passes,
		]);
	}
	
	switch ($pass_type) {
		case 'one':
			$pass_type = 62;
			break;
		case 'two':
			$pass_type = 72;
			break;
		default:
			controll_set_error("יש לבחור יום לכרטיס המשפחתי");
			return;
	}
	
	if (is_null($day_passes)) {
		controll_set_error("יש למלא שמות על הכרטיסים");
		return;
	}
	
	if (!is_array($day_passes))
		$day_passes = [ $day_passes ];
	
	$day_passes = array_filter($day_passes, function($name) { return strlen($name) > 0; });
	if (count($day_passes) < 3) {
		controll_set_error("כרטיס משפחתי מתאפשר רק החל משלושה כרטיסים ומעלה");
		return;
	}
	
	foreach ($day_passes as $name) {
		$res = controll_api()->passes()->buy($pass_type, $name);
		if ($res->status === false) {
			controll_set_error($res->error);
			return;
		}
	}
	controll_redirect_helper(ConTrollSettingsPage::get_shopping_cart_url());
}

function controll_verify_pass_package() {
	global $log;
	$log->Info("Starting verify pass package");
	$passes = controll_api()->passes()->user_catalog();
	$package_passes = [];
	foreach ($passes as $pass)
		$package_passes[$pass->pass->id][] = $pass;
	$log->info("Got pass types: " . print_r(array_map(function($val){return count($val);},$package_passes), true));
	if (is_array($package_passes[62]) && count($package_passes[62]) < 3) {
		$log->info("Need to remove passes from 62");
		foreach ($package_passes[62] as $pass) {
			controll_api()->passes()->delete($pass->id);
		}
	}
	if (is_array($package_passes[72]) && count($package_passes[72]) < 3) {
		$log->info("Need to remove passes from 72");
		foreach ($package_passes[72] as $pass) {
			controll_api()->passes()->delete($pass->id);
		}
	}
}
