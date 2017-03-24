<?php
date_default_timezone_set("Israel");

require_once dirname(__FILE__) . '/helpers.php';
require_once dirname(__FILE__) . '/controll-objects.php';
require_once dirname(__FILE__) . '/default-content.php';
require_once dirname(__FILE__) . '/shortcodes.php';
require_once dirname(__FILE__) . '/handlers.php';

// Register for POST form processing
add_action( 'wp_loaded', 'controll_handle_forms' );

// Register for error message display
add_action( 'the_post', 'controll_show_errors' );

/**
 * Auth redirect only retains the first argument (a bug in ConTroll),
 * until its fixed, support redirection with all the data by encoding it into
 * the session and redirecting with a coding ID.
 *
 * This function never returns.
 * @param array $data
 */
function controll_authorize($data = null) {
	if (!is_null($data)) {
		$id = uniqid();
		$_SESSION['controll-auth-with-data-' . $id] = $data;
		$callbackdata = '?controll-auth-data=' . urlencode($id);
	} else
		$callbackdata = '';
	@list($uri,$query) = explode('?',$_SERVER[REQUEST_URI]);
	$url = ConTrollSettingsPage::get_register_page_url() . '?redirect-url=' .
			urlencode("http://$_SERVER[HTTP_HOST]" . $uri . $callbackdata);
	controll_redirect_helper($url);
}

// The wp_loaded action is called before headers are sent, and before Wordpress does its own query parsing
// so its a great place for us to use PHP methods to handle our forms
function controll_handle_forms() {
	if (is_admin()) return; // we don't handle the admin dashboard
	
	if (array_key_exists('controll-auth-data', $_REQUEST)) {
		// load redirect with auth data into the request
		$id = $_REQUEST['controll-auth-data'];
		$data = $_SESSION['controll-auth-with-data-' . $id];
		if (is_array($data)) {
			foreach ($data as $key => $val)
				$_REQUEST[$key] = $val;
		}
		unset($_SESSION['controll-auth-with-data-' . $id]);
	}
	
	if (controll_api()->getUserEmail() and rand(0,3) == 0)
		controll_api()->verify(); // ping the ConTroll server to keep our auth token alive
	
	// Choose handling by type of ConTroll form being submitted
	switch (@$_REQUEST['controll-action']) {
		case 'buy-pass': return controll_handler_buy_pass();
		case 'cancel-pass': return controll_handler_cancel_pass();
		case 'pass-package': return controll_handler_pass_package();
		case 'cancel-ticket': return controll_handler_cancel_ticket();
		case 'login': return controll_authorize();
		case 'register-ticket': return controll_handler_registration();
	}
}

function controll_set_error($error_message) {
	global $controll_error_message;
	$controll_error_message = $error_message;
}

function controll_show_errors($the_post) {
	global $controll_error_message;
	if (!$controll_error_message)
		return;
	?>
	<h3 class="error"><?php echo $controll_error_message ?></h3>
	<?php
}