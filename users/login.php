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

function generateJWT($payload, $secretKey)
{
    $header = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode($payload));
    $signature = base64UrlEncode(hash_hmac('sha256', "$header.$payload", $secretKey, true));

    return "$header.$payload.$signature";
}

function base64UrlEncode($data)
{
    $base64 = base64_encode($data);
    $base64Url = str_replace(['+', '/', '='], ['-', '_', ''], $base64);
    return $base64Url;
}

// Handle HTTP POST request (User login)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $email = $input_data['email'];
    $password = $input_data['password'];

    // Retrieve user from the database by email
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $hashedPassword = $user['password'];

        // Verify the provided password
        if (password_verify($password, $hashedPassword)) {
            // Password is correct, generate JWT token
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days expiration
            $tokenPayload = array(
                'user_id' => $user['id'],
                'email' => $user['email'],
                'exp' => $expiry
                // Add any other relevant user data to the token payload
            );
            $secretKey = 'your-secret-key'; // Replace with your actual secret key
            $jwt = generateJWT($tokenPayload, $secretKey);

            // Prepare the response
            $response = array(
                'status' => 'success',
                'message' => 'Login successful.',
                'user' => array(
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'purchase_history' => $user['purchase_history'],
                    'shipping_address' => $user['shipping_address']
                ),
                'access_token' => $jwt
            );
            http_response_code(200);
            echo json_encode($response);
        } else {
            // Password is incorrect
            http_response_code(401);
            $error = array('status' => 'error', 'message' => 'Incorrect password.');
            echo json_encode($error);
        }
    } else {
        // User not found
        http_response_code(404);
        $error = array('status' => 'error', 'message' => 'User not found.');
        echo json_encode($error);
    }
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
    $productId = $input_data['id'];
    $title = $input_data['title'];
    $description = $input_data['description'];
    $image = $input_data['image'];
    $pricing = $input_data['pricing'];
    $shipping_cost = $input_data['shipping_cost'];

    // Update the user in the database
    $sql = "UPDATE users SET title='$title', description='$description', image='$image', pricing='$pricing', shipping_cost='$shipping_cost' WHERE id=$productId";

    if ($conn->query($sql) === TRUE) {
        // User updated successfully

        // Retrieve the updated user from the database
        $selectSql = "SELECT * FROM users WHERE id = $productId";
        $result = $conn->query($selectSql);
        $updatedProduct = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'User updated successfully.',
            'user' => $updatedProduct
        );

        // Set the HTTP status code to 200 (OK)
        http_response_code(200);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to update user
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'User update failed.'));
    }
}

// Handle HTTP DELETE request (Delete a user)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Retrieve the user ID from the request parameters or body
    $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;

    if ($user_id) {
        // Delete the user from the database
        $deleteSql = "DELETE FROM users WHERE id = $user_id";

        if ($conn->query($deleteSql) === TRUE) {
            // User deleted successfully
            echo json_encode(array('status' => 'success', 'message' => 'User deleted successfully.'));
        } else {
            // Failed to delete user
            http_response_code(400);
            echo json_encode(array('status' => 'error', 'message' => 'User deletion failed.'));
        }
    } else {
        // Invalid request, user_id is missing
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Invalid request. Missing user_id.'));
    }
}

// Close the database connection
$conn->close();
?>