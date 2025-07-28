<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once('db_cnn/cnn.php');
require_once './vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['email']) && isset($params['name'])) {
        $email = $params['email'];
        $name = $params['name'];
        $bio = $params['bio'] ?? '';

        if (empty($email) || empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and name are required']);
            exit;
        }

        // Check if author already exists
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM authors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result['count'] > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Author already exists']);
            exit;
        }

        // Insert new author (pending approval - is_active = 0)
        $is_active = 0;
        $created_at = date('Y-m-d H:i:s');
        $sql = "INSERT INTO authors (name, email, bio, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssis", $name, $email, $bio, $is_active, $created_at);
        
        if ($stmt->execute()) {
            $author_id = $conn->insert_id;
            $stmt->close();

            // Load email template
            $template_path = __DIR__ . '/welcomeAuthorPendingApproval.html';
            if (file_exists($template_path)) {
                $email_body = file_get_contents($template_path);
                
                // Replace placeholders in template
                $email_body = str_replace('{{name}}', $name, $email_body);
                $email_body = str_replace('{{email}}', $email, $email_body);
            } else {
                $email_body = "Welcome $name! Your author registration is pending approval.";
            }

            // Send welcome email
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->SMTPDebug = 2;                                     // Enable verbose debug output
            // $mail->isSMTP();   
                $mail->Host = 'mail.garbrix.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'no-reply@garbrix.com';
                $mail->Password = 'Mailer123';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 469;
                $mail->CharSet = 'UTF-8';

                //Recipients
                $mail->setFrom('no-reply@garbrix.com', 'Enteratelos');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Bienvenido a Enteratelos - Registro Pendiente de Aprobación';
                $mail->Body = $email_body;

                $mail->send();
                echo json_encode(['success' => true, 'message' => 'Author registered successfully', 'id' => $author_id]);
            } catch (Exception $e) {
                echo json_encode(['success' => true, 'message' => 'Author registered but email failed', 'id' => $author_id, 'email_error' => $mail->ErrorInfo]);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to register author']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>