<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
</head>
<body>

<p class="body">
$IntroText
</p>
<p class="body">
<% control Fields %>
	<% if Value.Checkboxes %>
		<strong>$Label</strong>:<br />
		<ul>
			<% control Value %>
				<li>$Value</li>
			<% end_control %>
		</ul>
	<% else %>
		<strong>$Label</strong>: $Value <br />
	<% end_if %>
<% end_control %>
</p>
<small>This email was received from <a href="$Domain">$Domain</a></small>
</body>
</html>