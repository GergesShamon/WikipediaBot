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
//Check if folders exists
if (!is_dir(FOLDER_ASSETS)) {
    mkdir(FOLDER_ASSETS);
}
if (!is_dir(FOLDER_SQL)) {
    mkdir(FOLDER_SQL);
}

//set folder logs
Bot\IO\Logger::setFolderLog(FOLDER_LOGS);
//load file .env
$env = parse_ini_file(".env");
// Create an authenticated API and services

$fileCookieJar = FOLDER_TMP."/.cookies";
if (file_exists($fileCookieJar)) {
    if (fileperms($fileCookieJar) !== 0600) {
        if (!chmod($fileCookieJar, 0600)) {
            Bot\IO\Logger::notice("Unable to change file Cookie Jar permissions.");
        }
    }
}
$client = new GuzzleHttp\Client(["cookies" => new GuzzleHttp\Cookie\FileCookieJar($fileCookieJar)]);
$auth = new \Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword($env["userbot"], $env["passwordbot"]);
$api = new \Addwiki\Mediawiki\Api\Client\Action\ActionApi($env["apibot"], $auth, $client);
$services = new \Addwiki\Mediawiki\Api\MediawikiFactory($api);
// Create an authenticated mysqli
$mysqli = new mysqli(
    $env["hostdb"],
    $env["userdb"],
    $env["passworddb"],
    $env["namedb"]
);