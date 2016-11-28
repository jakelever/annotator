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
	<p id="text">Importing Data</p>
	<p><img id="feedback" src="waiting.gif" /></p>
	<p><div id="button" style="display:none;"><button class="btn btn-danger" onclick="location.href='compare_next.php';">Start</button></div></p>
</div>

<?php
	flush();
	
	function showError($message)
	{
		$escaped = addslashes(htmlspecialchars($message));
		$js = "<script>";
		$js .= "document.getElementById('feedback').src='error.png'; ";
		$js .= "document.getElementById('text').innerHTML='$escaped';";
		$js .= "document.getElementById('button').style='display:block;'";
		$js .= "</script>";
		echo $js;
	}
	
	function showSuccess($message)
	{
		$escaped = addslashes(htmlspecialchars($message));
		$js = "<script>";
		$js .= "setTimeout(function() { document.getElementById('feedback').src='success.jpg'; ";
		$js .= "document.getElementById('text').innerHTML='$escaped';";
		$js .= "document.getElementById('button').style='display:block;' }, 2000)";
		$js .= "</script>";
		echo $js;
	}
	
	function redirect($url)
	{
		$redirectJS = "<script>setTimeout(function() {  window.location.href = '$url'; }, 2000); </script>";
		echo $redirectJS;
	}
	
	function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}
	
	// From: http://stackoverflow.com/questions/24783862/list-all-the-files-and-folders-in-a-directory-with-php-recursive-function
	function getDirContents($dir, $filter, &$results = array()){
		$files = scandir($dir);
		
		if ($files == FALSE)
			throw new Exception("Failed to scandir: $dir");

		foreach($files as $key => $value){
			//$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
			$path = $dir.'/'.$value;
			//print "$path<br />";
			if(!is_dir($path)) {
				if (endsWith($path,$filter))
					$results[] = $path;
			} else if($path != "." && $path != "..") {
				getDirContents($path, $filter, $results);
				//$results[] = $path;
			}
		}

		return $results;
	}
	
	function debugOut($text)
	{
		//echo "<p>$text</p>\n";
	}
	
	function array_flatten($array) { 
	  if (!is_array($array)) { 
		return FALSE; 
	  } 
	  $result = array(); 
	  foreach ($array as $key => $value) { 
		if (is_array($value)) { 
		  $result = array_merge($result, array_flatten($value)); 
		} 
		else { 
		  $result[$key] = $value; 
		} 
	  } 
	  return $result; 
	} 
	
	function array_cartesian() {
		$_ = func_get_args();
		if(count($_) == 0)
			return array(array());
		$a = array_shift($_);
		$c = call_user_func_array(__FUNCTION__, $_);
		$r = array();
		foreach($a as $v)
			foreach($c as $p)
				$r[] = array_merge(array($v), $p);
		return $r;
	}
	
	function print_array($a)
	{		
		echo "<pre>";
		print_R($a);
		echo "</pre>";
	}
	
	
	include 'dbopen.php';

	$tmpUnannotatedFilename = $_FILES['unannotated']['tmp_name'];
	$tmpAnnotated1Filename = $_FILES['annotated1']['tmp_name'];
	$tmpAnnotated2Filename = $_FILES['annotated2']['tmp_name'];
	$name1 = $_POST['name1'];
	$name2 = $_POST['name2'];
	
	$unannotatedFilename = 'compareData/unannotated.tar.gz';
	$annotated1Filename = 'compareData/annotated1.tar.gz';
	$annotated2Filename = 'compareData/annotated2.tar.gz';
	$mergedFilename = 'compareData/merged.tar';
	$namesFilename = 'compareData/names.json';
	$indexFilename = 'compareData/index.json';
	
	
	if (!isset($_POST['name1']) || $name1 == '')
		$name1 = "Annotation 1";
	if (!isset($_POST['name2']) || $name2 == '')
		$name2 = "Annotation 2";
	
	$names = array($name1,$name2);
	
	function createIndex($dir, $filter)
	{
		$filelist = getDirContents($dir,$filter);
		$index = [];
		foreach($filelist as $f)
		{
			$key = str_replace($filter,"",basename($f));
			$index[$key] = $f;
		}
		return $index;
	}
	
	try
	{
		$moveResult = move_uploaded_file($tmpUnannotatedFilename,$unannotatedFilename);
		if (!$moveResult)
			throw new Exception("Unable to move temporary archive");
		
		$moveResult = move_uploaded_file($tmpAnnotated1Filename,$annotated1Filename);
		if (!$moveResult)
			throw new Exception("Unable to move temporary archive");
		
		$moveResult = move_uploaded_file($tmpAnnotated2Filename,$annotated2Filename);
		if (!$moveResult)
			throw new Exception("Unable to move temporary archive");
		
		file_put_contents($namesFilename,json_encode($names, JSON_PRETTY_PRINT));
		
		if (file_exists($mergedFilename))
		{
			$delResult = unlink($mergedFilename);
			if (!$delResult)
				throw new Exception("Unable to delete old archive");
		}
		
		$indexData = [];
		$indexData['unannotated'] = createIndex('phar://'.$unannotatedFilename, ".json");
		$indexData['annotated1'] = createIndex('phar://'.$annotated1Filename, ".a2");
		$indexData['annotated2'] = createIndex('phar://'.$annotated2Filename, ".a2");
		$indexData['combined'] = array_unique(array_merge(array_keys($indexData['annotated1']),array_keys($indexData['annotated2'])));
		sort($indexData['combined']);
		
		file_put_contents($indexFilename,json_encode($indexData, JSON_PRETTY_PRINT));
		
		showSuccess("Data import complete");
	}
	catch (Exception $e)
	{
		showError("ERROR: " . $e->getMessage());
	}
?>