<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['navios_user_id'])) {
        $navios_user_id = $params['navios_user_id'];

        // Delete the session from platforms_users_sessions
        $sql = "DELETE FROM navios_users_sessions WHERE navios_user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $navios_user_id);

        if ($stmt->execute()) {
            echo json_encode(true);
        } else {
            echo json_encode(false);
        }

        $stmt->close();
    } else {
        echo json_encode(["message" => "Invalid input data"]);
    }
} else {
    echo json_encode(["message" => "Invalid request method"]);
}

$conn->close();
?>