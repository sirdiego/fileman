<?php
/*
 * fileman v1.3.1
 *
 * Copyright (c) 2009 diego (http://sirdiego.de/)
 * Licensed under the MIT license.
 * LICENSE.txt
 *
 */
define("PATH", realpath("."));
define("DATA", "/data/");
define("PASSWORD", "test");

session_start();
$files = $pastes = $list = array();
$sizes = array('B','KiB','MiB','GiB','TiB');

function update_log($filename) {
	$file = fopen(PATH.DATA."log.txt", "a");
	fwrite($file, date("Y-m-d h:i")." - from:".$_SERVER["REMOTE_ADDR"]." - file:".$filename."\n");
	fclose($file);
}

function sort_list($array, $order = "filename", $asc = true) {
	$n = count($array);
	do {
		$flag = false;
		for($i=0;$i<$n-1;$i++) {
			if(strtolower($array[$i][$order]) > strtolower($array[$i+1][$order])) {
				$tmp = $array[$i];
				$tmp2 = $array[$i+1];
				$array[$i] = $tmp2;
				$array[$i+1] = $tmp;
				$flag = true;
			}
		}
		$n--;
	}while($flag == true && $n >=1);
	
	return ($asc===true?$array:array_reverse($array));
}


if(isset($_POST["uploadit"])) {
	if($_POST["password"] == PASSWORD) {
		$_SESSION["password"] = PASSWORD;
	}
	if (isset($_FILES['file']) && !$_FILES['file']['error'] && $_POST["name"] != "") {
		$name = str_replace("{name}", $_FILES['file']['name'], $_POST["name"]);
		$name = str_replace("{datum}", date("Y-m-d"), $name);
		if(substr($name, -4) == ".php")
			$name .= ".txt";
		if(move_uploaded_file($_FILES['file']['tmp_name'], PATH.DATA.$name)) {
			update_log($name);
		}
	}else{
		if($_FILES['file']['error'] == 1) {
			die("Die Datei ist zu gro&szlig;!");
		}
	}
}
if(isset($_POST["pasteit"]) && $_POST["text"] != "") {
	$name = date("Y-m-d_His").".txt";
	$file = fopen(PATH.DATA.$name, "w");
	fwrite($file, $_POST["text"]);
	fclose($file);
	update_log($name);
	header("Location: .".DATA.$name);
}
if (isset($_SESSION["password"]) && $_SESSION["password"] == PASSWORD && isset($_GET["delete"]) && is_file(PATH.DATA.$_GET["delete"])) {
	unlink(PATH.DATA.$_GET["delete"]);
}

if (is_dir(PATH.DATA)) {
	$handle = opendir(PATH.DATA);
	while(($file = readdir($handle)) !== false) {
		if ($file != '.' && $file != '..' && is_file($path = PATH.DATA.'/'.$file)) {
			$entry = array('filename' => $file, 'dirpath' => PATH.DATA);
			$entry['modtime'] = filemtime($path);
			$entry['realsize'] = $entry['size'] = filesize($path);
			for($i=0;($entry['size']/1024)>=1;$i++){
					$entry['size'] = $entry['size']/1024;
			}
			$entry['size_extension'] = $sizes[$i];
			if(class_exists("finfo")) {
				$finfo = new finfo(FILEINFO_MIME);
				$entry['mimetype'] = substr($tmp = $finfo->file($path), 0, strpos($tmp, ";"));
			}else{
				$entry['mimetype'] = trim(exec('file -b --mime-type '.escapeshellarg($path)));
			}
			$list[] = $entry;
		}
	}
	closedir($handle);
}
if(isset($_GET["search"]) && preg_match("/[-_.A-Za-z0-9]*/", $_GET["search"])) 
	$regex = $_GET["search"];
else
	$regex = ".*";

$asc = true;
if(isset($_GET["desc"])) $asc = false;
if(isset($_GET["sort"])) {
	$sorts = array("modtime", "filename", "realsize");
	if(in_array($_GET["sort"], $sorts)) {
		$list = sort_list($list, $_GET["sort"], $asc);
	}
}else{
	$_GET["sort"] = "hellyeah";
}

