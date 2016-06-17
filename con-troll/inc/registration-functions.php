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
	if (is_array($object) and @$object[$first])
		return controll_data_path_lookup($path, $object[$first]);
	if (is_object($object) and @$object->$first)
		return controll_data_path_lookup($path, $object->$first);
	return null;
}

/**
 * Create a preg_replace_callback replacer that uses the first matching group as an
 * array lookup path into the object provided (array or StdClass are supported)
 * @param array|stdClass $object
 */
function get_controll_field_replacer($object) {
	return function($matches) use ($object) {
		return controll_data_path_lookup($matches[1], $object);
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
	$list = controll_data_path_lookup($path, controll_get_current_object());
	if (!is_array($list))
		return '';
	return join($delimiter, array_map(function($item) use ($content) {
		return controll_parse_template($item, do_shortcode($content));
	},$list));
}
add_shortcode('controll-list-repeat', 'controll_list_repeat');

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
