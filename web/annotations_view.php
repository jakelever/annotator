<?php

	include 'dbopen.php';

    $userID=1;
    
	$selectList = "tsi.tagsetid as tagsetid, s.sentenceid as sentenceid, s.text as sentencetext, tsi.description as tagsetdescription, s.pmid as sentencepmid, s.pmcid as sentencepmcid";
	
	$query = "SELECT $selectList,GROUP_CONCAT(DISTINCT(at.type)) AS annotated FROM tagsetinfos tsi, sentences s, annotations a, annotationtypes at WHERE tsi.tagsetid IN (SELECT tagsetid FROM annotations) AND tsi.sentenceid = s.sentenceid AND a.tagsetid=tsi.tagsetid AND a.annotationtypeid=at.annotationtypeid GROUP BY s.sentenceid ORDER BY s.sentenceid,at.type";
	
	//echo "<p>$query</p>";
    $result = mysqli_query($con,$query);
	$annotationCount = mysqli_num_rows($result);
    #$row = mysqli_fetch_array($result);

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
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
    <link href="css/tablecloth.css" rel="stylesheet">
    <link href="css/prettify.css" rel="stylesheet"> 

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
            <li class="active"><a href="annotations_view.php">View Annotations</a></li>
            <li><a href="annotate.php">New Annotation</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
      <!-- Main component for a primary marketing message or call to action -->
      <div class="jumbotron">
        

        <h1>Annotations</h1>
        <p>Have a gander at <?php echo $annotationCount; ?> annotations!.</p>
            
            
            <div class="panel-group" id="accordion">
            <table cellspacing="1" cellpadding="3" class="tablehead" style="background:#CCC;">
          
          <thead>
            <tr class="colhead">
              <th title="tagsetid">tagsetid</th>
              <th title="Description">Description</th>
              <th title="Annotations">Annotations</th>
              <th title="PMID">PMID</th>
              <th title="Empty"></th>
            </tr>
          </thead>
          <tbody>
            <?php
                $collapseID=0;
                while ($row = mysqli_fetch_assoc($result)) {
                    #$mutationid = $row[0];
                    echo "<tr>\n";
                    
                    #for($i=1; $i<count($row); $i++)
                    #    echo "<td>".$row[$i]."</td>\n";
				
				
					$sentenceid = $row['sentenceid'];
					$tagsetid = $row['tagsetid'];
					$sentencetext = $row['sentencetext'];
					$annotationText = $row['annotated'];
			
					echo "<td>".$row['tagsetid']."</td>\n";
					echo "<td>".$row['tagsetdescription']."</td>\n";
					echo "<td>".$annotationText."</td>\n";
					echo "<td>".$row['sentencepmid']."</td>\n";
                    
                    echo "<td>";
                    echo "<a data-toggle=\"collapse\" data-parent=\"#accordion\" href=\"#collapse$collapseID\">(Show)</a> ";
                    echo "<a href=\"annotation_delete.php?tagsetid=$tagsetid\">(Delete)</a> ";
                    echo "</td>\n";
                    echo "</tr>\n";
					
					
                    
                    $colspan = 6;
                    echo "<tr>\n";
                    echo "<td colspan=$colspan>\n";
                    echo "<div id=\"collapse$collapseID\" class=\"panel-collapse collapse\">";
                    echo $sentencetext;
                    echo "</div>";
                    echo "</td>\n";
                    echo "</tr>\n";
                    
                    $collapseID++;
					
					#if ($collapseID > 300)
					#	break;
                }
            ?>
          </tbody>
          </table>
            </div>
            
        
        

    </div> <!-- /container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <!-- <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script> -->
    
    <script src="js/jquery-1.7.2.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.metadata.js"></script>
    <script src="js/jquery.tablesorter.min.js"></script>
    <script src="js/jquery.tablecloth.js"></script>
    <script src="js/typeahead.js"></script>
    
    <script src="my.js"></script>
    
    <script type="text/javascript" charset="utf-8">
      $(document).ready(function() {
        $("table").tablecloth({
          theme: "paper",
          striped: true
        });
      });
    </script>
  </body>
</html>

<?php
    mysqli_close($con);
?>