<?php if ($error):?>
<p>
<strong><?php echo $error?></strong>
</p>
<?php endif;?>
<form method="POST" action="?">
<p>כדי להרשם למערכת עם שם משתמש וסיסמה, יש למלא את הפרטים הבאים:</p>
<input type="hidden" name="action" value="completeregister">
<p><label>שם מלא: <input type="text" name="name"></label></p>
<p><label>כתובת דואל: <input type="text" name="email"></label></p>
<p><label>סיסמה: <input type="password" name="password-register"></label></p>
<p><label>אישור סיסמה: <input type="password" name="password-confirm"></label></p>
<p><button type="submit">שלח</button></p>
</form>
