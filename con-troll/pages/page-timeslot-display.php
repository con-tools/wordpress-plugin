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

$mainwidth = 12;
if (is_active_sidebar('pageright'))
	$mainwidth -= 3;
if (is_active_sidebar('pageleft'))
	$mainwidth -= 3;
if ($mainwidth > 9)
	$mainwidth = 10; // don't let main content be too wide
?>

<div class="container">
	<div class="row">
	
		<?php if (is_active_sidebar('pageleft')): ?>
		<div class="col-md-3">
			<?php get_sidebar('left'); ?>
		</div>
		<?php endif; ?>
	
		<div class="col-md-<?php echo $mainwidth ?> registration event">
		<?php if ($mainwidth == 10):?>
			<div style="@media screen and (min-width: 783px) {margin-<?php echo is_rtl() ? 'right' : 'left'?>: -3.2em;}">
		<?php endif;?>
		<?php if ($errorMessage): ?>
		<h3>שגיאה: <?php echo $errorMessage ?></h3>
		<?php endif; ?>
		<form name="registration" method="post" action="?">
			<input type="hidden" name="id" value="<?php echo $timeslot->id ?>">
			<input type="hidden" name="action" value="<?php echo $formaction ?>">
		
		<?php
		the_post();
		controll_set_current_object($timeslot);
		ob_start();
		get_template_part( 'content', 'page' );
		echo controll_parse_template($timeslot, ob_get_clean());
		controll_set_current_object(null);
		?>
		</form>
		<?php if ($mainwidth == 10):?>
			</div>
		<?php endif;?>
		</div>
		
		<?php if (is_active_sidebar('pageright')): ?>
			<div class="col-md-3">
				<?php get_sidebar('right'); ?>
			</div>
		<?php endif; ?>
		
	</div>
</div>

<?php get_footer(); ?>
