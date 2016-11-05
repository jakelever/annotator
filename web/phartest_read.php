<?php
	// From: http://stackoverflow.com/questions/24783862/list-all-the-files-and-folders-in-a-directory-with-php-recursive-function
	function getDirContents($dir, $filter='', &$results = array()){
		$files = scandir($dir);

		foreach($files as $key => $value){
			//$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
			$path = $dir.'/'.$value;
			//print "$path<br />";
			if(!is_dir($path)) {
				if ($filter == '' || endsWith($path,$filter))
					$results[] = $path;
			} else if($path != "." && $path != "..") {
				getDirContents($path, $filter, $results);
				//$results[] = $path;
			}
		}

		return $results;
	}
	
	function print_array($a)
	{		
		echo "<pre>";
		print_R($a);
		echo "</pre>";
	}

	echo "<b>Hello</b><br />\n";
			
	$archiveFilename = 'archive.tar.gz';
	$dir = 'phar://'.$archiveFilename;
	$files = getDirContents($dir);
	
	print_array($files);
	
	$archive = new PharData($archiveFilename);
	foreach($archive as $file) {
			echo "$file<br />\n";
	}
?>