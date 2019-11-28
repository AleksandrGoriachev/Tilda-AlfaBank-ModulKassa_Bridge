<?php

require_once "API\config.php";

class Logs
{
    public static function saveToLogs(string $message)
    {
        $path = LOG_DIR;
        $date = date('Y-m-d H:i:s', time());
        if(!is_dir($path)){
            mkdir($path);
        }
        file_put_contents($path."operations_log.txt", $date . " : " . $message . "\r\n", FILE_APPEND);
    }

}