<?php

class dbConnect
{

    public $servername = "";
    public $username = "";
    public $password = "";
    public $dbName = "";

// Create connection
    public function __construct()
    {
        $this->servername;
        $this->username;
        $this->password;
        $this->dbName;
    }

    public function connect () {
        $mysqli = new mysqli($this->servername, $this->username, $this->password, $this->dbName);
        $mysqli->set_charset("utf8");
        return $mysqli;
    }
}
?>