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
	<p><div id="button" style="display:none;"><button class="btn btn-danger" onclick="location.href='admin.php';">Back</button></div></p>
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

	#$archiveFilename = 'mini.tar.gz';
	#$annotationTypesText = "None,Driver,Oncogenic,Tumor Suppressive";
	#$patternText = "cancer,gene\ncancer,mutation";
	
	//print_array($_FILES);
	//print_array($_POST);
	$archiveFilename = $_FILES['data']['tmp_name'];
	$annotationTypesText = $_POST['tags'];
	$patternText = $_POST['tuples'];
	
	//exit(1);
	
	
	
	// Add annotation types
	$values = [];
	foreach (explode(',',rtrim($annotationTypesText)) as $annotationType)
	{
		$escapedType = mysqli_real_escape_string($con,$annotationType);
		$values[] = "('$escapedType')";
	}
	$query = "INSERT INTO annotationtypes(type) VALUES " . implode(',',$values);
	debugOut($query);
	$result = mysqli_query($con,$query);
	
	$patterns = [];
	
	// Add patterns
	$patternLines = explode("\n",$patternText);
	foreach ($patternLines as $patternLine)
	{
		$escapedLine = mysqli_real_escape_string($con,$patternLine);
		
		$query = "INSERT INTO patterns(description) VALUES('$escapedLine');";
		debugOut($query);
		$result = mysqli_query($con,$query);
		$patternid = mysqli_insert_id($con);
		
		$patternTypes = explode(",",rtrim($patternLine));
		$patterns[$patternid] = $patternTypes;
	}
	
	
	$uniqTypes = array_unique(array_flatten($patterns));
	
	$filelist = getDirContents('phar://'.$archiveFilename, ".txt");
	
	foreach ($filelist as $txtFile)
	{
		$a1File = str_replace(".txt",".a1",$txtFile);
		$jsonFile = str_replace(".txt",".json",$txtFile);
		
		$text = file_get_contents($txtFile);
		$a1Data = file($a1File);
		$jsonData = json_decode(file_get_contents($jsonFile),true);
		
		$pmid = $jsonData['pmid'];
		$pmcid = $jsonData['pmcid'];
		
		$entitiesPerType = [];
		foreach ($uniqTypes as $type)
			$entitiesPerType[$type] = [];
			
		$filename = str_replace(".txt","",basename($txtFile));
		$escapedFilename = mysqli_real_escape_string($con,$filename);
		
		// Insert sentence into database
		$escapedText = mysqli_real_escape_string($con,$text);	
		$query = "INSERT INTO sentences(pmid,pmcid,text,filename) VALUES('$pmid','$pmcid','$escapedText','$escapedFilename');";
		debugOut($query);
		$result = mysqli_query($con,$query);
		$sentenceid = mysqli_insert_id($con);
	
		foreach($a1Data as $line)
		{
			$exploded = explode("\t",rtrim($line));
			$exploded2 = explode(" ",$exploded[1]);
			
			$sourceid = $exploded[0];
			$type = $exploded2[0];
			$startPos = $exploded2[1];
			$endPos = $exploded2[2];
			$tokens = $exploded[2];
			$escapedTokens = mysqli_real_escape_string($con,$tokens);
			$escapedSourceID = mysqli_real_escape_string($con,$sourceid);
			
			$query = "INSERT INTO tags(type,startpos,endpos,text,sourceid) VALUES('$type','$startPos','$endPos','$escapedTokens','$escapedSourceID');";
			debugOut($query);
			$result = mysqli_query($con,$query);
			$tagid = mysqli_insert_id($con);
		
			$entitiesPerType[$type][] = array("id"=>$tagid,"tokens"=>$tokens,"sourceid"=>$sourceid);
		}
		
		$query = "SELECT MAX(tagsetid) as maxid FROM tagsets";
		debugOut($query);
		$result = mysqli_query($con,$query);
		$row = mysqli_fetch_array($result);
		$tagsetid = $row['maxid'] + 1;
		
		foreach ($patterns as $patternid => $pattern)
		{
			$tmp = [];
			foreach ($pattern as $t)
				$tmp[] = $entitiesPerType[$t];
			//print_array($tmp);
			#$product = array_cartesian($tmp);
			$product = call_user_func_array('array_cartesian',$tmp);
			foreach ($product as $p)
			{
				$values = [];
				$desc = [];
				$a2out = [];
				foreach ($p as $i => $tagstuff)
				{
					$tagtype = $pattern[$i];
					$tagid = $tagstuff['id'];
					$sourceid = $tagstuff['sourceid'];
					$tokens = $tagstuff['tokens'];
					
					$values[] = "($tagsetid,$i,$tagid)";
					$desc[] = "$tagtype: $tokens";
					$a2out[] = "$tagtype:$sourceid";
				}
				
				$fullDesc = implode(' // ', $desc);
				$escapedDesc = mysqli_real_escape_string($con,$fullDesc);
				
				$fullA2out = implode(' ', $a2out);
				$escapedA2out = mysqli_real_escape_string($con,$fullA2out);
			
				$query = "INSERT INTO tagsets(tagsetid,patternindex,tagid) VALUES" . implode(",",$values);
				debugOut($query);
				$result = mysqli_query($con,$query);
				
				$query = "INSERT INTO tagsetinfos(tagsetid,sentenceid,patternid,description,a2output) VALUES('$tagsetid','$sentenceid','$patternid','$escapedDesc','$escapedA2out')";
				debugOut($query);
				$result = mysqli_query($con,$query);
				
				$tagsetid++;
			}
			
			//print_array($product);
		}
		
		//print_array($entitiesPerType);
		//break;
	}
	//$fd = fopen('phar:///some/file.tar.gz/some/file/in/the/archive', 'r');
	//$contents = file_get_contents('phar:///some/file.tar.gz/some/file/in/the/archive');
	showSuccess("Data import complete");
?>