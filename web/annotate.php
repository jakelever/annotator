<?php
	include 'dbopen.php';

    $userID=1;
	
    $annotationtypes = [];
	$query = "SELECT annotationtypeid,type FROM annotationtypes ORDER BY annotationtypeid";
	$result = mysqli_query($con,$query);
    while ($row = mysqli_fetch_array($result))
	{
		$annotationtypes[$row['annotationtypeid']] = $row['type'];
	}
	
	$query = "SELECT COUNT(DISTINCT sentenceid) as annotatedsentencecount FROM tagsetinfos WHERE tagsetid IN (SELECT tagsetid FROM annotations)";
	$result = mysqli_query($con,$query);
	$array = mysqli_fetch_array($result);
	$annotatedsentencecount = $array['annotatedsentencecount'];
	
	$tagsetid = -1;
    # Find an annotation that hasn't been tagged by the current user
	if (isset($_GET['tagsetid']) && is_numeric($_GET['tagsetid'])) 
	{
        $tagsetid = $_GET['tagsetid'];
	}
	else
	{
		# Check if there are annotations left to do
		$query = "SELECT COUNT(*) as count FROM tagsets WHERE tagsetid NOT IN (SELECT tagsetid FROM annotations)";
		$result = mysqli_query($con,$query);
		$row = mysqli_fetch_array($result);
        $remaining = $row['count'];
		
		# If so, get the appropriate ID for it
		if ($remaining > 0)
		{
			$query = "SELECT MIN(tagsetid) as tagsetid FROM tagsets WHERE tagsetid NOT IN (SELECT tagsetid FROM annotations)";
			$result = mysqli_query($con,$query);
			$row = mysqli_fetch_array($result);
			$tagsetid = $row['tagsetid'];
		}
	}
	
	$sentenceid = -1;
	if ($tagsetid > -1)
	{
		$query = "SELECT s.sentenceid as sentenceid, s.text as sentencetext, t.text as tagtext, s.pmid as sentencepmid, s.pmcid as sentencepmcid, t.startpos as startpos, t.endpos as endpos, t.tagid as tagid, ts.patternindex as patternindex, p.description as patterndescription, tsi.description as tagsetdescription FROM sentences s, tagsets ts, tags t, patterns p, tagsetinfos tsi WHERE tsi.sentenceid = s.sentenceid AND ts.tagid = t.tagid AND ts.tagsetid=tsi.tagsetid AND tsi.patternid = p.patternid AND ts.tagsetid='$tagsetid' ORDER BY ts.patternindex";
		
		$result = mysqli_query($con,$query);
		
		#print "<p>$query</p>\n";
		
		$tagdata = [];
		
		$theseStartPos = [];
		$theseEndPos = [];
		
		$content = '';
		$tagsetdescription = '';
		while ($row = mysqli_fetch_array($result))
		{
			//$tagsetid = $row['tagsetid'];
			$sentenceid = $row['sentenceid'];
			$content = $row['sentencetext'];
			$pmid = $row['sentencepmid'];
			$pmcid = $row['sentencepmcid'];
			$pubmedlink="http://www.ncbi.nlm.nih.gov/pubmed/".$pmid;
			$patterndescription = $row['patterndescription'];
			$patternexploded = explode(",",$patterndescription);
			$tagsetdescription = $row['tagsetdescription'];
			
			$tagid = $row['tagid'];
			$tagtext = $row['tagtext'];
			$patternindex = $row['patternindex'];
			$startpos = $row['startpos'];
			$endpos = $row['endpos'];
			
			$taginfo = array('tagid'=>$tagid,'tagtext'=>$tagtext,'patternindex'=>$patternindex,'patterntype'=>$patternexploded[$patternindex],'startpos'=>$startpos,'endpos'=>$endpos);
			$tagdata[] = $taginfo;
			
			$theseStartPos[] = $startpos;
			$theseEndPos[] = $endpos;
		}
		
		
		$contentArray = array();
		preg_match_all('/./u', $content, $contentArray);
		$contentArray = $contentArray[0];
		
		foreach($tagdata as $taginfo)
		{
			$startpos = $taginfo['startpos'];
			$endpos = $taginfo['endpos'];
			$contentArray[$startpos] = '<b>'.$contentArray[$startpos];
			$contentArray[$endpos-1] = $contentArray[$endpos-1].'</b>';
		}
		
		$query = "SELECT startpos, endpos FROM tags WHERE tagid IN (SELECT ts.tagid FROM tagsets ts, tagsetinfos tsi WHERE ts.tagsetid=tsi.tagsetid AND tsi.sentenceid=$sentenceid)";
		$result = mysqli_query($con,$query);
		while ($row = mysqli_fetch_array($result))
		{
			$startpos = $row['startpos'];
			$endpos = $row['endpos'];
			if (!in_array($startpos,$theseStartPos))
				$contentArray[$startpos] = '<u>'.$contentArray[$startpos];
			if (!in_array($endpos,$theseEndPos))
				$contentArray[$endpos-1] = $contentArray[$endpos-1].'</u>';
		}
		
		$content = implode('', $contentArray);
	}
	
	# Let's get some numbers for this sentence
	$query = "SELECT COUNT(*) as count FROM tagsetinfos tsi WHERE tsi.sentenceid=$sentenceid";
	$result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	$totalForSentence = $row['count'];
	
	$query = "SELECT COUNT(*) as count FROM tagsetinfos tsi WHERE tsi.sentenceid=$sentenceid AND tsi.tagsetid IN (SELECT tagsetid FROM annotations)";
	$result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	$currentForSentence = $row['count']+1;
	
	/*$contentArray = array();
	preg_match_all('/./u', $content, $contentArray);
	$contentArray = $contentArray[0];
	
	$contentArray[$startpos1] = '<b>'.$contentArray[$startpos1];
	$contentArray[$endpos1-1] = $contentArray[$endpos1-1].'</b>';
	$contentArray[$startpos2] = '<b>'.$contentArray[$startpos2];
	$contentArray[$endpos2-1] = $contentArray[$endpos2-1].'</b>';
	
	$query = "SELECT startpos, endpos FROM tags WHERE sentenceid=$sentenceid AND NOT (tagid=$tagid1 OR tagid=$tagid2)";
	$result = mysqli_query($con,$query);
	while ($row = mysqli_fetch_array($result))
	{
		$startpos = $row['startpos'];
		$endpos = $row['endpos'];
		$contentArray[$startpos] = '<u>'.$contentArray[$startpos];
		$contentArray[$endpos-1] = $contentArray[$endpos-1].'</u>';
	}
	
	$content = implode('', $contentArray);*/
	
    mysqli_close($con);

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
        </div><!--/.nav-collapse -->
      </div>
      <!-- Main component for a primary marketing message or call to action -->
      <div class="jumbotron">
        <?php if (isset($_GET['prevannotationid'])) { ?>
        <div class="alert alert-warning alert-dismissable">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <strong>Annotation added!</strong> Annotation added (#<?php echo $_GET['prevtagsetid']; ?>)
        </div>
        <?php } 
		
		if ($remaining == 0)
		{
			echo "<p>There are no more sentences to annotate!</p>";
		}
		else
		{
		?>

        <!-- <h1>Sentence Annotation</h1> -->
        <p>Please read the following sentence and annotate with appropriate cancer/gene relationship. Already tagged <?php print $annotatedsentencecount; ?> sentences. This is <?php echo $currentForSentence.'/'.$totalForSentence; ?> for this sentence.</p>
        <div class="panel panel-default">
          <div class="panel-body">
            <?php echo $content; ?>
            <a href="<?php echo $pubmedlink; ?>">(pubmed)</a>
          </div>
        </div>
		
		<div class="panel panel-default">
          <div class="panel-body">
            <?php echo $tagsetdescription; ?>
          </div>
        </div>
        
        <form role="form" action="annotation_new.php" method="GET">
        <input type="hidden" name="tagsetid" value="<?php echo $tagsetid; ?>">
        <div class="panel panel-default">
          <div class="panel-body">

        <?php
        foreach ($annotationtypes as $id => $name) {
        ?>
			  <div class="btn-group" data-toggle="buttons">
				  <label class="btn btn-info">
					<input type="checkbox" name="tag_<?php echo $id; ?>"><?php echo $name; ?>
				  </label>
			  </div>
      <?php
        }
        ?>
		<div class="example example-snvs">
		<input class="typeahead" type="text" placeholder="Other" name="tag_other" id="other">
		</div>
      <button type="submit" class="btn btn-danger">Submit</button>
		
        </div>
      </div>
      
      </form>
	  
	  
      <form role="form" action="prevsentence.php" method="GET">
      <button type="submit" class="btn btn-success">&lt; Remove Annotations and Move to Previous Sentence</button>
      </form>
      <form role="form" action="nextsentence.php" method="GET">
      <button type="submit" class="btn btn-success">Set None for Rest of Sentence &gt;</button>
      </form>
	  
	  <?php
		}
		?>

    </div> <!-- /container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/typeahead.js"></script>
    <script src="my.js"></script>
  </body>
</html>
