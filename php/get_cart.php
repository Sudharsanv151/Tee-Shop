<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "online_shop";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, title, price, image FROM cart";
$result = $conn->query($sql);

$cart = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cart[] = $row;
    }
}

echo json_encode($cart);

$conn->close();
?>
