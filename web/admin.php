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

$connectionOkay = true;
$tablesOkay = true;

$dbinfo = 'restricted/dbinfo.json';
$data = json_decode(file_get_contents($dbinfo), true);

$username = $data['username'];
$password = $data['password'];
$host = $data['host'];
$database = $data['database'];

#$con=mysqli_connect("localhost","root","","drivermine");
$con = @mysqli_connect($host,$username,$password,$database);
$sentenceCount = -1;
$annotationCount = -1;

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
	
	if ($success)
	{
		$query = 'SELECT COUNT(*) as sentenceCount FROM sentences';
		$result = mysqli_query($con,$query);
		$row = mysqli_fetch_array($result);
		$sentenceCount = $row['sentenceCount'];
		
		$query = 'SELECT COUNT(*) as annotationCount FROM annotations';
		$result = mysqli_query($con,$query);
		$row = mysqli_fetch_array($result);
		$annotationCount = $row['annotationCount'];
	}
	else
	{
		$connectionOkay = false;
	}
}
else
{
	$tablesOkay = false;
}

$htaccessExists = (file_exists('.htaccess') && file_exists('.htpasswd'));


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
            <li class="active"><a href="admin.php">Admin</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="#"></a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
      <!-- Main component for a primary marketing message or call to action -->
      <div class="jumbotron">

        <!-- <h1>Sentence Annotation</h1> -->
		<form action="setup_db.php" method="POST">
			<h1>Database Connection</h1>
			<p>Use the controls below to setup the connection to the database</p>
			<div class="panel panel-default">
			  <div class="panel-body">
				<?php
					if (!$connectionOkay)
						showAlert('Database connection is not set up. Please enter the correct details below.',true);
					else if (!$tablesOkay)
						showAlert('Tables are not setup correctly in database. You will need to rerun this setup.',true);
					else
						showAlert('Database connection is ready. No need to change these settings',false);
				?>
				<h2>Database Connection</h2>
				<table>
					<tr>
						<td>Username:</td>
						<td><input class="typeahead" type="text" placeholder="Username" name="username"></td>
					</tr>
					<tr>
						<td>Password:</td>
						<td><input class="typeahead" type="text" placeholder="Password" name="password"></td>
					</tr>
					<tr>
						<td>Host:</td>
						<td><input class="typeahead" type="text" placeholder="Host" name="host"></td>
					</tr>
					<tr>
						<td>Database:</td>
						<td><input class="typeahead" type="text" placeholder="Database" name="database"></td>
					</tr>
					<tr>
						<td><button type="submit" class="btn btn-danger">Setup Database</button></td>
					</td>
				</table>
			  </div>
			</div>
		</form>
			
		<form enctype="multipart/form-data" action="setup_data.php" method="POST">
			<h2>Importing Data</h2>
			<div class="panel panel-default">
			  <div class="panel-body">
				<?php
					if (!$connectionOkay || !$tablesOkay)
						showAlert('Fix the database connection above before importing data!',true);
					elseif ($sentenceCount <= 0)
						showAlert('No data is currently found. Time to import some!',true);
					else
						showAlert("$sentenceCount sentences are found. Be careful with further updates to avoid duplicates.",false);
				?>
				<table>
					<tr>
						<td>Data to Annotate (ST Format in GZipped archive):</td>
						<td><input type="hidden" name="MAX_FILE_SIZE" value="1000000" /><input type="file" name="data"></td>
					</tr>
					<tr>
						<td>Initial Annotation Tags (comma-delimited):</td>
						<td><input class="typeahead" type="text" placeholder="Tags" name="tags" value="None"></td>
					</tr>
					<tr>
						<td colspan="2">Acceptable Relations, comma-delimited on each line. For example: "gene,cancer" or "drug,gene,cancer"</textarea></td>
					</tr>
					<tr>
						<td colspan="2"><textarea class="form-control" rows="3" name="tuples"></textarea></td>
					</tr>
					<tr>
						<td><button type="submit" class="btn btn-danger">Import Data</button></td>
					</td>
				</table>
			  </div>
			</div>
		
		</form>
			
		
        <form action="exportToST.php" method="POST">
			<h2>Exporting Annotations</h2>
			<div class="panel panel-default">
			  <div class="panel-body">
				<?php
					if (!$connectionOkay || !$tablesOkay)
						showAlert('Fix the database connection above before exporting data!',true);
					elseif ($annotationCount <= 0)
						showAlert('No annotations are currently found. Time to annotate some!',true);
					else
						showAlert("$annotationCount annotations are found.",false);
				?>
				<table>
					<tr>
						<td>Download the annotations as a GZipped archive of A2 files (ST format):</td>
					</tr>
					<tr>
						<td><button type="submit" class="btn btn-danger">Export Data</button></td>
					</tr>
				</table>
			  </div>
			</div>
		</form>
		
		
        <form action="setup_access.php" method="POST">
			<h2>Set Password</h2>
			<div class="panel panel-default">
			  <div class="panel-body">
				<?php
					if (!$htaccessExists)
						showAlert('No password is set on annotation system. Maybe do that!',true);
					else
						showAlert('A password has been set.',false);
				?>
				<table>
					<tr>
						<td>Username:</td>
						<td><input class="typeahead" type="text" placeholder="username" name="username"></td>
					</tr>
					<tr>
						<td>Password:</td>
						<td><input class="typeahead" type="text" placeholder="password" name="password"></td>
					</tr>
					<tr>
						<td colspan="2"><button type="submit" class="btn btn-danger">Set Password</button></td>
					</tr>
				</table>
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
    <script src="my.js"></script>
  </body>
</html>
