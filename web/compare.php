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
	  
		<p>Please upload the unannotated data and the two annotation sets for comparison.</p>
			
		<form enctype="multipart/form-data" action="compare_start.php" method="POST">
			<div class="panel panel-default">
			  <div class="panel-body">
				<table>
					<tr>
						<td>Unannotated Data (A1 files, etc)</td>
						<td><input type="hidden" name="MAX_FILE_SIZE" value="1000000" /><input type="file" name="unannotated"></td>
						<td></td>
					</tr>
					<tr>
						<td>Annotations 1 (A2 files)</td>
						<td><input type="hidden" name="MAX_FILE_SIZE" value="1000000" /><input type="file" name="annotated1"></td>
						<td>Name: <input class="typeahead" type="text" placeholder="Annotation 1" name="name1"></td>
					</tr>
					<tr>
						<td>Annotations 2 (A2 files)</td>
						<td><input type="hidden" name="MAX_FILE_SIZE" value="1000000" /><input type="file" name="annotated2"></td>
						<td>Name: <input class="typeahead" type="text" placeholder="Annotation 2" name="name2"></td>
					</tr>
					<tr>
						<td colspan="3"><button type="submit" class="btn btn-danger">Import Data</button></td>
					</td>
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
