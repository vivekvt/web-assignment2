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

// Handle HTTP GET request (Get a cart)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if an ID parameter is provided
    if (isset($_GET['comment_id'])) {
        $id = $_GET['comment_id'];

        // Retrieve cart by ID
        $sql = "SELECT * FROM carts WHERE id = $id";
        $result = $conn->query($sql);

        // Check if cart found
        if ($result->num_rows > 0) {
            // Store cart in an associative array
            $cart = $result->fetch_assoc();

            // Return cart as JSON
            echo json_encode($cart);
        } else {
            // Cart not found
            echo json_encode(array('message' => 'Cart not found.'));
        }
    } else {
        // Retrieve all carts
        $sql = "SELECT * FROM carts";
        $result = $conn->query($sql);

        // Check if any carts found
        if ($result->num_rows > 0) {
            // Store carts in an associative array
            $carts = array();
            while ($row = $result->fetch_assoc()) {
                $carts[] = $row; // Append each cart to the array
            }

            // Return carts as JSON
            echo json_encode($carts);
        } else {
            // No carts found
            echo json_encode(array('message' => 'No carts found.'));
        }
    }
}

// Handle HTTP POST request (Create a cart)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $product_id = $input_data['product_id'];
    $user_id = $input_data['user_id'];
    $quantities = $input_data['quantities'];

    // Insert new cart into the database
    $sql = "INSERT INTO carts (product_id, user_id, quantities)
            VALUES ('$product_id', '$user_id', '$quantities')";

    if ($conn->query($sql) === TRUE) {
        // Cart inserted successfully

        // Retrieve the inserted cart from the database
        $insertedId = $conn->insert_id;
        $selectSql = "SELECT * FROM carts WHERE id = $insertedId";
        $result = $conn->query($selectSql);
        $insertedItem = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'Cart created successfully.',
            'cart' => $insertedItem
        );

        // Set the HTTP status code to 201 (Created)
        http_response_code(201);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to insert cart
        http_response_code(400);
        $error = array('status' => 'error', 'message' => 'Cart creation failed', 'error' => $conn->error);
        echo json_encode($error);
    }
}

// Handle HTTP PUT request (Update a cart)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Retrieve PUT data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $id = $input_data['id'];
    $product_id = $input_data['product_id'];
    $user_id = $input_data['user_id'];
    $quantities = $input_data['quantities'];

    // Update the cart in the database
    $sql = "UPDATE carts SET product_id='$product_id', user_id='$user_id', quantities='$quantities' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        // Cart updated successfully

        // Retrieve the updated cart from the database
        $selectSql = "SELECT * FROM carts WHERE id = $id";
        $result = $conn->query($selectSql);
        $updatedRecord = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'Cart updated successfully.',
            'cart' => $updatedRecord
        );

        // Set the HTTP status code to 200 (OK)
        http_response_code(200);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to update cart
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Cart update failed.'));
    }

}

// Handle HTTP DELETE request (Delete a cart)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Retrieve the cart ID from the request parameters or body
    $id = isset($_REQUEST['cart_id']) ? $_REQUEST['cart_id'] : null;

    if ($id) {
        // Delete the cart from the database
        $deleteSql = "DELETE FROM carts WHERE id = $id";

        if ($conn->query($deleteSql) === TRUE) {
            // Cart deleted successfully
            echo json_encode(array('status' => 'success', 'message' => 'Cart deleted successfully.'));
        } else {
            // Failed to delete cart
            http_response_code(400);
            echo json_encode(array('status' => 'error', 'message' => 'Cart deletion failed.'));
        }
    } else {
        // Invalid request, cart_id is missing
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Invalid request. Missing cart_id.'));
    }
}

// Close the database connection
$conn->close();
?>