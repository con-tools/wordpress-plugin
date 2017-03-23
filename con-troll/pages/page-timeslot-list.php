<?php
/**
 * Template Name: רשימת ארועים
 * Iterate over the list of public timeslots and render the single page for every time slot
 * @package ConTroll
 */

$tags = [];
foreach (controll_api()->tags()->catalog() as $tag) {
	if ($tag->title != 'משבצת זמן')
		usort($tag->values, function($a,$b){
			if (ord($a[0]) < 127 && ord($b[0]) > 127)
				return 1; // hate english
			if (ord($b[0]) < 127 && ord($a[0]) > 127)
				return -1; // hate english
			return strcasecmp($a,$b);
		});
	$tags[$tag->title] = $tag->values;
}

// parse tag names in query string (because PHP parsing mangles field names)
$filters = [];
$query = array_reduce(array_map(function($part){
	return array_map(function($kv){ return urldecode($kv); },explode('=', $part,2));
}, explode('&', $_SERVER['QUERY_STRING'])), function($query, $pair) {
	$query[$pair[0]] = @$pair[1];
	return $query;
}, []);
foreach ($query as $key => $value) {
	if (strpos($key, "tag:") !== 0)
		continue;
	if ($value)
		$filters[$key] = $value;
}
logger()->Info("Filtering by ".print_r($filters, true));

$timeslots = array_map('helper_timeslot_fields', controll_api()->timeslots()->publicCatalog($filters));
usort($timeslots, function($a,$b){
	$diff = helper_controll_datetime_diff($a->start, $b->start);
	if ($diff == 0)
		return helper_controll_datetime_diff($a->end, $b->end);
	return $diff;
});

get_header();

?>

<div id="primary" class="content-area event-list">
	<main id="main" class="site-main" role="main">
		
		<?php /* Search tools */ ?>
		<form method="get" action="">
		<input type="hidden" name="action" value="filter">
		
		<div class="filter-element">
		<label for="round">משבצת זמן:</label>
		<select id="round" name="tag:משבצת זמן" onchange="return this.form.submit();">
			<option value="">הכל</option>
			<?php foreach ($tags['משבצת זמן'] as $value):?>
			<option value="<?php echo $value?>" <?php
				if ($filters['tag:משבצת זמן'] == $value) {?>selected="selected"<?php }
				?>><?php echo $value?></option>
			<?php endforeach;?>
		</select>
		</div>

		<div class="filter-element">
		<label for="event_type">משבצת תוכן:</label>
		<select id="event_type" name="tag:משבצת תוכן" onchange="return this.form.submit();">
			<option value="">הכל</option>
			<?php foreach ($tags['משבצת תוכן'] as $value):?>
			<option value="<?php echo $value?>" <?php
				if ($filters['tag:משבצת תוכן'] == $value) {?>selected="selected"<?php }
				?>><?php echo $value?></option>
			<?php endforeach;?>
		</select>
		</div>
		
		<div class="filter-element">
		<label for="genre">שיטה:</label>
		<select id="genre" name="tag:שיטה" onchange="return this.form.submit();">
			<option value="">הכל</option>
			<?php foreach ($tags["שיטה"] as $value):?>
			<option value="<?php echo $value?>" <?php
				if ($filters['tag:שיטה'] == $value) {?>selected="selected"<?php }
				?>><?php echo $value?></option>
			<?php endforeach;?>
		</select>
		</div>
		
		<div class="filter-element">
		<label for="genre">מגבלת גיל:</label>
		<select id="genre" name="tag:מגבלת גיל" onchange="return this.form.submit();">
			<option value="">הכל</option>
			<?php foreach ($tags["מגבלת גיל"] as $value):?>
			<option value="<?php echo $value?>" <?php
				if ($filters['tag:מגבלת גיל'] == $value) {?>selected="selected"<?php }
				?>><?php echo $value?></option>
			<?php endforeach;?>
		</select>
		</div>
		
		</form>
		
		<?php
		
		the_post();
		foreach ($timeslots as $timeslot) {
			//if ($filters['tag:משבצת זמן'] and $filters['tag:משבצת זמן'] != $timeslot->round)
			//	continue;
			controll_set_current_object($timeslot);
			ob_start();
			the_content();
			echo controll_parse_template($timeslot, ob_get_clean());
			controll_set_current_object(null);
		}
		
		?>
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
