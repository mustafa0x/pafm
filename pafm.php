<?php
/*
	@name:                    PHP AJAX File Manager (PAFM)
	@filename:                pafm.php
	@version:                 1.5
	@date:                    April 6th, 2012

	@author:                  mustafa
	@website:                 http://mus.tafa.us
	@email:                   mustafa.0x@gmail.com

	@server requirements:     PHP 4.4+
	@browser requirements:    modern browser

	Copyright (C) 2007-2012 mustafa
	This program is free software; you can redistribute it and/or modify it under the terms of the
	GNU General Public License as published by the Free Software Foundation. See COPYING
*/


/*
 * configuration
 */

define('PASSWORD', 'auth');

/*
 *
 * _relative_ path of root folder to manage.
 *
 * Setting this to a path outside of webroot works,
 * but your URIs will be broken.
 *
 * This directive will be ignored if set to an
 * invalid directory.
 *
 */
define('ROOT', '.');

/*
 * /configuration
 */

define('AUTHORIZE', true);

/*
 * Checks for:
 *  - leading /
 *  - trailing /
 *  - ..
 *  - empty path
 *  - //
 */
define('SanatizePath', true);

/*
 * files larger than this are not editable
 *
 * the unit is mega-bytes
 */
define('MaxEditableSize', 1);

/*
 * Makefile
 *   1 -> 0
 */
define('DEV', 1);

define('VERSION', '1.5');
define('CODEMIRROR_PATH', '_codemirror');

$pathRegEx = SanatizePath ? '/\.\.|\/\/|\/$|^\/|^$/' : '//';

$path = preg_match($pathRegEx, $_GET['path']) ? '.' : $_GET['path'];
$pathURL = escape($path);
$pathHTML = htmlspecialchars($path);

$pafm = basename($_SERVER['SCRIPT_NAME']);
$redir = $pafm . '?path=' . $pathURL; //$pafm is prefixed for safari (still relavent?)

$codeMirrorModes = array("js", "php", "css", "py", "rb"); //TODO: complete array

$maxUpload = min(return_bytes(ini_get('post_max_size')), return_bytes(ini_get('upload_max_filesize')));
$dirContents = array('folders' => array(), 'files' => array());
$footer = '<a href="http://github.com/mustafa0x/pafm" title="pafm @ github">pafm v'.VERSION.'</a> by <a href="http://mus.tafa.us" title="mus.tafa.us">mustafa</a>';

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
	exit($is_image ? base64_decode($_R[$r]) : $_R[$r]); //security concern?
}

/*
 * init
 */
$do = $_GET['do'];

if (AUTHORIZE) {
	session_start();
	doAuth();
}

/** directory checks and chdir **/

if (is_dir(ROOT))
	chdir(ROOT);

if (!is_dir($path)) {
	if ($path != '.') {
		header('Location: ?path=.');
		exit();
	}
	else
		echo 'path (' . $pathHTML . ') can\'t be read'; //exit shouldn't be necessary
}

if(!is_readable($path)) {
	chmod($path, 0777);
	if (!is_readable($path))
		echo 'path (' . $pathHTML . ') can\'t be read';
}

/** clean variables **/
if (!isNull($_GET['subject'])) {
	$subject = str_replace('/', null, $_GET['subject']);
	$subjectURL = escape($subject);
	$subjectHTML = htmlspecialchars($subject);
}

if (!isNull($_GET['to'])) {
	$to = preg_match($pathRegEx, $_GET['to']) ? null : $_GET['to'];
	$toHTML = htmlspecialchars($to);
	$toURL = escape($to);
}

/** perform requested action **/
if ($do) {
	switch ($do) {
		case 'login':
			exit(doLogin($_POST['pwd']));
		case 'create':
			exit(doCreate($_POST['file'], $_POST['folder'], $path));
		case 'upload':
			exit(doUpload($path));
		case 'chmod':
			exit(doChmod($subject, $path, $_POST['mod']));
		case 'extract':
			exit(doExtract($subject, $path));
		case 'readFile':
			exit(doReadFile($subject, $path));
		case 'rename':
			exit(doRename($subject, $path));
		case 'delete':
			exit(doDelete($subject, $path));
		case 'saveEdit':
			exit(doSaveEdit($subject, $path));
		case 'copy':
			exit(doCopy($subject, $path));
		case 'move':
			exit(doMove($subject, $path));
		case 'moveList':
			exit(moveList($subject, $path, $to));
		case 'installCodeMirror':
			exit(installCodeMirror());
		case 'fileExists':
			exit(file_exists($path .'/'. $subject));
		case 'getfs':
			exit(getFs($path .'/'. $subject));
		case 'remoteCopy':
			exit(doRemoteCopy($path));
		case 'logout':
			exit(doLogout());
	}
}

