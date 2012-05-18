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

//$error_msg_lib = 'Required library not found. Please copy include/archiver.inc, modules/system/system.archiver.inc & modules/system/system.tar.inc to __start';

require_once '__start/archiver.inc';// or die($error_msg_lib);
require_once '__start/system.archiver.inc';
require_once '__start/system.tar.inc';

$stage = getStage(); // Stage of the process

$drupal_version = '7.14';

$drupalPath = 'drupal-'.$drupal_version.'/';
$headerMsg = '';

switch ($stage) {
	case 1:

		// TODO: cookie case

		// Processing drupal
		$initInstall = step1processDrupal();
		$headerMsg = "INSTALL DRUPAL\n\n";
		if ($drupalPath = $initInstall['path']) {
			
			// Taking care of cookie issue
			rename($drupalPath.'misc/jquery.cookie.js', $drupalPath.'misc/jquery_cookie.js');
			
			// get contents of a file into a string
			$filename = $drupalPath . 'modules/system/system.module';
			$handle = fopen($filename, "r");
			$contents = fread($handle, filesize($filename));
			fclose($handle);
			unlink($filename);
			
			$contents = str_replace('jquery.cookie.js', 'jquery_cookie.js', $contents);
			
			$fp = fopen($filename, 'w');
			fwrite($fp, $contents);
			fclose($fp);
			$headerMsg .= $initInstall['message'] . "\nTaking care of cookie issue\n";
			
			//.htacess file
			$request = str_replace('/__start.php', '', $_SERVER["REQUEST_URI"]);
			//$params = explode('/', $request);
			if ( ($request != '') && ($request != '/')
			) {
				$request = ($request[strlen($request)-1] == '/')?substr($request, 0, strlen($request)-1):$request;
				$request = ($request[0] == '/')?$request:'/'.$request;
				
				$filename = $drupalPath . '.htaccess';
				$handle = fopen($filename, "r");
				$contents = fread($handle, filesize($filename));
				fclose($handle);
				unlink($filename);
				
				$contents = str_replace('  # RewriteBase /drupal', '  RewriteBase '.$request, $contents);
				
				$fp = fopen($filename, 'w');
				fwrite($fp, $contents);
				fclose($fp);
				$headerMsg .= "\nTake care of .htaccess file\n";
			}
			
			buildForm($stage, $headerMsg, 'Libraries');
		}
		else {
			print '<pre>' .$initInstall['message'] . '</pre>';
			exit;
		}

		break;
	case 2:

		// Processing libraries
		$headerMsg = "INSTALL LIBRARIES\n\n";
		$headerMsg .= step2processLibraries($drupalPath, 'http://download.cksource.com/CKEditor/CKEditor/CKEditor%203.6.3/ckeditor_3.6.3.zip', 'ckeditor_3.6.3.zip');
		$headerMsg .= step2processLibraries($drupalPath, 'http://css3pie.com/download-latest', 'PIE-1.0.0.zip', 'PIE');

		buildForm($stage, $headerMsg, 'Themes');
		break;
	case 3:

		// TODO: Create subtheme http://drupal.org/node/1298682

		// Processing themes
		$headerMsg = "INSTALL THEMES\n\n";
		$headerMsg .= step2processThemes($drupalPath, 'http://ftp.drupal.org/files/projects/omega-7.x-3.1.zip', 'omega-7.x-3.1.zip');
		
		// Create subtheme
		if (isset($_POST['subtheme-name'])) {
			$src = $drupalPath . 'sites/all/themes/omega/starterkits/omega-html5';
			$dst = $drupalPath . 'sites/all/themes/' . $_POST['subtheme-name'];
			rcopy($src, $dst);
			$headerMsg .= "\nOmega subtheme copied\n";
			
			//rename ($dst . '/starterkit_omega_html5.info', $dst . '/'.$_POST['subtheme-name'].'.info');
			

			// get contents of a file into a string
			$filename = $dst . '/starterkit_omega_html5.info';
			$handle = fopen($filename, "r");
			$contents = fread($handle, filesize($filename));
			fclose($handle);
			unlink($filename);
			
			$theme_origin = 'name = Omega HTML5 Starterkit
description = Default starterkit for <a href="http://drupal.org/project/omega">Omega</a>. You should not directly edit this starterkit, but make your own copy. Information on this can be found in the <a href="http://himer.us/omega-docs">Omega Documentation</a>
core = 7.x
engine = phptemplate
screenshot = screenshot.png
base theme = omega

; IMPORTANT: DELETE THESE TWO LINES IN YOUR SUBTHEME

hidden = TRUE
starterkit = TRUE';

			$theme_new = 'name = iFactory Omega HTML5 Theme
description = iFactory default theme based on <a href="http://drupal.org/project/omega">Omega</a> HTML5 subtheme. More information on this can be found in the <a href="http://himer.us/omega-docs">Omega Documentation</a>
core = 7.x
engine = phptemplate
screenshot = screenshot.png
base theme = omega';
			
			$contents = str_replace($theme_origin, $theme_new, $contents);
			
			$fp = fopen($dst . '/'.$_POST['subtheme-name'].'.info', 'w');
			fwrite($fp, $contents);
			fclose($fp);
			$headerMsg .= "\nSubtheme info file generated\n";
			
			// CSS renaming
			$cssdst = $dst . '/css';
			$files = scandir($cssdst);
			foreach ($files as $file) {
				if (strpos($file, 'YOURTHEME') !== false) { 
					rename("$cssdst/$file", str_replace('YOURTHEME', $_POST['subtheme-name'], "$dst/$file"));
				}
			}
			$headerMsg .= "\nSubtheme CSS files renamed\n";
		}
		
		// Instalation parameters
		if (isset($_POST['inst-option-default'])) {
			$startFld = $drupalPath . 'profiles/faultstart';
			//mkdir($startFld, 0755);
			if (mkdir($startFld, 0755)) {
			
				$filesToRead = array(
					//array('url' => 'https://raw.github.com/VladimirAus/DruInit/master/__start.php', 'file' => '__start.php'),
					array('url' => 'https://raw.github.com/VladimirAus/DruInit/master/faultstart/faultstart.install', 'file' => $startFld . '/faultstart.install'),
					array('url' => 'https://raw.github.com/VladimirAus/DruInit/master/faultstart/faultstart.info', 'file' => $startFld . '/faultstart.info'),
					array('url' => 'https://raw.github.com/VladimirAus/DruInit/master/faultstart/faultstart.profile', 'file' => $startFld . '/faultstart.profile'),
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
					fclose($fp);
				}
			}
			$headerMsg .= "\nDefault installation profile installed\n";
			
			// Setup default theme
			$filename = $startFld . '/faultstart.install';
			$handle = fopen($filename, "r");
			$contents = fread($handle, filesize($filename));
			fclose($handle);
			unlink($filename);
			
			$contents = str_replace('$default_theme = variable_get(\'theme_default\', \'bartik\');', 
									'$default_theme = variable_get(\'theme_default\', \''.$_POST['subtheme-name'].'\');', $contents);
			
			$fp = fopen($filename, 'w');
			fwrite($fp, $contents);
			fclose($fp);
			$headerMsg .= "Setting default theme in installation profile\n";
			
		}
		
		buildForm($stage, $headerMsg, 'Modules');
		break;
	case 4:

		// Processing modules
		$headerMsg = "INSTALL MODULES\n\n";
		
		// TODO: check that all files are zip
		$modules = array(
					// Libraries
					'http://ftp.drupal.org/files/projects/libraries-7.x-1.0.zip', 
					'http://ftp.drupal.org/files/projects/ckeditor-7.x-1.9.zip', 
					'http://ftp.drupal.org/files/projects/css3pie-7.x-2.1.zip', 
					'http://ftp.drupal.org/files/projects/fontyourface-7.x-2.2.zip', 
					// Structure
					'http://ftp.drupal.org/files/projects/webform-7.x-3.18.zip',  
					'http://ftp.drupal.org/files/projects/ctools-7.x-1.0.zip',
					'http://ftp.drupal.org/files/projects/views-7.x-3.3.zip', 
					'http://ftp.drupal.org/files/projects/views_bulk_operations-7.x-3.0-rc1.zip', 
					'http://ftp.drupal.org/files/projects/views_slideshow-7.x-3.0.zip',
					'http://ftp.drupal.org/files/projects/entity-7.x-1.0-rc2.zip', 
					'http://ftp.drupal.org/files/projects/entityreference-7.x-1.0-rc1.zip', 
					// Fields
					'http://ftp.drupal.org/files/projects/date-7.x-2.5.zip', 
					'http://ftp.drupal.org/files/projects/addressfield-7.x-1.0-beta2.zip',
					//'http://ftp.drupal.org/files/projects/views_php-7.x-1.x-dev.zip', // Safe to use but try not to use it
					// Configuration
					'http://ftp.drupal.org/files/projects/admin_menu-7.x-3.0-rc3.zip',
					'http://ftp.drupal.org/files/projects/token-7.x-1.1.zip',
					'http://ftp.drupal.org/files/projects/pathauto-7.x-1.1.zip',
					'http://ftp.drupal.org/files/projects/node_clone-7.x-1.0-beta1.zip',
					'http://ftp.drupal.org/files/projects/logintoboggan-7.x-1.3.zip',
					'http://ftp.drupal.org/files/projects/globalredirect-7.x-1.4.zip',
					'http://ftp.drupal.org/files/projects/print-7.x-1.0.zip',
					'http://ftp.drupal.org/files/projects/features-7.x-1.0-rc2.zip',
					// Permissions
					'http://ftp.drupal.org/files/projects/override_node_options-7.x-1.12.zip',
					'http://ftp.drupal.org/files/projects/field_permissions-7.x-1.0-beta2.zip',
					// Development & support
					'http://ftp.drupal.org/files/projects/devel-7.x-1.2.zip',
					'http://ftp.drupal.org/files/projects/backup_migrate-7.x-2.3.zip',
					'http://ftp.drupal.org/files/projects/css_injector-7.x-1.7.zip',
					'http://ftp.drupal.org/files/projects/js_injector-7.x-1.x-dev.zip',
					'http://ftp.drupal.org/files/projects/nice_menus-7.x-2.1.zip',
					// SEO
					'http://ftp.drupal.org/files/projects/google_analytics-7.x-1.2.zip',
					'http://ftp.drupal.org/files/projects/metatag-7.x-1.0-alpha6.zip',
					'http://ftp.drupal.org/files/projects/xmlsitemap-7.x-2.0-rc1.zip',
					// Theme support
					'http://ftp.drupal.org/files/projects/context-7.x-3.0-beta2.zip',
					'http://ftp.drupal.org/files/projects/delta-7.x-3.0-beta9.zip',
					'http://ftp.drupal.org/files/projects/omega_tools-7.x-3.0-rc4.zip',
					);

		foreach ($modules as $module) {
			$filename = explode('/', $module);
			$headerMsg .= step2processModules($drupalPath, $module, $filename[count($filename) - 1]);
		}
		
		buildForm($stage, $headerMsg, '');
		break;
	case 5:
		rrmdir('__start');
		rcopy($drupalPath, '');
		rrmdir($drupalPath);
		
		// Redirect to installation
		$pageURL = str_replace('/__start.php', '', 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
		header("Location: " . $pageURL);
		break;
}


////////////////////////
// FUNCTIONS

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

function step1processDrupal() {
	global $drupal_version;
	
	$result = array('path' => '', 'message' => '');
	
	$archZipFilename = 'drupal-'.$drupal_version.'.tar.gz';
	$archTarFilename = 'drupal-'.$drupal_version.'.tar';
	$drupalPath = '';
	
	if (!file_exists($archZipFilename)) {
		// Download file from internet. Might not always work as require more than 30 sec
		if (file_put_contents($archZipFilename, file_get_contents('http://ftp.drupal.org/files/projects/drupal-'.$drupal_version.'.tar.gz')) !== false) {
			$result['message'] .= date('Y-m-d H:i:s') . "\n";
			$result['message'] .= 'Drupal '.$drupal_version.' downloaded'."\n";
		}
		else {
			$result['message'] .= "Error: failed to download Drupal\n";
			$result['path'] = false;
			return $result;
		}
	}
	else {
		print "Notice: ".$archZipFilename." detected\n";
	}
	
	// Uncompressing
		
	file_put_contents($archTarFilename, file_get_contents("compress.zlib://" . $archZipFilename)); // get tar
	//file_put_contents('/', file_get_contents('compress.zlib://drupal-'.$drupal_version.'.tar'));
	//system('tar -xvwzf drupal-'.$drupal_version.'.tar.gz');
	//exec('rm /drupal-'.$drupal_version.'.tar.gz');
	//exec('rm drupal-'.$drupal_version.'.tar');
	
	// Data from update.manager.inc
	// TODO:: might just call update_manager_archive_extract()
	
	
	$archiver = new ArchiverTar($archTarFilename);
	if (!$archiver) {
		//print (t());
		$result['message'] .= 'Cannot extract '.$file.' not a valid archive.';
		$result['path'] = false;
		return $result;
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
	
		$result['message'] .= "Drupal extraxted...\n";
	}
	else
	{
		$result['message'] .= "Drupal install exists...\n";
		return $result;
	}	
	
	if (file_exists(unlink($archZipFilename))) {	
		unlink($archZipFilename);
	}
	if (file_exists(unlink($archTarFilename))) {
		unlink($archTarFilename);
	}
	
	$result['path'] = $drupalPath;
	return $result;
}
	
//////////////////////
// CK Editor download
//////////////////////

function step2processLibraries($drupalPath = 'drupal-7.14/',
						$ckzip = 'http://download.cksource.com/CKEditor/CKEditor/CKEditor%203.6.2/ckeditor_3.6.2.zip', 
						$ckzipFile = 'ckeditor_3.6.2.zip', $extractFolder = '') {
							
		return step2process($drupalPath, $ckzip, $ckzipFile, $extractFolder, 'libraries/');
	
}

function step2processModules($drupalPath = 'drupal-7.14/',
						$ckzip = 'http://ftp.drupal.org/files/projects/ckeditor-7.x-1.8.zip', 
						$ckzipFile = 'ckeditor-7.x-1.8.zip', $extractFolder = '') {
							
		return step2process($drupalPath, $ckzip, $ckzipFile, $extractFolder, 'modules/');
	
}

function step2processThemes($drupalPath = 'drupal-7.14/',
						$ckzip = 'http://ftp.drupal.org/files/projects/omega-7.x-3.1.zip', 
						$ckzipFile = 'omega-7.x-3.1.zip', $extractFolder = '') {
							
		return step2process($drupalPath, $ckzip, $ckzipFile, $extractFolder, 'themes/');
	
}

function step2process($drupalPath, $ckzip, $ckzipFile, $extractFolder, $mainSitesFolder) {
	
	$result = '';
	if (file_put_contents($ckzipFile, file_get_contents($ckzip)) !== false) {
		$result .= date('Y-m-d H:i:s') . "\n";
		$result .= "File [".$ckzipFile."] downloaded\n";
		
		$archiver = new ArchiverZip($ckzipFile);
		if (!$archiver) {
			$result .=  'Cannot extract '.$file.', not a valid archive.';
		}
	
		$directory = $drupalPath . 'sites/all/' . $mainSitesFolder;
		if (!file_exists($directory)) {
			mkdir($directory);
		}
		if (!file_exists($directory.$extractFolder)) {
			mkdir($directory.$extractFolder);
		}
		$files = $archiver->listContents();
		$archiver->extract($directory.$extractFolder);
		//$archiver->getArchive()->extractModify($directory, 'ckeditor/_samples');
		
		unlink($ckzipFile);
		
		$result .=  "Library [".$ckzipFile."] extraxted...\n";
	}
	
	return $result;
}

function getStage() {
	$stage = 1;
	if (isset($_POST['stage'])) {
		$stage = $_POST['stage'] + 1;
	}
	return $stage;
}

function buildForm($stage, $result, $stepNext) {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Installing Drupal: stage <?php print $stage; ?></title>
</head>

<body>
<form id="form-stage" name="form-stage" method="post" action="">
	<input name="stage" type="hidden" value="<?php print $stage; ?>" />
    <? if ($stage == 2):?>
	    <label for="subtheme-name">Omega theme name:</label>
		<input type="text" name="subtheme-name" id="subtheme-name" value="ifd7demo" /><br />
        <label for="subtheme-name">Select intall options:</label><br />
		<input type="checkbox" name="inst-option-default" id="inst-option-default" value="1" checked /> Default iFactory installation profile<br />
        More options TBA<br />
        <br />
	<? endif; ?>
	<? if ($stage == 4):?>
		<input type="submit" name="submit-stage" id="submit-stage" value="Finish" /><br />
	<? else:?>
		<input type="submit" name="submit-stage" id="submit-stage" value="Install <?php print $stepNext; ?>" /><br />
	<? endif; ?>
	<textarea name="console" cols="100" rows="40"><?php print $result; ?></textarea><br />
</form>
</body>
</html>
<?php
}

/* functions from http://www.php.net/manual/en/function.copy.php#104020 */
function rrmdir($dir) {
	if (is_dir($dir)) {
		$files = scandir($dir);
		foreach ($files as $file)
			if ($file != "." && $file != "..") rrmdir("$dir/$file");
		rmdir($dir);
	}
	else if (file_exists($dir)) unlink($dir);
}

// copies files and non-empty directories
function rcopy($src, $dst) {
	if (file_exists($dst)) rrmdir($dst);
	if (is_dir($src)) {
		if (strlen($dst))
			mkdir($dst);
		$files = scandir($src);
		foreach ($files as $file)
		if ($file != "." && $file != "..") 
			rcopy("$src/$file", strlen($dst)?"$dst/$file":"$file");
	}
	else if (file_exists($src)) copy($src, $dst);
}