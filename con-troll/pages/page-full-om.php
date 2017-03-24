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

ob_start();
get_header();

?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		
		the_post();
		log_info("Starting full OM page");
		$com = (object)([
				"timeslots" => controll_load_catalog('timeslots'),
				"locations" => controll_load_catalog('locations'),
				"passes" => controll_load_catalog('user-passes'),
		]);
		controll_set_current_object($com);
		the_content();
		echo controll_parse_template($com, ob_get_clean());
		controll_set_current_object(null);
		?>
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
