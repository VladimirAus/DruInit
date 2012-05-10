<?php
	
	// 1. Get start file
	// 2.a Create folder
	// 2.b Get Drupal files
	// 3. Redirect
	
	$startFld = '__start';
	//mkdir($startFld, 0755);
	if (mkdir($startFld, 0755)) {
	
		$filesToRead = array(
			array('url' => 'https://raw.github.com/VladimirAus/DruInit/master/__start.php', 'file' => '__start.php'),
			array('url' => 'https://raw.github.com/drupal/drupal/7.x/includes/archiver.inc', 'file' => $startFld . '/archiver.inc'),
			array('url' => 'https://raw.github.com/drupal/drupal/7.x/modules/system/system.archiver.inc', 'file' => $startFld . '/system.archiver.inc'),
			array('url' => 'https://raw.github.com/drupal/drupal/7.x/modules/system/system.tar.inc', 'file' => $startFld . '/system.tar.inc'),
		);
		
		foreach ($filesToRead as $fileToCopy) {
		
			$handle = fopen($fileToCopy['url'], "rb");
			$contents = '';
			while (!feof($handle)) {
			  $contents .= fread($handle, 8192);
			}
			fclose($handle);
			
			$fp = fopen($fileToCopy['file'], 'w');
			fwrite($fp, $contents);
			//fwrite($fp, '23');
			fclose($fp);
		}
	}
		
	header("Location: http://www.go-fest.me/_stage2/__start.php"); /* Redirect browser */
	