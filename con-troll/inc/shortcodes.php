<?php

function controll_date_format($atts, $content = null) {
	extract(shortcode_atts([
			'path' => null,
			'format' => 'd/n H:i',
	], $atts));
	$date = controll_data_path_lookup($path, controll_get_current_object());
	try {
		if (!($date instanceof DateTime))
			$date = new DateTime($date);
			return date($format, $date->getTimestamp());
	} catch (Exception $e) {
		return 'Invalid Date';
	}
}
add_shortcode('controll-date-format', 'controll_date_format');

function controll_list_repeat_int($path, $delimiter, $content) {
	$curobj = controll_get_current_object();
	$list = controll_data_path_lookup($path, $curobj);
	if (!is_array($list))
		return '';
		return join($delimiter, array_map(function($item) use ($content, $curobj) {
			controll_push_current_object($item);
			try {
				return  str_replace(["\n","\r"],"",controll_parse_template($item, do_shortcode($content)));
			} finally {
				controll_pop_current_object();
			}
		},$list));
}

function controll_list_repeat($atts, $content = null) {
	remove_filter( 'the_content', 'wpautop' );
	extract(shortcode_atts([
			'path' => null,
			'source' => null,
			'delimiter' => ' ',
	], $atts));
	if (!is_null($source)) { // caller doesn't have the data loaded, try to get the catalog for them
		try {
			controll_push_current_object(controll_load_catalog($source));
			return controll_list_repeat_int('.', $delimiter, $content); // we want to iterate over the sourced list
		} finally {
			controll_pop_current_object();
		}
	} else {
		return controll_list_repeat_int($path, $delimiter, $content);
	}
}
add_shortcode('controll-list-repeat', 'controll_list_repeat');
add_shortcode('controll-list-repeat-1', 'controll_list_repeat'); // multiple copies to allow nesting
add_shortcode('controll-list-repeat-2', 'controll_list_repeat');
add_shortcode('controll-list-repeat-3', 'controll_list_repeat');

function controll_handle_buy_pass($atts, $content = null) {
	$atts = shortcode_atts([
			'success-page' => null,
			'pass-field' => 'pass',
			'name-field' => 'name,'
	], $atts, 'controll-handle-buy-pass');

	// Handle POST requests to implement the purchse

	if (@$_REQUEST['ticketpass']) {
		list($passname, $passid) = $_SESSION['controll_daily_pass'][$_REQUEST['ticketpass']];
	} else {
		if (!is_numeric(@$_REQUEST[$atts['pass-field']]))
			return;

			$passid = $_REQUEST[$atts['pass-field']];
			$passnamef = controll_parse_template(['id' => $passid], $atts['name-field']);
			$passname = @$_REQUEST[$passnamef];
			if (!$passname) {
				$errorMessage = "חובה למלא שם בעל הכרטיס";
				return;
			}
	}

	$email = controll_api()->getUserEmail();
	if (!$email) {
		$id = uniqid();
		$_SESSION['controll_daily_pass'][$id] = [$passname, $passid];
		$url = "http://api.con-troll.org/auth/verify?redirect-url=" .
				urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]?".
						'ticketpass=' . urlencode($id));
				controll_redirect_helper($url);
	}

	$res = controll_api()->passes()->buy($passid, $passname);
	if ($res->status === false) {
		$errorMessage = $res->error;
		return;
	}

	if (is_null($atts['success-page'])) {
		$url = ConTrollSettingsPage::get_shopping_cart_url();
	} else {
		$url = get_permalink(get_page_by_path($atts['success-page']));
	}
	controll_redirect_helper($url);
}
add_shortcode('controll-handle-buy-pass', 'controll_handle_buy_pass');

function controll_verify_auth($atts, $content = null) {
	controll_api()->checkAuthentication();
	//check if the user is logged in
	$email = controll_api()->getUserEmail();
	if (!$email) {
		controll_redirect_helper("http://api.con-troll.org/auth/verify?redirect-url=" .
				urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"), 302);
	}
}
add_shortcode('controll-verify-auth', 'controll_verify_auth');

function controll_show_user($atts, $content = null) {
	$email = controll_api()->getUserEmail();
	if (!$email) {
		controll_verify_auth($atts);
	} else {
		return controll_api()->getUserName();
	}
}
add_shortcode('controll-user', 'controll_show_user');

function controll_register_button($atts, $content = null) {
	$atts = shortcode_atts([
			'inactive-text' => 'ההרשמה לא פעילה',
			'login-text' => 'כניסה למערכת ההרשמה',
			'register-text' => 'הרשמה',
			'unavailable-text' => 'תפוס',
			'class' => null,
	], $atts, 'controll-register-button');
	$timeslot = controll_get_current_object();
	$tid = $timeslot->id;
	ob_start();
	if (ConTrollSettingsPage::is_registration_active()) {
		$email = controll_api()->getUserEmail();
		if ($email) {
			$usesPasses = controll_api()->usesPasses();
			if ($usesPasses) {
				?>
				<button class="<?php $atts['class']?>" type="button" onclick="toggle_popup_box('#register-for-<?php echo $tid?>');"><?php echo $atts['register-text']?></button>
				<div class="controll-popup" style="position: absolute; display: none; z-index:100;" id="register-for-<?php echo $tid?>">
				<?php
				if (is_null($content))
					$content = controll_get_default_register_with_passes();
				$timeslot->passes = controll_api()->passes()->timeslot_availability($tid);
				controll_push_current_object($timeslot);
				echo controll_parse_template($timeslot, do_shortcode($content));
				controll_pop_current_object();
				?>
				</div>
				<?php
			} else {
				?><button class="<?php $atts['class']?>" type="submit"><?php echo $atts['register-text']?></button><?php
			}
		} else {
			?><button class="<?php $atts['class']?>" type="submit"><?php echo $atts['login-text']?></button><?php
		}
	} else {
		?><button class="<?php $atts['class']?>" disabled="disabled"><?php echo $atts['inactive-text']?></button><?php
	}
	return ob_get_clean();
}
add_shortcode('controll-register-button', 'controll_register_button');
