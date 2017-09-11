<?php
//exec("/opt/php7.0/bin/php functions/1c_xmlparse.php > /dev/null &"); die();

ini_set('error_reporting', -1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include_once ($_SERVER['DOCUMENT_ROOT'].'/functions/functions.php');

$user	= "username";
$pass	= "password";

function auth() {
	header('WWW-Authenticate: Basic realm="1C-Exchange"');
	header('HTTP/1.0 401 Unauthorized');
	$_SESSION["login"] = false;
	die("failure");
}
session_start();

if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER["PHP_AUTH_PW"]) && !isset($_REQUEST["type"])) { auth(); }

if (($_SERVER["PHP_AUTH_USER"] == $user && $_SERVER["PHP_AUTH_PW"] == $pass) || $_SESSION["login"]) {
	if (isset($_REQUEST['mode'])) {
		if (!isset($_COOKIE["1c_exchange"]) && $_REQUEST['mode'] == 'checkauth') { 
			echo "success\n1c_exchange\n".md5(date('m.d.y'));
		}
		elseif ($_COOKIE["1c_exchange"] == md5(date('m.d.y'))) {
			// Инициализация выгрузки каталога
			if ($_REQUEST['mode'] == 'init') {
				fs_clear('upload/');
				if (extension_loaded('zip')) { echo "zip=yes\n"; } 
				echo 'file_limit='.asBytes('1024k');
			}
			// Выгрузка файлов обмена (*.zip)
			elseif ($_REQUEST['mode'] == 'file' && isset($_REQUEST['filename'])) {
				file_put_contents('upload/'.$_REQUEST['filename'], file_get_contents("php://input"), FILE_APPEND | LOCK_EX);
				if (file_exists('upload/'.$_REQUEST['filename'])) { 
					exit("success");
				}
			}
			// Выгрузка *.xml файлов обмена
			elseif ($_REQUEST['mode'] == 'import' && isset($_REQUEST['filename'])) {
				file_put_contents('upload/'.$_REQUEST['filename'], file_get_contents("php://input"), FILE_APPEND | LOCK_EX);
				if (file_exists('upload/'.$_REQUEST['filename'])) {
					if ($_REQUEST['filename'] == 'import.xml') {
						// Распаковываем архивы
						fs_clear('import/');
						zip_unpack('upload','import/');
						// Обрабатываем *.xml
						exec("/opt/php7.0/bin/php functions/1c_xmlparse.php > /dev/null &");
					}
					exit("success");
				}
			}
		}
	}
} else { auth(); }
