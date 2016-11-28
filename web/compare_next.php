<?php
	function showAlert($text,$isBad)
	{
		if ($isBad)
			$code = '<div class="alert alert-danger alert-dismissable">';
		else
			$code = '<div class="alert alert-warning alert-dismissable">';
		$code .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		$code .= '<strong>'.$text.'</strong>';
		$code .= '</div>';
		
		print $code;
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
	
	
	function printArray($array)
	{
		print "<pre>";
		print_R($array);
		print "</pre>";
	}
	
	
	function loadA2Data($entities,$fileData)
	{
		$rels = [];
		#printArray($fileData);
		foreach($fileData as $line)
		{
			$line = rtrim($line);
			if (strlen($line) == 0)
				continue;
			
			$explode1 = explode("\t", $line);
			$id = $explode1[0];
			$explode2 = explode(" ", $explode1[1]);
			$reltype = $explode2[0];
			$args = [];
			
			$argTxtsWithIDs = [];
			$argTxtsWithEntities = [];
			
			for($i=1; $i<count($explode2); $i++)
			{
				$argInfo = $explode2[$i];
				$explode3 = explode(":",$argInfo);
				$argName = $explode3[0];
				$argValue = $explode3[1];
				
				$entityName = $entities[$argValue]['entitytxt'];
				
				$args[] = array('name'=>$argName,'value'=>$argValue);
				
				$argTxtsWithIDs[] = $argInfo;
				$argTxtsWithEntities[] = "$argName:$entityName";
			}
			
			#sort($args);
			#$rel = array('type'=>$reltype, 'args'=>$args);
			sort($argTxtsWithIDs);
			$relTxt = implode(' ',$argTxtsWithIDs);
			#$rels[] = $rel;
			$rels[$relTxt][] = $reltype;
		}
		
		foreach (array_keys($rels) as $args)
			sort($rels[$args]);
		
		return $rels;
	}
	
	function convertIDsToEntities($entities,$relations)
	{
		$newRelations = [];
		foreach ($relations as $relation)
		{
			$argTxtsWithEntities = [];
			$explode1 = explode(" ", $relation);
			foreach ($explode1 as $argInfo)
			{
				$explode2 = explode(":",$argInfo);
				$argName = $explode2[0];
				$argValue = $explode2[1];
				
				$entityName = $entities[$argValue]['entitytxt'];
			
				$argTxtsWithEntities[] = "$argName:$entityName";
			}
			$newRelation = implode(' ', $argTxtsWithEntities);
			$newRelations[$relation] = $newRelation;
		}
		return($newRelations);
	}
	
	
	function basenameNoExtensionArray($array)
	{
		$newArray = [];
		foreach($array as $item)
			$newArray[] = str_replace(".a2","",basename($item));
		return $newArray;
	}
	
	/*function findArrayElement($array,$needle)
	{		
		$ret = NULL;
		foreach($array as $item)
		{
			if (strpos($item,$needle)!=false)
			{
				$ret = $item;
				break;
			}
		}
		return $ret;
	}*/
	
	$unannotatedFilename = 'compareData/unannotated.tar.gz';
	$annotated1Filename = 'compareData/annotated1.tar.gz';
	$annotated2Filename = 'compareData/annotated2.tar.gz';
	
	$mergedFilename = 'compareData/merged.tar';
	$namesFilename = 'compareData/names.json';
	$indexFilename = 'compareData/index.json';
	
	$namesData = json_decode(file_get_contents($namesFilename), true);
	$indexData = json_decode(file_get_contents($indexFilename), true);
	
	$name1 = $namesData[0];
	$name2 = $namesData[1];
	
	$mergedFilelist = [];
	if (file_exists($mergedFilename))
		$mergedFilelist = getDirContents('phar://'.$mergedFilename, ".a2");
	
	#printArray($mergedFilelist);
	
	$combinedFilelist = $indexData['combined'];
	$missing = array_diff($combinedFilelist,basenameNoExtensionArray($mergedFilelist));
	
	sort($missing);
	
	
	
	function compareAnnotations($base,$indexData)
	{
		#$unannotatedJsonFile = findArrayElement($unannotatedFilelist,$base);
		$unannotatedJsonFile = $indexData['unannotated'][$base];
		$a2File1 = $indexData['annotated1'][$base];
		$a2File2 = $indexData['annotated2'][$base];
		
		#print "<b>$base</b></br>";
		#print "<b>$unannotatedJsonFile</b></br>";
		#print "<b>$a2File1</b></br>";
		#print "<b>$a2File2</b></br>";
		
		$unannotatedJsonData = json_decode(file_get_contents($unannotatedJsonFile), true);
		$content = $unannotatedJsonData['text'];
		$pmid = $unannotatedJsonData['pmid'];
		$pmcid = $unannotatedJsonData['pmcid'];
		$pubmedlink = "http://www.ncbi.nlm.nih.gov/pubmed/".$pmid;
		$entities = $unannotatedJsonData['entities'];
		
		#printArray($entities);
		
		$a2Data1 = loadA2Data($entities,file($a2File1));
		$a2Data2 = loadA2Data($entities,file($a2File2));
		#printArray($a2Data1);
		
		$allRelations = array_unique(array_merge(array_keys($a2Data1),array_keys($a2Data2)));
		sort($allRelations);
		$convertedRelations = convertIDsToEntities($entities,$allRelations);
		#printArray($allRelations);
		#printArray($convertedRelations);
		
		#$annotations1 = [];
		#$annotations2 = [];
		#$matching = [];
		$finalData = [];
		$hasDifferences = false;
		foreach ($allRelations as $rel)
		{
			#$in1[] = in_array($rel,$a2Data1);
			#$in2[] = in_array($rel,$a2Data2);
			
			if (array_key_exists($rel,$a2Data1))
				$annotation1 = implode('|',$a2Data1[$rel]);
			else
				$annotation1 = 'None';
			
			if (array_key_exists($rel,$a2Data2))
				$annotation2 = implode('|',$a2Data2[$rel]);
			else
				$annotation2 = 'None';
			
			#$annotations1[] = $annotation1;
			#$annotations2[] = $annotation2;
			$matching = ($annotation1 == $annotation2);
			if (!$matching)
				$hasDifferences = true;
			$withEntities = $convertedRelations[$rel];
			$finalData[] = array('relWithIDs'=>$rel, 'relWithEntities'=>$withEntities, 'annotation1'=>$annotation1, 'annotation2'=>$annotation2, 'matching'=>$matching);
		}
		
		$result = array('content'=>$content, 'pmid'=>$pmid, 'finalData'=>$finalData);
		
		if ($hasDifferences)
			return $result;
		else
			return false;
	}
	
	$phar = new PharData($mergedFilename, 0, null, Phar::TAR);

	$skipped = 0;
	$sentenceToAnnotate = false;
	foreach ($missing as $base)
	{
		$result = compareAnnotations($base,$indexData);
		
		if ($result == false)
		{
			// No changes, so let's skip it and copy in data from one of the annotations
			$a2File1 = $indexData['annotated1'][$base];
			$fileData = file_get_contents($a2File1);
			$phar->addFromString("output/$base.a2",$fileData);
			$skipped++;
		}
		else
		{
			$content = $result['content'];
			$pmid = $result['pmid'];
			$finalData = $result['finalData'];
			$sentenceToAnnotate = true;
			break;
		}
	}
	
	if (!$sentenceToAnnotate)
	{
		$mergedFilenameGZ = $mergedFilename.'.gz';
		print "<b>Comparison finished. No more sentences found. <a href=\"$mergedFilenameGZ\">Download Annotations</a></b>";
		
		if (file_exists($mergedFilenameGZ))
			unlink($mergedFilenameGZ);
		
		$phar->compress(Phar::GZ); # Creates archive.tar.gz
		
		exit(0);
	}
		
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- <link rel="shortcut icon" href="../../docs-assets/ico/favicon.png"> -->

    <title>annotator</title>

    <!-- Bootstrap core CSS -->
	
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
    <link href="css/tablecloth.css" rel="stylesheet">
    <link href="css/prettify.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="navbar.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy this line! -->
    <!--[if lt IE 9]><script src="../../docs-assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
    <link rel="stylesheet" href="my.css">
  </head>

  <body>

    <div class="container">

      <!-- Static navbar -->
      <div class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">annotator</a>
        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li><a href="annotations_view.php">View Annotations</a></li>
            <li class="active"><a href="annotate.php">New Annotation</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="#">Log Out</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
      <!-- Main component for a primary marketing message or call to action -->
      <div class="jumbotron">
	  
		<?php
		if ($skipped > 0)
			showAlert("Skipped $skipped sentence(s) that had no differences",false);
		?>

        <!-- <h1>Sentence Annotation</h1> -->
        <p>Please compare this sentence and resolve any conflicts between the two annotation sets</p>
        <div class="panel panel-default">
          <div class="panel-body">
            <?php echo $content; ?>
            <a href="<?php echo $pubmedlink; ?>">(pubmed)</a>
          </div>
        </div>
		
		
		<form enctype="multipart/form-data" action="compare_save.php" method="POST">
			<input type="hidden" name="filename_base" value="<?php echo $base; ?>" />
			<div class="panel panel-default">
			  <div class="panel-body">
				<table cellspacing="1" cellpadding="3" class="tablehead" style="background:#CCC;">
					<thead>
						<tr class="colhead">
							<th>Relation</th>
							<th><?php echo $name1; ?></th>
							<th><?php echo $name2; ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$count = 0;
						foreach ($finalData as $d)
						{
							$relWithEntities = $d['relWithEntities'];
							$relWithIDs = $d['relWithIDs'];
							$annotation1 = $d['annotation1'];
							$annotation2 = $d['annotation2'];
							$matching = $d['matching'];
							print "<tr>\n";
							print "<td><input type=\"hidden\" name=\"rel[$count]\" value=\"$relWithIDs\" />$relWithEntities</td>\n";
							
							if ($matching)
							{
								print "<td colspan=\"2\" style=\"text-align:center\"><input type=\"hidden\" name=\"annotation[$count]\" value=\"$annotation1\" />$annotation1</td>\n";
							}
							else
							{
								print "<td><input type=\"radio\" name=\"annotation[$count]\" value=\"$annotation1\"/> $annotation1</td>\n";
								print "<td><input type=\"radio\" name=\"annotation[$count]\" value=\"$annotation2\"/> $annotation2</td>\n";
							}
							
							print "</tr>\n";
							
							$count++;
						}
						?>
					</tbody>
				</table>
				
				<button type="submit" class="btn btn-danger">Done</button>
				
			  </div>
			</div>
		
		</form>
        	  
	  

    </div> <!-- /container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/typeahead.js"></script>
	
    <script src="js/jquery-1.7.2.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.metadata.js"></script>
    <script src="js/jquery.tablesorter.min.js"></script>
    <script src="js/jquery.tablecloth.js"></script>
    <script src="js/typeahead.js"></script>
    
	
    <script src="my.js"></script>
	
    <script type="text/javascript" charset="utf-8">
      $(document).ready(function() {
        $("table").tablecloth({
          theme: "paper",
          striped: true
        });
      });
    </script>
	
  </body>
</html>
