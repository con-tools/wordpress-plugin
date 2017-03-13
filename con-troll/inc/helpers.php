<?php

function helper_controll_datetime_diff(DateTime $a, DateTime $b) {
	if ($a == $b)
		return 0;
		$diff = $a->diff($b);
		if ($diff->invert)
			return 1;
			return -1;
}

function helper_controll_fake_redirect($url) {
	ob_start();
	?>
	<script>
		window.location.href = "<?php echo $url ?>";
	</script>
	<?php
	return ob_get_clean();
}

function helper_timeslot_fields($timeslot){
	if (!($timeslot instanceof stdClass))
		return $timeslot;
	// provide some custom fields to help display
	$timeslot->start = new DateTime($timeslot->start);
	$timeslot->end = new DateTime($timeslot->end);
	if (!$timeslot->available_tickets)
		$timeslot->available_tickets = 'אין יותר מקומות';
	return $timeslot;
}

/**
 * Try to detect if we can safely send an HTTP 302 redirections, or
 * fall back to HTML/JS redirect.
 *
 * This function never returns.
 * @param stirng $url URL to redirect to
 * @param number $code Type of HTTP redirect to do, if we can do HTTP redirect (default 302)
 */
function controll_redirect_helper($url, $code = 302) {
	if (headers_sent()) {
		?>
		<p>על מנת להמשיך - <a href="<?php echo $url ?>">יש ללחוץ כאן</a></p>
		<script>
		window.location.href = '<?php echo $url ?>';
		</script>
		<?php
	} else {
		wp_redirect($url, $code);
	}
	exit();
}

function controll_render_template($file, $args = []) {
	extract($args);
	include __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$file;
}
