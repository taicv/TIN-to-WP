<?php
// Start output buffering to prevent any unwanted whitespace
ob_start();

/**
 * WordPress Website Generator - Main Processing Script
 * 
 * This script handles all backend processing including:
 * - Business data collection
 * - AI content generation
 * - WordPress integration
 * - Image management
 * - Progress tracking
 * - Cache management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/CacheManager.php';
require_once __DIR__ . '/VietnamBusinessCollector.php';
require_once __DIR__ . '/AIContentGenerator.php';
require_once __DIR__ . '/WordPressIntegrator.php';
require_once __DIR__ . '/ImageManager.php';

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
$aiGenerator = new AIContentGenerator(OPENAI_API_KEY, OPENAI_API_ENDPOINT);
$imageManager = new ImageManager([
    'unsplash' => UNSPLASH_ACCESS_KEY,
    'pexels' => PEXELS_API_KEY,
    'pixabay' => PIXABAY_API_KEY
]);
$cacheManager = new CacheManager();

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

// Route requests to appropriate handlers
switch ($action) {
    case 'generate_website':
        handleWebsiteGeneration($input);
        break;
        
    case 'get_progress':
        //die();
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
        
    case 'get_cache_stats':
        handleGetCacheStats();
        break;
        
    case 'clear_cache':
        handleClearCache($_GET['type'] ?? null);
        break;
        
    case 'get_cached_session':
        handleGetCachedSession($_GET['session_id'] ?? '');
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
    global $pdo, $businessCollector, $aiGenerator, $imageManager, $cacheManager;
    
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
        
        // Cache the initial session data
        $sessionData = [
            'tax_code' => $data['taxCode'],
            'color_palette' => $data['colorPalette'],
            'website_style' => $data['websiteStyle'],
            'wp_url' => $data['wpUrl'],
            'wp_username' => $data['wpUsername'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $cacheManager->cacheWebsiteSession($sessionId, $sessionData);
        
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
    global $pdo, $cacheManager;
    
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
        
        // Get additional debug information
        $debugInfo = getDebugInfo($sessionId, $progress);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'step' => $progress['current_step'],
                'progress' => $progress['step_progress'],
                'message' => $progress['status_message'],
                'completed' => $progress['completed'] == 1,
                'error' => $progress['error_message'],
                'debug_info' => $debugInfo,
                'timestamp' => $progress['updated_at'] ?? $progress['created_at']
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
    try {
        $required_fields = ['wp_url', 'wp_username', 'wp_password'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Create WordPressIntegrator instance with provided credentials
        $wpIntegrator = new WordPressIntegrator(
            $data['wp_url'],
            $data['wp_username'],
            $data['wp_password']
        );
        
        $result = $wpIntegrator->testConnection();
        
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['success'] ? 'WordPress connection successful' : $result['error'],
            'user' => $result['user'] ?? null
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
        // Basic tax code validation - check if it's a 10 or 13 digit number
        $isValid = preg_match('/^\d{10,13}$/', $taxCode);
        
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
        // For now, return mock data since the actual method doesn't exist
        $businessInfo = [
            'tax_code' => $taxCode,
            'company_name' => 'Sample Company Name',
            'address' => 'Sample Address, Vietnam',
            'business_type' => 'Limited Liability Company',
            'status' => 'Active'
        ];
        
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
 * Handle cache statistics request
 */
