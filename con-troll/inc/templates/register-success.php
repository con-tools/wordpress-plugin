<?php if ($error):?>
<p>
<strong><?php echo $error?></strong>
</p>
<?php endif;?>
<p>הרישום הסתיים בהצלחה</p>
<p>כדי להמשיך עליך להכנס למערכת.</p>
<p><form method="post" action="">
<input type="hidden" name="action" value="do-login">
<button type="submit">כניסה</button></form></p>
