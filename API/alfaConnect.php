<?php

class alfaConnect
{
    public $userName ="";
    public $password ="";
    public $host = "";

    //    Constructor of the object
    public function __construct()
    {
        $this->userName = ALFA_USER;
        $this->password = ALFA_PASSWORD;
        $this->host = ALFA_SERVER;
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


    //    Get method for the extended order status check
    public function getExtOrderStatus($orderId)
    {
//        Build a new query array
        $data = array(
            'orderId' => $orderId,
            'language' => 'ru',
            'userName' => $this->userName,
            'password' => $this->password,
        );
        $requestUrl = $this->host . $this::GET_EXT_ORDER_STATUS; // Build complete request address
        $curl = curl_init($requestUrl); // Initialisation of the request
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true, // Request of the response
            CURLOPT_SSL_VERIFYPEER => false, // Ignore local SSL settings
            CURLOPT_POST => true, // Use POST method
            CURLOPT_POSTFIELDS => http_build_query($data), // Request data preparation
            CURLOPT_CUSTOMREQUEST => "POST"
        ));
        $response = curl_exec($curl); // Send request
        if(!$response){
            echo "Alfa connection cURL error: " . curl_error($curl); // Check for errors
        }
        $response = json_decode($response, true); // Decode JSON response to an array
        curl_close($curl); // Close connection
        return $response;
    }


    //    Get method for the order status check
    public function getOrderStatus($orderId)
    {
//        Build a new query array
        $data = array(
            'orderId' => $orderId,
            'language' => 'ru',
            'userName' => $this->userName,
            'password' => $this->password,
        );
        $requestUrl = $this->host . $this::GET_ORDER_STATUS; // Build complete request address
        $curl = curl_init($requestUrl); // Initialisation of the request
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true, // Request of the response
            CURLOPT_SSL_VERIFYPEER => false, // Ignore local SSL settings
            CURLOPT_POST => true, // Use POST method
            CURLOPT_POSTFIELDS => http_build_query($data), // Request data preparation
            CURLOPT_CUSTOMREQUEST => "POST"
        ));
        $response = curl_exec($curl); // Send request
        if(!$response){
            echo "Alfa connection cURL error:  " . curl_error($curl); // Check for errors
        }
        $response = json_decode($response, true); // Decode JSON response to an array
        curl_close($curl); // Close connection
        return $response;
    }

}