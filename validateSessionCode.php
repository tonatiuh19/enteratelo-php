<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
require_once './vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['email']) && isset($params['code'])) {
        $email = $params['email'];
        $code = $params['code'];

        // Fetch the session code from authors_sessions
        $sql = "SELECT a.session_code, a.session, a.id, a.user_id 
                FROM authors_sessions as a 
                INNER JOIN authors as b on b.id=a.user_id 
                WHERE b.email= ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $sessionData = $result->fetch_assoc();
            $id = $sessionData['id'];
            $session_code = $sessionData['session_code'];
            $session_active = $sessionData['session'];
            $user_id = $sessionData['user_id'];
        } else {
            echo json_encode(false);
            exit;
        }
        $stmt->close();

        // Validate the session code
        if ($code == $session_code) {
            // Update the session to true (1)
            $sql = "UPDATE authors_sessions SET session = 1 WHERE id = ? AND session_code = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $code);
            $stmt->execute();
            $stmt->close();

            // Fetch and return author data
            $sql = "SELECT a.id, a.user_id, a.name, a.slug, a.email, a.bio, a.avatar_url, a.social_twitter, a.social_instagram, a.social_linkedin, a.social_facebook, a.position, a.specialization, a.article_count, a.total_views, a.total_likes, a.meta_title, a.meta_description, a.created_at FROM authors as a WHERE a.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $authorResult = $stmt->get_result();
            if ($authorResult->num_rows > 0) {
                $authorData = $authorResult->fetch_assoc();
                echo json_encode($authorData);
            } else {
                echo json_encode(false);
            }
            $stmt->close();
        } else {
            echo json_encode(false);
        }
    } else {
        echo json_encode(false);
    }
} else {
    echo json_encode(false);
}

$conn->close();
?>