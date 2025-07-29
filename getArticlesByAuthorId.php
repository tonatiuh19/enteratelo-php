<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once('db_cnn/cnn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $author_id = isset($input['author_id']) ? intval($input['author_id']) : 0;

    if ($author_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid author_id']);
        exit;
    }

    $sql = "SELECT * FROM articles WHERE author_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $author_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'articles' => $articles]);
}

$conn->close();
?>