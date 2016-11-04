<?php	include 'dbopen.php';
	
   	$query = "SELECT MAX(t.sentenceid) FROM annotations a, tagpairs tp, tags t WHERE a.tagpairid=tp.tagpairid AND tp.tagid1=t.tagid";
	$result = mysqli_query($con,$query);
	$sentenceid = mysqli_fetch_row($result)[0];
	
	
   	$query = "DELETE FROM annotations WHERE tagpairid IN (SELECT tp.tagpairid FROM tagpairs tp, tags t WHERE tp.tagid1=t.tagid AND t.sentenceid=$sentenceid)";
	$result = mysqli_query($con,$query);
	
    mysqli_close($con);


    $path = pathinfo( $_SERVER['PHP_SELF'] );
    $page = "http://".$_SERVER['SERVER_NAME'].$path['dirname']."/annotate.php";
	header("Location: $page");
?>