<?php

namespace Bot\Tasks;

use mysqli;
use Addwiki\Mediawiki\Api\MediawikiFactory;
use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Bot\IO\QueryDB;

abstract class Task {

    protected ActionApi $api;
    protected MediawikiFactory $services;
    protected QueryDB $query;
	
	public function __construct(ActionApi $api, MediawikiFactory $services, mysqli $mysqli) {
        $this->api = $api;
        $this->services = $services;
        $this->query = new QueryDB($mysqli);
    }

}
