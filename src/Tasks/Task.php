<?php

namespace Bot\Tasks;

use mysqli;
use WikiConnect\MediawikiApi\MediawikiFactory;
use WikiConnect\MediawikiApi\Client\Action\ActionApi;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Bot\IO\QueryDB;

abstract class Task {

    protected ActionApi $api;
    protected MediawikiFactory $services;
    protected QueryDB $query;
    protected Logger $log;
	
	public function __construct(ActionApi $api, MediawikiFactory $services, mysqli $mysqli) {
        $this->api = $api;
        $this->services = $services;
        $this->query = new QueryDB($mysqli);
        $this->log = new Logger("Task");
        $this->log->pushHandler($this->getStreamLogger());
    }
    public function getStreamLogger(): StreamHandler {
        $day = date("d-M-Y");
        return new StreamHandler(FOLDER_LOGS . "/" . str_replace("Bot\\Tasks\\","",get_class($this)) . "/log-{$day}.log");
    }

}
