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
function decodeJWT($token, $secretKey)
{
    $tokenParts = explode('.', $token);
    if (count($tokenParts) === 3) {
        $header = base64UrlDecode($tokenParts[0]);
        $payload = base64UrlDecode($tokenParts[1]);

        $headerData = json_decode($header, true);
        $algorithm = isset($headerData['alg']) ? $headerData['alg'] : '';

        if ($algorithm === 'HS256') {
            $signature = base64UrlDecode($tokenParts[2]);
            $expectedSignature = hash_hmac('sha256', "$tokenParts[0].$tokenParts[1]", $secretKey, true);

            if (hash_equals($signature, $expectedSignature)) {
                $decodedPayload = json_decode($payload, true);
                return $decodedPayload;
            }
        }
    }
    return null;
}

function base64UrlDecode($data)
{
    $base64Url = str_replace(['-', '_'], ['+', '/'], $data);
    $base64 = base64_decode($base64Url);
    return $base64;
}

// Handle HTTP GET request (Get user by token)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get the JWT token from the Authorization header
    $headers = apache_request_headers();
    $authorizationHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    $token = str_replace('Bearer ', '', $authorizationHeader);

    // Verify and decode the JWT token
    $secretKey = 'your-secret-key'; // Replace with your actual secret key
    $decodedPayload = decodeJWT($token, $secretKey);

    if ($decodedPayload !== null) {
        $user_id = $decodedPayload['user_id'];

        // Retrieve user from the database by user_id
        $sql = "SELECT * FROM users WHERE id = '$user_id'";
        $result = $conn->query($sql);

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Prepare the response
            $response = array(
                'status' => 'success',
                'message' => 'User found.',
                'user' => array(
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'purchase_history' => $user['purchase_history'],
                    'shipping_address' => $user['shipping_address']
                )
            );
            http_response_code(200);
            echo json_encode($response);
        } else {
            // User not found
            http_response_code(404);
            $error = array('status' => 'error', 'message' => 'User not found.');
            echo json_encode($error);
        }
    } else {
        // Token validation failed
        http_response_code(401);
        $error = array('status' => 'error', 'message' => 'Invalid token.');
        echo json_encode($error);
    }
}

// Handle HTTP PUT request (Update a user)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Retrieve PUT data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $userId = $input_data['id'];
    $email = $input_data['email'];
    $username = $input_data['username'];
    $purchase_history = $input_data['purchase_history'];
    $shipping_address = $input_data['shipping_address'];

    // Update the user in the database
    $sql = "UPDATE users SET email='$email', username='$username', purchase_history='$purchase_history', shipping_address='$shipping_address' WHERE id=$userId";

    if ($conn->query($sql) === TRUE) {
        // User updated successfully

        // Retrieve the updated user from the database
        $selectSql = "SELECT * FROM users WHERE id = $userId";
        $result = $conn->query($selectSql);
        $updatedUser = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'User updated successfully.',
            'user' => $updatedUser
        );

        // Set the HTTP status code to 200 (OK)
        http_response_code(200);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to update user
        http_response_code(400);
        $error = array('status' => 'error', 'message' => 'User update failed', 'error' => $conn->error);
        echo json_encode($error);
    }
}


// Close the database connection
$conn->close();
?>