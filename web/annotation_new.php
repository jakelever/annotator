<?php

	include 'dbopen.php';
	
    $tags = [];
   	$query = "SELECT annotationtypeid,type FROM annotationtypes ORDER BY annotationtypeid";
	$result = mysqli_query($con,$query);
    while ($row = mysqli_fetch_array($result))
	{
		$tags[$row['annotationtypeid']] = $row['type'];
	}
	
    $tagsetid=$_GET['tagsetid'];
    $userid=1;
    $annotation['userid'] = $userid;
    //$annotation['sentenceid'] = $sentenceid;
    
    $path = pathinfo( $_SERVER['PHP_SELF'] );
    $page = "http://".$_SERVER['SERVER_NAME'].$path['dirname']."/annotate.php";

	
	
    foreach ($tags as $id => $name) {
        $boolean = isset($_GET["tag_".$id]) && $_GET["tag_".$id]=='on';
        $annotation[$name] = $boolean ? 1 : 0;
		
		#print "$name<br>";
		#print $annotation[$name];
		#print "<br>";
		
		if ($boolean)
		{
			$query = "INSERT INTO annotations(tagsetid,annotationtypeid) VALUES($tagsetid,$id)";
			print "<p>$query</p>";
			$result = mysqli_query($con,$query);
		}
    }
	
	if (isset($_GET['tag_other']) and strlen($_GET['tag_other']) > 0)
	{
			$other = $_GET['tag_other'];
			
			$query = "INSERT INTO annotationtypes(type) VALUES('$other')";
			#print "<p>$query</p>";
			$result = mysqli_query($con,$query);
			
			$id = mysqli_insert_id($con);
			
			$query = "INSERT INTO annotations(tagsetid,annotationtypeid) VALUES($tagsetid,$id)";
			#print "<p>$query</p>";
			$result = mysqli_query($con,$query);
	}
    
	
    mysqli_close($con);

	header("Location: $page?oldtagsetid=$tagsetid");
?>