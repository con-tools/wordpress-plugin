<?php

wp_enqueue_style( 'controll-fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', [], '4.6.3' );

/**
 * Template Name: עמוד אישי
 * Show a single event, specified by the query parameter "id"
 * @package ConTroll
 */
controll_api()->checkAuthentication();
//check if the user is logged in
$email = controll_api()->getUserEmail();
if (!$email) {
	wp_redirect(ConTrollSettingsPage::get_register_page_url() . "?redirect-url=" .
			urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"), 302);
	exit;
}

switch (@$_REQUEST['controll-action']) {
	case 'update-ticket-amount':
		$amount = (int)$_REQUEST['amount'];
		if (@$_REQUEST['delete'])
			$amount = 0;
		$res = controll_api()->tickets()->update((int)$_REQUEST['id'], $amount);
		if (!$res->status) {
			$_SESSION['error-message'] = $res->error;
		}
		wp_redirect(get_permalink(), 302);
		exit;
	case 'cancel-pass':
		$res = controll_api()->passes()->delete((int)$_REQUEST['id']);
		wp_redirect(get_permalink(), 302);
		exit;
	case 'update-purchase-amount':
		$amount = (int)$_REQUEST['amount'];
		if (@$_REQUEST['delete'])
			$amount = 0;
		$res = controll_api()->purchases()->update($_REQUEST['sku'], $amount);
		if (!$res->status) {
			$_SESSION['error-message'] = $res->error;
		}
		wp_redirect(get_permalink(), 302);
		exit;
	case 'transaction-done':
		wp_redirect(ConTrollSettingsPage::get_my_page_url(), 302); // if its a different page, the admin wants us there, so they can have fancy DOM
		exit;
	case 'transaction-failed':
		$_SESSION['error-message'] = 'התשלום נכשל: ' . @$_REQUEST['reason'];
		wp_redirect(get_permalink(), 302);
		exit;
}

$errorMessage = @$_SESSION['error-message'];
unset($_SESSION['error-message']);
switch ($errorMessage) {
	case "No more tickets left":
		$errorMessage = "לא נשארו מספיק כרטיסים זמינים בארוע";
		break;
	case 'התשלום נכשל: user cancelled':
		$errorMessage = "התשלום נכשל. אם לא ביטלת את התשלום בעצמך, כדאי לנסות שנית.";
		break;
}

$usesPasses = controll_api()->usesPasses();
if ($usesPasses) {
	$passes = controll_api()->passes()->user_catalog();
	$pending_passes = array_filter($passes, function($pass) {
		return $pass->status == 'reserved' or $pass->status == 'processing';
	});
	if ($pending_passes)
		logger()->Debug("User $email has some pending passes: " . count($pending_passes));
	$authorized_passes = array_filter($passes, function($pass){
		return $pass->status == 'authorized';
	});
}

$tickets = controll_api()->tickets()->catalog();
if (!empty($tickets)) {
	$pending_tickets = array_filter($tickets, function($ticket){
		return $ticket->status == 'reserved' or $ticket->status == 'processing';
	});
	if ($pending_tickets)
		logger()->Debug("User $email has some pending tickets: " . count($pending_tickets));
	
	$authorized_tickets = array_filter($tickets, function($ticket){
		return $ticket->status == 'authorized';
	});
} else {
	$authorized_tickets = [];
}

$purchases = controll_api()->purchases()->catalog();
if (!empty($purchases)) {
	$pending_purchases = array_filter($purchases, function($purchase){
		return $purchase->status == 'reserved' or $purchase->status == 'processing';
	});
	
	$authorized_purchases = array_filter($purchases, function($purchase){
		return $purchase->status == 'authorized';
	});
}

$coupons = controll_api()->coupons()->catalog();
$showcart = $pending_purchases || $pending_tickets || $pending_passes;

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
		
		<div class="hidden visible-print-block" style="text-align: center">
		<h1>דף אישור הרשמה</h1>
		</div>
		
		<?php if (!empty($coupons)):?>
		<div class="shopping-cart">
		<h3>קופונים זמינים</h3>
		<table>
		<thead>
			<tr>
				<th>סוג</th><th>סכום</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($coupons as $coupon):?>
			<tr>
				<td class="numeric"><?php echo $coupon->type->title?></td>
				<td class="numeric"><?php echo $coupon->value?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
		</table>
		<?php endif;?>
		
		<?php if ($showcart):?>
			<?php $total = 0 ?>
			<div class="shopping-cart">
			<h3>עגלת קניות</h3>
			<table>
			
			<?php if ($usesPasses): ?>
			<thead>
				<tr>
					<th>כרטיס</th><th>שם</th><th>מחיר</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($pending_passes as $pass):?>
				<?php $total += $pass->price ?>
				<tr>
					<td><?php echo $pass->pass->title ?></td>
					<td><?php echo $pass->name ?></td>
					<td class="numeric"><?php echo $pass->price ?></td>
					<td class="numeric">
						<form method="post" action="?">
							<input type="hidden" name="controll-action" value="cancel-pass">
							<input type="hidden" name="id" value="<?php echo $pass->id ?>">
							<button class="fieldupd small" type="submit" name="delete" value="1" title="מחק כרטיס"><span class="fa fa-trash-o"></span></button>
						</form>
					</td>
				</tr>
				<?php endforeach ?>
			</tbody>
			<?php elseif ($pending_tickets and !empty($pending_tickets)):?>
			<thead>
				<tr>
					<th>ארוע</th><th>כרטיסים</th><th>מחיר</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($pending_tickets as $ticket):?>
				<?php $ticket->timeslot = helper_timeslot_fields($ticket->timeslot); ?>
				<?php $total += $ticket->price ?>
				<tr>
					<td><a href="<?php echo ConTrollSettingsPage::get_event_page_url()?>?id=<?php echo $ticket->timeslot->id?>">
						<?php echo $ticket->timeslot->event->title?>
					</a></td>
					<td class="numeric">
						<form method="post" action="?">
							<input type="hidden" name="controll-action" value="update-ticket-amount">
							<input type="hidden" name="id" value="<?php echo $ticket->id ?>">
							<input type="number" onchange="return disableCheckout()" onmouseup="return disableCheckout()"
								onkeyup="return disableCheckout()" name="amount" min="0"
								max="<?php echo $ticket->amount + $ticket->timeslot->available_tickets ?>"
								value="<?php echo $ticket->amount ?>">
							<button class="fieldupd small" type="submit" name="update" value="1" title="עדכן מספר כרטיסים"><span class="fa fa-check-square-o"></span></button>
							<button class="fieldupd small" type="submit" name="delete" value="1" title="מחק כרטיס"><span class="fa fa-trash-o"></span></button>
						</form>
					</td>
					<td class="numeric"><?php echo $ticket->price ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
			<?php endif;?>
			
			<?php if ($pending_purchases and !empty($pending_purchases)):?>
			<thead>
				<tr>
					<th colspan="2">מוצר</th><th>כמות</th><th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($pending_purchases as $purchase):?>
				<?php $total += $purchase->price ?>
				<tr>
					<td colspan="2"><a href="/shirts/buy"><?php echo $purchase->sku->title?></a></td>
					<td class="numeric">
						<form method="post" action="?">
							<input type="hidden" name="controll-action" value="update-purchase-amount">
							<input type="hidden" name="updatetype" value="purchase">
							<input type="hidden" name="sku" value="<?php echo $purchase->sku->code ?>">
							<input type="number" onchange="return disableCheckout()" onmouseup="return disableCheckout()"
								onkeyup="return disableCheckout()" name="amount" min="0" value="<?php echo $purchase->amount ?>">
							<button class="fieldupd small" type="submit" name="update" value="1" title="עדכן מספר מוצרים"><span class="fa fa-check-square-o"></span></button>
							<button class="fieldupd small" type="submit" name="delete" value="1" title="מחק מוצר"><span class="fa fa-trash-o"></span></button>
						</form>
					</td>
					<td class="numeric"><?php echo $purchase->price ?></td>
				</tr>
				<?php endforeach;?>
			</tbody>
			<?php endif;?>
			
			<tbody>
				<tr>
					<td colspan="3" style="text-align:left;">סה"כ:</td>
					<td class="numeric"><?php echo $total ?></td>
				</tr>
				<tr>
					<td colspan="3"></td>
					<td class="numeric">
						<form method="POST" action="http://api.con-troll.org/checkout">
						<input type="hidden" name="token" value="<?php echo controll_api()->getSessionToken()?>">
						<input type="hidden" name="convention" value="<?php echo controll_api()->getKey()?>">
						<input type="hidden" name="ok" value="<?php echo get_permalink();?>?action=transaction-done">
						<input type="hidden" name="fail" value="<?php echo get_permalink();?>?action=transaction-failed">
						<button type="submit" id="shoppingcart-checkout-button">לתשלום</button>
						</form>
					</td>
				</tr>
			</tbody>
			</table>
			</div>
			
			<div class="shopping-cart">
				<form method="POST" action="?">
				<input type="hidden" name="controll-action" value="activate-coupon">
				<label>קוד קופון הנחה:
				<input type="text" name="code" value="" class="short">
				</label>
				<button type="submit" class="small">הפעלה</button>
				</form>
			</div>
		<?php endif ?>
		
		<?php
		the_post();
		controll_set_current_object($tickets);
		ob_start();
		get_template_part( 'content', 'page' );
		controll_set_current_object(null);
		?>

		<div class="shopping-cart">
		<table id="my-tickets" class="purchased-items">
		<?php if ($$authorized_passes and !empty($authorized_passes)): ?>
			<thead>
				<tr>
					<th>כרטיס</th><th>שם</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($authorized_passes as $pass):?>
				<tr>
					<td><?php echo $pass->pass->title ?></td>
					<td><?php echo $pass->name ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		<?php endif ?>
		<?php if ($authorized_tickets and !empty($authorized_tickets)):?>
			<?php $allowprint=true; ?>
			<thead>
				<tr><th colspan="3"><h3>הכרטיסים שלי</h3></th></tr>
				<tr>
					<th>ארוע</th><th>כרטיסים</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($authorized_tickets as $ticket):?>
				<?php $ticket->timeslot = helper_timeslot_fields($ticket->timeslot); ?>
				<tr>
					<td><a href="<?php echo ConTrollSettingsPage::get_event_page_url()?>?id=<?php echo $ticket->timeslot->id?>">
						<?php echo $ticket->timeslot->event->title?>
					</a></td>
					<td class="numeric"><?php echo $ticket->amount ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		<?php endif;?>
		<?php if ($authorized_purchases and !empty($authorized_purchases)):?>
			<?php $allowprint=true; ?>
			<thead>
				<tr><th colspan="3"><h3>המוצרים שלי</h3></th></tr>
				<tr>
					<th colspan="2">מוצר</th><th>כמות</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($authorized_purchases as $purchase):?>
				<tr>
					<td colspan="2"><a href="/shirts/buy"><?php echo $purchase->sku->title?></a></td>
					<td class="numeric"><?php echo $purchase->amount ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		<?php endif;?>
		</table>
		</div>
		<?php $hostings = array_map('helper_timeslot_fields', controll_api()->timeslots()->myHosting() ?: []); ?>
		<?php usort($hostings, function($a, $b){
			$diff = helper_controll_datetime_diff($a->start, $b->start);
			if ($diff == 0)
				return helper_controll_datetime_diff($a->end, $b->end);
			return $diff;
		}); ?>
		<?php if (count($hostings)):?>
		<?php $allowprint=true; ?>
		<div class="shopping-cart">
		<table class="purchased-items">
		<thead>
			<tr><th colspan="3"><h3>המשחקים שאני מנחה</h3></th></tr>
			<tr>
				<th>ארוע</th><th>סבב</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($hostings as $timeslot):?>
			<tr>
				<td><a href="<?php echo ConTrollSettingsPage::get_event_page_url()?>?id=<?php echo $timeslot->id?>">
					<?php echo $timeslot->event->title?>
				</a></td>
				<td><?php echo $timeslot->round ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
		</table>
		</div>
		<?php endif; ?>
		
		<?php if ($allowprint):?>
		<a href="#" class="button hidden-print" onclick="window.print();">הדפס דף אישור</a>
		<?php endif;?>
		
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
