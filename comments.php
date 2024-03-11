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

// Handle HTTP GET request (Get a comment)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if an ID parameter is provided
    if (isset($_GET['comment_id'])) {
        $comment_id = $_GET['comment_id'];

        // Retrieve comment by ID
        $sql = "SELECT * FROM comments WHERE id = $comment_id";
        $result = $conn->query($sql);

        // Check if comment found
        if ($result->num_rows > 0) {
            // Store comment in an associative array
            $comment = $result->fetch_assoc();

            // Return comment as JSON
            echo json_encode($comment);
        } else {
            // Comment not found
            echo json_encode(array('message' => 'Comment not found.'));
        }
    } else {
        // Retrieve all comments
        $sql = "SELECT * FROM comments";
        $result = $conn->query($sql);

        // Check if any comments found
        if ($result->num_rows > 0) {
            // Store comments in an associative array
            $comments = array();
            while ($row = $result->fetch_assoc()) {
                $comments[] = $row; // Append each comment to the array
            }

            // Return comments as JSON
            echo json_encode($comments);
        } else {
            // No comments found
            echo json_encode(array('message' => 'No comments found.'));
        }
    }
}

// Handle HTTP POST request (Create a comment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $product_id = $input_data['product_id'];
    $user_id = $input_data['user_id'];
    $rating = $input_data['rating'];
    $image = $input_data['image'];
    $text = $input_data['text'];

    // Insert new comment into the database
    $sql = "INSERT INTO comments (product_id, user_id, rating, image, text)
            VALUES ('$product_id', '$user_id', '$rating', '$image', '$text')";

    if ($conn->query($sql) === TRUE) {
        // Comment inserted successfully

        // Retrieve the inserted comment from the database
        $insertedId = $conn->insert_id;
        $selectSql = "SELECT * FROM comments WHERE id = $insertedId";
        $result = $conn->query($selectSql);
        $insertedComment = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'Comment created successfully.',
            'comment' => $insertedComment
        );

        // Set the HTTP status code to 201 (Created)
        http_response_code(201);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to insert comment
        http_response_code(400);
        $error = array('status' => 'error', 'message' => 'Comment creation failed', 'error' => $conn->error);
        echo json_encode($error);
    }
}

// Handle HTTP PUT request (Update a comment)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Retrieve PUT data
    $input_data = json_decode(file_get_contents("php://input"), true);
    $comment_id = $input_data['id'];
    $product_id = $input_data['product_id'];
    $user_id = $input_data['user_id'];
    $rating = $input_data['rating'];
    $image = $input_data['image'];
    $text = $input_data['text'];

    // Update the comment in the database
    $sql = "UPDATE comments SET product_id='$product_id', user_id='$user_id', rating='$rating', image='$image', text='$text' WHERE id=$comment_id";

    if ($conn->query($sql) === TRUE) {
        // Comment updated successfully

        // Retrieve the updated comment from the database
        $selectSql = "SELECT * FROM comments WHERE id = $comment_id";
        $result = $conn->query($selectSql);
        $updatedComment = $result->fetch_assoc();

        // Prepare the response data
        $response = array(
            'status' => 'success',
            'message' => 'Comment updated successfully.',
            'comment' => $updatedComment
        );

        // Set the HTTP status code to 200 (OK)
        http_response_code(200);

        // Return the response as JSON
        echo json_encode($response);
    } else {
        // Failed to update comment
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Comment update failed.'));
    }

}

// Handle HTTP DELETE request (Delete a comment)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Retrieve the comment ID from the request parameters or body
    $comment_id = isset($_REQUEST['comment_id']) ? $_REQUEST['comment_id'] : null;

    if ($comment_id) {
        // Delete the comment from the database
        $deleteSql = "DELETE FROM comments WHERE id = $comment_id";

        if ($conn->query($deleteSql) === TRUE) {
            // Comment deleted successfully
            echo json_encode(array('status' => 'success', 'message' => 'Comment deleted successfully.'));
        } else {
            // Failed to delete comment
            http_response_code(400);
            echo json_encode(array('status' => 'error', 'message' => 'Comment deletion failed.'));
        }
    } else {
        // Invalid request, comment_id is missing
        http_response_code(400);
        echo json_encode(array('status' => 'error', 'message' => 'Invalid request. Missing comment_id.'));
    }
}

// Close the database connection
$conn->close();
?>