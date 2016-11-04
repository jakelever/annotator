<?php
	
	if (file_exists("./archive.tar"))
		unlink("./archive.tar");
	if (file_exists("./archive.tar.gz"))
		unlink("./archive.tar.gz");
	if (file_exists("./archive2.tar.gz"))
		unlink("./archive2.tar.gz");
	if (file_exists("./archive2.zip"))
		unlink("./archive2.zip");
	if (file_exists("./archive2.tar"))
		unlink("./archive2.tar");

	$phar = new PharData("./archive.tar", 0, null, Phar::TAR);
	$phar->addFromString('out/fileA.txt','The quick brown fox jumped over the lazy dog');
	$phar->compress(Phar::GZ); # Creates archive.tar.gz
	
	//$phar->close();
	
	//unlink($tarArchive);
	
	file_put_contents('phar://archive2.phar/out/fileB.txt', "Colourless green ideas sleep furiously");
	$tarphar = new Phar('archive2.phar');
	$tar = $tarphar->convertToData(Phar::TAR); # Creates archive2.tar
	$zip = $tarphar->convertToData(Phar::ZIP); # Creates archive2.zip
	$tgz = $tarphar->convertToData(Phar::TAR, Phar::GZ, '.tar.gz'); # Creates archive2.tar.gz
	
	/*$tmpFilename = "tmp.".uniqid();
	
	file_put_contents($tmpFilename, 'The quick brown fox jumped over the lazy dog');
	$phar->addFile($tmpFilename,'out/file1');
	unlink($tmpFilename);
	
	file_put_contents($tmpFilename, 'Bananarama');
	$phar->addFile($tmpFilename,'out/file2');
	unlink($tmpFilename);*/
	
	//symlink('./archive.tar', './archive.phar');
	
	/*$context = stream_context_create(array(
        'phar' => array(
                'compress' => Phar::GZ //set compression
        )
	));*/
	
	//file_put_contents('phar://sample.phar/out/file.txt', "Sample Data", 0, $context);
	
	//file_put_contents("phar://./archive.phar/out/file.txt", "The quick brown fox jumped over the lazy dog");
?>