function handleGetCacheStats() {
    global $cacheManager;
    
    try {
        $stats = $cacheManager->getCacheStats();
        
        echo json_encode([
            'success' => true,
            'data' => $stats
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
 * Handle cache clearing request
 */
function handleClearCache($type = null) {
    global $cacheManager;
    
    try {
        $result = $cacheManager->clearCache($type);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Cache cleared successfully' : 'Failed to clear cache',
            'type' => $type ?? 'all'
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
 * Handle cached session retrieval request
 */
function handleGetCachedSession($sessionId) {
    global $cacheManager;
    
    if (empty($sessionId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Session ID is required'
        ]);
        return;
    }
    
    try {
        $sessionData = $cacheManager->getCachedWebsiteSession($sessionId);
        
        if (!$sessionData) {
            throw new Exception('Cached session not found');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $sessionData
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

/**
 * Get debug information for a session
 */
function getDebugInfo($sessionId, $progress) {
    global $cacheManager;
    
    $debugInfo = [
        'session_id' => $sessionId,
        'current_step' => $progress['current_step'],
        'step_progress' => $progress['step_progress'],
        'status_message' => $progress['status_message'],
        'last_updated' => $progress['updated_at'] ?? $progress['created_at'],
        'step_details' => []
    ];
    
    // Get step-specific debug information
    switch ($progress['current_step']) {
        case 'business':
            $debugInfo['step_details'] = getBusinessStepDebugInfo($sessionId);
            break;
        case 'content':
            $debugInfo['step_details'] = getContentStepDebugInfo($sessionId);
            break;
        case 'wordpress':
            $debugInfo['step_details'] = getWordPressStepDebugInfo($sessionId);
            break;
        case 'images':
            $debugInfo['step_details'] = getImagesStepDebugInfo($sessionId);
            break;
        case 'error':
            $debugInfo['step_details'] = getErrorStepDebugInfo($sessionId);
            break;
    }
    
    // Get cached session data
    $cachedSession = $cacheManager->getCachedWebsiteSession($sessionId);
    if ($cachedSession) {
        $debugInfo['cached_session'] = $cachedSession;
    }
    
    // Get recent log entries
    $debugInfo['recent_logs'] = getRecentLogs($sessionId);
    
    return $debugInfo;
}

/**
 * Get debug information for business step
 */
function getBusinessStepDebugInfo($sessionId) {
    global $cacheManager;
    
    $debugInfo = [
        'step' => 'business',
        'description' => 'Collecting business information from various sources',
        'sources' => [
            'official_portal' => 'Official Vietnam Business Portal',
            'web_search' => 'Web Search Engines',
            'business_directories' => 'Business Directories'
        ],
        'cache_status' => 'unknown',
        'business_data' => null,
        'errors' => []
    ];
    
    // Check if business data is cached
    $cachedBusinessData = $cacheManager->getCachedBusinessData($sessionId);
    if ($cachedBusinessData) {
        $debugInfo['cache_status'] = 'hit';
        $debugInfo['business_data'] = $cachedBusinessData;
    } else {
        $debugInfo['cache_status'] = 'miss';
    }
    
    // Check for cached API responses
    $apiResponses = [];
    $sources = ['official_portal', 'web_search', 'business_directories'];
    foreach ($sources as $source) {
        $cachedResponse = $cacheManager->getCachedAPIResponse($source . '_' . $sessionId);
        if ($cachedResponse) {
            $apiResponses[$source] = [
                'cached' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            $apiResponses[$source] = [
                'cached' => false,
                'status' => 'not_tried'
            ];
        }
    }
    $debugInfo['api_responses'] = $apiResponses;
    
    return $debugInfo;
}

/**
 * Get debug information for content step
 */
function getContentStepDebugInfo($sessionId) {
    global $cacheManager;
    
    $debugInfo = [
        'step' => 'content',
        'description' => 'Generating website content using AI',
        'ai_status' => 'unknown',
        'content_data' => null
    ];
    
    // Check if content is cached
    $cachedContent = $cacheManager->getCachedWebsiteSession($sessionId . '_content');
    if ($cachedContent) {
        $debugInfo['ai_status'] = 'completed';
        $debugInfo['content_data'] = $cachedContent;
    }
    
    return $debugInfo;
}

/**
 * Get debug information for WordPress step
 */
function getWordPressStepDebugInfo($sessionId) {
    global $cacheManager;
    
    $debugInfo = [
        'step' => 'wordpress',
        'description' => 'Creating website in WordPress',
        'connection_status' => 'unknown',
        'creation_status' => 'unknown',
        'wordpress_data' => null
    ];
    
    // Check if WordPress data is cached
    $cachedWordPress = $cacheManager->getCachedWebsiteSession($sessionId . '_wordpress');
    if ($cachedWordPress) {
        $debugInfo['creation_status'] = 'completed';
        $debugInfo['wordpress_data'] = $cachedWordPress;
    }
    
    return $debugInfo;
}

/**
 * Get debug information for images step
 */
function getImagesStepDebugInfo($sessionId) {
    global $cacheManager;
    
    $debugInfo = [
        'step' => 'images',
        'description' => 'Adding images to website',
        'image_status' => 'unknown',
        'image_data' => null
    ];
    
    // Check if image data is cached
    $cachedImages = $cacheManager->getCachedWebsiteSession($sessionId . '_images');
    if ($cachedImages) {
        $debugInfo['image_status'] = 'completed';
        $debugInfo['image_data'] = $cachedImages;
    }
    
    return $debugInfo;
}

/**
 * Get debug information for error step
 */
function getErrorStepDebugInfo($sessionId) {
    global $cacheManager;
    
    $debugInfo = [
        'step' => 'error',
        'description' => 'Error occurred during processing',
        'error_data' => null
    ];
    
    // Check if error data is cached
    $cachedError = $cacheManager->getCachedWebsiteSession($sessionId . '_error');
    if ($cachedError) {
        $debugInfo['error_data'] = $cachedError;
    }
    
    return $debugInfo;
}

/**
 * Get recent log entries for a session
 */
function getRecentLogs($sessionId) {
    $logFile = LOG_FILE;
    $logs = [];
    
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLines = array_slice($lines, -20); // Get last 20 lines
        
        foreach ($recentLines as $line) {
            $logEntry = json_decode($line, true);
            if ($logEntry && strpos($logEntry['message'], $sessionId) !== false) {
                $logs[] = $logEntry;
            }
        }
    }
    
    return array_slice($logs, -10); // Return last 10 session-related logs
}

// Clean and flush output buffer to prevent any unwanted whitespace
$output = ob_get_clean();
echo trim($output);