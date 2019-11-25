<?php

class modulConnect
{
    public $userName =""; // UserName for the production
//    public $userName =""; // UserName for the development
    public $terminal = "";
    public $password = "";    // Password for the production
//    public $password = "";    // Password for the development
//    public $terminalPass = "";
    public $host = "";   //Production server
//    private $associateHost = "";

    //    Constructor of the object
    public function __construct()
    {
        $this->userName;
        $this->password;
        $this->host;
    }

    // Get method for the password
    public function getPassword()
    {
        return $this->password;
    }

    //Get method for the user's name
    public function getUserName()
    {
        return $this->userName;
    }

    //    Get method for the request processing host
    public function getHost()
    {
        return $this->host;
    }

    //    Get terminal name
    public function getTerminal()
    {
        return $this->terminal;
    }

//    Get terminal password
    public function getTerminalPass()
    {
        return $this->terminalPass;
    }


//    Check remote module status
    public function getModuleStatus()
    {
        $requestString = "v1/status";
        $login = $this->userName;
        $password = $this->password;
        $pair = $login . ":" . $password;
        $link = $this->host . $requestString;
        $curl = curl_init($link);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => true,
            CURLAUTH_BASIC => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => $pair,
            CURLOPT_CUSTOMREQUEST => "GET", // Use GET method
        ));
        $response = curl_exec($curl);
        if (!$response) {
            echo "Modul cURL connection error: " . curl_error($curl);
        }
        curl_close($curl);
        return $response;
    }


//    Upload the fiscal data on server
public function uploadCheque ($data)
{
    $requestString = "v1/doc";
    $login = $this->userName;
    $password = $this->password;
    $pair = $login . ":" . $password;
    $link = $this->host . $requestString;
    $curl = curl_init($link);
    curl_setopt_array($curl, array(
        CURLOPT_HTTPAUTH => true,
        CURLAUTH_BASIC => true,
        CURLOPT_USERPWD => $pair,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data, // Request data preparation
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => array(
            'Accept: */*',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)),
        )
    );
    $response = curl_exec($curl);
    var_dump($response);
    if (!$response) {
        echo "Modul cURL connection error: " . curl_error($curl);
    }
    $parse = json_decode($response, true);
    curl_close($curl);
    if ($parse['status'] === "FAILED") {
        return null;
    }
    return $parse;
}



//    Check document status
    public function getChequeStatus($orderId)
    {
        $requestString = "v1/doc/$orderId/status";
        $login = $this->userName;
        $password = $this->password;
        $pair = $login . ":" . $password;
        $link = $this->host . $requestString;
        $curl = curl_init($link);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => true,
            CURLAUTH_BASIC => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => $pair,
            CURLOPT_CUSTOMREQUEST => "GET", // Use GET method
        ));
        $response = curl_exec($curl);
        var_dump($response);
        if (!$response) {
            echo "Modul cURL connection error: " . curl_error($curl);
        }
        $parse = json_decode($response, true);
        curl_close($curl);
        if ($parse['status'] === "FAILED") {
            return null;
        }
        return $parse;
    }



//    //  This method is being used for the association only
//    private function getAssociateHost()
//    {
//        return $this->associateHost;
//    }
//
//
//    //    Required once when you need to associate a new terminal to get login and pass
//    public function associateTerminal()
//    {
//        $login = $this->getUserName();
//        $password = $this->getPassword();
//        $pair = $login . ":" . $password;
//        $associateLink = $this->getAssociateHost() . $this->getTerminal();
//        $curl = curl_init($associateLink);
//        curl_setopt_array($curl, array(
//            CURLOPT_HTTPAUTH => true,
//            CURLAUTH_BASIC => true,
//            CURLOPT_SSL_VERIFYPEER => false,
//            CURLOPT_USERPWD => $pair,
//            CURLOPT_CUSTOMREQUEST => "POST", // Use POST method
//        ));
//        $response = curl_exec($curl);
//        if (!$response) {
//            echo "Curl error:" . curl_error($curl);
//        }
//        echo json_decode($response);
//        curl_close($curl);
//    }
}