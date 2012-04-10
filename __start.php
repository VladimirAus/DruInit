<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// TODO:: maybe extract them from archive
/*
require_once '__start/database.inc';
require_once '__start/cache.inc';
require_once '__start/bootstrap.inc';
require_once '__start/module.inc';
require_once '__start/file.inc';
require_once '__start/common.inc';

require_once '__start/stream_wrappers.inc';
require_once '__start/archiver.inc';
*/
require_once '__start/archiver.inc';
require_once '__start/system.archiver.inc';
require_once '__start/system.tar.inc';

function drupal_mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
  if (!isset($mode)) {
    $mode = variable_get('file_chmod_directory', 0775);
  }

  if (!isset($context)) {
    return mkdir($uri, $mode, $recursive);
  }
  else {
    return mkdir($uri, $mode, $recursive, $context);
  }
}

function drupal_unlink($uri, $context = NULL) {
  $scheme = file_uri_scheme($uri);
  if ((!$scheme || !file_stream_wrapper_valid_scheme($scheme)) && (substr(PHP_OS, 0, 3) == 'WIN')) {
    chmod($uri, 0600);
  }
  if ($context) {
    return unlink($uri, $context);
  }
  else {
    return unlink($uri);
  }
}

$archZipFilename = 'drupal-7.12.tar.gz';
$archTarFilename = 'drupal-7.12.tar';
$drupalPath = '';

if (file_put_contents($archZipFilename, file_get_contents('http://ftp.drupal.org/files/projects/drupal-7.12.tar.gz')) !== false) {
	print date('Y-m-d H:i:s') . "<br />";
	print "Drupal 7.12 downloaded<br />";
	
	file_put_contents($archTarFilename, file_get_contents("compress.zlib://" . $archZipFilename)); // get tar
	//file_put_contents('/', file_get_contents("compress.zlib://drupal-7.12.tar"));
	//system('tar -xvwzf drupal-7.12.tar.gz');
	//exec('rm /drupal-7.12.tar.gz');
	//exec('rm drupal-7.12.tar');
	
	// Data from update.manager.inc
	// TODO:: might just call update_manager_archive_extract()
	
	
	$archiver = new ArchiverTar($archTarFilename);
	if (!$archiver) {
		print (t('Cannot extract %file, not a valid archive.', array ('%file' => $file)));
	}

	// Remove the directory if it exists, otherwise it might contain a mixture of
	// old files mixed with the new files (e.g. in cases where files were removed
	// from a later release).
	$files = $archiver->listContents();
	$drupalPath = $files[0];
	//print $archiver->getArchive()->extractModify($files[1], $files[0]) . '-<br />';
	
	/*
	print "<pre>";
	var_dump($files);
	print "</pre>";
	*/
	
	// Unfortunately, we can only use the directory name to determine the project
	// name. Some archivers list the first file as the directory (i.e., MODULE/)
	// and others list an actual file (i.e., MODULE/README.TXT).
	//$project = strtok($files[0], '/\\');
	
	
	/*
	$removePath = $files[0];
	for ($i=0; $i < 5; $i++)
	{
		$currentFile = str_replace($removePath, '', $files[$i]);
		if strlen($currentFile > 0)
		{
			if (substr($files[$i], strlen($files[$i]) - 1, 1) == '/') // dir
			{
				mkdir(substr($files[$i], 0, strlen($files[$i]) - 1));
			}
			else // file
			{
				file_put_contents($files[$i], file_get_contents("compress.zlib://" . $archTarFilename . '/' . $files[$i]));
			}
		}
		
	}
	*/

	$directory = '';
	//$extract_location = $directory . '/' . $project;
	$extract_location = $directory . '/';
	
	/*
	if (file_exists($extract_location)) {
		file_unmanaged_delete_recursive($extract_location);
	}
	*/

	//$project = strtok($files[0], '/\\');
	//$archiver->getArchive()->addModify($files, $directory, $project);
	//$archiver->getArchive()->addModify($files, $directory, $project);
	if (!file_exists($drupalPath))
	{
		$archiver->extract($directory);
	//$archiver->getArchive()->extractModify($directory, $files[0]);
	
	//$archiver->tar->extractList($directory, '', $files[0]); // doent work
	
		print "Drupal extraxted...<br />";
	}
	else
	{
		"Drupal install exists...<br />";
	}		
	unlink($archZipFilename);
	unlink($archTarFilename);
	
}
else {
	print "Error: failed to download Drupal<br />";
}

$ckzip = 'http://download.cksource.com/CKEditor/CKEditor/CKEditor%203.6.2/ckeditor_3.6.2.zip';
$ckzipFile = 'ckeditor_3.6.2.zip';
if (file_put_contents($ckzipFile, file_get_contents($ckzip)) !== false) {
	print date('Y-m-d H:i:s') . "<br />";
	print "CKEditor 3.6.2 downloaded<br />";
	
	$archiver = new ArchiverZip($ckzipFile);
	if (!$archiver) {
		print (t('Cannot extract %file, not a valid archive.', array ('%file' => $file)));
	}

	$directory = $drupalPath . 'sites/all/libraries/';
	mkdir($drupalPath . 'sites/all/libraries/');
	$files = $archiver->listContents();
	$archiver->extract($directory);
	//$archiver->getArchive()->extractModify($directory, 'ckeditor/_samples');
	
	unlink($ckzipFile);
	
	print "CKEditor extraxted...<br />";
}