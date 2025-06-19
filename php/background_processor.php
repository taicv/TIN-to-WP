<?php
/**
 * Background Processor for WordPress Website Generator
 * 
 * This script handles the actual website generation process in the background.
 * It's called by process.php to avoid blocking the main request.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/CacheManager.php';
require_once __DIR__ . '/VietnamBusinessCollector.php';
require_once __DIR__ . '/AIContentGenerator.php';
require_once __DIR__ . '/WordPressIntegrator.php';
require_once __DIR__ . '/ImageManager.php';

// Get session ID from command line argument
$sessionId = $argv[1] ?? null;

if (!$sessionId) {
    error_log("Background processor: No session ID provided");
    exit(1);
}

try {
    // Initialize database connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $db_options
    );
    
    // Get session data
    $stmt = $pdo->prepare("SELECT * FROM generation_sessions WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    if (!$session) {
        throw new Exception("Session not found: $sessionId");
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
    
    // Update progress - Starting business data collection
    updateProgress($pdo, $sessionId, 'business', 5, 'Initializing business data collection...');
    
    // Step 1: Collect business information with detailed progress
    updateProgress($pdo, $sessionId, 'business', 10, 'Checking cache for business data...');
    
    // Check cache first
    $cachedData = $cacheManager->getCachedBusinessData($session['tax_code']);
    if ($cachedData) {
        updateProgress($pdo, $sessionId, 'business', 100, 'Using cached business data');
        $businessInfo = $cachedData;
    } else {
        updateProgress($pdo, $sessionId, 'business', 15, 'Cache miss - collecting from external sources...');
        
        // Initialize business collector with detailed logging
        $businessCollector = new VietnamBusinessCollector();
        
        // Collect with detailed progress updates
        $businessInfo = collectBusinessDataWithProgress($pdo, $sessionId, $businessCollector, $session['tax_code']);
        
        if (!$businessInfo) {
            throw new Exception("Failed to collect business information for tax code: " . $session['tax_code']);
        }
    }
    
    updateProgress($pdo, $sessionId, 'business', 100, 'Business information collected successfully');
    
    // Step 2: Generate AI content
    updateProgress($pdo, $sessionId, 'content', 10, 'Generating website content...');
    
    $content = $aiGenerator->generateWebsiteContent($businessInfo, $session['color_palette']);
    if (!$content) {
        throw new Exception("Failed to generate website content");
    }
    
    // Cache the complete content generation result
    $cacheManager->cacheWebsiteSession($sessionId . '_content', $content, 86400); // Cache for 24 hours
    
    updateProgress($pdo, $sessionId, 'content', 100, 'Website content generated successfully');
    
    // Step 3: WordPress integration
    updateProgress($pdo, $sessionId, 'wordpress', 10, 'Connecting to WordPress...');
    
    $wpIntegrator = new WordPressIntegrator(
        $session['wp_url'],
        $session['wp_username'],
        $session['wp_password_hash'] // Note: This should be decrypted or stored differently
    );
    
    // Test WordPress connection first
    $connectionTest = $wpIntegrator->testConnection();
    if (!$connectionTest['success']) {
        throw new Exception("WordPress connection failed: " . $connectionTest['error']);
    }
    
    updateProgress($pdo, $sessionId, 'wordpress', 30, 'WordPress connection established');
    
    // Create website in WordPress
    $wpResults = $wpIntegrator->createWebsite($content);
    if (!$wpResults['success']) {
        throw new Exception("WordPress website creation failed: " . implode(', ', $wpResults['errors']));
    }
    
    // Cache the WordPress creation results
    $cacheManager->cacheWebsiteSession($sessionId . '_wordpress', $wpResults, 86400);
    
    updateProgress($pdo, $sessionId, 'wordpress', 100, 'WordPress website created successfully');
    
    // Step 4: Image management
    updateProgress($pdo, $sessionId, 'images', 10, 'Adding images to website...');
    
    $imageResults = $imageManager->assignImagesForWebsite($content, $wpIntegrator);
    if (!$imageResults['success']) {
        // Log image errors but don't fail the entire process
        error_log("Image assignment errors: " . implode(', ', $imageResults['errors']));
    }
    
    // Cache the image assignment results
    $cacheManager->cacheWebsiteSession($sessionId . '_images', $imageResults, 86400);
    
    updateProgress($pdo, $sessionId, 'images', 100, 'Images added successfully');
    
    // Step 5: Complete
    updateProgress($pdo, $sessionId, 'complete', 100, 'Website generation completed successfully');
    
    // Store results
    $results = [
        'success' => true,
        'website_url' => $session['wp_url'],
        'pages_count' => count($wpResults['pages']),
        'posts_count' => count($wpResults['posts']),
        'images_count' => count($imageResults['featured_images']) + count($imageResults['content_images']),
        'generation_time' => date('Y-m-d H:i:s'),
        'next_steps' => [
            'Review and customize your website content',
            'Add your business logo and branding',
            'Configure SEO settings',
            'Set up analytics tracking',
            'Test all pages and functionality'
        ],
        'cache_info' => [
            'content_cache_key' => $sessionId . '_content',
            'wordpress_cache_key' => $sessionId . '_wordpress',
            'images_cache_key' => $sessionId . '_images'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO generation_results (session_id, result_data, created_at) 
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE result_data = VALUES(result_data), created_at = NOW()
    ");
    $stmt->execute([$sessionId, json_encode($results)]);
    
    // Cache the final results
    $cacheManager->cacheWebsiteSession($sessionId . '_final', $results, 86400);
    
    // Mark session as completed
    $stmt = $pdo->prepare("
        UPDATE generation_progress 
        SET completed = 1, completed_at = NOW() 
        WHERE session_id = ?
    ");
    $stmt->execute([$sessionId]);
    
    logInfo("Website generation completed successfully for session: $sessionId");
    
} catch (Exception $e) {
    error_log("Background processor error for session $sessionId: " . $e->getMessage());
    
    // Update progress with error
    try {
        updateProgress($pdo, $sessionId, 'error', 0, 'Error: ' . $e->getMessage());
        
        // Store error in results
        $errorResults = [
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO generation_results (session_id, result_data, created_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE result_data = VALUES(result_data), created_at = NOW()
        ");
        $stmt->execute([$sessionId, json_encode($errorResults)]);
        
        // Cache the error results
        $cacheManager = new CacheManager();
        $cacheManager->cacheWebsiteSession($sessionId . '_error', $errorResults, 86400);
        
    } catch (Exception $updateError) {
        error_log("Failed to update error status: " . $updateError->getMessage());
    }
    
    exit(1);
}

/**
 * Update progress in database
 */
