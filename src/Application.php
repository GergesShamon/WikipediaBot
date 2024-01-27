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
define("FOLDER_JSON", dirname(__DIR__) . "/assets/JSON");
define("FOLDER_MODELS", dirname(__DIR__) . "/assets/Models");
define("FOLDER_SPARQL", dirname(__DIR__) . "/assets/SPARQL");

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


//load file .env
$env = parse_ini_file(".env");

$cookieFile = FOLDER_TMP . "/.cookies";

$client = new \GuzzleHttp\Client([
    "cookies" => new \GuzzleHttp\Cookie\FileCookieJar($cookieFile, true)
]);

$auth = new \WikiConnect\MediawikiApi\Client\Auth\UserAndPassword($env["userbot"], $env["passwordbot"]);
$api = new \WikiConnect\MediawikiApi\Client\Action\ActionApi($env["apibot"], $auth, $client);
$services = new \WikiConnect\MediawikiApi\MediawikiFactory($api);
// Create an authenticated mysqli
$mysqli = new mysqli(
    $env["hostdb"],
    $env["userdb"],
    $env["passworddb"],
    $env["namedb"]
);

if (file_exists($cookieFile)) {
    chmod($cookieFile, 0600);
}
