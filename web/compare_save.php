<?php 
	function printArray($array)
	{
		print "<pre>";
		print_R($array);
		print "</pre>";
	}
	
	#printArray($_POST);
	
	$relCount = count($_POST['rel']);
	$annotationCount = count($_POST['annotation']);
	$base = $_POST['filename_base'];
	
	$mergedFilename = 'compareData/merged.tar';
	
	$phar = new PharData($mergedFilename, 0, null, Phar::TAR);
	
	if ($relCount != $annotationCount)
	{
		print "<b>Unexpected error: relCount != annotationCount</b>";
		exit(0);
	}
	
	$lines = [];
	$count = 1;
	for ($i=0; $i<$relCount; $i++)
	{
		$rel = $_POST['rel'][$i];
		$annotation = $_POST['annotation'][$i];
		
		if ($annotation != 'None')
		{
			$line = "E$count\t$annotation $rel\n";
			$lines[] = $line;
			$count++;
		}
	}
	$fileData = implode('',$lines);
	
	$phar->addFromString("output/$base.a2",$fileData);
	
	header("Location: compare_next.php");
?>
