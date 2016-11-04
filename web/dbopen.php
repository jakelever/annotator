<?php

	$dbinfo = 'restricted/dbinfo.json';
	$data = json_decode(file_get_contents($dbinfo), true);
	
	$username = $data['username'];
	$password = $data['password'];
	$host = $data['host'];
	$database = $data['database'];
	
    #$con=mysqli_connect("localhost","root","","drivermine");
	$con = @mysqli_connect($host,$username,$password,$database);

	if ($con)
	{
		$testQueries = [];
		$testQueries[] = 'SELECT * FROM annotations LIMIT 1';
		$testQueries[] = 'SELECT * FROM annotationtypes LIMIT 1';
		$testQueries[] = 'SELECT * FROM sentences LIMIT 1';
		$testQueries[] = 'SELECT * FROM tagsets LIMIT 1';
		$testQueries[] = 'SELECT * FROM tags LIMIT 1';
		
		$success = true;
		foreach ($testQueries as $testQuery)
		{
			if (!mysqli_query($con,$testQuery))
			{
				$success = false;
				break;
			}
		}
		
		if (!$success)
		{
			echo "<b>ERROR: Database missing expected tables</b>";
			exit(1);
		}
	}
	else
	{
		echo "<b>ERROR: Unable to connect to database</b>";
		exit(1);
	}
?>