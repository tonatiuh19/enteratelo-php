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
    $slug = isset($input['slug']) ? trim($input['slug']) : '';

    if (empty($slug)) {
        echo json_encode(['success' => false, 'error' => 'Invalid slug']);
        exit;
    }

    $sql = "SELECT articles.*, authors.name AS author_name 
            FROM articles 
            LEFT JOIN authors ON articles.author_id = authors.id 
            WHERE articles.slug = ? AND articles.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Increment view_count
        $article_id = $row['id'];
        $updateSql = "UPDATE articles SET view_count = view_count + 1 WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $article_id);
        $updateStmt->execute();
        $updateStmt->close();

        echo json_encode(['success' => true, 'article' => $row]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Article not found']);
    }
    $stmt->close();
}

$conn->close();
?>