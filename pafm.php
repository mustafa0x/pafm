<?php
/*
	@name:                    PHP AJAX File Manager (PAFM)
	@filename:                pafm.php
	@version:                 1.7 RC2
	@date:                    January 19th, 2013

	@author:                  mustafa
	@website:                 http://mus.tafa.us
	@email:                   mustafa.0x@gmail.com

	@server requirements:     PHP 5
	@browser requirements:    modern browser

	Copyright (C) 2007-2013 mustafa
	This program is free software; you can redistribute it and/or modify it under the terms of the
	GNU General Public License as published by the Free Software Foundation. See COPYING
*/


/*
 * configuration
 */

define('PASSWORD', 'auth');
define('PASSWORD_SALT', 'P5`SU2"6]NALYR}');

/**
 * Local (absolute or relative) path of folder to manage.
 *
 * By default, the directory pafm is in is what is used.
 *
 * Setting this to a path outside of webroot works,
 * but will break URIs.
 *
 * This directive will be ignored if set to an
 * invalid directory.
 *
 */
define('ROOT', '');

/*
 * /configuration
 */


/*
 * bruteforce prevention options
 */
define('BRUTEFORCE_FILE', __DIR__ . '/_pafm_bruteforce');

define('BRUTEFORCE_ATTEMPTS', 5);

/**
 * Attempt limit lockout time
 *
 * @var int unit: Seconds
 */
define('BRUTEFORCE_TIME_LOCK', 15 * 60);

define('AUTHORIZE', true);

/**
 * files larger than this are not editable
 *
 * @var int unit: MegaBytes
 */
define('MaxEditableSize', 1);

/*
 * Makefile
 *   1 -> 0
 */
define('DEV', 1);

define('VERSION', '1.7 RC2');

define('CODEMIRROR_PATH', __DIR__ . '/_cm');

$path = isset($_GET['path']) ? $_GET['path'] : '.';
$pathURL = escape($path);
$pathHTML = htmlspecialchars($path);
$redir = '?path=' . $pathURL;

$codeMirrorModes = array('html', 'md', 'js', 'php', 'css', 'py', 'rb'); //TODO: complete array

$maxUpload = min(return_bytes(ini_get('post_max_size')), return_bytes(ini_get('upload_max_filesize')));
$dirContents = array('folders' => array(), 'files' => array());
$dirCount = array('folders' => 0, 'files' => 0);
$footer = '<a href="http://github.com/mustafa0x/pafm">pafm v'.VERSION.'</a> '
	. 'by <a href="http://mus.tafa.us">mustafa</a>';

/*
 * resource retrieval
 */
$_R_HEADERS = array('js' => 'text/javascript', 'css' => 'text/css', 'png' => 'image/png', 'gif' => 'image/gif');
$_R = array();

if (!DEV && isset($_GET['r'])){
	$r = $_GET['r'];
	$is_image = strpos($r, '.') !== false;
	//TODO: cache headers
	header('Content-Type: ' . $_R_HEADERS[$is_image ? getExt($r) : $r]);
	exit($is_image ? base64_decode($_R[$r]) : $_R[$r]);
}

/*
 * init
 */
$do = isset($_GET['do']) ? $_GET['do'] : null;

if (AUTHORIZE) {
	session_start();
	doAuth();
}

$nonce = isset($_SESSION['nonce']) ? $_SESSION['nonce'] : '';

/*
 * A warning is issued when the timezone is not set.
 */
if (function_exists('date_default_timezone_set'))
	date_default_timezone_set('UTC');
$tz_offset = isset($_SESSION['tz_offset']) ? $_SESSION['tz_offset'] : 0;

/**
 * directory checks and chdir
 */

if (!isNull(ROOT) && is_dir(ROOT))
	chdir(ROOT);

if (!is_dir($path)) {
	if ($path != '.')
		exit(header('Location: ?path=.'));
	else
		echo 'The current directory '.getcwd().' can\'t be read';
}

if (!is_readable($path)) {
	chmod($path, 0755);
	if (!is_readable($path))
		echo 'path (' . $pathHTML . ') can\'t be read';
}

/**
 * perform requested action
 */
