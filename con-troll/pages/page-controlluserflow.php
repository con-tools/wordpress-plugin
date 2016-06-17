<?php
/**
 * Template Name: עמוד ניהול סיסמה
 * Handle user password reset and other user management workflows
 * @package ConTroll
 */
$template = null;
$text = null;

switch ($_REQUEST['action']) {
	case 'register':
		$template = 'register-user.php';
		break;
	case 'completeregister':
		$name = @$_REQUEST['name'];
		$email = @$_REQUEST['email'];
		$password1 = @$_REQUEST['password-register'];
		$password2 = @$_REQUEST['password-confirm'];
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
		} elseif (!controll_api()->registerUser($email, $name, $password1)) {
			$template = [ 'register-user.php', [
					'error' => "ארעה שגיאה לא צפויה ברישום, אנא פנה למנהל המערכת"
			]];
		} else {
			$template = 'register-success.php';
		}
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
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<p><label>סיסמה: <input type="password" name="password1"></label></p>
			<p><label>אישור סיסמה: <input type="password" name="password2"></label>
			<p><button type="submit">שלח</button></p>
			</form>
			<?php
		}
		$text = ob_get_clean();
		break;
	case 'resetpassword':
		$token = @$_REQUEST['token'];
		$password1 = @$_REQUEST['password1'];
		$password2 = @$_REQUEST['password2'];
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
		
		<?php
		if ($template) {
			if (is_array($template)) {
				render_template($template[0], $template[1]);
			} else {
				render_template($template);
			}
		} elseif ($text) {
			echo $text;
		} else {
			the_post();
			get_template_part( 'content', 'page' );
		}
		?>
		
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
