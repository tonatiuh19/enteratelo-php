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
    $category_slug = isset($input['slug']) ? trim($input['slug']) : '';

    if (empty($category_slug)) {
        echo json_encode(['success' => false, 'error' => 'Invalid category slug']);
        exit;
    }

    $sql = "SELECT a.id, a.title, a.slug, a.excerpt, a.content, a.category_id, a.author_id, 
                   a.meta_title, a.meta_description, a.meta_keywords, a.canonical_url, 
                   a.featured_image_url, a.featured_image_alt, a.featured_image_caption, a.gallery, 
                   a.status, a.published_at, a.scheduled_at, a.is_featured, a.is_trending, 
                   a.is_breaking_news, a.is_editors_pick, a.view_count, a.like_count, a.comment_count, 
                   a.share_count, a.estimated_read_time, a.word_count, a.tags, a.external_source, 
                   a.language, a.created_at, a.updated_at, a.is_active, 
                   authors.name AS author_name
            FROM articles as a
            INNER JOIN categories as b on b.id = a.category_id
            LEFT JOIN authors ON a.author_id = authors.id
            WHERE a.is_active = 1 AND b.slug = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category_slug);
    $stmt->execute();
    $result = $stmt->get_result();

    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }

    echo json_encode(['success' => true, 'articles' => $articles]);
    $stmt->close();
}

$conn->close();
?>