if ($do) {
	if (isset($_GET['subject']) && !isNull($_GET['subject'])) {
		$subject = str_replace('/', null, $_GET['subject']);
		$subjectURL = escape($subject);
		$subjectHTML = htmlspecialchars($subject);
	}

	switch ($do) {
		case 'login':
			exit(doLogin());
		case 'logout':
			exit(doLogout());
		case 'shell':
			nonce_check();
			exit(shell_exec($_POST['cmd']));
		case 'create':
			nonce_check();
			exit(doCreate($_POST['f_name'], $_GET['f_type'], $path));
		case 'upload':
			nonce_check();
			exit(doUpload($path));
		case 'chmod':
			nonce_check();
			exit(doChmod($subject, $path, $_POST['mod']));
		case 'extract':
			nonce_check();
			exit(doExtract($subject, $path));
		case 'readFile':
			exit(doReadFile($subject, $path));
		case 'rename':
			nonce_check();
			exit(doRename($subject, $path));
		case 'delete':
			nonce_check();
			exit(doDelete($subject, $path));
		case 'saveEdit':
			nonce_check();
			exit(doSaveEdit($subject, $path));
		case 'copy':
			nonce_check();
			exit(doCopy($subject, $path));
		case 'move':
			nonce_check();
			exit(doMove($subject, $path));
		case 'moveList':
			exit(moveList($subject, $path));
		case 'installCodeMirror':
			exit(installCodeMirror());
		case 'fileExists':
			exit(file_exists($path .'/'. $subject));
		case 'getfs':
			exit(getFs($path .'/'. $subject));
		case 'remoteCopy':
			nonce_check();
			exit(doRemoteCopy($path));
	}
}

/**
 * no action; list current directory
 */
getDirContents($path);

/**
 * helper functions
 */

/**
 * @return bool returns true if any empty values are passed
 */
function isNull() {
	foreach (func_get_args() as $value)
		if (!strlen($value))
			return true;
	return false;
}
function zipSupport(){
	if (function_exists('zip_open'))
		return 'function';
	if (class_exists('ZipArchive'))
		return 'class';
	if (strpos(PHP_OS, 'WIN') === false && @shell_exec('unzip'))
		return 'exec';
	return false;
}
function escape($uri){
	return str_replace('%2F', '/', rawurlencode($uri));
}
function removeQuotes($subject, $single = true, $double = true) {
	if ($single)
		$subject = str_replace('\'', null, $subject);
	if ($double)
		$subject = str_replace('"', null, $subject);
	return $subject;
}
function return_bytes($val) { //for upload. http://php.net/ini_get
    $val = trim($val);
    $last = strtolower($val{strlen($val)-1});
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}
function getExt($file){
	return strrpos($file, '.') ? strtolower(substr($file, strrpos($file, '.') + 1)) : '&lt;&gt;';
}
function getMod($subject){
	return substr(sprintf('%o', fileperms($subject)), -4);
}
function redirect(){
	global $redir;
	@header('Location: ' . $redir);
}
function refresh($message, $speed = 2){
	global $redir;
	return '<meta http-equiv="refresh" content="'.$speed.';url='.$redir.'">'.$message;
}
function getFs($file, $hr = true){
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		if (class_exists("COM")) {
			$fsobj = new COM('Scripting.FileSystemObject');
			$f = $fsobj->GetFile(realpath($file));
			$size = $f->Size;
		} else {
			$size = trim(exec("for %F in (\"" . $file . "\") do @echo %~zF"));
		}
	} elseif (PHP_OS == 'Darwin') {
		$size = trim(shell_exec("stat -f %z " . escapeshellarg($file)));
	} elseif ((PHP_OS == 'Linux') || (PHP_OS == 'FreeBSD') || (PHP_OS == 'Unix') || (PHP_OS == 'SunOS')) {
		$size = trim(shell_exec("stat -c%s " . escapeshellarg($file)));
	} else {
		$size = filesize($file);
    }
	if(!$hr)
		return $size;
	elseif ($size <= 1024)
		return $size.' <b title="Bytes" style="background-color: #B9D4B8">B</b>';
	elseif ($size <= 1048576)
		return round($size/1024, 2).' <b title="KiloBytes" style="background-color: yellow">KB</b>';
	elseif ($size < 1073741824) 
		return round($size/1048576, 2).' <b title="MegaBytes" style="background-color: red">MB</b>';
	else
		return round($size/1073741824, 2).' <b title="GigaBytes" style="background-color: green">GB</b>';
}
function rrd($dir){
	$handle = opendir($dir);
	while (($dirItem = readdir($handle)) !== false) {
		if ($dirItem == '.' || $dirItem == '..')
			continue;
		$path = $dir.'/'.$dirItem;
		is_dir($path) ? rrd($path) : unlink($path);
	}
	closedir($handle);
	return rmdir($dir);
}
function pathCrumbs(){
	global $pathHTML, $pathURL;
	$crumbs = explode('/', $pathHTML);
	$crumbsLink = explode('/', $pathURL);
	$pathSplit = '';
	$crumb = str_replace('/', ' / ', dirname(getcwd())) . ' / ';
	for ($i = 0; $i < count($crumbs); $i++) {
		$slash = $i ? '/' : '';
		$pathSplit .= $slash . $crumbsLink[$i];
		$crumb .= '<a href="?path=' . $pathSplit . '" title="Go to ' . $crumbs[$i] . '">'
			. ($i ? $crumbs[$i] : '<em>'.basename(getcwd()).'</em>') . "</a> /\n";
	}
	return $crumb;
}

