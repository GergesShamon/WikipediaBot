<?php

namespace Bot\Tasks;

use mysqli;
use WikiConnect\MediawikiApi\MediawikiFactory;
use WikiConnect\MediawikiApi\Client\Action\ActionApi;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Bot\IO\QueryDB;
use Throwable;

abstract class Task {

    protected ActionApi $api;
    protected MediawikiFactory $services;
    protected QueryDB $query;
    protected mysqli $mysqli;
    protected Logger $log;
	
	public function __construct(ActionApi $api, MediawikiFactory $services, mysqli $mysqli) {
        $this->api = $api;
        $this->services = $services;
        $this->query = new QueryDB($mysqli);
        $this->mysqli = $mysqli;
        $this->log = new Logger("Task");
        $this->log->pushHandler($this->getStreamLogger());
    }
    public function getStreamLogger(): StreamHandler {
        $day = date("d-M-Y");
        return new StreamHandler(FOLDER_LOGS . "/" . str_replace("Bot\\Tasks\\","",get_class($this)) . "/log-{$day}.log");
    }
    protected function running($fun): void {
        try{
            call_user_func($fun);
            $this->log->info("Task ".str_replace("Bot\\Tasks\\","",get_class($this))." succeeded to execute.");
        } catch (Throwable $error) {
            $this->log->debug("Task ".str_replace("Bot\\Tasks\\","",get_class($this))." failed to execute.", [$error->__toString()]);
        }
    }

}
