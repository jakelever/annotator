<?php	include 'dbopen.php';
	
   	$query = "SELECT MAX(tsi.sentenceid) FROM annotations a, tagsetinfos tsi WHERE a.tagsetid=tsi.tagsetid";
	$result = mysqli_query($con,$query);
	if (!$result)
	{
		echo "<b>ERROR: Cannot get sentenceid to delete</b> SQL: $query";
		exit(1);
	}
	$sentenceid = mysqli_fetch_row($result)[0];
	
	
   	$query = "DELETE FROM annotations WHERE tagsetid IN (SELECT tagsetid FROM tagsetinfos WHERE sentenceid=$sentenceid)";
	$result = mysqli_query($con,$query);
	if (!$result)
	{
		echo "<b>ERROR: Cannot delete annotations</b> SQL: $query";
		exit(1);
	}
	
    mysqli_close($con);

    $path = pathinfo( $_SERVER['PHP_SELF'] );
    $page = "http://".$_SERVER['SERVER_NAME'].$path['dirname']."/annotate.php";
	header("Location: $page");
?>