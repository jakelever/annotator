<?php
include 'dbopen.php';
	
$filename = 'merged.txt';

$handle = fopen($filename, "r");

while (($line = fgets($handle)) !== false) {
	$line = trim($line,"\n\r");
	
	$exploded = explode("\t",$line);
	
	$pmid = $exploded[0];
	$pmcid = $exploded[1];
	$sentence = $exploded[2];
	$escapedSentence = mysqli_real_escape_string($con,$sentence);
	
	#print "<pre>";
	#print_r($exploded);
	#print "</pre>";
	#exit(0);
	#print $exploded[0]." | $sentence<br/>";
	
	
	$query = "SELECT Count(*) as count FROM sentences WHERE pmid='$pmid' AND pmcid='$pmcid' AND text='$escapedSentence'";
	print "<p>$query</p>";
    $result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	$count = $row['count'];
	if ($count > 0)
		continue;
	
	$query = "INSERT INTO sentences(pmid,pmcid,text) VALUES('$pmid','$pmcid','$escapedSentence');";
	print "<p>$query</p>";
    $result = mysqli_query($con,$query);
	$sentenceid = mysqli_insert_id($con);
	
	
	$cancer_tagids = [];
	$gene_tagids = [];
	$mutation_tagids = [];
	for ($i=3; $i<count($exploded); $i++)
	{
		$tagExploded = explode('|',$exploded[$i]);
		$type = $tagExploded[0];
		$wordlistid = $tagExploded[1];
		$startPos = $tagExploded[2];
		$endPos = $tagExploded[3];
		$tokens = $tagExploded[4];
		$escapedTokens = mysqli_real_escape_string($con,$tokens);
		
		$query = "INSERT INTO tags(sentenceid,type,startpos,endpos,text) VALUES('$sentenceid','$type','$startPos','$endPos','$escapedTokens');";
		print "<p>$query</p>";
		$result = mysqli_query($con,$query);
		$tagid = mysqli_insert_id($con);
		
		if ($type == 'cancer')
			$cancer_tagids[] = $tagid;
		elseif ($type == 'gene')
			$gene_tagids[] = $tagid;
		elseif ($type == 'mutation')
			$mutation_tagids[] = $tagid;
	}
	
	foreach ($cancer_tagids as $cancer_tagid)
	{
		foreach ($gene_tagids as $gene_tagid)
		{
			$query = "INSERT INTO tagpairs(tagid1,tagid2) VALUES('$cancer_tagid','$gene_tagid');";
			print "<p>Cancer/Gene: $query</p>";
			$result = mysqli_query($con,$query);
		}
	}
	
	foreach ($gene_tagids as $gene_tagid)
	{
		foreach ($mutation_tagids as $mutation_tagid)
		{
			$query = "INSERT INTO tagpairs(tagid1,tagid2) VALUES('$gene_tagid','$mutation_tagid');";
			print "<p>Gene/Mutation: $query</p>";
			$result = mysqli_query($con,$query);
		}
	}
}

mysqli_close($con);
	
?>