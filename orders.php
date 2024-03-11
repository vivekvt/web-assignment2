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

// Handle HTTP GET request (Get a order)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if an ID parameter is provided
    if (isset($_GET['order_id'])) {
        $id = $_GET['order_id'];

        // Retrieve order by ID
        $sql = "SELECT * FROM orders WHERE id = $id";
        $result = $conn->query($sql);

        // Check if order found
        if ($result->num_rows > 0) {
            // Store order in an associative array
            $order = $result->fetch_assoc();

            // Return order as JSON
            echo json_encode($order);
        } else {
            // Order not found
            echo json_encode(array('message' => 'Order not found.'));
        }
    } else {
        // Retrieve all orders
        $sql = "SELECT * FROM orders";
        $result = $conn->query($sql);

        // Check if any orders found
        if ($result->num_rows > 0) {
            // Store orders in an associative array
            $orders = array();
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row; // Append each order to the array
            }

            // Return orders as JSON
            echo json_encode($orders);
        } else {
            // No orders found
            echo json_encode(array('message' => 'No orders found.'));
        }
    }
}

// Handle HTTP POST request (Create a order)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $input_data = json_decode(file_get_contents("php://input"), true);
    // $product_id = $input_data['product_id'];
    $user_id = $input_data['user_id'];
    $order_items_json = $input_data['order_items'];
    $order_items = json_encode($order_items_json);
    $total_price = $input_data['total_price'];

    // Insert new order into the database
    $sql = "INSERT INTO orders (user_id, order_items, total_price)
            VALUES ('$user_id', '$order_items', '$total_price')";

    if ($conn->query($sql) === TRUE) {
        // Order inserted successfully

        // Retrieve the inserted order from the database
        $insertedId = $conn->insert_id;
        $selectSql = "SELECT * FROM orders WHERE id = $insertedId";
        $result = $conn->query($selectSql);
        $insertedOrder = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'Order created successfully.',
            'order' => $insertedOrder
        );

        // Set the HTTP status code to 201 (Created)
        http_response_code(201);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to insert order
        http_response_code(400);
        $error = array('status' => 'error', 'message' => 'Order creation failed', 'error' => $conn->error);
        echo json_encode($error);
    }
}

// Handle HTTP PUT request (Update a order)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Retrieve PUT data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $order_id = $input_data['id'];
    $user_id = $input_data['user_id'];
    $order_items_json = $input_data['order_items'];
    $order_items = json_encode($order_items_json);
    $total_price = $input_data['total_price'];

    // Update the order in the database
    $sql = "UPDATE orders SET user_id='$user_id', order_items='$order_items', total_price='$total_price' WHERE id=$order_id";

    if ($conn->query($sql) === TRUE) {
        // Order updated successfully

        // Retrieve the updated order from the database
        $selectSql = "SELECT * FROM orders WHERE id = $order_id";
        $result = $conn->query($selectSql);
        $updatedComment = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'Order updated successfully.',
            'order' => $updatedComment
        );

        // Set the HTTP status code to 200 (OK)
        http_response_code(200);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to update order
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Order update failed.'));
    }

}

// Handle HTTP DELETE request (Delete a order)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Retrieve the order ID from the request parameters or body
    $id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null;

    if ($id) {
        // Delete the order from the database
        $deleteSql = "DELETE FROM orders WHERE id = $id";

        if ($conn->query($deleteSql) === TRUE) {
            // Order deleted successfully
            echo json_encode(array('status' => 'success', 'message' => 'Order deleted successfully.'));
        } else {
            // Failed to delete order
            http_response_code(400);
            echo json_encode(array('status' => 'error', 'message' => 'Order deletion failed.'));
        }
    } else {
        // Invalid request, order_id is missing
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Invalid request. Missing order_id.'));
    }
}

// Close the database connection
$conn->close();
?>