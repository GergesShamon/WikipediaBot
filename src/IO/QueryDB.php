<?php
namespace Bot\IO;

use mysqli;

class QueryDB
{
    private $mysqli;
    public function __construct(mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }
    public function executeQuery(string $query) {
        return $this->mysqli->query($query);
    }
    public function getArray(string $query) : array {
        $result = $this->executeQuery($query);
        $resultDB = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $resultDB[] = $row;
        }
        mysqli_free_result($result);
        return $resultDB;
    }

}