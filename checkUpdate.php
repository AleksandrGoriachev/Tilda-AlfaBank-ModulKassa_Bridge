<?php


require_once "API/modulConnect.php";
require_once "API/alfaConnect.php";
require_once "API/dbConnect.php";

const TIME_LIMIT = 90; //Period of time while money can be refunded to a customer in days

//Connect to the database to check if there any uncompleted cheques
$connect = new dbConnect();
$mysqli = $connect->connect();
$query = $mysqli->query("SELECT `modul`.`status` as status, `modul`.`attempt` as attempt, `modul`.`id` as id,
                               `orders`.`orderId` as orderId
                                  FROM `modul` JOIN `orders`
                                    WHERE `status` <> 'COMPLETED' AND `orders`.`id` = `modul`.`idOrder`");
if ($query->field_count !== 0) { // If we have any uncompleted order than...
    while ($getStat = $query->fetch_array(MYSQLI_ASSOC)) { // We select each item and check for updates on the Modul Kassa side
        $status = $getStat['status'];
        $attempt = $getStat['attempt'] + 1;
        $id = $getStat['id'];
        $orderId = $getStat['orderId'];
        $modul = new modulConnect();
        $newState = $modul->getChequeStatus($orderId);
        $newStatus = $newState['status'];
        $newTime = $newState['timeStatusChanged'];
        if ($newStatus !== $status) {   // If status changed we update an appropriate row in the table
            $mysqli->query("UPDATE `modul` SET `status` = '$newStatus', `time` = '$newTime', `attempt` = $attempt
                                    WHERE `id` = $id LIMIT 1");
        }
    }
} else {
    echo "There is no entries in DB 8(";
}
// Return message if we have an error
if ($mysqli->error) {
    echo  "Error : " . $mysqli->error;
}
$mysqli->close();

// Check if status of the order has been changed during prescribed period of time
function getOrderStatus() : array
{
    $changedStatus = [];
    $mysqli = new dbConnect();
    $db = $mysqli->connect();
    $query = $db->query("SELECT `orderId`, `orderStatus`, `date`, `amount`, `expired` FROM `orders` WHERE `expired` = '0'");
    while ($status = $query->fetch_assoc()) {
        $orderTime = $status['date'] / 1000;
        $currentTime = mktime();
        $currentOrderStatus = (new alfaConnect())->getExtOrderStatus($status['orderId']);
        $orderIdFromALfa = $currentOrderStatus['attributes'][0]['value'];
        $statusFromAlfa = $currentOrderStatus['orderStatus'];
        if ($currentTime - $orderTime < TIME_LIMIT * 24 * 3600) {
            if ($statusFromAlfa != $status['orderStatus'] && $orderIdFromALfa === $status['orderId']) {
                $changedStatus[$orderIdFromALfa] = $statusFromAlfa;
                $db->query("UPDATE orders SET `orderStatus` = '$statusFromAlfa' WHERE orderId = '$orderIdFromALfa' LIMIT 1");
            }
        } elseif ($currentTime - $orderTime > TIME_LIMIT * 24 * 3600) {
            echo "\n" .$orderIdFromALfa . "\n";
            $db->query("UPDATE orders SET `expired` = '1' WHERE orderId = '$orderIdFromALfa' LIMIT 1");
        }
    }
    $db->close();
    return $changedStatus;
}


//Unique check number generator
function chequeId()
{
    $firstPart = preg_replace("/[\.]{1,}/", "-", uniqid("", true));
    $secondPart = explode(".", uniqid("1", true));
    $lastPart = implode("-", array_map(function ($part) {
        return str_shuffle($part);
    }, $secondPart));
    return $chequeId = $firstPart . "-" . $lastPart;
}


//  Upload RETURN cheque onto Modul server
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
    if($get['orderStatus'] == 2) {
        echo "We can't print return cheque without returning money from the bank account.\n";
        return false;
    }
//    Now we generate a new chequeId especially for ModulKassa
    $chequeId = chequeId();
//    Create json request for check upload
    $jsonQuery = array (
        "docNum" => $get['orderNumber'],
        "docType" => "RETURN",
        "checkoutDateTime" => date("c"),
        "email" => $get['email'],
        "printReceipt" => true,
        "id" => $chequeId,
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
    $mysqli->query("INSERT INTO `returns` (`id`, `idOrder`, `status`, `time`, `chequeId`) VALUES (NULL, '$idOrder', '$status', '$time', '$chequeId')");
    if ($mysqli->error) {
        echo "Modul status storage in the database failed: " . $mysqli->error;
        $mysqli->close();
        return null;
    }
    $mysqli->close();
    return true;
}

function sendReturnCheque(array $changedStatus)
{
    foreach ($changedStatus as $orderId => $status) {
        if ($status === 4 || $status === 3 ||  $status === 6){
            chequeUpload($orderId);
        }
    }
}

// Run process of fiscalization of the RETURNED money
sendReturnCheque(getOrderStatus());


//Connect to the database to check if there any uncompleted cheques in RETURNS
$connect = new dbConnect();
$mysqli = $connect->connect();
$query = $mysqli->query("SELECT `returns`.`chequeId` as orderId, `returns`.`status` as status, `returns`.`attempt` as attempt, `returns`.`id` as id
                                FROM `returns`
                                WHERE `returns`.`status` <> 'COMPLETED'");
if ($query->field_count > 0) { // If we have any uncompleted order than...
    while ($getStat = $query->fetch_array(MYSQLI_ASSOC)) { // We select each item and check for updates on the Modul Kassa side
        $status = $getStat['status'];
        $attempt = $getStat['attempt'] + 1;
        $id = $getStat['id'];
        $orderId = $getStat['orderId'];
        $modul = new modulConnect();
        $newState = $modul->getChequeStatus($orderId);
        $newStatus = $newState['status'];
        $newTime = $newState['timeStatusChanged'];
        if ($newStatus !== $status) {   // If status changed we update an appropriate row in the table
            $mysqli->query("UPDATE `returns` SET `status` = '$newStatus', `time` = '$newTime', `attempt` = $attempt
                                    WHERE `id` = $id LIMIT 1");
        }
    }
} else {
    echo "There is no entries in DB 8(";
}
// Return message if we have an error
if ($mysqli->error) {
    echo  "Error : " . $mysqli->error;
}
$mysqli->close();

