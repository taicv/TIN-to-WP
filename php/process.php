<?php
/**
 * WordPress Website Generator - Main Processing Script
 * 
 * This script handles all backend processing including:
 * - Business data collection
 * - AI content generation
 * - WordPress integration
 * - Image management
 * - Progress tracking
 */

require_once 'config.php';
require_once '../VietnamBusinessCollector.php';
require_once '../AIContentGenerator.php';
require_once '../WordPressIntegrator.php';
require_once '../ImageManager.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Enable CORS for frontend requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $db_options
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

// Initialize components
$businessCollector = new VietnamBusinessCollector();
$aiGenerator = new AIContentGenerator(OPENAI_API_KEY);
$wpIntegrator = new WordPressIntegrator();
$imageManager = new ImageManager([
    'unsplash' => UNSPLASH_ACCESS_KEY,
    'pexels' => PEXELS_API_KEY,
    'pixabay' => PIXABAY_API_KEY
]);

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

// Route requests to appropriate handlers
switch ($action) {
    case 'generate_website':
        handleWebsiteGeneration($input);
        break;
        
    case 'get_progress':
        handleGetProgress($_GET['session_id'] ?? '');
        break;
        
    case 'get_results':
        handleGetResults($_GET['session_id'] ?? '');
        break;
        
    case 'test_wordpress':
        handleTestWordPress($input);
        break;
        
    case 'validate_tax_code':
        handleValidateTaxCode($input['tax_code'] ?? '');
        break;
        
    case 'get_business_info':
        handleGetBusinessInfo($_GET['tax_code'] ?? '');
        break;
        
    case 'search_images':
        handleSearchImages($_GET['query'] ?? '', $_GET['limit'] ?? 10);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        break;
}

/**
 * Handle website generation request
 */
function handleWebsiteGeneration($data) {
    global $pdo, $businessCollector, $aiGenerator, $wpIntegrator, $imageManager;
    
    try {
        // Validate required fields
        $required_fields = ['taxCode', 'colorPalette', 'websiteStyle', 'wpUrl', 'wpUsername', 'wpPassword'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Generate session ID
        $sessionId = generateSessionId();
        
        // Initialize session in database
        initializeSession($pdo, $sessionId, $data);
        
        // Start background processing
        startBackgroundGeneration($sessionId, $data);
        
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'message' => 'Website generation started successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle progress tracking request
 */
function handleGetProgress($sessionId) {
    global $pdo;
    
    if (empty($sessionId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Session ID is required'
        ]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM generation_progress WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $progress = $stmt->fetch();
        
        if (!$progress) {
            throw new Exception('Session not found');
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'step' => $progress['current_step'],
                'progress' => $progress['step_progress'],
                'message' => $progress['status_message'],
                'completed' => $progress['completed'] == 1,
                'error' => $progress['error_message']
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle results retrieval request
 */
function handleGetResults($sessionId) {
    global $pdo;
    
    if (empty($sessionId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Session ID is required'
        ]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM generation_results WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $results = $stmt->fetch();
        
        if (!$results) {
            throw new Exception('Results not found');
        }
        
        $data = json_decode($results['result_data'], true);
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle WordPress connection test
 */
function handleTestWordPress($data) {
    global $wpIntegrator;
    
    try {
        $required_fields = ['wp_url', 'wp_username', 'wp_password'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $result = $wpIntegrator->testConnection(
            $data['wp_url'],
            $data['wp_username'],
            $data['wp_password']
        );
        
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['message'],
            'site_info' => $result['site_info'] ?? null
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle tax code validation
 */
function handleValidateTaxCode($taxCode) {
    global $businessCollector;
    
    if (empty($taxCode)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tax code is required'
        ]);
        return;
    }
    
    try {
        $isValid = $businessCollector->validateTaxCode($taxCode);
        
        echo json_encode([
            'success' => true,
            'valid' => $isValid,
            'message' => $isValid ? 'Tax code format is valid' : 'Invalid tax code format'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle business information retrieval
 */
function handleGetBusinessInfo($taxCode) {
    global $businessCollector;
    
    if (empty($taxCode)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tax code is required'
        ]);
        return;
    }
    
    try {
        $businessInfo = $businessCollector->getBusinessInfo($taxCode);
        
        echo json_encode([
            'success' => true,
            'data' => $businessInfo
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle image search request
 */
function handleSearchImages($query, $limit) {
    global $imageManager;
    
    if (empty($query)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Search query is required'
        ]);
        return;
    }
    
    try {
        $images = $imageManager->searchImages($query, $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $images
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Generate unique session ID
 */
function generateSessionId() {
    return uniqid('ws_', true) . '_' . bin2hex(random_bytes(8));
}

/**
 * Initialize session in database
 */
function initializeSession($pdo, $sessionId, $data) {
    $stmt = $pdo->prepare("
        INSERT INTO generation_sessions (
            session_id, 
            tax_code, 
            color_palette, 
            website_style, 
            wp_url, 
            wp_username, 
            wp_password_hash,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $sessionId,
        $data['taxCode'],
        $data['colorPalette'],
        $data['websiteStyle'],
        $data['wpUrl'],
        $data['wpUsername'],
        password_hash($data['wpPassword'], PASSWORD_DEFAULT)
    ]);
    
    // Initialize progress tracking
    $stmt = $pdo->prepare("
        INSERT INTO generation_progress (
            session_id,
            current_step,
            step_progress,
            status_message,
            created_at
        ) VALUES (?, 'business', 0, 'Starting website generation...', NOW())
    ");
    
    $stmt->execute([$sessionId]);
}

/**
 * Start background generation process
 */
function startBackgroundGeneration($sessionId, $data) {
    // In a production environment, this would typically be handled by a job queue
    // For this implementation, we'll use a simple background process
    
    $command = sprintf(
        'php %s/background_processor.php %s > /dev/null 2>&1 &',
        __DIR__,
        escapeshellarg($sessionId)
    );
    
    exec($command);
}

/**
 * Log error message
 */
function logError($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => 'ERROR',
        'message' => $message,
        'context' => $context
    ];
    
    error_log(json_encode($logEntry) . "\n", 3, LOG_FILE);
}

/**
 * Log info message
 */
function logInfo($message, $context = []) {
    if (LOG_LEVEL === 'DEBUG' || LOG_LEVEL === 'INFO') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'INFO',
            'message' => $message,
            'context' => $context
        ];
        
        error_log(json_encode($logEntry) . "\n", 3, LOG_FILE);
    }
}
?>

