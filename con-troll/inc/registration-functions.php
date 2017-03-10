<?php
date_default_timezone_set("Israel");

function controll_filters($filters, $object) {
	if (is_null($filters))
		return $object;
	if (preg_match('/exist\(([^\)]*)\)/', $filters, $matches)) {
		return $object ? $matches[1] : "";
	}
	if (preg_match('/unless\(([^\)]*)\)/', $filters, $matches)) {
		return $object ? "" : $matches[1];
	}
	if (preg_match('/date\(([^\)]*)\)/', $filters, $matches)) {
		return date($matches[1] ? $matches[1] : "d/n H:i",
				(($object instanceof DateTime) ?
					$object->getTimestamp() : $object));
	}
	return $object;
}

function controll_data_path_lookup($path, $object) {
	if ($path == '.') {
		return $object;
	}
	if (is_string($path)) {
		@list($path, $filters) = explode("|", $path, 2);
		$path = explode('.', $path);
	}
	if (empty($path)) {
		return $object;
	}
	$first = array_shift($path);
	if (is_array($object)) {
		if (@array_key_exists($first, $object))
			return controll_filters($filters, controll_data_path_lookup($path, $object[$first]));
		if ($first == "length" or $first == "size")
			return count($object);
	}
	if (is_object($object) and isset($object->$first))
		return controll_filters($filters, controll_data_path_lookup($path, $object->$first));
	return "no such field '$first'";//" in " . print_r($object,true);
}

/**
 * Create a preg_replace_callback replacer that uses the first matching group as an
 * array lookup path into the object provided (array or StdClass are supported)
 * @param array|stdClass $object
 */
function get_controll_field_replacer($object) {
	return function($matches) use ($object) {
		$obj = controll_data_path_lookup($matches[1], $object);
		if ($obj instanceof DateTime)
			return date("d/n H:i", $obj->getTimestamp());
		if ($obj instanceof stdclass)
			return print_r($obj, true);
		return trim("{$obj}");
	};
}

function controll_load_catalog($source) {
	switch ($source) {
		case 'timeslots':
			$timeslots = controll_api()->timeslots()->publicCatalog($filters);
			$timeslots = array_map('helper_timeslot_fields', $timeslots);
			usort($timeslots, function($a,$b){
				$diff = helper_controll_datetime_diff($a->start, $b->start);
				if ($diff == 0)
					return helper_controll_datetime_diff($a->end, $b->end);
				return $diff;
			});
			return $timeslots;
		case 'locations': return controll_api()->locations()->catalog();
		case 'passes':
			$usesPasses = controll_api()->usesPasses();
			if ($usesPasses) {
				return controll_api()->passes()->catalog();
			}
			return [];
		case 'user-passes':
			$usesPasses = controll_api()->usesPasses();
			if ($usesPasses) {
				return $authorized_passes = array_filter(controll_api()->passes()->user_catalog(), function($pass){
					return $pass->status == 'authorized';
				});
			}
			return [];
		case 'tickets':
			return array_filter(controll_api()->tickets()->catalog(), function($ticket){
					return $ticket->status == 'authorized';
			});
		case 'hosting':
			$hosting = array_map('helper_timeslot_fields', controll_api()->timeslots()->myHosting() ?: []);
			usort($hosting, function($a, $b){
				$diff = helper_controll_datetime_diff($a->start, $b->start);
				if ($diff == 0)
					return helper_controll_datetime_diff($a->end, $b->end);
					return $diff;
			});
			return $hosting;
		case 'purchases':
			return array_filter(controll_api()->purchases()->catalog(), function($purchase){
					return $purchase->status == 'authorized';
			});
		default: return [];
	}
}

function controll_set_current_object($object) {
	global $_controll_current_object;
	return $_controll_current_object = $object;
}

function controll_get_current_object() {
	global $_controll_current_object;
	return $_controll_current_object;
}

function controll_push_current_object($object) {
	global $_controll_current_object_stack;
	if (!@$_controll_current_object_stack) $_controll_current_object_stack = [];
	array_push($_controll_current_object_stack, controll_get_current_object());
	controll_set_current_object($object);
}

function controll_pop_current_object() {
	global $_controll_current_object_stack;
	controll_set_current_object(array_pop($_controll_current_object_stack));
}

function controll_redirect_helper($url, $code = 302) {
	if (headers_sent()) {
		?>
		<p>על מנת להמשיך - <a href="<?php echo $url ?>">יש ללחוץ כאן</a></p>
		<script>
		window.location.href = '<?php echo $url ?>';
		</script>
		<?php
	} else {
		wp_redirect($url, $code);
	}
	exit();
}

function controll_parse_template($object, $text) {
	return trim(preg_replace_callback('/\{\{([^\}]+)\}\}/', get_controll_field_replacer($object), $text));
}

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

function controll_list_repeat($atts, $content = null) {
	remove_filter( 'the_content', 'wpautop' );
	extract(shortcode_atts([
			'path' => null,
			'source' => null,
			'delimiter' => ' ',
	], $atts));
	if (!is_null($source)) { // caller doesn't have the data loaded, try to get the catalog for them
		controll_set_current_object(controll_load_catalog($source));
		$path = '.'; // we want to iterate over the sourced list
	}
	$curobj = controll_get_current_object();
	$list = controll_data_path_lookup($path, $curobj);
	if (!is_array($list))
		return '';
	return join($delimiter, array_map(function($item) use ($content, $curobj) {
		controll_set_current_object($item);
		try {
			return  str_replace(["\n","\r"],"",controll_parse_template($item, do_shortcode($content)));
		} finally {
			controll_set_current_object($curobj);
		}
	},$list));
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

function helper_controll_datetime_diff(DateTime $a, DateTime $b) {
	if ($a == $b)
		return 0;
	$diff = $a->diff($b);
	if ($diff->invert)
		return 1;
	return -1;
}

function helper_controll_fake_redirect($url) {
	ob_start();
	?>
	<script>
		window.location.href = "<?php echo $url ?>";
	</script>
	<?php
	return ob_get_clean();
}

function helper_timeslot_fields($timeslot){
	if (!($timeslot instanceof stdClass))
		return $timeslot;
	// provide some custom fields to help display
	$timeslot->start = $start = new DateTime($timeslot->start);
	$end = (clone $start);
	$end->add(new DateInterval("PT" . $timeslot->duration . "M"));
	$timeslot->end = $end;
	if (!$timeslot->available_tickets)
		$timeslot->available_tickets = 'אין יותר מקומות';
	return $timeslot;
}
