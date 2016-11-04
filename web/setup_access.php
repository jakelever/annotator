<html>
<head>
	<title>Annotate - Setup</title>
    <link href="css/bootstrap.css" rel="stylesheet">
	<style media="screen" type="text/css">

	.container {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translateX(-50%) translateY(-50%);
		text-align:center;
	}

	</style>

</head>

<body>

<div class="container">
	<p id="text">Setting Password</p>
	<p><img id="feedback" src="waiting.gif" /></p>
	<p><div id="button" style="display:none;"><button class="btn btn-danger" onclick="location.href='admin.php';">Back</button></div></p>
</div>

<?php

	function redirect($url)
	{
		$redirectJS = "<script>setTimeout(function() {  window.location.href = '$url'; }, 2000); </script>";
		echo $redirectJS;
	}
	
	$username = $_POST['username'];
	$clearTextPassword = $_POST['password'];

	$passwordHash = base64_encode(sha1($clearTextPassword, true));
	$output[] = $username . ':{SHA}' . $passwordHash;

	file_put_contents('.htpasswd', implode(PHP_EOL,$output));

	$here = getcwd();

	$htaccess = [];
	$htaccess[] = "AuthType Basic";
	$htaccess[] = "AuthName \"Insight\"";
	$htaccess[] = "AuthUserFile $here/.htpasswd";
	$htaccess[] = "Require valid-user";
	file_put_contents('.htaccess',implode(PHP_EOL,$htaccess));

	redirect('admin.php');

?>