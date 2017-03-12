<?php

function controll_get_default_register_with_passes() {
	ob_start();
	?>
	<p>Please choose a daily pass</p>
	<table>
	<thead>
		<tr><th>Name</th><th>Pass</th></tr>
	</thead>
	<tbody>
	[controll-list-repeat path="passes"]
		<tr>
			<td>{{name}}</td><td>{{pass.title}}</td>
			<td>
			<span style="display:{{available|exist(inline)}}{{available|unless(none)}}">
				<button class="small" type="submit" name="pass" value="{{id}}">Register</button>
			</span>
			<span style="display:{{available|exist(none)}}{{available|unless(inline)}}">
				<button class="small" disabled="disabled">Not available</button>
			</span>
			</td>
		</tr>
	[/controll-list-repeat]
	</tbody>
	<tbody>
		<tr>
			<td><input type="text" name="pass-name" value="" placeholder="Owner name" pattern=".+" error-text="Owner name is required"></td>
			<td><select name="pass-type">
				[controll-list-repeat source="passes"]
					<option value="{{id}}">{{title}}: ¤{{price}}</option>
				[/controll-list-repeat]
				</select></td>
			<td><button class="controll-popup-button" type="submit" name="pass" value="new">
				<i class="fa fa-shopping-cart"></i> Acquire a new pass
				</button></td>
		</tr>
	</tbody>
	</table>
	<?php
	return ob_get_clean();
}
