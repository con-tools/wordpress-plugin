<?php
/**
 * Template Name: מודל מלא
 * ׂTemplate with access (using controll display EL) to all objects.
 *
 * Currently supported timeslots and locations
 *
 * @package ConTroll
 */

wp_enqueue_style( 'controll-fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', [], '4.6.3' );

if (in_array('controll_need_auth', get_post_custom_keys(get_the_ID())))
	controll_verify_auth([]);

$timeslots = controll_api()->timeslots()->publicCatalog($filters);

$timeslots = array_map('helper_timeslot_fields', $timeslots);
usort($timeslots, function($a,$b){
	$diff = helper_controll_datetime_diff($a->start, $b->start);
	if ($diff == 0)
		return helper_controll_datetime_diff($a->end, $b->end);
		return $diff;
});

$locations = controll_api()->locations()->catalog();

$usesPasses = controll_api()->usesPasses();
if ($usesPasses) {
	$passes = controll_api()->passes()->catalog();
}

ob_start();
get_header();

?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		
		<?php if ($errorMessage): ?>
		<h3>שגיאה: <?php echo $errorMessage ?></h3>
		<?php endif; ?>

		<?php
		
		the_post();
		$com = (object)([
				"timeslots" => $timeslots,
				"locations" => $locations,
				"passes" => $passes,
		]);
		controll_set_current_object($com);
		the_content();
		echo controll_parse_template($com, ob_get_clean());
		controll_set_current_object(null);
		?>
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
