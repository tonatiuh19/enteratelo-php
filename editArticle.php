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

function save_uploaded_image($file, $uploadDir) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid() . '_' . basename($file['name']);
        $destPath = $uploadDir . '/' . $fileName;
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return $fileName;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];

    if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false) {
        echo json_encode(['success' => false, 'error' => 'Content-Type must be multipart/form-data']);
        exit;
    }

    $article_id = intval($_POST['id'] ?? 0);
    if (!$article_id) {
        echo json_encode(['success' => false, 'error' => 'Missing article id']);
        exit;
    }

    // Get fields (same as insertArticle.php)
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $slug = $conn->real_escape_string($_POST['slug'] ?? '');
    $excerpt = $conn->real_escape_string($_POST['excerpt'] ?? '');
    $content_blocks = json_decode($_POST['content_blocks'] ?? '[]', true);
    $category_id = $_POST['category_id'];
    $meta_title = $conn->real_escape_string($_POST['meta_title'] ?? '');
    $meta_description = $conn->real_escape_string($_POST['meta_description'] ?? '');
    $meta_keywords = $conn->real_escape_string($_POST['meta_keywords'] ?? '');
    $canonical_url = $conn->real_escape_string($_POST['canonical_url'] ?? '');
    $featured_image_caption = $conn->real_escape_string($_POST['featured_image_caption'] ?? '');
    $gallery = $_POST['gallery'] ?? '[]';
    if (empty($gallery) || $gallery === '') {
        $gallery = '[]';
    }
    $gallery = $conn->real_escape_string($gallery);
    $status = $conn->real_escape_string($_POST['status'] ?? 'draft');
    $published_at = $conn->real_escape_string($_POST['published_at'] ?? null);
    $scheduled_at = $conn->real_escape_string($_POST['scheduled_at'] ?? null);
    $is_featured = intval($_POST['is_featured'] ?? 0);
    $is_trending = intval($_POST['is_trending'] ?? 0);
    $is_breaking_news = intval($_POST['is_breaking_news'] ?? $_POST['is_breaking'] ?? 0);
    $is_editors_pick = intval($_POST['is_editors_pick'] ?? 0);
    $tags = $conn->real_escape_string(json_encode($_POST['tags'] ?? []));
    $external_source = $conn->real_escape_string($_POST['external_source'] ?? '');
    $language = $conn->real_escape_string($_POST['language'] ?? 'es');
    $updated_at = date('Y-m-d H:i:s');

    $uploadDir = __DIR__ . "/../data/articles/$article_id/images_used";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle featured image upload
    $featured_image_url = '';
    $featured_image_alt = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $fileName = save_uploaded_image($_FILES['featured_image'], $uploadDir);
        if ($fileName) {
            $featured_image_url = "/data/articles/$article_id/images_used/$fileName";
            $featured_image_alt = $fileName;
        }
    } else if (!empty($_POST['image_url'])) {
        $featured_image_url = $conn->real_escape_string($_POST['image_url']);
        $featured_image_alt = '';
    }

    // Handle images in content blocks
    foreach ($content_blocks as $idx => &$block) {
        if ($block['type'] === 'image' && isset($_FILES["content_image_$idx"])) {
            $imgFile = $_FILES["content_image_$idx"];
            $imgName = save_uploaded_image($imgFile, $uploadDir);
            if ($imgName) {
                $block['url'] = "/data/articles/$article_id/images_used/$imgName";
            }
        }
    }
    unset($block);

    function blocks_to_html($blocks) {
        $html = '';
        foreach ($blocks as $block) {
            $content = isset($block['content']) ? $block['content'] : '';
            $formatting = is_array($content) ? $content : [];
            switch ($block['type']) {
                case 'text':
                    $html .= '<p>' . htmlspecialchars(is_string($content) ? $content : '') . '</p>';
                    break;
                case 'quote':
                    $html .= '<blockquote>' . htmlspecialchars(is_string($content) ? $content : '') . '</blockquote>';
                    break;
                case 'image':
                    $url = $block['url'] ?? '';
                    $alt = $formatting['alt'] ?? '';
                    $caption = $formatting['caption'] ?? '';
                    $html .= '<div style="text-align:center;margin:24px 0;">';
                    $html .= '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars($alt) . '" style="max-width:100%;border-radius:8px;" />';
                    if ($caption) {
                        $html .= '<p style="font-size:14px;color:#64748b;font-style:italic;">' . htmlspecialchars($caption) . '</p>';
                    }
                    $html .= '</div>';
                    break;
                default:
                    $html .= '<p>' . htmlspecialchars(is_string($content) ? $content : '') . '</p>';
            }
        }
        return $html;
    }
    $content_html = blocks_to_html($content_blocks);

    // Update the article
    $sql = "UPDATE articles SET
        title = '$title',
        slug = '$slug',
        excerpt = '$excerpt',
        content = '" . $conn->real_escape_string($content_html) . "',
        content_blocks = '" . $conn->real_escape_string(json_encode($content_blocks)) . "',
        category_id = $category_id,
        meta_title = '$meta_title',
        meta_description = '$meta_description',
        meta_keywords = '$meta_keywords',
        canonical_url = '$canonical_url',
        featured_image_url = '$featured_image_url',
        featured_image_alt = '$featured_image_alt',
        featured_image_caption = '$featured_image_caption',
        gallery = '$gallery',
        status = '$status',
        published_at = " . ($published_at ? "'$published_at'" : "NULL") . ",
        scheduled_at = " . ($scheduled_at ? "'$scheduled_at'" : "NULL") . ",
        is_featured = $is_featured,
        is_trending = $is_trending,
        is_breaking_news = $is_breaking_news,
        is_editors_pick = $is_editors_pick,
        tags = '$tags',
        external_source = '$external_source',
        language = '$language',
        updated_at = '$updated_at'
        WHERE id = $article_id
    ";

    if ($conn->query($sql)) {
        $result = $conn->query("SELECT * FROM articles WHERE id = $article_id");
        $article = $result->fetch_assoc();
        $response['success'] = true;
        $response['article'] = $article;
    } else {
        $response['error'] = $conn->error;
    }

    echo json_encode($response);
}

$conn->close();
?>