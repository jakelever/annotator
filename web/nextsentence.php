<?php	include 'dbopen.php';
	
   	$query = "SELECT annotationtypeid FROM annotationtypes WHERE type='None'";
	#print "<p>$query</p>";
	$result = mysqli_query($con,$query);
	$annotationtypeid = mysqli_fetch_row($result)[0];
	
	
   	$query = "SELECT s.sentenceid FROM tagpairs tp, tags t1, tags t2, sentences s WHERE tp.tagid1=t1.tagid AND tp.tagid2=t2.tagid AND s.sentenceid=t1.sentenceid AND s.sentenceid=t2.sentenceid AND NOT tp.tagpairid in (SELECT tagpairid FROM annotations) ORDER BY tp.tagpairid LIMIT 1 ";
	#print "<p>$query</p>";
	$result = mysqli_query($con,$query);
	$sentenceid = mysqli_fetch_row($result)[0];
	
	$query = "SELECT tp.tagpairid FROM tagpairs tp, tags t1, tags t2, sentences s WHERE tp.tagid1=t1.tagid AND tp.tagid2=t2.tagid AND s.sentenceid=t1.sentenceid AND s.sentenceid=t2.sentenceid AND NOT tp.tagpairid in (SELECT tagpairid FROM annotations) AND s.sentenceid=$sentenceid";
	#print "<p>$query</p>";
	$result1 = mysqli_query($con,$query);
	
	while ($row = mysqli_fetch_array($result1))
	{
		$tagpairid = $row[0];
		$query2 = "INSERT INTO annotations(tagpairid,annotationtypeid) VALUES('$tagpairid','$annotationtypeid')";
		#print "<p>$query2</p>";
		$result2 = mysqli_query($con,$query2);
	}
	
	
    mysqli_close($con);


    $path = pathinfo( $_SERVER['PHP_SELF'] );
    $page = "http://".$_SERVER['SERVER_NAME'].$path['dirname']."/annotate.php";
	header("Location: $page");
?>