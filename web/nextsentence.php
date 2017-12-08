<?php	include 'dbopen.php';
	
	$query = "SELECT type FROM annotationtypes ORDER BY annotationtypeid LIMIT 1";
	$result = mysqli_query($con,$query);
	$array = mysqli_fetch_array($result);
	$defaultAnnotation = $array['type'];
	
   	$query = "SELECT annotationtypeid FROM annotationtypes WHERE type='$defaultAnnotation'";
	#print "<p>$query</p>";
	$result = mysqli_query($con,$query);
	if (!$result || mysqli_num_rows($result) < 1)
	{
		echo "<b>ERROR: Cannot get annotationtypeid for $defaultAnnotation</b> SQL: $query";
		exit(1);
	}
	$annotationtypeid = mysqli_fetch_row($result)[0];
	
	$query = "SELECT MIN(tagsetid) as tagsetid FROM tagsets WHERE tagsetid NOT IN (SELECT tagsetid FROM annotations)";
	$result = mysqli_query($con,$query);
	if (!$result || mysqli_num_rows($result) < 1)
	{
		echo "<b>ERROR: Cannot get tagsetid for current sentence</b> SQL: $query";
		exit(1);
	}
	$row = mysqli_fetch_array($result);
	$tagsetid = $row['tagsetid'];
	
   	$query = "SELECT sentenceid FROM tagsetinfos WHERE tagsetid=$tagsetid";
	#print "<p>$query</p>";
	$result = mysqli_query($con,$query);
	if (!$result || mysqli_num_rows($result) < 1)
	{
		echo "<b>ERROR: Cannot get annotationtypeid for $defaultAnnotation</b> SQL: $query";
		exit(1);
	}
	$sentenceid = mysqli_fetch_row($result)[0];
	
	$query = "SELECT tagsetid FROM tagsetinfos WHERE sentenceid='$sentenceid' AND tagsetid NOT IN (SELECT tagsetid FROM annotations)";
	#print "<p>$query</p>";
	$result1 = mysqli_query($con,$query);
	if (!$result1)
	{
		echo "<b>ERROR: Cannot get find tagsetids to set to NONE</b> SQL: $query";
		exit(1);
	}
	
	while ($row = mysqli_fetch_array($result1))
	{
		$tagsetid = $row[0];
		$query2 = "INSERT INTO annotations(tagsetid,annotationtypeid) VALUES('$tagsetid','$annotationtypeid')";
		#print "<p>$query2</p>";
		$result2 = mysqli_query($con,$query2);
		if (!$result2)
		{
			echo "<b>ERROR: Unable to add $defaultAnnotation annotation</b> SQL: $query";
			exit(1);
		}
	}
	
	
    mysqli_close($con);


    $path = pathinfo( $_SERVER['PHP_SELF'] );
    $page = "http://".$_SERVER['SERVER_NAME'].$path['dirname']."/annotate.php";
	header("Location: $page");
?>