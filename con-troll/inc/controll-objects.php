<?php

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
							$object->getTimestamp() : new DateTime("$object")));
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
	global $_controll_source_cache;
	if (!isset($_controll_source_cache))
		$_controll_source_cache = [];
	if (isset($_controll_source_cache[$source]))
		return $_controll_source_cache[$source];
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
			return $_controll_source_cache[$source] = $timeslots;
		case 'locations':
			return $_controll_source_cache[$source] = controll_api()->locations()->catalog();
		case 'passes':
			$usesPasses = controll_api()->usesPasses();
			if ($usesPasses) {
				return $_controll_source_cache[$source] = controll_api()->passes()->catalog();
			}
			return [];
		case 'user-passes':
			$usesPasses = controll_api()->usesPasses();
			if ($usesPasses) {
				return $_controll_source_cache[$source] = array_filter(controll_api()->passes()->user_catalog(), function($pass){
					return $pass->status == 'authorized';
				});
			}
			return [];
		case 'tickets':
			return $_controll_source_cache[$source] = array_filter(controll_api()->tickets()->catalog(), function($ticket){
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
			return $_controll_source_cache[$source] = $hosting;
		case 'purchases':
			return $_controll_source_cache[$source] = array_filter(controll_api()->purchases()->catalog(), function($purchase){
				return $purchase->status == 'authorized';
			});
		case 'coupons':
			return $_controll_source_cache[$source] = array_filter(controll_api()->coupons()->catalog(), function($coupon){
				return !$coupon->used;
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

function controll_parse_template($object, $text) {
	return trim(preg_replace_callback('/\{\{([^\}]+)\}\}/', get_controll_field_replacer($object), $text));
}
