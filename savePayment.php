<?php

require_once 'API/dbConnect.php';
require_once "API/alfaConnect.php";
require_once "API/modulConnect.php";


//Get orderId from bank's response after the payment
$orderId = (string) $_GET['orderId'];

//Now we save a Unique Order Number from Alfa to our database
function saveAlfaOrder ($orderId) {
// We call dependency from dbConnect
    if(strlen($orderId ) < 30) {
        echo "Wrong order number length: " . strlen($orderId );
        return null;
    }
    $dbConn = new dbConnect();
    $mysqli = $dbConn->connect();
//    Store date to DB
    $mysqli->query("INSERT INTO `orders` (`id`, `orderId`) VALUES (NULL, '$orderId')");
    if ($mysqli->error) {
        echo $mysqli->error;
        $mysqli->close();
        return null;
    }
    $mysqli->close();
    return $orderId;
}

//  Update database table with gained parameters
function orderUpdate($orderId) {
    $alfa = new alfaConnect(); //   Initiate alfaConnect method
    $result = $alfa->getExtOrderStatus($orderId);  //  Get order status
    $orderNumber = $result['orderNumber'];
    $errorMessage = $result['errorMessage'];
    $date = $result['date'];
    $orderStatus = $result['orderStatus'];
    $amount = $result['amount'];
    $currency = $result['currency'];
    $paymentSystem = $result['cardAuthInfo']['paymentSystem'];
    $cardHolder = $result['cardAuthInfo']['cardholderName'];
    $phone = $result['orderBundle']['customerDetails']['phone'];
    $email = $result['orderBundle']['customerDetails']['email'];
    $orderName = $result['orderBundle']['cartItems']['items']['0']['name'];
    $quantity = $result['orderBundle']['cartItems']['items']['0']['quantity']['value'];
    $measure = $result['orderBundle']['cartItems']['items']['0']['quantity']['measure'];
    $itemAmount = $result['orderBundle']['cartItems']['items']['0']['itemAmount'];
    $dbConn = new dbConnect();
    $mysqli = $dbConn->connect();
    $mysqli->query("UPDATE orders SET 
                          `orderNumber` = '$orderNumber', 
                          `errorMessage` = '$errorMessage', 
                          `date` = '$date', 
                          `orderStatus` = '$orderStatus',
                          `quantity` = '$quantity',
                          `itemAmount` = '$itemAmount',
                          `measure` = '$measure',
                          `amount` = '$amount',
                          `currency` = '$currency',
                          `paymentSystem` = '$paymentSystem',
                          `cardHolder` = '$cardHolder',
                          `phone` = '$phone',
                          `email` = '$email',
                          `orderName` = '$orderName' WHERE orderId = '$orderId' LIMIT 1");
    if ($mysqli->error) {
        echo $mysqli->error;
        $mysqli->close();
        return null;
    }
    $mysqli->close();
    if ($orderStatus !== 3  && $orderStatus !== 4 && $orderStatus !== 6) {
        return $orderId;
    }
    return null;
}

//  Upload data onto Modul server
function chequeUpload($orderId, $taxMode = "SIMPLIFIED", $vatTag = "1105", $paymentMethod = "full_payment") {
    $dbConn = new dbConnect();
    $mysqli = $dbConn->connect();
    $query = $mysqli->query("SELECT * FROM orders WHERE `orderId` = '$orderId' LIMIT 1");
    $get = $query->fetch_array(MYSQLI_ASSOC);
    if ($mysqli->error) {
        echo "Error reading orders table: " . $mysqli->error;
        $mysqli->close();
        return null;
    }
//    Create json request for check upload
    $jsonQuery = array (
        "docNum" => $get['orderNumber'],
        "docType" => "SALE",
        "checkoutDateTime" => date("c"),
        "email" => $get['email'],
        "printReceipt" => true,
        "id" => $orderId,
        "taxMode" => $taxMode,
        "inventPositions" => array ( array (
            "name" => $get['orderName'],
            "price" => ($get['itemAmount'] / $get['quantity'] )/ 100,
            "quantity" => $get['quantity'],
            "vatTag" => $vatTag,
            "paymentObject" => "service",
            "paymentMethod" => $paymentMethod,
        ) ),
        "moneyPositions" => array (array (
            "paymentType" => "CARD",
            "sum" => $get['amount'] / 100,
        )),
    );
    $idOrder = $get['id'];
    $mysqli->close();
    $cheque = json_encode($jsonQuery);  // Convert to json
    $upload = new modulConnect(); // Create new object
    $response = $upload->uploadCheque($cheque);  // Upload cheque onto Modul server
    $dbConn = new dbConnect();
    $status = $response['status'];
    $time = $response['timeStatusChanged'];
    $mysqli = $dbConn->connect();
    $mysqli->query("INSERT INTO `modul` (`id`, `idOrder`, `status`, `time`) VALUES (NULL, '$idOrder', '$status', '$time')");
    if ($mysqli->error) {
        echo "Modul status storage in the database failed: " . $mysqli->error;
        $mysqli->close();
        return null;
    }
    $mysqli->close();
    return $orderId;
}

//Proceed parsing, analysis, storage, fiscalization and redirect to a success page
function successReturn ($orderId) {
    // Prescribed success page on Tilda
    $successUrl = SUCCESS_URL;
    if ($orderId === null) {
        echo "Order storage failed...";
        exit;
    }
    $update = orderUpdate($orderId);
    if ($update === $orderId) {
        $success = chequeUpload($orderId);
        if ($success === $orderId) {
        header("Location: " . $successUrl );
        } else {
            echo "Entry update failed, sorry...";
            exit;
        }
    }
}


//Now we process the "SUCCESS" response from ALfa
successReturn(saveAlfaOrder($orderId));
