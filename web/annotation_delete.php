<?php
	$tagpairid = $_GET['tagpairid'];
	include 'dbopen.php';
	
   	$query = "DELETE FROM annotations WHERE tagpairid=$tagpairid";
	$result = mysqli_query($con,$query);
	
    mysqli_close($con);

    $path = pathinfo( $_SERVER['PHP_SELF'] );
    $page = "http://".$_SERVER['SERVER_NAME'].$path['dirname']."/annotate.php";
	header("Location: $page");
?>