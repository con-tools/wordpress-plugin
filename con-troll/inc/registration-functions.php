<?php
date_default_timezone_set("Israel");

$controll_bigor_rounds = [
		'צהריים' 	=> [ new DateTime("2016-06-30 14:00"), new DateTime("2016-06-30 18:00")],
		'ערב' 		=> [ new DateTime("2016-06-30 20:00"), new DateTime("2016-06-31 00:00")],
];

function controll_data_path_lookup($path, $object) {
	if ($path == '.') {
		return $object;
	}
	if (!is_array($path))
		$path = explode('.', $path);
	if (empty($path)) {
		return $object;
	}
	$first = array_shift($path);
	if (is_array($object)) {
		if (@array_key_exists($first, $object))
			return controll_data_path_lookup($path, $object[$first]);
		if ($first == "length" or $first == "size")
			return count($object);
	}
	if (is_object($object) and isset($object->$first))
		return controll_data_path_lookup($path, $object->$first);
	return "no such field '$first'";
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
		return $obj;
	}; 
}

function controll_set_current_object($object) {
	global $_controll_current_object;
	return $_controll_current_object = $object;
}

function controll_get_current_object() {
	global $_controll_current_object;
	return $_controll_current_object;
}

function controll_parse_template($object, $text) {
	return preg_replace_callback('/\{\{([^\}]+)\}\}/', get_controll_field_replacer($object), $text);
}

function controll_date_format($atts, $content = null) {
	extract(shortcode_atts([
			'path' => null,
			'format' => 'd/n H:i',
	], $atts));
	$date = controll_data_path_lookup($path, controll_get_current_object());
	if (!($date instanceof DateTime))
		$date = new DateTime($date);
	return date($format, $date->getTimestamp());
}
add_shortcode('controll-date-format', 'controll_date_format');

function controll_list_repeat($atts, $content = null) {
	extract(shortcode_atts([
			'path' => null,
			'delimiter' => ' ',
	], $atts));
	$curobj = controll_get_current_object();
	$list = controll_data_path_lookup($path, $curobj);
	if (!is_array($list))
		return '';
	return join($delimiter, array_map(function($item) use ($content, $curobj) {
		controll_set_current_object($item);
		try {
			return controll_parse_template($item, do_shortcode($content));
		} finally {
			controll_set_current_object($curobj);
		}
	},$list));
}
add_shortcode('controll-list-repeat', 'controll_list_repeat');
add_shortcode('controll-list-repeat-1', 'controll_list_repeat');
add_shortcode('controll-list-repeat-2', 'controll_list_repeat');
add_shortcode('controll-list-repeat-3', 'controll_list_repeat');

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

function helper_controll_get_round_name($startTime) {
	global $controll_bigor_rounds;
	foreach ($controll_bigor_rounds as $name => $times) {
		if ($times[0] <= $startTime and $startTime < $times[1])
			return $name;
	}
	return '';
}

function helper_timeslot_fields($timeslot){
	if (!($timeslot instanceof stdClass))
		return $timeslot;
	// provide some custom fields to help display
	$timeslot->start = $start = new DateTime($timeslot->start);
	$end = (clone $start);
	$end->add(new DateInterval("PT" . $timeslot->duration . "M"));
	$timeslot->end = $end;
	$timeslot->round = helper_controll_get_round_name($timeslot->start);
	if (!$timeslot->available_tickets)
		$timeslot->available_tickets = 'אין יותר מקומות';
	return $timeslot;
}
