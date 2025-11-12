<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/QuizController.php';




// Add this at the VERY TOP of your api.php - before any output or headers
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Lax');

session_set_cookie_params([
    'lifetime' => 86400, // 1 day
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
// Check if user is logged in
if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    $username = $_SESSION['user']['username'];
}

// Check if quiz session ID exists in session
if (isset($_SESSION['quizSessionId'])) {
    $quizSessionId = $_SESSION['quizSessionId'];
}

// Headers for CORS and JSON responses
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header("Access-Control-Allow-Credentials: true");
header("Vary: Origin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Parse request path (more robust handling)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = dirname($_SERVER['SCRIPT_NAME']);

// Remove base path and any trailing api.php if present
$requestPath = trim(substr($requestUri, strlen($basePath)), '/');
$requestPath = preg_replace('/^api\.php\/?/', '', $requestPath);

$method = $_SERVER['REQUEST_METHOD'];

// Initialize controllers
$authController = new AuthController();
$quizController = new QuizController();

try {
    // Route the request
    switch (true) {
        // Auth routes
        case $requestPath === 'auth/register' && $method === 'POST':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON input: ' . json_last_error_msg(), 400);
                }

                $result = $authController->register($data);
                echo json_encode($result);
            } catch (Exception $e) {
                http_response_code($e->getCode() ?: 400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => $e->getCode()
                ]);
            }
            break;

        case $requestPath === 'auth/login' && $method === 'POST':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON input', 400);
                }

                $result = $authController->login($data);

                // Store user in session
                $_SESSION['user'] = [
                    'id' => $result['userId'],
                    'username' => $result['username'],
                    'email' => $data['email'],
                    'logged_in' => true
                ];

                // Regenerate session ID to prevent fixation attacks
                session_regenerate_id(true);

                // Explicitly save the session
                session_write_close();

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'token' => $result['token'],
                    'user' => $_SESSION['user']
                ]);
            } catch (Exception $e) {
                http_response_code($e->getCode() ?: 401);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            break;


        case $requestPath === 'auth/check' && $method === 'GET':
            // Ensure session is started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $authenticated = isset($_SESSION['user']);

            // Clear session if not authenticated
            if (!$authenticated) {
                session_unset();    // Unset all session variables
                session_destroy();  // Destroy the session
            }

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'authenticated' => $authenticated,
                'user' => $_SESSION['user'] ?? null
            ]);
            break;



            case $requestPath === 'answers' && $method === 'POST':
                try { 
                    // Decode input data
                    $data = json_decode(file_get_contents('php://input'), true);
            
                    // Check required session values
                    if (!isset($_SESSION['user']['id']) || !isset($_SESSION['quizSessionId'])) {
                        throw new Exception("User or session not found in session data", 401);
                    }
            
                    // Add session values to data
                    $data['userId'] = $_SESSION['user']['id'];
                    $data['sessionId'] = $_SESSION['quizSessionId'];
            
                    // Required fields (excluding userId and sessionId which now come from session)
                    $required = ['questionId', 'userAnswer', 'isCorrect', 'difficulty', 'timeLeft'];
                    foreach ($required as $field) {
                        if (!isset($data[$field])) {
                            throw new Exception("Missing required field: $field", 400);
                        }
                    }
            
                    // Save user answer
                    $result = $quizController->saveUserAnswer($data['userId'], $data);
            
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'answerId' => $result,
                        'sessionId' => $data['sessionId'] // Echo back for client reference if needed
                    ]);
                } catch (Exception $e) {
                    http_response_code($e->getCode() ?: 400);
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                break;
            

    
            break;
  
  
  
            case $requestPath === 'auth/logout' && $method === 'POST':
            // Clear session data
            $_SESSION = [];

            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Destroy session
            session_destroy();

            http_response_code(200);
            echo json_encode(['success' => true]);
            break;




        case $requestPath === 'questions' && $method === 'GET':
            try {
                $difficulty = $_GET['difficulty'] ?? 'easy';
                $count = $_GET['count'] ?? 5;

                $questions = $quizController->getQuestions($difficulty, $count);
                header('Content-Type: application/json');
                echo json_encode($questions);
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case $requestPath === 'leaderboard' && $method === 'GET':
            try {
                $difficulty = $_GET['difficulty'] ?? null;
                $limit = min((int)($_GET['limit'] ?? 10), 100);

                // Get leaderboard data
                $results = $quizController->getLeaderboard($difficulty, $limit);

                // Ensure we always return a consistent format
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => $results
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to retrieve leaderboard',
                    'error' => $e->getMessage()
                ]);
            }
            break;
        case $requestPath === 'results' && $method === 'GET':
            try {
                // Make sure user is logged in
                if (!isset($_SESSION['user']['id'])) {
                    throw new Exception("User not logged in", 401);
                }

                $userId = $_SESSION['user']['id'];
                $sessionId = $_SESSION['quizSessionId'] ?? null;

                if (!$sessionId) {
                    throw new Exception("Quiz session ID not found", 400);
                }

                $results = $quizController->getUserAnswers($userId, $sessionId);

                $responseData = [
                    'success' => true,
                    'data' => [
                        'responses' => $results['responses'] ?? [],
                        'totalQuestions' => $results['totalQuestions'] ?? 0,
                        'correctAnswers' => $results['correctAnswers'] ?? 0,
                        'difficulty' => $results['difficulty'] ?? 'medium',
                        'sessionId' => $sessionId
                    ]
                ];

                echo json_encode($responseData);
            } catch (Exception $e) {
                http_response_code($e->getCode() ?: 400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => null
                ]);
            }
            break;
            case $requestPath === 'results' && $method === 'POST':
                try {
                    // Decode JSON input
                    $data = json_decode(file_get_contents('php://input'), true);
            
                    // Check required session values
                    if (!isset($_SESSION['user']['id']) || !isset($_SESSION['quizSessionId'])) {
                        throw new Exception("User or session not found in session data", 401);
                    }
            
                    // Extract from session
                    $data['userId'] = $_SESSION['user']['id'];
                    $data['sessionId'] = $_SESSION['quizSessionId'];
            
                    // Check required POST body fields
                    if (!isset($data['score'], $data['correctAnswers'], $data['totalQuestions'], $data['difficulty'])) {
                        throw new Exception("Missing required fields", 400);
                    }
            
                    // Save result
                    $quizController->saveResult($data);
            
                    // Destroy the session ID after saving
                    unset($_SESSION['quizSessionId']);
            
                    echo json_encode([
                        'success' => true,
                        'message' => 'Result saved successfully'
                    ]);
                } catch (Exception $e) {
                    http_response_code($e->getCode() ?: 400);
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                break;  default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found', 'path' => $requestPath]);
            break;
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode() ?: 500,
        'trace' => $e->getTrace() // Remove in production
    ]);
}
