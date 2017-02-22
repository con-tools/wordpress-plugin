<?php
/**
 * Template Name: עמוד ארוע
 * Show a single event, specified by the query parameter "id"
 * @package ConTroll
 */
$registration_enabled = true;

$timeslot_id = (int)@$_REQUEST['id'];
if (!$timeslot_id) { // sanity
	wp_redirect('/',302);
	echo "No event ID specified\n";
	exit;
}

$timeslot = controll_api()->timeslots()->get($timeslot_id);
if (!$timeslot) {
	wp_redirect('/',302);
	echo "No timeslot found for $timeslot_id\n";
	exit;
}

$timeslot = helper_timeslot_fields($timeslot);

//check if the user is logged in
$email = controll_api()->getUserEmail();

if (ConTrollSettingsPage::is_registration_active()) {
	// handle form submit
	switch (@$_REQUEST['action']) {
		case 'login':
			wp_redirect("http://api.con-troll.org/auth/verify?redirect-url=" .
					urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]?id=" . $timeslot_id), 302);
			exit;
		case 'register':
			if (!$registration_enabled) {
				wp_redirect("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]?id=" . $timeslot_id, 302);
				exit;
			}
			$ticket = controll_api()->tickets()->create($timeslot->id);
			if ($ticket->status) {
				wp_redirect(ConTrollSettingsPage::get_my_page_url(), 302);
				exit;
			}
			$errorMessage = $ticket->error;
			break;
	}

	if ($email) {
		$timeslot->{"register-button"} = 'הרשמה';
		$formaction = 'register';
	} else {
		$timeslot->{"register-button"} = 'כניסה למערכת ההרשמה';
		$formaction = 'login';
	}
} else {
	$timeslot->{"register-button"} = 'ההרשמה לא פעילה';
}

$title = $timeslot->event->title;
function assignPageTitle(){
	global $title;
	return $title . " | " . get_bloginfo('name');
}
add_filter('wp_title', 'assignPageTitle');

get_header();

?>

<div id="primary" class="content-area event-page">
	<main id="main" class="site-main" role="main">
	
		<div class="col-md-<?php echo $mainwidth ?> registration event">
		
		<?php if ($errorMessage): ?>
		<h3>שגיאה: <?php echo $errorMessage ?></h3>
		<?php endif; ?>
		<form name="registration" method="post" action="?<?php echo $_SERVER['QUERY_STRING'] ?>">
			<input type="hidden" name="id" value="<?php echo $timeslot->id ?>">
			<input type="hidden" name="action" value="<?php echo $formaction ?>">
		
		<?php
		
		the_post();
		controll_set_current_object($timeslot);
		ob_start();
		the_content();
		echo controll_parse_template($timeslot, ob_get_clean());
		controll_set_current_object(null);
		
		?>
		</form>

	</main><!-- #main -->
</div><!-- #primary -->
		
<?php get_footer(); ?>
