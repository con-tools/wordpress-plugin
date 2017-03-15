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
			return  controll_parse_template($item, do_shortcode($content));
		} finally {
			controll_pop_current_object();
		}
	},$list));
}

function controll_list_repeat($atts, $content = null) {
	extract(shortcode_atts([
			'path' => null,
			'source' => null,
			'delimiter' => ' ',
			'debug' => null,
	], $atts));
	$content = preg_replace(['{^\s*</p>\s*}','{^\s*<br ?/?>\s*}','{\s*<p>\s*$}','{\s*<br ?/?>\s*$}'], '', $content); // sometimes WP auto-p is very aggressive, lets ignore it
	if (!is_null($source)) { // caller doesn't have the data loaded, try to get the catalog for them
		try {
			controll_push_current_object(controll_load_catalog($source));
			$res = controll_list_repeat_int('.', $delimiter, $content); // we want to iterate over the sourced list
		} finally {
			controll_pop_current_object();
		}
	} else {
		$res = controll_list_repeat_int($path, $delimiter, $content);
	}
	if ($debug) {
		echo json_encode($content);
	}
	return $res;
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
		$url = ConTrollSettingsPage::get_register_page_url() . "?redirect-url=" .
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

function controll_buy_pass($atts, $content = null) {
	$atts = shortcode_atts([
			'success-page' => null,
			'name-prompt' => 'שם בעל הכרטיס',
			'confirm' => 'אישור',
			'buy-text' => 'רכישה',
	], $atts, 'controll-buy-pass');
	$pass = controll_get_current_object();
	$id = $pass->id;
	ob_start();
	?>
	<button class="small" type="button" onclick="toggle_popup_in_group('buypass','#pass-form-<?php echo $id?>')">
	<i class="fa fa-shopping-cart"></i> <?php echo $atts['buy-text']?></button>
	<div class="pass-form" id="pass-form-<?php echo $id?>" style="display:none;position:absolute;z-index:100;">
		<form method="post" action="<?php echo $_SERVER[REQUEST_URI]?>">
		<input type="hidden" name="controll-action" value="buy-pass">
		<?php if (!is_null($atts['success-page'])):?>
		<input type="hidden" name="controll-success-page" value="<?php echo $atts['success-page']?>">
		<?php endif;?>
		<label><?php echo $atts['name-prompt']?>: <input name="pass-name" type="text" /></label>
		<button class="small" name="pass-id" type="submit" value="<?php echo $id?>">
		<i class="fa fa-check"></i> <?php echo $atts['confirm']?></button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('controll-buy-pass', 'controll_buy_pass');

function controll_verify_auth($atts, $content = null) {
	controll_api()->checkAuthentication();
	//check if the user is logged in
	$email = controll_api()->getUserEmail();
	if (!$email) {
		controll_redirect_helper(ConTrollSettingsPage::get_register_page_url() . "?redirect-url=" .
				urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"), 302);
	}
}
add_shortcode('controll-verify-auth', 'controll_verify_auth');

function controll_if_auth($atts, $content = null) {
	controll_api()->checkAuthentication();
	if (controll_api()->getUserEmail()) {
		return do_shortcode($content);
	}
}
add_shortcode('controll-if-auth', 'controll_if_auth');

function controll_unless_auth($atts, $content = null) {
	controll_api()->checkAuthentication();
	if (!controll_api()->getUserEmail()) {
		return do_shortcode($content);
	}
}
add_shortcode('controll-unless-auth', 'controll_unless_auth');

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
			'inactive-text' => "Registration isn't open",
			'login-text' => 'Login',
			'register-text' => 'Register',
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

function controll_delete_ticket($atts, $content = null) {
	$atts = shortcode_atts([
	], $atts, 'controll-delete-ticket');
	$ticket = controll_get_current_object();
	if (!$ticket)
		return;
	ob_start();
	?>
	<button class="small" type="submit" name="ticket-id" value="<?php echo $ticket->id?>" title="מחק כרטיס"
		onclick="return confirm('לבטל את הכרטיס ל<?php echo $ticket->timeslot->event->title?>?');">
		<span class="fa fa-trash-o"></span>
	</button>
	<?php
	return ob_get_clean();
}
add_shortcode('controll-delete-ticket', 'controll_delete_ticket');

function controll_login_link($atts, $content = null) {
	$atts = shortcode_atts([
			'provider' => null,
			'default-page' => ConTrollSettingsPage::get_my_page_url(),
	], $atts, 'controll-login-link');
	$url = @$_REQUEST['redirect-url'] ?? $atts['default-page'];
	ob_start();
	?><a href="http://api.con-troll.org/auth/select/<?php echo $atts['provider']?>?redirect-url=<?php echo urlencode($url)?>"><?php
	echo do_shortcode($content);
	?></a><?php
	return ob_get_clean();
}
add_shortcode('controll-login-link', 'controll_login_link');

function controll_login_form($atts, $content = null) {
	$atts = shortcode_atts([
			'default-page' => ConTrollSettingsPage::get_my_page_url(),
	], $atts, 'controll-login-link');
	$url = @$_REQUEST['redirect-url'] ?? $atts['default-page'];
	ob_start();
	?>
	<form method="post" action="http://api.con-troll.org/auth/signin" id="password-auth">
	<input type="hidden" name="redirect-url" value="<?php echo $url?>">
	<?php
	echo do_shortcode($content);
	?>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode('controll-login-form', 'controll_login_form');