//authorize functions
function doAuth(){
	global $do, $pathURL, $footer;
	$pwd = isset($_SESSION['pwd']) ? $_SESSION['pwd'] : '';
	if ($do == 'login' || $do == 'logout')
		return; //TODO: login/logout take place here
	if ($pwd != crypt(PASSWORD, PASSWORD_SALT))
		if ($do)
			exit('Please refresh the page and login');
		else
			exit('<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Log In | pafm</title>
  <style type="text/css">
    body {
        margin: auto;
        max-width: 20em;
        text-align: center;
    }
    form {
        width: 20em;
        position: fixed;
        top: 30%;
    }
    a {
        text-decoration: none;
        color: #B22424;
    }
    a:visited {
        color: #FF2F00;
    }
    a:hover {
        color: #DD836F;
    }
    p {
        margin-top: 7.5em;
        font: italic 12px verdana,arial;
    }
  </style>
</head>
<body>
  <form action="?do=login&amp;path='.$pathURL.'" method="post">
    <fieldset>
      <legend style="text-align: left;">Log in</legend>
      <input type="password" name="pwd" title="Password" autofocus>
      <input type="hidden" value="" id="tz_offset" name="tz_offset">
      <input type="submit" value="&#10003;" title="Log In">
    </fieldset>
    <p>'.$footer.'</p>
  </form>
  <script type="text/javascript">
	document.getElementById("tz_offset").value = (new Date()).getTimezoneOffset() * -60;
  </script>
</body>
</html>');
}
function doLogin(){
	$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
	$bruteforce_file_exists = file_exists(BRUTEFORCE_FILE);

	if ($bruteforce_file_exists){
		$bruteforce_contents = explode('|', file_get_contents(BRUTEFORCE_FILE));
		if ((time() - $bruteforce_contents[0]) < BRUTEFORCE_TIME_LOCK && $bruteforce_contents[1] >= BRUTEFORCE_ATTEMPTS)
				return refresh('Attempt limit reached, please wait: '
					. ($bruteforce_contents[0] + BRUTEFORCE_TIME_LOCK - time()) . ' seconds');
	}

	if ($pwd == PASSWORD){
		$_SESSION['tz_offset'] = intval($_POST['tz_offset']);
		$_SESSION['pwd'] = crypt(PASSWORD, PASSWORD_SALT);
		$_SESSION['nonce'] = crypt(uniqid(), rand());
		$bruteforce_file_exists && unlink(BRUTEFORCE_FILE);
		return redirect();
	}

	$bruteforce_data = time() . '|';
	/**
	 * The second condition, if reached, implies an expired bruteforce lock
	 */
	if (!$bruteforce_file_exists || $bruteforce_contents[1] >= BRUTEFORCE_ATTEMPTS)
		$bruteforce_data .= 1;
	else
		$bruteforce_data .= ++$bruteforce_contents[1];

	file_put_contents(BRUTEFORCE_FILE, $bruteforce_data);
	chmod(BRUTEFORCE_FILE, 0700); //prevent others from viewing
	return refresh('Password is incorrect');
}
function doLogout(){
	session_destroy();
	redirect();
}
function nonce_check(){
	if (AUTHORIZE && $_GET['nonce'] != $_SESSION['nonce'])
		exit(refresh('Invalid nonce, try again.'));
}

//fOp functions
function doCreate($f_name, $f_type, $path){
	if (isNull($f_name))
		return refresh('A filename has not been entered');

	$invalidChars = strpos(PHP_OS, 'WIN') !== false ? '/\\|\/|:|\*|\?|\"|\<|\>|\|/' : '/\//';
	if (preg_match($invalidChars, $f_name))
		return refresh('Filename contains invalid characters');

	if ($f_type == 'file' && !file_exists($path.'/'.$f_name))
		fclose(fopen($path.'/'.$f_name, 'w'));
	elseif ($f_type == 'folder' && !file_exists($path.'/'.$f_name))
		mkdir($path.'/'.$f_name);
	else
		return refresh(htmlspecialchars($f_name).' already exists');
	redirect();
}
function installCodeMirror(){
	mkdir(CODEMIRROR_PATH);
	$cmjs = CODEMIRROR_PATH . '/cm.js';
	$cmcss = CODEMIRROR_PATH . '/cm.css';
	$out = null;

	copy('http://cloud.github.com/downloads/mustafa0x/pafm/_codemirror.js', $cmjs);
	copy('http://cloud.github.com/downloads/mustafa0x/pafm/_codemirror.css', $cmcss);

	/**
	 * avoid using modified CodeMirror files
	 */
	if (md5_file($cmjs) != '65f5ba3c8d38bb08544717fc93c14024')
		$out = unlink($cmjs);
	if (md5_file($cmcss) != '23d441d9125538e3c5d69448f8741bfe')
		$out = unlink($cmcss);

	return $out ? '-' : '';
}
function doUpload($path){
	if (!$_FILES)
		return refresh('$_FILES array can not be read. Check file size limits and the max execution time limit.');

	$uploadErrors = array(null,
		'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		'The uploaded file was only partially uploaded.',
		'No file was uploaded.',
		'Missing a temporary folder.',
		'Failed to write file to disk.',
		'File upload stopped by extension.'
	);
	$error_message = ' Please see <a href="http://www.php.net/file-upload.errors">File Upload Error Messages</a>';

	$fail = false;

	if ($_FILES['file']['error']) {
		if ($uploadErrors[$_FILES['file']['error']])
			return refresh($uploadErrors[$_FILES['file']['error']] . $error_message);
		else
			return refresh('Unknown error occurred.' . $error_message);
	}

	if (!is_file($_FILES['file']['tmp_name']))
		return refresh($_FILES['file']['name'] . ' could not be uploaded.'
			. 'Possible causes could be the <b>post_max_size</b> and <b>memory_limit</b> directives in php.ini.');

	if (!is_uploaded_file($_FILES['file']['tmp_name']))
		return refresh(basename($_FILES['file']['name']) . ' is not a POST-uploaded file');

	if (!move_uploaded_file($_FILES['file']['tmp_name'], $path . '/' . basename($_FILES['file']['name'])))
		$fail = true;

	return $fail ? 'One or more files could not be moved.' : $_FILES['file']['name'] . ' uploaded';
}
function doChmod($subject, $path, $mod){
	if (isNull($mod))
		return refresh('chmod field is empty');

	chmod($path . '/' . $subject, octdec(strlen($mod) == 3 ? 0 . $mod : $mod));
	redirect();
}
function doExtract($subject, $path){
	global $subjectHTML;
	switch (zipSupport()) {
		case 'function':
			if (!is_resource($zip = zip_open($path.'/'.$subject)))
				return refresh($subjectHTML . ' could not be read for extracting');

			while ($zip_entry = zip_read($zip)){
				zip_entry_open($zip, $zip_entry);
				if (substr(zip_entry_name($zip_entry), -1) == '/') {
					$zdir = substr(zip_entry_name($zip_entry), 0, -1);
					if (file_exists($path.'/'.$zdir))
						return refresh(htmlspecialchars($zdir) . ' exists!');
					mkdir($path.'/'.$zdir);
				}
				else {
					if (file_exists($path.'/'.zip_entry_name($zip_entry)))
						return refresh(htmlspecialchars($path.'/'.zip_entry_name($zip_entry)) . ' exists!');

					$fopen = fopen($path.'/'.zip_entry_name($zip_entry), 'w');
					$ze_fs = zip_entry_filesize($zip_entry);
					fwrite($fopen, zip_entry_read($zip_entry, $ze_fs), $ze_fs);
				}
				zip_entry_close($zip_entry);
			}
			zip_close($zip);
			break;
		case 'class':
			$zip = new ZipArchive();
			if ($zip->open($path.'/'.$subject) !== true)
				return refresh($subjectHTML . ' could not be read for extracting');
			$zip->extractTo($path);
			$zip->close();
			break;
		case 'exec':
			shell_exec('unzip ' . escapeshellarg($path.'/'.$subject));
	}
	redirect();
}
function doReadFile($subject, $path){
	return file_get_contents($path.'/'.$subject);
}
function doCopy($subject, $path){
	$to = isset($_POST['to']) ? $_POST['to'] : '';
	$dest = $path.'/'.$to;

	if (isNull($subject, $path, $to))
		return refresh('Values could not be read');

	if (is_dir($path.'/'.$subject)) {
		copyDir($path.'/'.$subject, $dest);
		redirect();
	}

	if (file_exists($dest))
		return refresh('Destination ('.$dest.') exists');

	if(!copy($path.'/'.$subject, $dest))
		return refresh($subject . ' could not be copied to ' . $to);

	redirect();
}
function copyDir($subject, $to){
	if (file_exists($to) || !mkdir($to))
		return refresh('Destination exists or creation of destination failed.');

	$handle = opendir($subject);
	while(($dirItem = readdir($handle)) !== false)  {
		if ($dirItem == '.' || $dirItem == '..')
			continue;

		$path = $subject.'/'.$dirItem;
		if (is_dir($path))
			copyDir($path, $to.'/'.$dirItem);
		else
			copy($path, $to.'/'.$dirItem);
	}

	closedir($handle);
}
function doRemoteCopy($path){
	$location = isset($_POST['location']) ? $_POST['location'] : '';
	$to = isset($_POST['to']) ? $_POST['to'] : '';
	$dest = $path.'/'.$to;

	if (isNull($path, $location, $to))
		return refresh('Values could not be read');

	if (file_exists($dest))
		return refresh('Destination ('.$dest.') exists');

	if(!copy($location, $dest))
		return refresh($location . ' could not be copied to '. ($dest));
	redirect();
}
function doRename($subject, $path){
	$rename = isset($_POST['rename']) ? $_POST['rename'] : '';
	if (isNull($subject, $rename))
		return refresh('Values could not be read');

	if (file_exists($path.'/'.$rename))
		return refresh(htmlspecialchars($rename) . ' exists, please choose another name');

	rename($path.'/'.$subject, $path.'/'.$rename);
	redirect();
}
function doDelete($subject, $path){
	global $subjectHTML;
	$fullPath = $path .'/'. $subject;

	if (isNull($subject, $path))
		return refresh('Values could not be read');
	if (!file_exists($fullPath))
		return refresh($subjectHTML . ' doesn\'t exist');

	if (is_file($fullPath))
		if (!unlink($fullPath))
			return refresh($subjectHTML . ' could not be removed');

	if (is_dir($fullPath))
		if (!rrd($fullPath))
			return refresh($subjectHTML . ' could not be removed');

	redirect();
}
function doSaveEdit($subject, $path){
	global $subjectHTML, $tz_offset;
	$data =	get_magic_quotes_gpc() ? stripslashes($_POST['data']) : $_POST['data'];
	if (!is_file($path .'/'. $subject))
		return 'Error: ' . $subjectHTML . ' is not a valid file';

	if (file_put_contents($path .'/'. $subject, $data) === false)
		return $subject . ' could not be saved';
	else
		return 'saved at ' . date('H:i:s', time() + $tz_offset);
}
function doMove($subject, $path){
	global $pathHTML, $subjectHTML;

	if (isset($_GET['to']) && !isNull($_GET['to'])) {
		$to = $_GET['to'];
		$toHTML = htmlspecialchars($to);
		$toURL = escape($to);
	}
	if (isNull($subject, $path, $to))
		return refresh('Values could not be read');

	if ($path == $to)
		return refresh('The source and destination are the same');

	if (array_search($subject, explode('/', $to)) == array_search($subject, explode('/', $path . '/' . $subject)))
		return refresh($toHTML . ' is a subfolder of ' . $pathHTML);

	if (file_exists($to.'/'.$subject))
		return refresh($subjectHTML . ' exists in ' . $toHTML);

	rename($path . '/' . $subject, $to.'/'.$subject);
	redirect();
}
function moveList($subject, $path){
	global $pathURL, $pathHTML, $subjectURL, $subjectHTML, $nonce;

	if (isset($_GET['to']) && !isNull($_GET['to'])) {
		$to = $_GET['to'];
		$toHTML = htmlspecialchars($to);
		$toURL = escape($to);
	}
	if (isNull($subject, $path, $to))
		return refresh('Values could not be read');

	$return = '["div",
	{attributes: {"id": "movelist"}},
	[
		"span",
		{attributes: {"class": "pathCrumbs"}},
		[
	';
	$crumbs = explode('/', $toHTML);
	$crumbsLink = explode('/', $toURL);
	$pathSplit = '';

	for ($i = 0; $i < count($crumbs); $i++) {
		$slash = $i ? '/' : null;
		$pathSplit .= $slash . $crumbsLink[$i];
		$return .= ($i ? ',' : null) . '"a",
		{
			attributes : {
				"href" : "#",
				"title" : "Go to ' . $crumbs[$i] . '"
			},
			events : {
				click : function(e){
					fOp.moveList("'.$subjectURL.'", "'.$pathURL.'", "'.$pathSplit.'");
					e.preventDefault ? e.preventDefault() : e.returnValue = false;
				}
			},
			text : "' . ($i ? $crumbs[$i] : 'root') . '",
			postText : " / "
		}';
	}

	$return .= '
		],
		"ul",
		{attributes: {"id": "moveListUL"}}';

	$j = 0;
	//TODO: sort output
	$handle = opendir($to);
	while (($dirItem = readdir($handle)) !== false)	{
		$fullPath = $to.'/'.$dirItem;
		if (!is_dir($fullPath) || $dirItem == '.' || $dirItem == '..')
			continue;
		$fullPathURL = escape($fullPath);
		$dirItemHTML = htmlspecialchars($dirItem);
		$return .= ',
	[
		"li",
		{},
		[
			"a",
			{
				attributes : {"href" : "#"},
				events : {
					click : function(e){
						fOp.moveList("'.$subjectURL.'", "'.$pathURL.'", "'.$fullPathURL.'");
						e.preventDefault ? e.preventDefault() : e.returnValue = false;
					}
				}
			},
			["img", {attributes: {"src": "'. (DEV ? 'pafm-files/' : '?r=')
			.'images/odir.png", "title": "Open '.$dirItemHTML.'"}}],
			"a",
			{
				attributes: {"href": "?do=move&subject='.$subjectURL.'&path='.$pathURL.'&to='.$fullPathURL
				.'&nonce='.$nonce.'", "title" : "move '.$subject.' to '.$dirItemHTML.'", "class": "dir"},
				text: "'.$dirItemHTML.'"
			}
		]
	]';
		$j++;
	}
	if (!$j)
		$return .= ',
		"b", {text: "No directories found"},
		"br", {},
		"br", {}';
	$return .= ',
	"a",
	{
		attributes: {"href": "?do=move&subject='.$subjectURL.'&path='.$pathURL.'&to='.$toURL
		.'&nonce='.$nonce.'", "id": "movehere", "title": "move here ('.$toHTML.')"},
		text : "move here"
	}]
]';
	return $return;
}
function getDirContents($path){
	global $dirContents, $dirCount;
	$itemType = '';

	$dirHandle = opendir($path);
	while (($dirItem = readdir($dirHandle)) !== false) {
		if ($dirItem == '.' || $dirItem == '..')
			continue;
		$fullPath = $path.'/'.$dirItem;
		$itemType = is_file($fullPath) ? 'files' : 'folders';
		$dirContents[$itemType][] = $dirItem;
		$dirCount[$itemType]++;
	}
	closedir($dirHandle);
}

/**
 * Output the file list
 */
function getDirs($path){
	global $dirContents, $pathURL, $nonce, $tz_offset;

	if (!count($dirContents['folders']))
		return;

	natcasesort($dirContents['folders']);

	foreach ($dirContents['folders'] as $dirItem){
		$dirItemURL = escape($dirItem);
		$dirItemHTML = htmlspecialchars($dirItem);
		$fullPath = $path.'/'.$dirItem;

		$mtime = filemtime($fullPath);
		$mod = getMod($path.'/'.$dirItem);

		echo '  <li title="' . $dirItemHTML . '">' .
		"\n\t" . '<a href="?path=' . escape($fullPath) . '" title="' . $dirItemHTML . '" class="dir">'.$dirItemHTML.'</a>'.
		"\n\t" . '<span class="filemtime" title="'.date('c', $mtime).'">' . date('y-m-d | H:i:s', $mtime + $tz_offset) . '</span>' .
		"\n\t" . '<span class="mode" title="mode">' . $mod . '</span>' .
		"\n\t" . '<a href="#" title="Chmod '.$dirItemHTML.'" onclick="fOp.chmod(\''.$pathURL.'\', \''.$dirItemURL.'\', \''.$mod.'\'); return false;" class="chmod b"></a>' .
		"\n\t" . '<a href="#" title="Move '.$dirItemHTML.'" onclick="fOp.moveList(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.$pathURL.'\'); return false;" class="move b"></a>' .
		"\n\t" . '<a href="#" title="Copy '.$dirItemHTML.'" onclick="fOp.copy(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.$pathURL.'\'); return false;" class="copy b"></a>' .
		"\n\t" . '<a href="#" title="Rename '.$dirItemHTML.'" onclick="fOp.rename(\''.$dirItemHTML.'\', \''.$pathURL.'\'); return false;" class="rename b"></a>' .
		"\n\t" . '<a href="?do=delete&amp;path='.$pathURL.'&amp;subject='.$dirItemURL.'&amp;nonce=' . $nonce.'" title="Delete '.$dirItemHTML.'" onclick="return confirm(\'Are you sure you want to delete '.removeQuotes($dirItem).'?\');" class="del b"></a>' .
		"\n  </li>\n";
	}
}
function getFiles($path){
	global $dirContents, $pathURL, $codeMirrorModes, $nonce, $tz_offset;
	$filePath = $path == '.' ? '/' : '/' . $path.'/';

	if (!count($dirContents['files']))
		return;

	natcasesort($dirContents['files']);

	$codeMirrorExists = (int)is_dir(CODEMIRROR_PATH);
	$zipSupport = zipSupport();

	foreach ($dirContents['files'] as $dirItem){
		$dirItemURL = escape($dirItem);
		$dirItemHTML = htmlspecialchars($dirItem);
		$fullPath = $path.'/'.$dirItem;

		$mtime = filemtime($fullPath);
		$mod = getMod($fullPath);
		$ext = getExt($dirItem);
		$cmSupport = in_array($ext, $codeMirrorModes) ? 'cp ' : '';

		echo '  <li title="' . $dirItemHTML . '">' .
		"\n\t" . '<a href="' . escape(ROOT . $filePath . $dirItem) . '" title="' . $dirItemHTML . '" class="file">'.$dirItemHTML.'</a>' .
		"\n\t" . '<span class="fs"  title="file size">' . getfs($path.'/'.$dirItem) . '</span>' .
		"\n\t" . '<span class="extension" title="file extension">' . $ext . '</span>' .
		"\n\t" . '<span class="filemtime" title="'.date('c', $mtime).'">' . date('y-m-d | H:i:s', $mtime + $tz_offset) . '</span>' .
		"\n\t" . '<span class="mode" title="mode">' . $mod . '</span>' .
		(($zipSupport && $ext == 'zip')
			? "\n\t" . '<a href="?do=extract&amp;path='.$pathURL.'&amp;subject='.$dirItemURL.'&amp;nonce=' . $nonce.'" title="Extract '.$dirItemHTML.'" class="extract b"></a>'
			: '') .
		(getFs($fullPath, false) <= (1048576 * MaxEditableSize)
			? "\n\t" . '<a href="#" title="Edit '.$dirItemHTML.'" onclick="edit.init(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.$ext.'\', '.$codeMirrorExists.'); return false;" class="edit '.$cmSupport.'b"></a>'
			: '') .
		"\n\t" . '<a href="#" title="Chmod '.$dirItemHTML.'" onclick="fOp.chmod(\''.$pathURL.'\', \''.$dirItemURL.'\', \''.$mod.'\'); return false;" class="chmod b"></a>' .
		"\n\t" . '<a href="#" title="Move '.$dirItemHTML.'" onclick="fOp.moveList(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.$pathURL.'\'); return false;" class="move b"></a>' .
		"\n\t" . '<a href="#" title="Copy '.$dirItemHTML.'" onclick="fOp.copy(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.$pathURL.'\'); return false;" class="copy b"></a>' .
		"\n\t" . '<a href="#" title="Rename '.$dirItemHTML.'" onclick="fOp.rename(\''.$dirItemHTML.'\', \''.$pathURL.'\'); return false;" class="rename b"></a>' .
		"\n\t" . '<a href="?do=delete&amp;path='.$pathURL.'&amp;subject='.$dirItemURL.'&amp;nonce=' . $nonce.'" title="Delete '.$dirItemHTML.'" onclick="return confirm(\'Are you sure you want to delete '.removeQuotes($dirItem).'?\');" class="del b"></a>'.
		"\n  </li>\n";
	}
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?php echo str_replace('www.', '', $_SERVER['HTTP_HOST']); ?> | pafm</title>
  <style type="text/css">@import "<?php echo DEV ? 'pafm-files/style.css' : '?r=css';?>";</style>
  <script type="text/javascript">var nonce = "<?php echo $_SESSION['nonce']; ?>";</script>
  <script src="<?php echo DEV ? 'pafm-files/js.js' : '?r=js';?>" type="text/javascript"></script>
</head>
<body>

<div id="header">
  <?php
	if (AUTHORIZE):
  ?>
  <a href="?do=logout&amp;path=<?php echo $pathURL; ?>" title="logout" id="logout">logout</a>
  <?php
	endif;
  ?>
  <span class="pathCrumbs"><?php echo pathCrumbs(); ?>
    <span id="dir-count">
		folders: <?php echo $dirCount['folders']; ?> | files: <?php echo $dirCount['files']; ?>
    </span>
  </span>
</div>

<div id="dirList">
<ul id="info">
  <li>
    <span id="file">name</span>
    <span class="extension">extension</span>
    <span class="filemtime">last modified</span>
    <span class="mode">mode</span>
    <span class="fs">size</span>
    <span id="fileop">file operations</span>
  </li>
</ul>

<ul>
<?php getDirs($path);?>
</ul>

<ul>
<?php getFiles($path);?>
</ul>
</div>

<div id="add" class="b">
  <a href="#" title="Create File" onclick="fOp.create('file', '<?php echo $pathURL; ?>'); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/addfile.gif" alt="Create File"></a>
  <a href="#" title="Create Folder" onclick="fOp.create('folder', '<?php echo $pathURL; ?>'); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/addfolder.gif" alt="Create Folder"></a>
  <br>
  <a href="#" title="Remote Copy File" onclick="fOp.remoteCopy('<?php echo $pathURL; ?>'); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/remotecopy.png" alt="Remote Copy"></a>
  <a href="#" title="Upload File" onclick="upload.init('<?php echo $pathURL; ?>', <?php echo $maxUpload; ?>); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/upload.gif" alt="Upload File"></a>
  <br>
  <a href="#" title="Open Shell" onclick="shell.init('<?php echo @trim(shell_exec('whoami')); ?>', '<?php echo @trim(shell_exec('pwd')); ?>'); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/terminal.png" alt="Terminal"></a>
</div>

<div id="footer">
  <p><?php echo $footer; ?></p>
  <?php
	if (PASSWORD == 'auth') echo '<span>change your password</span>';
  ?>
</div>

</body>
</html>
