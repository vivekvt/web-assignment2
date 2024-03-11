<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Connect to the MySQL database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "assignment2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle HTTP POST request (Register a user)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $email = $input_data['email'];
    $password = $input_data['password'];
    $username = $input_data['username'];
    $purchase_history = $input_data['purchase_history'];
    $shipping_address = $input_data['shipping_address'];

    // Encrypt the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into the database
    $sql = "INSERT INTO users (email, password, username, purchase_history, shipping_address)
            VALUES ('$email', '$hashedPassword', '$username', '$purchase_history', '$shipping_address')";

    if ($conn->query($sql) === TRUE) {
        // User registered successfully
        $response = array(
            'status' => 'success',
            'message' => 'User registered successfully.'
        );
        http_response_code(200);
        echo json_encode($response);
    } else {
        // Failed to register user
        http_response_code(400);
        $error = array('status' => 'error', 'message' => 'User registration failed', 'error' => $conn->error);
        echo json_encode($error);
    }
}

// Close the database connection
$conn->close();
?>