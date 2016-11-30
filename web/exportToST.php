<?php 
	include 'dbopen.php';
	
	$step = 100;
	
	$basename = 'export';
	$directory = 'output';
	
	$tarArchive = "$basename.tar";
	$gzArchive = "$basename.tar.gz";
	
	$filter = isset($_GET['filter']);
	
	if (!isset($_GET['start']))
	{
	
		$message = "Starting export...";
	
		$query = "SELECT at.type as annotationtype, tsi.a2output as a2output, s.filename as filename, s.sentenceid as sentenceid FROM annotations a, annotationtypes at, tagsetinfos tsi, sentences s WHERE a.annotationtypeid=at.annotationtypeid AND a.tagsetid=tsi.tagsetid AND s.sentenceid=tsi.sentenceid ORDER BY s.sentenceid LIMIT $step";
		
		if (file_exists($tarArchive))
			unlink($tarArchive);
	}
	else 
	{
		$start = intval($_GET['start']);
		$message = "Exported $start files...";
		
		$query = "SELECT at.type as annotationtype, tsi.a2output as a2output, s.filename as filename, s.sentenceid as sentenceid FROM annotations a, annotationtypes at, tagsetinfos tsi, sentences s WHERE a.annotationtypeid=at.annotationtypeid AND a.tagsetid=tsi.tagsetid AND s.sentenceid=tsi.sentenceid AND s.sentenceid > $start ORDER BY s.sentenceid LIMIT $step";
		
	}
	//print "<p>$query</p>";
	$result = mysqli_query($con,$query);
	
	$phar = new PharData($tarArchive, 0, null, Phar::TAR);
	
	$rowCount = mysqli_num_rows($result);
	if ($rowCount==0)
	{
		// We're done
		
		if (file_exists($gzArchive))
			unlink($gzArchive);
		$phar->compress(Phar::GZ); # Creates archive.tar.gz
		
		header("Location: $gzArchive");
		exit(0);
	}
	
	
	//$phar->addEmptyDir($directory);
	
	$outData = [];
	
	while ($row = mysqli_fetch_array($result))
	{
		$annotationtype = $row['annotationtype'];
		$a2output = $row['a2output'];
		$filename = $row['filename'];
		$sentenceid = $row['sentenceid'];
		
		if (!array_key_exists($filename,$outData))
			$outData[$filename] = [];
		
		if (!$filter || $annotationtype != 'None')
		{
			$eventID = 1;
			if (array_key_exists($filename,$outData))
				$eventID = count($outData[$filename])+1;
			$nospacetype = str_replace(" ","_",$annotationtype);
			$line = "E$eventID\t$nospacetype $a2output\n";
			//print "<p>$line</p>";
			//file_put_contents("phar://./$basename.phar/$directory/$filename.a2", $line);
			//$phar->addFromString("$directory/$filename.a2",$line);
			$outData[$filename][] = $line;
		}
	}
	$maxsentenceid = $sentenceid;
	
	foreach ($outData as $filename => $lines)
	{
		$fileData = implode("",$lines);
		$phar->addFromString("$directory/$filename.a2",$fileData);
	}
	
	#header("Location: exportToST.php?start=$maxsentenceid");
	
	if ($filter)
		$redirectURL = "exportToST.php?filter&start=$maxsentenceid";
	else
		$redirectURL = "exportToST.php?start=$maxsentenceid";
		
	
	function redirect($url)
	{
		$redirectJS = "<script>setTimeout(function() {  window.location.href = '$url'; }, 1000); </script>";
		echo $redirectJS;
	}
	
	//echo("Location: exportToST.php?start=$maxsentenceid");
?>
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

<?php
	redirect($redirectURL);
?>
	
</head>

<body>

<div class="container">
	<p id="text"><?php echo $message ?></p>
	<p><img id="feedback" src="waiting.gif" /></p>
	<p><div id="button" style="display:none;"><button class="btn btn-danger" onclick="location.href='admin.php';">Back</button></div></p>
</div>


</body>
</html>