<?php 
	include 'dbopen.php';
	
	$query = "SELECT at.type as annotationtype, tsi.a2output as a2output, s.filename as filename FROM annotations a, annotationtypes at, tagsetinfos tsi, sentences s WHERE a.annotationtypeid=at.annotationtypeid AND a.tagsetid=tsi.tagsetid AND s.sentenceid=tsi.sentenceid";
	$result = mysqli_query($con,$query);
	
	$basename = 'export';
	$directory = 'output';
	
	$tarArchive = "$basename.tar";
	$gzArchive = "$basename.tar.gz";
	
	if (file_exists($tarArchive))
		unlink($tarArchive);
	if (file_exists($gzArchive))
		unlink($gzArchive);
	
	//$phar->addEmptyDir($directory);
	
	$outData = [];
	
	while ($row = mysqli_fetch_array($result))
	{
		$annotationtype = $row['annotationtype'];
		$a2output = $row['a2output'];
		$filename = $row['filename'];
		
		if (!in_array($filename,$outData))
			$outData[$filename] = [];
		
		if ($annotationtype != 'None')
		{
			$eventID = 1;
			if (in_array($filename,$outData))
				$eventID = count($outData[$filename])+1;
			$line = "E$eventID\t$annotationtype $a2output\n";
			//print "<p>$line</p>";
			//file_put_contents("phar://./$basename.phar/$directory/$filename.a2", $line);
			//$phar->addFromString("$directory/$filename.a2",$line);
			$outData[$filename][] = $line;
		}
	}
	
	
	$phar = new PharData($tarArchive, 0, null, Phar::TAR);
	foreach ($outData as $filename => $lines)
	{
		$fileData = implode("\n",$lines)."\n";
		$phar->addFromString("$directory/$filename.a2",$fileData);
	}
	
	$phar->compress(Phar::GZ); # Creates archive.tar.gz
	
	header("Location: $gzArchive");
?>