function updateProgress($pdo, $sessionId, $step, $progress, $message) {
    $stmt = $pdo->prepare("
        UPDATE generation_progress 
        SET current_step = ?, step_progress = ?, status_message = ?, updated_at = NOW()
        WHERE session_id = ?
    ");
    $stmt->execute([$step, $progress, $message, $sessionId]);
    
    logInfo("Progress updated for session $sessionId: $step - $progress% - $message");
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
 * Collect business data with detailed progress tracking
 */
function collectBusinessDataWithProgress($pdo, $sessionId, $businessCollector, $taxCode) {
    $sources = [
        'official_portal' => [
            'name' => 'Official Vietnam Business Portal',
            'method' => 'collectFromOfficialPortal',
            'weight' => 20
        ],
        'web_search' => [
            'name' => 'Web Search Engines',
            'method' => 'collectFromWebSearch', 
            'weight' => 30
        ],
        'business_directories' => [
            'name' => 'Business Directories',
            'method' => 'collectFromBusinessDirectories',
            'weight' => 25
        ]
    ];
    
    $totalWeight = array_sum(array_column($sources, 'weight'));
    $currentProgress = 20; // Start at 20% after initialization
    
    $businessInfo = [
        'tax_code' => $taxCode,
        'company_name' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'website' => '',
        'business_type' => '',
        'industry' => '',
        'services' => [],
        'registration_date' => '',
        'status' => '',
        'source' => '',
        'collected_at' => date('Y-m-d H:i:s'),
        'debug_info' => [
            'sources_tried' => [],
            'errors' => [],
            'successful_source' => null
        ]
    ];
    
    foreach ($sources as $sourceName => $sourceConfig) {
        $sourceProgress = ($sourceConfig['weight'] / $totalWeight) * 80; // 80% of remaining progress
        
        updateProgress($pdo, $sessionId, 'business', $currentProgress, 
            "Trying {$sourceConfig['name']}...");
        
        try {
            // Use reflection to call the private method
            $reflection = new ReflectionClass($businessCollector);
            $method = $reflection->getMethod($sourceConfig['method']);
            $method->setAccessible(true);
            
            $data = $method->invoke($businessCollector, $taxCode);
            
            $businessInfo['debug_info']['sources_tried'][] = [
                'source' => $sourceName,
                'status' => 'success',
                'data_found' => !empty($data['company_name']),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($data['company_name'])) {
                $businessInfo = array_merge($businessInfo, $data);
                $businessInfo['source'] = $sourceName;
                $businessInfo['debug_info']['successful_source'] = $sourceName;
                
                updateProgress($pdo, $sessionId, 'business', $currentProgress + $sourceProgress, 
                    "Found business data from {$sourceConfig['name']}");
                break;
            }
            
        } catch (Exception $e) {
            $businessInfo['debug_info']['sources_tried'][] = [
                'source' => $sourceName,
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $businessInfo['debug_info']['errors'][] = [
                'source' => $sourceName,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            updateProgress($pdo, $sessionId, 'business', $currentProgress + ($sourceProgress / 2), 
                "Error with {$sourceConfig['name']}: " . substr($e->getMessage(), 0, 50) . "...");
            
            logInfo("Business data collection error for source {$sourceName}: " . $e->getMessage());
            continue;
        }
        
        $currentProgress += $sourceProgress;
    }
    
    // If no data found from any source, try web search enhancement
    if (empty($businessInfo['company_name'])) {
        updateProgress($pdo, $sessionId, 'business', 90, 'No data found from primary sources, trying web search enhancement...');
        
        try {
            // Use reflection to call the private method
            $reflection = new ReflectionClass($businessCollector);
            $method = $reflection->getMethod('enhanceWithWebSearch');
            $method->setAccessible(true);
            
            $enhancedData = $method->invoke($businessCollector, $businessInfo);
            $businessInfo = array_merge($businessInfo, $enhancedData);
            
            $businessInfo['debug_info']['enhancement_attempted'] = true;
            $businessInfo['debug_info']['enhancement_success'] = !empty($enhancedData['company_name']);
            
        } catch (Exception $e) {
            $businessInfo['debug_info']['enhancement_error'] = $e->getMessage();
            logInfo("Web search enhancement error: " . $e->getMessage());
        }
    }
    
    updateProgress($pdo, $sessionId, 'business', 95, 'Finalizing business data collection...');
    
    // Cache the business data with debug info
    $cacheManager = new CacheManager();
    $cacheManager->cacheBusinessData($taxCode, $businessInfo, $businessInfo['source']);
    
    updateProgress($pdo, $sessionId, 'business', 100, 
        !empty($businessInfo['company_name']) ? 'Business data collected successfully' : 'Business data collection completed (no data found)');
    
    return $businessInfo;
}
?> 