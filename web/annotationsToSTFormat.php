<?php	include 'dbopen.php';
	
	#$query = "SELECT DISTINCT s.sentenceid as sentenceid, s.text as sentencetext FROM sentences s, tagpairs tp, tags t, annotations a WHERE a.tagpairid = tp.tagpairid AND s.sentenceid = t.sentenceid AND (t.tagid = tp.tagid1 OR t.tagid = tp.tagid2) ORDER BY s.sentenceid";
	
	$query = "SELECT DISTINCT s.sentenceid as sentenceid, s.text as sentencetext FROM annotations a, tagpairs tp, tags t, sentences s WHERE a.tagpairid=tp.tagpairid AND tp.tagid1=t.tagid AND t.sentenceid=s.sentenceid ORDER BY s.sentenceid";
    $result1 = mysqli_query($con,$query);
	
	$outDir = 'out';
	
	$counter = 0;
	while ($row = mysqli_fetch_row($result1)) {
		$sentenceid = $row[0];
		$sentencetext = $row[1];
		#print "<p>$sentenceid</p>";
		
		$paddedSentenceID=str_pad($sentenceid,8,"0",STR_PAD_LEFT);
		
		$outTxtFile = "$outDir/$paddedSentenceID.txt";
		$outA1File = "$outDir/$paddedSentenceID.a1";
		$outA2File = "$outDir/$paddedSentenceID.a2";
		
		file_put_contents($outTxtFile,$sentencetext);
		
		$tagsToTriggerID = [];
		$query = "SELECT tagid,type,startpos,endpos,text FROM tags WHERE sentenceid=$sentenceid";
		$result2 = mysqli_query($con,$query);
		$triggerID = 1;
		$triggerText = [];
		while ($assoc = mysqli_fetch_assoc($result2)) {
			$tagid = $assoc['tagid'];
			$type = $assoc['type'];
			$startpos = $assoc['startpos'];
			$endpos = $assoc['endpos'];
			$text = $assoc['text'];
			
			//if ($type == 'cancer' || $type == 'gene') {
				$line = "T$triggerID\t$type $startpos $endpos\t$text";
				$triggerText[] = $line."\n";
				print "<p>$line</p>";
				$tagsToTriggerID[$tagid] = $triggerID;
				$triggerID++;
			//}
		}
		
		file_put_contents($outA1File,implode("",$triggerText));
		
		$query = "SELECT t1.tagid as tagid1,t2.tagid as tagid2,at.type as type FROM annotationtypes at, annotations a, tagpairs tp, tags t1, tags t2 WHERE at.annotationtypeid=a.annotationtypeid AND a.tagpairid=tp.tagpairid AND tp.tagid1=t1.tagid AND tp.tagid2=t2.tagid AND t1.sentenceid=$sentenceid AND t2.sentenceid=$sentenceid";
		
		$result3 = mysqli_query($con,$query);
		$eventID = 1;
		$eventText = [];
		while ($assoc = mysqli_fetch_assoc($result3)) {
			$type = $assoc['type'];
			
			//if ($type == 'Driver') {
			if ($type != 'None') {
				$tagid1 = $assoc['tagid1'];
				$tagid2 = $assoc['tagid2'];
				$triggerid1 = $tagsToTriggerID[$tagid1];
				$triggerid2 = $tagsToTriggerID[$tagid2];
			
				$eventName = str_replace(' ','_',$type);
				$line = "E$eventID\t$eventName arg1:T$triggerid1 arg2:T$triggerid2";
				$eventText[] = $line."\n";
				print "<p>$line</p>";
				$eventID++;
			}
			
		}
		
		file_put_contents($outA2File,implode("",$eventText));
		
		$counter++;
		//if ($counter > 15)
		//	break;
	}
	
    mysqli_close($con);
?>