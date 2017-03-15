<?php
/**
 * Template Name: חנות חולצות
 * Show a single event, specified by the query parameter "id"
 * @package ConTroll
 */

function try_purchase($type, $color, $size, $amount, $allskus) {
	if (!$type or !$color or !$size) {
		$_SESSION['error-message'] = 'נא לבחור צבע וגודל';
		return;
	}
		
	$skucode = stripslashes("$type:$color:$size");
	$sku = null;
	foreach ($allskus as $test) {
		if ($test->code != $skucode) continue;
		$sku = $test;
		break;
	}
	
	if (!$sku) {
		$_SESSION['error-message'] = 'לא נמצא מוצר עם ההגדרות שנבחרו - אנא פנה למנהל';
		return;
	}
	
	$res = controll_api()->purchases()->create($skucode, $amount);
	logger()->Info("Added purchase: " . print_r($res, true));
	wp_redirect(ConTrollSettingsPage::get_shopping_cart_url());
	exit;
}
 
controll_api()->checkAuthentication();
//check if the user is logged in
$email = controll_api()->getUserEmail();
if (!$email) {
	wp_redirect(ConTrollSettingsPage::get_register_page_url() . "?redirect-url=" .
			urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"), 302);
	exit;
}

$skus = controll_api()->merchandise()->catalog();

switch (@$_REQUEST['action']) {
	case 'addtocart':
		$type = $_REQUEST['skutype'];
		$color = $_REQUEST['color'];
		$size = $_REQUEST['size'];
		$amount = @$_REQUEST['amount'] ?: 1;
		try_purchase($type, $color, $size, $amount, $skus);
}

$errorMessage = @$_SESSION['error-message'];
unset($_SESSION['error-message']);
switch ($errorMessage) {
	case 'controll-error-text':
		$errorMessage = "my error text.";
		break;
}

$skumap = [];

// break down SKU codes to fields
foreach ($skus as $sku) {
	list($type, $color, $size) = explode(':', $sku->code,3);
	$skumap[$type]['color'][] = $color;
	$skumap[$type]['size'][] = $size;
	$skumap[$type]['price'] = $sku->price;
}
// remove field duplicates
foreach ($skumap as $type => $skufields)
	foreach ($skufields as $field => $data) {
		if (!is_array($data)) continue;
		$skumap[$type][$field] = array_unique($data, SORT_STRING);
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

<script>
function disableCheckout() {
	jQuery('#shoppingcart-checkout-button').attr('disabled','disabled');
	jQuery('#shoppingcart-checkout-button').text("נא לעדכן");
}
</script>

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
		the_post();
		controll_set_current_object($skus);
		ob_start();
		get_template_part( 'content', 'page' );
		controll_set_current_object(null);
		?>
		
		<?php foreach ($skumap as $skutitle => $skufields):?>
			<form method="post" action="">
				<input type="hidden" name="action" value="addtocart">
				<input type="hidden" name="skutype" value="<?php echo $skutitle?>">
				<h3><?php echo $skutitle?></h3>
				<label>מחיר: <input type="text" readonly="readonly" value="₪<?php echo $skufields['price']?>"></label>
				
				<p>
					<label>צבע: <select name="color">
						<?php if (count($skufields['color']) > 1):?>
						<option value="">לבחור:</option>
						<?php endif?>
						<?php foreach ($skufields['color'] as $color):?>
						<option value="<?php echo $color?>"><?php echo $color?></option>
						<?php endforeach;?>
					</select></label>
				</p>
				
				<p>
					<label>גודל: <select name="size">
						<?php if (count($skufields['size']) > 1):?>
						<option value="">לבחור:</option>
						<?php endif;?>
						<?php foreach ($skufields['size'] as $size):?>
						<option value="<?php echo $size?>"><?php echo $size?></option>
						<?php endforeach;?>
					</select></label>
				</p>
				
				<p>
				<label>כמות: <input type="number" name="amount" min="1" value="1"></label>
				</p>
				
				<p>
				<button type="submit">הוסף לעגלת הקניות</button>
				</p>
			</form>
		<?php endforeach;?>
		
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
