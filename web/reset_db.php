<?php include 'dbopen.php'; ?>
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
	<p id="text">Resetting database</p>
	<p><img id="feedback" src="waiting.gif" /></p>
	<p><div id="button" style="display:none;"><button class="btn btn-danger" onclick="location.href='admin.php';">Back</button></div></p>
</div>

<?php

	function showError($errorMessage)
	{
		$escaped = addslashes(htmlspecialchars($errorMessage));
		$errorJS = "<script>";
		$errorJS .= "document.getElementById('feedback').src='error.png'; ";
		$errorJS .= "document.getElementById('text').innerHTML='$escaped';";
		$errorJS .= "document.getElementById('button').style='display:block;'";
		$errorJS .= "</script>";
		echo $errorJS;
	}
	
	function redirect($url)
	{
		$redirectJS = "<script>setTimeout(function() {  window.location.href = '$url'; }, 2000); </script>";
		echo $redirectJS;
	}
	
	
	function run_sql_file($con,$location){
		//load file
		$commands = file_get_contents($location);

		//delete comments
		$lines = explode("\n",$commands);
		$commands = '';
		foreach($lines as $line){
			$line = trim($line);
			if( $line && !startsWith($line,'--') ){
				$commands .= $line . "\n";
			}
		}

		//convert to array
		$commands = explode(";", $commands);

		//run commands
		$total = $success = 0;
		foreach($commands as $command){
			if(trim($command)){
				$success += (mysqli_query($con,$command)==false ? 0 : 1);
				$total += 1;
				if ($success == 0)
				{
					$error = mysqli_error($con);
					echo "<p>$error</p>";
				}
			}
		}

		//return number of successful queries and total number of queries found
		return array(
			"success" => $success,
			"total" => $total
		);
	}

	// Here's a startsWith function
	function startsWith($haystack, $needle){
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	$initiate_script = 'restricted/initiate.sql';

	$result = run_sql_file($con,$initiate_script);
	
	if ($result["success"] == $result["total"])
		redirect("admin.php");
	else
		showError("Failed to create database tables");
?>


</body>
</html>
