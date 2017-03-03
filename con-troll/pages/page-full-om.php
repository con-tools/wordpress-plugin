<?php
/**
 * Template Name: מודל מלא
 * ׂTemplate with access (using controll display EL) to all objects.
 *
 * Currently supported timeslots and locations
 *
 * @package ConTroll
 */

$timeslots = array_map('helper_timeslot_fields', controll_api()->timeslots()->publicCatalog($filters));
usort($timeslots, function($a,$b){
	$diff = helper_controll_datetime_diff($a->start, $b->start);
	if ($diff == 0)
		return helper_controll_datetime_diff($a->end, $b->end);
		return $diff;
});

$locations = controll_api()->locations()->catalog();

get_header();

?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		
		<?php
		
		the_post();
		$com = (object)([
				"timeslots" => $timeslots,
				"locations" => $locations,
		]);
		controll_set_current_object($com);
		ob_start();
		the_content();
		echo controll_parse_template($com, ob_get_clean());
		controll_set_current_object(null);
		
		?>
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