/** no action; list current directory **/
getDirContents($path);

// helper functions
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
function getFs($file){
	if (filesize($file) <= 1024)
		return filesize($file).' <b title="Bytes" style="background-color: #B9D4B8">B</b>';
	elseif (filesize($file) <= 1024000)
		return round(filesize($file)/1024, 2).' <b title="KiloBytes" style="background-color: yellow">KB</b>';
	else
		return round(filesize($file)/1024000, 2).' <b title="MegaBytes" style="background-color: red">MB</b>';
}
function rrd($dir){
	$handle = opendir($dir);
	while (($dirItem = readdir($handle)) !== false) {
		if($dirItem == '.' || $dirItem == '..')
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
	for ($i = 0; $i < count($crumbs); $i++) {
		$slash = $i ? '/' : null;
		$pathSplit .= $slash . escape($crumbs[$i]);
		$crumb .= '<a href="?path=' . $pathSplit . '" title="Go to ' . $crumbs[$i] . '">' . ($i === 0 ? '<em>root</em>' : $crumbs[$i]) . '</a> /' . "\n";
	}
	return $crumb;
}
/*
 * TODO: rewrite entire function
 *
 * FIXME: pulling from remote server has risks
 */
function installCodeMirror(){
	global $path;
	$name = 'codemirror2-latest.zip';
	copy('http://codemirror.net/'.$name, $name);
	doExtract($name, $path, true);
	rename($path . '/CodeMirror2', $path . '/' . CODEMIRROR_PATH);
}

//authorize functions
function doAuth(){
	global $do, $pathURL, $footer;
	if ($do == 'login' || $do == 'logout')
		return;
	if ($do && $_SESSION['pwd'] != PASSWORD)
		exit('Please refresh the page and login');
	if ($_SESSION['pwd'] != PASSWORD)
		exit ('<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>Log In | pafm</title>
  <style type="text/css">
    /*<![CDATA[*/
    form {
    	position: absolute;
    	left: 50%;
    	top: 45%;
    	margin-left: -6.50em;
    	margin-top: -1em;
    	text-align: center;
    	font-family: calibri;
    }
    a {
    	text-decoration: none;
    	font-style: italic;
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
    }
    /*]]>*/
  </style>
  <script type="text/javascript">
    onload = function(){
    	var pwd = document.getElementsByName("pwd")[0];
    	pwd.focus();
    };
  </script>
</head>
<body>
  <form action="?do=login&amp;path='. $pathURL .'" method="post">
    <fieldset>
      <legend style="text-align: left;">Log in</legend>
      <input type="password" name="pwd" title="Password">
      <input type="submit" value="&#10003;" title="Log In">
    </fieldset>
    <p>'.$footer.'</p>
  </form>
</body>
</html>');
}
function doLogin($pwd){
	if ($pwd == PASSWORD)
		$_SESSION['pwd'] = PASSWORD;
	else
		return refresh('Password is incorrect');
	redirect();
}
function doLogout(){
	session_destroy();
	redirect();
}

//fOp functions
function doCreate($file, $folder, $path){
	if (isNull($file) && isNull($folder))
		return refresh('A filename has not been entered');

	$invalidChars = strpos(PHP_OS, 'WIN') !== false ? '/\\|\/|:|\*|\?|\"|\<|\>|\|/' : '/\//';
	if (preg_match($invalidChars, $file ? $file : $folder))
		return refresh('Filename contains invalid characters');

	if (!isNull($file) && !file_exists($path.'/'.$file))
		fclose(fopen($path.'/'.$file, 'w'));
	elseif (!isNull($folder) && !file_exists($path.'/'.$folder))
		mkdir($path.'/'.$folder);
	else
		return refresh(htmlspecialchars($file).htmlspecialchars($folder).' already exists');
	redirect();
}
function doUpload($path){
	if (!$_FILES)
		return refresh('$_FILES array can not be read. Check file size limits and the max execution time limit.');
	$uploadErrors = array(null, 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'The uploaded file was only partially uploaded.', 'No file was uploaded.', 'Missing a temporary folder.', 'Failed to write file to disk.', 'File upload stopped by extension.');
	$l = count($_FILES['file']['name']);
	for ($i = 0; $i < $l; $i++) { //FIXME: this loop is no longer relavent
		if ($_FILES['file']['error'][$i]) {
			if ($uploadErrors[$_FILES['file']['error'][$i]])
				return refresh($uploadErrors[$_FILES['file']['error'][$i]] . ' Please see <a href="http://www.php.net/file-upload.errors">File Upload Error Messages</a>');
			else
				return refresh('Unknown error occurred. Please see <a href="http://www.php.net/file-upload.errors">File Upload Error Messages</a>');
		}

		if (!is_file($_FILES['file']['tmp_name'][$i]))
			return refresh($_FILES['file']['name'][$i] . ' could not be uploaded. Possible causes could be the <b>post_max_size</b> and <b>memory_limit</b> directives in php.ini.');

		if (!is_uploaded_file($_FILES['file']['tmp_name'][$i]))
			return refresh(basename($_FILES['file']['name'][$i]) . ' is not a POST-uploaded file');

		if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $path . '/' . basename($_FILES['file']['name'][$i])))
			$fail = true;
	}
	return $fail ? 'One or more files could not be moved.' : $_FILES['file']['name'][0] . ' uploaded';
}
function doChmod($subject, $path, $mod){
	if (isNull($mod))
		return refresh('chmod field is empty');

	chmod($path . '/' . $subject, octdec(strlen($mod) == 3 ? 0 . $mod : $mod));
	redirect();
}
function doExtract($subject, $path, $codeMirror){
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
					$fopen = fopen($path.'/'.zip_entry_name($zip_entry), "w");
					//TODO: file-exists check
					fwrite($fopen, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)), zip_entry_filesize($zip_entry));
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
	$codeMirror || redirect();
}
function doReadFile($subject, $path){
	return file_get_contents($path.'/'.$subject);
}
function doCopy($subject, $path){
	if (isNull($subject, $path))
		return refresh('Values could not be read');
	$name = $_POST['name'];
	//TODO: more var checks

	if(!copy($path . '/' . $subject, $path.'/'.$name))
		return refresh($subject.' could not be copied to '.$name);
	redirect();
}
function doRemoteCopy($path){
	$location = $_POST['location'];
	$name = $_POST['name'];
	if (isNull($path, $location, $name))
		return refresh('Values could not be read');

	if(!copy($location, $path.'/'.$name)) //TODO: more checks of what location is
		return refresh($location . ' could not be copied to '. ($path . '/' . $name));
	redirect();
}
function doMove($subject, $path){
	global $pathHTML, $subjectHTML, $to, $toHTML;
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
function doRename($subject, $path){
	$rename = $_POST['rename'];
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
	global $subjectHTML;
	$data =	get_magic_quotes_gpc() ? stripslashes($_POST['data']) : $_POST['data'];
	if (!is_file($path .'/'. $subject))
		return 'Error: ' . $subjectHTML . ' is not a valid file';
	if (isNull($data))
		return 'Error: There is nothing to save';

 	if (!($openf = fopen($path .'/'. $subject, 'w')))
		return $subject . ' could not be opened';
	fwrite($openf, $data);
	fclose($openf);
	return 'Saved';
}
function moveList($subject, $path){
	global $pathURL, $pathHTML, $subjectURL, $subjectHTML, $to, $toURL, $toHTML;
	if (isNull($subject, $path, $to))
		return refresh('Values could not be read');

	$return = '["div",
	{
		attributes : {
			"id" : "movelist"
		}
	},
	[
		"span",
		{
			attributes : {
				"class" : "pathCrumbs"
			}
		},
		[
	';
	$crumbs = explode('/', $toHTML);
	$crumbsLink = explode('/', $toURL);
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
		{
			attributes : {
				"id" : "moveListUL"
			}
		}';

	$j = 0;
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
				attributes : {
					"href" : "#"
				},
				events : {
					click : function(e){
						fOp.moveList("'.$subjectURL.'", "'.$pathURL.'", "'.$fullPathURL.'");
						e.preventDefault ? e.preventDefault() : e.returnValue = false;
					}
				}
			},
			[
				"img",
				{
					attributes : {
						"src" : "'. (DEV ? 'pafm-files/' : '?r=') .'images/odir.png",
						"title" : "Open '.$dirItemHTML.'"
					}
				}
			],
			"a",
			{
				attributes : {
					"href" : "?do=move&subject='.$subjectURL.'&path='.$pathURL.'&to='.$fullPathURL.'",
					"title" : "move '.$subject.' to '.$dirItemHTML.'",
					"class" : "dir"
				},
				text : "'.$dirItemHTML.'"
			}
		]
	]';
		$j++;
	}
	if (!$j)
		$return .= ',
		"b",
		{
			text : "No directories found"
		},
		"br",
		{},
		"br",
		{}';
	$return .= ',
	"a",
	{
		attributes : {
			"href" : "?do=move&subject='.$subjectURL.'&path='.$pathURL.'&to='.$toURL.'",
			"id" : "movehere",
			"title" : "move here ('.$toHTML.')"
		},
		text : "move here"
	}]
]';
	return $return;
}
function getDirContents($path){
	global $dirContents;
	$dirHandle = opendir($path);
	while (($dirItem = readdir($dirHandle)) !== false) {
		if ($dirItem == '.' || $dirItem == '..')
			continue;
		$fullPath = $path.'/'.$dirItem;
		$dirContents[is_file($fullPath) ? 'files' : 'folders'][] = $dirItem;
	}
	closedir($dirHandle);
}
//list directory contents functions
function getDirs($path){
	global $dirContents, $pathURL;

	$l = count($dirContents['folders']);

	if (!$l)
		return;

	sort($dirContents['folders']); //TODO: Better sort (below also)

	for ($i = 0; $i < $l; $i++){
		$dirItem = $dirContents['folders'][$i];
		$dirItemURL = escape($dirItem);
		$dirItemHTML = htmlspecialchars($dirItem);
		$fullPath = $path.'/'.$dirItem;

		$mod = getmod($path.'/'.$dirItem);

		echo '  <li title="' . $dirItemHTML . '">' .
		"\n\t" . '<a href="?path=' . escape($fullPath) . '" title="' . $dirItemHTML . '" class="dir">'.$dirItemHTML.'</a><!-- '.$dirItemHTML." -->" .
		"\n\t" . '<span class="filemtime" title="file modified time">' . date('c', filemtime($fullPath)) . '</span>' .
		"\n\t" . '<span class="mode" title="mode">' . $mod . '</span>' .
		"\n\t" . '<a href="#" title="Chmod '.$dirItemHTML.'" onclick="fOp.chmod(\''.$pathURL.'\', \''.$dirItemURL.'\', \''.$mod.'\'); return false;" class="chmod b"></a><!-- Chmod '.$dirItemHTML." -->" . //Chmod $dirItem
		"\n\t" . '<a href="#" title="Move '.$dirItemHTML.'" onclick="fOp.moveList(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.$pathURL.'\'); return false;" class="move b"></a><!-- Move '.$dirItemHTML." -->" . //Move $dirItem
		"\n\t" . '<a href="#" title="Rename '.$dirItemHTML.'" onclick="fOp.rename(\''.$dirItemHTML.'\', \''.$pathURL.'\'); return false;" class="rename b"></a><!-- Rename '.$dirItemHTML." -->" . //Rename $dirItem
		"\n\t" . '<a href="?do=delete&amp;path='.$pathURL.'&amp;subject='.$dirItemURL.'" title="Delete '.$dirItemHTML.'" onclick="return confirm(\'Are you sure you want to delete '.removeQuotes($dirItem).'?\');" class="del b"></a><!-- Delete '.$dirItemHTML." -->" . //Delete $dirItem
		"\n  </li>\n";
	}
}
function getFiles($path){
	global $dirContents, $pathURL, $codeMirrorModes;
	$filePath = $path == '.' ? '/' : '/' . $path.'/';

	$l = count($dirContents['files']);

	if (!$l)
		return;

	sort($dirContents['files']);

	for ($i = 0; $i < $l; $i++){
		$dirItem = $dirContents['files'][$i];
		$dirItemURL = escape($dirItem);
		$dirItemHTML = htmlspecialchars($dirItem);
		$fullPath = $path.'/'.$dirItem;

		$mod = getmod($fullPath);
		$ext = getExt($dirItem);

		echo '  <li title="' . $dirItemHTML . '">' .
		"\n\t" . '<a href="' . escape(ROOT . $filePath . $dirItem) . '" title="' . $dirItemHTML . '" class="file">'.$dirItemHTML.'</a><!-- '.$dirItemHTML." -->" .
		"\n\t" . '<span class="fs"  title="file size">' . getfs($path.'/'.$dirItem) . '</span>' .
		"\n\t" . '<span class="extension" title="file extension">' . $ext . '</span>' .
		"\n\t" . '<span class="filemtime" title="file modified time">' . date('c', filemtime($fullPath)) . '</span>' .
		"\n\t" . '<span class="mode" title="mode">' . $mod . '</span>' .
		((zipSupport() && $ext == 'zip')
			? "\n\t" . '<a href="?do=extract&amp;path='.$pathURL.'&amp;subject='.$dirItemURL.'" title="Extract '.$dirItemHTML.'" class="extract b"></a><!-- Extract '.$dirItemHTML." -->" //Zip extract $dirItem
			: null) .
		(filesize($fullPath) <= (1048576 * MaxEditableSize) ? (in_array($ext, $codeMirrorModes)
			? "\n\t" . '<a href="#" title="Edit '.$dirItemHTML.'" onclick="edit.init(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.getExt($dirItem).'\', '.(int)is_dir(CODEMIRROR_PATH).'); return false;" class="edit cp b"></a><!-- Edit '.$dirItemHTML." -->" //Edit $dirItem
			: "\n\t" . '<a href="#" title="Edit '.$dirItemHTML.'" onclick="edit.init(\''.$dirItemURL.'\', \''.$pathURL.'\', null); return false;" class="edit b"></a><!-- Edit '.$dirItemHTML." -->") : null) . //Edit $dirItem
		"\n\t" . '<a href="#" title="Chmod '.$dirItemHTML.'" onclick="fOp.chmod(\''.$pathURL.'\', \''.$dirItemURL.'\', \''.$mod.'\'); return false;" class="chmod b"></a><!-- Chmod '.$dirItemHTML." -->" . //Chmod $dirItem
		"\n\t" . '<a href="#" title="Move '.$dirItemHTML.'" onclick="fOp.moveList(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.$pathURL.'\'); return false;" class="move b"></a><!-- Move '.$dirItemHTML." -->" . //Move $dirItem
		"\n\t" . '<a href="#" title="Copy '.$dirItemHTML.'" onclick="fOp.copy(\''.$dirItemURL.'\', \''.$pathURL.'\', \''.$pathURL.'\'); return false;" class="copy b"></a><!-- copy '.$dirItemHTML." -->" . //copy $dirItem
		"\n\t" . '<a href="#" title="Rename '.$dirItemHTML.'" onclick="fOp.rename(\''.$dirItemHTML.'\', \''.$pathURL.'\'); return false;" class="rename b"></a><!-- Rename '.$dirItemHTML.' -->' . //Rename $dirItem
		"\n\t" . '<a href="?do=delete&amp;path='.$pathURL.'&amp;subject='.$dirItemURL.'" title="Delete '.$dirItemHTML.'" onclick="return confirm(\'Are you sure you want to delete '.removeQuotes($dirItem).'?\');" class="del b"></a><!-- Delete '.$dirItemHTML." -->" . //Delete $dirItem
		"\n  </li>\n";
	}
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo str_replace('www.', null, $_SERVER['HTTP_HOST']); ?> | pafm</title>
  <style type="text/css">@import "<?php echo DEV ? "pafm-files/style.css" : "?r=css";?>";</style>
  <script src="<?php echo DEV ? "pafm-files/js.js" : "?r=js";?>" type="text/javascript"></script>
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
  <span class="pathCrumbs"><?php echo pathCrumbs(); ?></span>
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

<!-- BEGIN list dirs -->
<ul>
<?php
getDirs($path);
?>
</ul>
<!-- //END list dirs -->

<!-- BEGIN list files -->
<ul>
<?php
getFiles($path);
?>
</ul>
<!-- //END list files -->
</div>

<div id="add" class="b">
  <a href="#" title="Remote Copy File" onclick="fOp.remoteCopy('<?php echo $pathURL; ?>'); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/remotecopy.png" alt="Remote Copy"></a>
  <a href="#" title="Create File" onclick="fOp.create('file', '<?php echo $pathURL; ?>'); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/addfile.gif" alt="Create File"></a>
  <a href="#" title="Create Folder" onclick="fOp.create('folder', '<?php echo $pathURL; ?>'); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/addfolder.gif" alt="Create Folder"></a>
  <a href="#" title="Upload File" onclick="upload.init('<?php echo $pathURL; ?>', <?php echo $maxUpload; ?>); return false;"><img src="<?php echo DEV ? "pafm-files/" : "?r="?>images/upload.gif" alt="Upload File"></a>
</div>

<div id="footer">
  <p><?php echo $footer; ?></p>
  <?php
  	if (PASSWORD == 'auth'):
  ?>
  	<span>change your password</span>
  <?php
  	endif;
  ?>
</div>

</body>
</html>
