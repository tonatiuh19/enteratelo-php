<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once('db_cnn/cnn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "SELECT a.id, a.name, a.slug, a.description, a.icon, a.color, a.parent_id, a.sort_order, a.is_active, a.meta_title, a.meta_description, a.created_at, a.updated_at FROM categories as a WHERE a.is_active = 1";
    $result = $conn->query($sql);

    $categories = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    echo json_encode($categories);
}

$conn->close();
?>