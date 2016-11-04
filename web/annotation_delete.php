<?php
	$tagsetid = $_GET['tagsetid'];
	include 'dbopen.php';
	
   	$query = "DELETE FROM annotations WHERE tagsetid=$tagsetid";
	$result = mysqli_query($con,$query);
	
    mysqli_close($con);

    $path = pathinfo( $_SERVER['PHP_SELF'] );
    $page = "http://".$_SERVER['SERVER_NAME'].$path['dirname']."/annotations_view.php";
	header("Location: $page");
?>