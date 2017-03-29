<?php
/**
 * Template Name: עמוד ניהול סיסמה
 * Handle user password reset and other user management workflows
 * @package ConTroll
 */
$template = null;
$text = null;

function controll_replace_content_text($content) {
	global $replace_content_text;
	return $replace_content_text;
}

switch ($_REQUEST['action']) {
	case 'register':
		$template = 'register-user.php';
		break;
	case 'completeregister':
		$name = @$_REQUEST['controll-register-name'];
		$email = @$_REQUEST['email'];
		$password1 = @$_REQUEST['controll-register-password-register'];
		$password2 = @$_REQUEST['controll-register-password-confirm'];
		if (!trim($name)) {
			$template = [ 'register-user.php', [
					'error' => "יש למלא את השם"
			]];
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$template = [ 'register-user.php', [
					'error' => "יש למלא כתובת דואר אלקטרוני חוקית"
			]];
		} elseif (strlen($password1) < 6) {
			$template = [ 'register-user.php', [
					'error' => "יש למלא סיסמה באורך של 6 תוים לפחות"
			]];
		} elseif ($password1 != $password2) {
			$template = [ 'register-user.php', [
					'error' => "יש לוודא ששני שדות הסיסמה זהים"
			]];
		} else { // try to register
			switch (controll_api()->registerUser($email, $name, $password1)) {
				case Controll::REGISTER_STATUS_OK:
					$template = 'register-success.php';
					break;
				case Controll::REGISTER_STATUS_ERR_EXIST:
					$template = [ 'register-user.php', [
						'error' => "חשבון הדואל כבר רשום במערכת - נסה להכנס עם הספק המתאים או לבקש אתחול סיסמה"
					]];
					break;
				default:
					$template = [ 'register-user.php', [
						'error' => "ארעה שגיאה לא צפויה ברישום, אנא פנה למנהל המערכת"
					]];
			}
		}
		break;
	case 'do-login':
		controll_verify_auth([]);
		$text = "<p>נכנסת בהצלחה!</p>";
		break;
	case 'logout':
		controll_api()->logout();
		$text = '<p>התנתקת מהמערכת</p>';
		break;
	case 'needreset':
		ob_start();
		?>
		<form method="POST" action="?">
		<p>כדי לאפס את הסיסמה, יש למלא את כתובת הדואר</p>
		<input type="hidden" name="action" value="startreset">
		<label>כתובת דואר: <input type="text" name="email" value=""/></label>
		<p>
		<button type="submit">שלח</button>
		</p>
		</form>
		<?php
		$text = ob_get_clean();
		break;
	case 'startreset':
		$email = $_REQUEST['email'];
		$url =  "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		logger()->Info("Sending password reset back to $url");
		controll_api()->passwordReset($email, $url . "?action=completereset");
		ob_start();
		?>
		<p>אם מילאת את פרטיך נכון, מכתב איפוס סיסמה נשלח לכתובת הדואר שלך. כדי להשלים את איפוס הסיסמה שלך, יש ללחוץ על הקישור במכתב.</p>
		<p>הדואר יגיע עם מ-ConTroll. אם לא קיבלת אותו תוך מספר דקות, כדאי לחפש בתיבת דואר הזבל.</p>
		<?php
		$text = ob_get_clean();
		break;
	case 'completereset':
		$token = @$_REQUEST['token'];
		ob_start();
		if (!$token) {
			?>
			<p>לא נמצא זיהוי משתמש</p>
			<?php
		} elseif (!($user = controll_api()->getUserData($token))) {
			?>
			<p>זיהוי משתמש לא תקין - יתכן שהזיהוי כבר נוצל לאיפוס סיסמה בעבר?</p>
			<?php
		} else {
			logger()->Info("Found password reset token $token for ". print_r($user, true));
			?>
			<form method="POST" action="?">
			<p>כדי להשלים את איפוס הסיסמה, יש לרשום את הסיסמה הרצויה פעמיים:</p>
			<input type="hidden" name="action" value="resetpassword">
			<input type="hidden" name="controll-register-token" value="<?php echo $token ?>">
			<p><label>סיסמה: <input type="password" name="controll-register-password1"></label></p>
			<p><label>אישור סיסמה: <input type="password" name="controll-register-password2"></label>
			<p><button type="submit">שלח</button></p>
			</form>
			<?php
		}
		$text = ob_get_clean();
		break;
	case 'resetpassword':
		$token = @$_REQUEST['controll-register-token'];
		$password1 = @$_REQUEST['controll-register-password1'];
		$password2 = @$_REQUEST['controll-register-password2'];
		ob_start();
		if (!$token) {
			?>
			<p>לא נמצא זיהוי משתמש</p>
			<?php
		} elseif (!$password1) {
			?>
			<p>הסיסמה אינה יכולה להיות ריקה. חזור לעמוד הקודם כדי לנסות שנית.</p>
			<?php
		} elseif ($password1 != $password2) {
			?>
			<p>סיסמת האישור אינה זהה לסיסמה. חזור לעמוד הקודם כדי לנסות שנית.</p>
			<?php
		} else {
			logger()->Info("Got token $token, sending password reset");
			if (!controll_api()->setPassword($token, $password1)) {
				?>
				<p>חלה שגיאה במהלך איפוס הסיסמה - יש לפנות למנהל המערכת.</p>
				<?php
			} else {
				?>
				<p>עדכון הסיסמה הסתיים בהצלחה. עתה יש להכנס מחדש למערכת.</p>
				<?php
			}
		}
		$text = ob_get_clean();
		break;
}

get_header();

if ($errorMessage) {
	?><h3>שגיאה: <?php echo $errorMessage ?></h3><?php
}

if ($template) {
	ob_start();
	if (is_array($template)) {
		controll_render_template($template[0], $template[1]);
	} else {
		controll_render_template($template);
	}
	$replace_content_text = ob_get_clean();
	add_filter('the_content', 'controll_replace_content_text');
} elseif ($text) {
	$replace_content_text = $text;
	add_filter('the_content', 'controll_replace_content_text');
}

get_template_part('page');

get_footer();