foreach($list as $file) {
	if(preg_match("/".$regex."/", $file['filename']) > 0) {
		$html = sprintf("\t\t\t<tr>\n\t\t\t\t<td><a href=\"data/%s\">%s <span>%s</span></a></td>\n\t\t\t\t<td>%.2f %s <span>%s</span></td>\n\t\t\t\t<td><a href=\"%s?delete=%s\">L&ouml;schen</a></td>\n\t\t\t</tr>\n",
		$file['filename'], $file['filename'], $file['mimetype'], $file['size'], $file['size_extension'], date("y-m-d H:i", $file['modtime']), $_SERVER["PHP_SELF"], $file['filename']);
		$files[] = $html;
	}
}
echo '<?xml version="1.0" encoding="utf-8" ?>';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>fileman</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<meta name="robots" content="none" />
		<style type="text/css"><!--
			table{border-collapse:collapse;font-size:1.2em;width:100%}
			td{border:1px solid #000;padding:5px}
			thead td,legend{font-weight:bold}
			a{text-decoration:none}
			td span{color:#000;font-size:.7em;display:block}
			textarea{width:100%;height:15em}
			.free{color:green}
			.locked{color:red}
			fieldset{margin:1em 0}
		//--></style>
	</head>
	<body>
		<h1>fileman &lt;3</h1>
		<ol>
			<li><a href="<?=$_SERVER["PHP_SELF"]?>#files">Datei-Verzeichniss</a></li>
			<li><a href="<?=$_SERVER["PHP_SELF"]?>#paste">Textschnipsel hochladen</a></li>
			<li>Aktionen sind <span class="<?=(isset($_SESSION["password"]) && $_SESSION["password"] == PASSWORD)?"free \">freigeschaltet":"locked\">gesperrt"?></span></li>
			<li>
				<form action="<?=$_SERVER["PHP_SELF"]?>#files" method="get">
					<input type="text" name="search" id="search" value="<?=$regex?>" />
					<input type="submit" value="suchen" />
				</form>
			</li>
		</ol>
		<form action="<?=$_SERVER["PHP_SELF"]?>" method="post" enctype="multipart/form-data">
		<fieldset>
			<legend id="upload">Datei hochladen</legend>
			<label for="file">Datei:</label> <input type="file" name="file" id="file" />
			<label for="name">Dateiname:</label> <input type="text" name="name" id="name" value="{name}" />
			<label for="password">Passwort:</label> <input type="password" name="password" id="password" />
			<input type="submit" name="uploadit" value="hochladen" />
		</fieldset>
		</form>
		<h2 id="files">Datei-Verzeichniss</h2>
		<table>
			<thead>
			<tr>
				<td><a href="index.php?sort=filename<?=($_GET["sort"]=="filename"&&!isset($_GET["desc"])?"&amp;desc":"")?>">Name</a>/MIME-Type</td>
				<td><a href="index.php?sort=realsize<?=($_GET["sort"]=="realsize"&&!isset($_GET["desc"])?"&amp;desc":"")?>">Gr&ouml;&szlig;e</a>/<a href="index.php?sort=modtime<?=($_GET["sort"]=="modtime"&&!isset($_GET["desc"])?"&amp;desc":"")?>">Datum</a></td>
				<td>Aktionen</td>
			</tr>
			</thead>
			<tbody>
<?php
foreach($files as $file) {
	echo $file;	
}
?>
			</tbody>
		</table>
		<form action="<?=$_SERVER["PHP_SELF"]?>" method="post">
		<fieldset>
			<legend id="paste">Textschnipsel hochladen</legend>
			<label for="text">Text:</label> <textarea name="text" id="text" rows="5" cols="200"></textarea>
			<input type="submit" name="pasteit" value="hochladen" />
		</fieldset>
		</form>
		<p>&copy; 2009 <a href="http://sirdiego.de/">diego</a> - Diese Software (<a href="http://sirdiego.ath.cx/f/fileman-1.3.1.tar.gz">herunterladen</a>) steht unter der <a href="./LICENSE.txt">MIT-Lizenz</a></p>
	</body>
</html>
