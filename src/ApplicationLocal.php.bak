<?php
require_once(dirname(__DIR__) . "/vendor/autoload.php");

//autoload
spl_autoload_register(function ($class) {
    $file = __DIR__ . "/" . str_replace("\\", "/", str_replace("Bot\\", "", $class)) . ".php";
    if (file_exists($file)) {
        require $file;
    }
});

//Set constants
define("FOLDER_TMP", dirname(__DIR__) . "/tmp");
define("FOLDER_LOGS", dirname(__DIR__) . "/logs");
define("FOLDER_ASSETS", dirname(__DIR__) . "/assets");
define("FOLDER_SQL", dirname(__DIR__) . "/assets/SQL");
$_SERVER["SERVER_NAME"] = "WikipediaBot";
//Check if folders exists
if (!is_dir(FOLDER_ASSETS)) {
    mkdir(FOLDER_ASSETS);
}
if (!is_dir(FOLDER_SQL)) {
    mkdir(FOLDER_SQL);
}

if (!is_dir(FOLDER_TMP)) {
    mkdir(FOLDER_TMP);
}
if (!is_dir(FOLDER_LOGS)) {
    mkdir(FOLDER_LOGS);
}

//set folder logs
Bot\IO\Logger::setFolderLog(FOLDER_LOGS);
//load file .env
$env = parse_ini_file(".env");
// Create an authenticated API and services

$auth = new \Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword($env["userbot"], $env["passwordbot"]);
$api = new \Addwiki\Mediawiki\Api\Client\Action\ActionApi($env["apibot"], $auth);
$services = new \Addwiki\Mediawiki\Api\MediawikiFactory($api);
// Create an authenticated mysqli
$mysqli = new mysqli(
    $env["hostdb"],
    $env["userdb"],
    $env["passworddb"],
    $env["namedb"]
);