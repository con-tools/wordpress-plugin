<?php
/**
 * Template Name: עמוד ארוע
 * Show a single event, specified by the query parameter "id"
 * @package ConTroll
 */
$timeslot_id = (int)@$_REQUEST['id'];
if (!$timeslot_id) { // sanity
	wp_redirect('/',302);
	echo "No event ID specified\n";
	exit;
}

$thisPageURL = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

function show_error($errormsg) {
	global $thisPageURL;
	wp_redirect($thisPageURL . (strstr($thisPageURL, '?')?'&':'?') . 'error=' . urlencode($errormsg));
	exit;
}

/**
 * Handle the registration process
 * This function never returns
 */
function handle_registration($timeslot) {
	if (controll_api()->usesPasses())
		handle_pass_registration($timeslot);
	else
		handle_ticket_registration($timeslot);
}

/**
 * Register a ticket to an existing pass, or reserve a new pass, under a convention where users buy daily passes
 * This function never returns
 */
function handle_pass_registration($timeslot) {
	if (!array_key_exists('pass', $_REQUEST))
		show_error("Missing pass ID!");
	$pass = $_REQUEST['pass'];
	if (is_numeric($pass)) { // user submitted an existing pass to register a ticket for
		$res = controll_api()->passes()->register($pass, $timeslot->id);
		if (!$res->status)
			show_error($ticket->error);
		wp_redirect(ConTrollSettingsPage::get_my_page_url(), 302);
		exit;
	} else { // user requests a new pass
		if (!array_key_exists('pass-type', $_REQUEST))
			show_error("Missing pass type ID to purchase!");
		if (!array_key_exists('pass-name', $_REQUEST))
			show_error("Missing pass owner name!");
		$res = controll_api()->passes()->buy($_REQUEST['pass-type'], $_REQUEST['pass-name']);
		if ($res->error)
			show_error($res->error);
		$pass = $res->id;
		$res = controll_api()->passes()->register($pass, $timeslot->id);
		if (!$res->status)
			show_error($ticket->error);
		wp_redirect(ConTrollSettingsPage::get_shopping_cart_url(), 302);
		exit;
	}
}

/**
 * Reserve a ticket for the user, under a convention were users buy tickets
 * This function never returns
 */
function handle_ticket_registration($timeslot) {
	$ticket = controll_api()->tickets()->create($timeslot->id);
	if ($ticket->status) {
		wp_redirect(ConTrollSettingsPage::get_shopping_cart_url(), 302);
		exit;
	}
	show_error($ticket->error);
}


$timeslot = controll_api()->timeslots()->get($timeslot_id);
if (!$timeslot) {
	wp_redirect('/',302);
	echo "No timeslot found for $timeslot_id\n";
	exit;
}

$timeslot = helper_timeslot_fields($timeslot);

if (@$_REQUEST['error']) {
	$errorMessage = stripslashes($_REQUEST['error']);
}

//check if the user is logged in
$email = controll_api()->getUserEmail();

if (ConTrollSettingsPage::is_registration_active()) {
	// handle form submit
	switch (@$_REQUEST['action']) {
		case 'login':
			wp_redirect(ConTrollSettingsPage::get_register_page_url() . "?redirect-url=" .
					urlencode($thisPageURL), 302);
			exit;
		case 'register':
			handle_registration($timeslot);
	}

	if ($email) {
		$timeslot->{"register-button"} = 'הרשמה';
		$formaction = 'register';
	} else {
		$timeslot->{"register-button"} = 'כניסה למערכת ההרשמה';
		$formaction = 'login';
	}
	$timeslot->{'registration-active'} = true;
} else {
	$timeslot->{"register-button"} = 'ההרשמה לא פעילה';
	$timeslot->{'registration-active'} = false;
}

function assignPageTitle($orig = null){
	global $timeslot;
	if (is_array($orig)) {// title_parts mode
		$orig['title'] = $timeslot->event->title;
		return $orig;
	}
	return $timeslot->event->title . " | " . get_bloginfo('name');
}
add_filter('wp_title', 'assignPageTitle',20);
add_filter('document_title_parts', 'assignPageTitle');

get_header();

?>

<div id="primary" class="content-area event-page">
	<main id="main" class="site-main" role="main">
	
		<div class="registration event">
		
		<?php if ($errorMessage): ?>
		<h3>שגיאה: <?php echo $errorMessage ?></h3>
		<?php endif; ?>
		
		<form name="registration" method="post" action="?<?php echo $_SERVER['QUERY_STRING'] ?>" onsubmit="registration_submit_callback(event)">
			<input type="hidden" name="id" value="<?php echo $timeslot->id ?>">
			<input type="hidden" name="action" value="<?php echo $formaction ?>">
		
		<?php
		
		the_post();
		controll_push_current_object($timeslot);
		ob_start();
		the_content();
		echo controll_parse_template($timeslot, ob_get_clean());
		controll_pop_current_object();
		
		?>
		</form>

	</main><!-- #main -->
</div><!-- #primary -->
		
<?php get_footer(); ?>
