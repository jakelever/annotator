<pre>
<?php

function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (is_dir($dir."/".$object))
           rrmdir($dir."/".$object);
         else
           unlink($dir."/".$object); 
       } 
     }
     rmdir($dir); 
   } 
}

function mvdir($hereDir,$thereDir)
{
	$dir_iterator = new RecursiveDirectoryIterator($hereDir);
	$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
	foreach ($iterator as $therePath)
	{		
		if (is_dir($therePath))
		{
			$herePath = str_replace($hereDir,$thereDir,$therePath);
			if (is_dir($herePath))
			{
				echo "Skipping: $herePath\n";
				continue;
			}
			echo "Creating: $herePath\n";
			if (!mkdir($herePath,0777,true))
			{
				echo "ERROR: Unable to create directory: $herePath\n";
				exit(1);
			}
		}
	}

	$dir_iterator = new RecursiveDirectoryIterator($hereDir);
	$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
	foreach ($iterator as $therePath)
	{
		if (is_file($therePath))
		{
			$herePath = str_replace($hereDir,$thereDir,$therePath);
			echo "Moving: $herePath\n";
			if (!rename($therePath,$herePath))
			{
				echo "ERROR: Unable to move file: $herePath\n";
				exit(1);
			}
		}
	}
}

if (is_file('latest.zip'))
	unlink('latest.zip');

if (is_dir('unzipped'))
	rrmdir('unzipped');

file_put_contents("latest.zip", fopen("https://github.com/jakelever/annotator/archive/master.zip", 'r'));

$zip = new ZipArchive;
if ($zip->open('latest.zip') === TRUE) {
    $zip->extractTo('unzipped');
    $zip->close();
    echo "Unzip successful\n";
} else {
    echo "ERROR: Unable to open zip file\n";
	exit(1);
}

#rename('unzipped/annotator-master/web', 'web');

$dirToCopy='unzipped/annotator-master/web';

mvdir($dirToCopy,'.');

rrmdir('unzipped');

?>
</pre>
