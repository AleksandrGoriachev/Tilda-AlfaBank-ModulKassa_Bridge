<?php

class dbConnect
{

    protected $servername = "";
    protected $username = "";
    protected $password = "";
    protected $dbName = "";

// Create connection
    public function __construct()
    {
        $this->servername = DB_SERVER;
        $this->username = DB_USER;
        $this->password = DB_PASSWORD;
        $this->dbName = DB_NAME;
    }

    public function connect () {
        $mysqli = new mysqli($this->servername, $this->username, $this->password, $this->dbName);
        $mysqli->set_charset("utf8");
        return $mysqli;
    }
}
?>