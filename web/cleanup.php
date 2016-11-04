<?php

$con=mysqli_connect("localhost","root","","drivermine");
	
$filename = 'merged.txt';

$handle = fopen($filename, "r");

$sentencesToDelete = [];
$sentencesMatched = [];

while (($line = fgets($handle)) !== false) {
	$line = trim($line,"\n\r");
	
	$exploded = explode("\t",$line);
	
	$pmid = $exploded[0];
	$pmcid = $exploded[1];
	$sentence = $exploded[2];
	$escapedSentence = mysqli_real_escape_string($con,$sentence);
	
	$query = "SELECT sentenceid FROM sentences WHERE pmid='$pmid' AND pmcid='$pmcid' AND text='$escapedSentence'";
	print "<p>$query</p>";
    $result = mysqli_query($con,$query);
	$count = mysqli_num_rows($result);
	
	if ($count == 0)
	{
		continue;
	}
	else if ($count > 1)
	{
		print "<p>Found sentence more than once! Will remove all...</p>";
		#exit(0);
		while ($row = mysqli_fetch_array($result)) {
			$tmpsentenceid = $row['sentenceid'];
			if (!in_array($tmpsentenceid,$sentencesToDelete))
			{
				$sentencesToDelete[] = $tmpsentenceid;
				print "<p>Removing duplicate sentence with id: $tmpsentenceid</p>";
			}
		}
		continue;
	}
	
	$sentenceid = mysqli_fetch_array($result)['sentenceid'];
	$sentencesMatched[] = $sentenceid;
	
	$deleteThisSentence = False;
	
	$cancer_tagids = [];
	$gene_tagids = [];
	$mutation_tagids = [];
	$all_tagids = [];
	for ($i=3; $i<count($exploded); $i++)
	{
		$tagExploded = explode('|',$exploded[$i]);
		$type = $tagExploded[0];
		$wordlistid = $tagExploded[1];
		$startPos = $tagExploded[2];
		$endPos = $tagExploded[3];
		$tokens = $tagExploded[4];
		$escapedTokens = mysqli_real_escape_string($con,$tokens);
		
		#$query = "INSERT INTO tags(sentenceid,type,startpos,endpos,text) VALUES('$sentenceid','$type','$startPos','$endPos','$escapedTokens');";
		$query = "SELECT tagid FROM tags WHERE sentenceid='$sentenceid' AND type='$type' AND startpos='$startPos' AND endpos='$endPos' AND text='$escapedTokens'";
		print "<p>$query</p>";
		$result = mysqli_query($con,$query);
		$count = mysqli_num_rows($result);
		if ($count != 1)
		{
			$deleteThisSentence = True;
			break;
		}
		
		$tagid = mysqli_fetch_array($result)['tagid'];
		
		if ($type == 'cancer')
			$cancer_tagids[] = $tagid;
		elseif ($type == 'gene')
			$gene_tagids[] = $tagid;
		elseif ($type == 'mutation')
			$mutation_tagids[] = $tagid;
		$all_tagids[] = $tagid;
	}
	
	if (!$deleteThisSentence)
	{
		$query = "SELECT tagid FROM tags WHERE sentenceid='$sentenceid'";
		$result = mysqli_query($con,$query);
		while ($row = mysqli_fetch_array($result)) {
			$tagid = $row['tagid'];
			if (!in_array($tagid, $all_tagids))
			{
				$deleteThisSentence = True;
				break;
			}
		}
	}
	
	foreach ($cancer_tagids as $cancer_tagid)
	{
		if ($deleteThisSentence)
			break;
		foreach ($gene_tagids as $gene_tagid)
		{
			#$query = "INSERT INTO tagpairs(tagid1,tagid2) VALUES('$cancer_tagid','$gene_tagid');";
			$query = "SELECT tagpairid FROM tagpairs WHERE tagid1='$cancer_tagid' AND tagid2='$gene_tagid'";
			print "<p>Cancer/Gene: $query</p>";
			$result = mysqli_query($con,$query);
			$count = mysqli_num_rows($result);
			if ($count != 1)
			{
				$deleteThisSentence = True;
				break;
			}
		}
	}
	
	foreach ($gene_tagids as $gene_tagid)
	{
		if ($deleteThisSentence)
			break;
		foreach ($mutation_tagids as $mutation_tagid)
		{
			#$query = "INSERT INTO tagpairs(tagid1,tagid2) VALUES('$gene_tagid','$mutation_tagid');";
			$query = "SELECT tagpairid FROM tagpairs WHERE tagid1='$gene_tagid' AND tagid2='$mutation_tagid'";
			print "<p>Gene/Mutation: $query</p>";
			$result = mysqli_query($con,$query);
		}
	}
	
	if ($deleteThisSentence && !in_array($sentenceid,$sentencesToDelete))
		$sentencesToDelete[] = $sentenceid;
}

$query = "SELECT sentenceid FROM sentences";
$result = mysqli_query($con,$query);
while ($row = mysqli_fetch_array($result)) {
	$sentenceid = $row['sentenceid'];
	if (!in_array($sentenceid, $sentencesMatched) && !in_array($sentenceid,$sentencesToDelete))
		$sentencesToDelete[] = $sentenceid;
}

sort($sentencesToDelete);

#print "<pre>";
#print_r($sentencesToDelete);
#print "</pre>";

$tagsToDelete = [];
$sentencesToDeleteImploded = implode(",",$sentencesToDelete);
$query = "SELECT tagid FROM tags WHERE sentenceid IN ($sentencesToDeleteImploded)";
$result = mysqli_query($con,$query);
while ($row = mysqli_fetch_array($result)) {
	$tagsToDelete[] = $row['tagid'];
}

$tagpairsToDelete = [];
$tagsToDeleteImploded = implode(",",$tagsToDelete);
$query = "SELECT tagpairid FROM tagpairs WHERE tagid1 IN ($tagsToDeleteImploded) OR tagid2 IN ($tagsToDeleteImploded)";
$result = mysqli_query($con,$query);
while ($row = mysqli_fetch_array($result)) {
	$tagpairsToDelete[] = $row['tagpairid'];
}
$tagpairsToDeleteImploded = implode(",",$tagpairsToDelete);

print "<p><b>sentencesToDeleteImploded:</b> $sentencesToDeleteImploded</p>";
print "<p><b>tagsToDeleteImploded:</b> $tagsToDeleteImploded</p>";
print "<p><b>tagpairsToDeleteImploded:</b> $tagpairsToDeleteImploded</p>";

$query = "DELETE FROM sentences WHERE sentenceid IN ($sentencesToDeleteImploded)";
$result = mysqli_query($con,$query);
$query = "DELETE FROM tags WHERE tagid IN ($tagsToDeleteImploded)";
$result = mysqli_query($con,$query);
$query = "DELETE FROM tagpairs WHERE tagpairid IN ($tagpairsToDeleteImploded)";
$result = mysqli_query($con,$query);
$query = "DELETE FROM annotations WHERE tagpairid IN ($tagpairsToDeleteImploded)";
$result = mysqli_query($con,$query);


mysqli_close($con);
	
?>