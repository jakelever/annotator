<?php 
	
function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (is_dir($dir."/".$object))
           rrmdir($dir."/".$object);
         else
           unlink($dir."/".$object); 
       } 
     }
     rmdir($dir); 
   } 
}
	include 'dbopen.php';
	
	$step = 50;
	
	$basename = 'export';
	$directory = 'tmp/output';
	
	$tarArchive = "$basename.tar";
	$gzArchive = "$basename.tar.gz";
	
	$filter = isset($_GET['filter']);
	
	if (!isset($_GET['start']))
	{
	
		$message = "Starting export...";
	
		$query = "SELECT MIN(sentenceid) FROM sentences";
		$result = mysqli_query($con,$query);
		$row = mysqli_fetch_row($result);
		$start = $row[0];
		
		$query = "SELECT sentenceid FROM sentences WHERE sentenceid > $start ORDER BY sentenceid LIMIT $step";
		$result = mysqli_query($con,$query);
		$end = $start;
		while ($row = mysqli_fetch_row($result))
			$end = $row[0];
		
		#$end = $row[0];
		#$query = "SELECT at.type as annotationtype, tsi.a2output as a2output, s.filename as filename, s.sentenceid as sentenceid FROM annotations a, annotationtypes at, tagsetinfos tsi, sentences s WHERE a.annotationtypeid=at.annotationtypeid AND a.tagsetid=tsi.tagsetid AND s.sentenceid=tsi.sentenceid ORDER BY s.sentenceid LIMIT $step";
		
		#$end = $start + $step;
		$query = "SELECT at.type as annotationtype, tsi.a2output as a2output, s.filename as filename, s.sentenceid as sentenceid FROM annotations a, annotationtypes at, tagsetinfos tsi, sentences s WHERE a.annotationtypeid=at.annotationtypeid AND a.tagsetid=tsi.tagsetid AND s.sentenceid=tsi.sentenceid AND s.sentenceid >= $start AND s.sentenceid < $end ORDER BY s.sentenceid";
		
		if (is_dir('tmp'))
			rrmdir('tmp');
		mkdir($directory,0777,true);
	}
	else 
	{
		$start = intval($_GET['start']);
		$message = "Exported $start files...";
		
		$query = "SELECT sentenceid FROM sentences WHERE sentenceid > $start ORDER BY sentenceid LIMIT $step";
		$result = mysqli_query($con,$query);
		$end = $start;
		while ($row = mysqli_fetch_row($result))
			$end = $row[0];
		
		#$query = "SELECT at.type as annotationtype, tsi.a2output as a2output, s.filename as filename, s.sentenceid as sentenceid FROM annotations a, annotationtypes at, tagsetinfos tsi, sentences s WHERE a.annotationtypeid=at.annotationtypeid AND a.tagsetid=tsi.tagsetid AND s.sentenceid=tsi.sentenceid AND s.sentenceid > $start ORDER BY s.sentenceid LIMIT $step";
		#$end = $start + $step;
		$query = "SELECT at.type as annotationtype, tsi.a2output as a2output, s.filename as filename, s.sentenceid as sentenceid FROM annotations a, annotationtypes at, tagsetinfos tsi, sentences s WHERE a.annotationtypeid=at.annotationtypeid AND a.tagsetid=tsi.tagsetid AND s.sentenceid=tsi.sentenceid AND s.sentenceid >= $start AND s.sentenceid < $end ORDER BY s.sentenceid";
		
	}
	
	#print "<p>$query</p>";
	$result = mysqli_query($con,$query);
	#exit(1);
	
	
	$rowCount = mysqli_num_rows($result);
	if ($rowCount==0)
	{
		if (file_exists($tarArchive))
			unlink($tarArchive);
		if (file_exists($gzArchive))
			unlink($gzArchive);
		
		$phar = new PharData($tarArchive, 0, null, Phar::TAR);
		$phar->buildFromDirectory('tmp');
		// We're done
		
		$phar->compress(Phar::GZ); # Creates archive.tar.gz
		
		if (is_dir('tmp'))
			rrmdir('tmp');
		
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
	$nextstart = $sentenceid+1;
	
	foreach ($outData as $filename => $lines)
	{
		$fileData = implode("",$lines);
		#$phar->addFromString("$directory/$filename.a2",$fileData);
		file_put_contents("$directory/$filename.a2",$fileData);
	}
	
	#header("Location: exportToST.php?start=$maxsentenceid");
	
	if ($filter)
		$redirectURL = "exportToST.php?filter&start=$nextatart";
	else
		$redirectURL = "exportToST.php?start=$nextstart";
		
	
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