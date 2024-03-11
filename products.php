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

// Handle HTTP GET request (Get a product)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if an ID parameter is provided
    if (isset($_GET['product_id'])) {
        $product_id = $_GET['product_id'];

        // Retrieve product by ID
        $sql = "SELECT * FROM products WHERE id = $product_id";
        $result = $conn->query($sql);

        // Check if product found
        if ($result->num_rows > 0) {
            // Store product in an associative array
            $product = $result->fetch_assoc();

            // Return product as JSON
            echo json_encode($product);
        } else {
            // Product not found
            echo json_encode(array('message' => 'Product not found.'));
        }
    } else {
        // Retrieve all products
        $sql = "SELECT * FROM products";
        $result = $conn->query($sql);

        // Check if any products found
        if ($result->num_rows > 0) {
            // Store products in an associative array
            $products = array();
            while ($row = $result->fetch_assoc()) {
                $products[] = $row; // Append each product to the array
            }

            // Return products as JSON
            echo json_encode($products);
        } else {
            // No products found
            echo json_encode(array('message' => 'No products found.'));
        }
    }
}

// Handle HTTP POST request (Create a product)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $title = $input_data['title'];
    $description = $input_data['description'];
    $image = $input_data['image'];
    $pricing = $input_data['pricing'];
    $shipping_cost = $input_data['shipping_cost'];

    // Insert new product into the database
    $sql = "INSERT INTO products (title, description, image, pricing, shipping_cost)
            VALUES ('$title', '$description', '$image', '$pricing', '$shipping_cost')";

    if ($conn->query($sql) === TRUE) {
        // Product inserted successfully

        // Retrieve the inserted product from the database
        $insertedProductId = $conn->insert_id;
        $selectSql = "SELECT * FROM products WHERE id = $insertedProductId";
        $result = $conn->query($selectSql);
        $insertedProduct = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'Product created successfully.',
            'product' => $insertedProduct
        );

        // Set the HTTP status code to 201 (Created)
        http_response_code(201);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to insert product
        http_response_code(400);
        $error = array('status' => 'error', 'message' => 'User creation failed', 'error' => $conn->error);
        echo json_encode($error);
    }
}

// Handle HTTP PUT request (Update a product)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Retrieve PUT data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $productId = $input_data['id'];
    $title = $input_data['title'];
    $description = $input_data['description'];
    $image = $input_data['image'];
    $pricing = $input_data['pricing'];
    $shipping_cost = $input_data['shipping_cost'];

    // Update the product in the database
    $sql = "UPDATE products SET title='$title', description='$description', image='$image', pricing='$pricing', shipping_cost='$shipping_cost' WHERE id=$productId";

    if ($conn->query($sql) === TRUE) {
        // Product updated successfully

        // Retrieve the updated product from the database
        $selectSql = "SELECT * FROM products WHERE id = $productId";
        $result = $conn->query($selectSql);
        $updatedProduct = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'Product updated successfully.',
            'product' => $updatedProduct
        );

        // Set the HTTP status code to 200 (OK)
        http_response_code(200);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to update product
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Product update failed.'));
    }
}

// Handle HTTP DELETE request (Delete a product)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Retrieve the product ID from the request parameters or body
    $product_id = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : null;

    if ($product_id) {
        // Delete the product from the database
        $deleteSql = "DELETE FROM products WHERE id = $product_id";

        if ($conn->query($deleteSql) === TRUE) {
            // Product deleted successfully
            echo json_encode(array('status' => 'success', 'message' => 'Product deleted successfully.'));
        } else {
            // Failed to delete product
            http_response_code(400);
            echo json_encode(array('status' => 'error', 'message' => 'Product deletion failed.'));
        }
    } else {
        // Invalid request, product_id is missing
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Invalid request. Missing product_id.'));
    }
}

// Close the database connection
$conn->close();
?>