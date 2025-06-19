<?php
/**
 * Cache System Test Script
 * 
 * This script tests the caching functionality to ensure it's working correctly.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/CacheManager.php';

echo "=== Cache System Test ===\n\n";

// Initialize cache manager
$cacheManager = new CacheManager();

// Test 1: Cache AI Response
echo "Test 1: Caching AI Response\n";
$aiKey = 'test_ai_response';
$aiData = [
    'content' => 'This is a test AI response',
    'model' => 'gpt-4o',
    'tokens_used' => 150
];

$result = $cacheManager->cacheAIResponse($aiKey, $aiData, [
    'test' => true,
    'timestamp' => date('Y-m-d H:i:s')
]);

echo "Cache AI Response: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test 2: Retrieve Cached AI Response
echo "\nTest 2: Retrieving Cached AI Response\n";
$cachedAiData = $cacheManager->getCachedAIResponse($aiKey);
echo "Retrieve AI Response: " . ($cachedAiData ? "SUCCESS" : "FAILED") . "\n";
if ($cachedAiData) {
    echo "Content: " . $cachedAiData['content'] . "\n";
}

// Test 3: Cache API Response
echo "\nTest 3: Caching API Response\n";
$apiKey = 'test_api_response';
$apiData = [
    'status' => 'success',
    'data' => ['id' => 123, 'name' => 'Test Company'],
    'source' => 'test_api'
];

$result = $cacheManager->cacheAPIResponse($apiKey, $apiData, [
    'test' => true,
    'endpoint' => '/test/endpoint'
]);

echo "Cache API Response: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test 4: Retrieve Cached API Response
echo "\nTest 4: Retrieving Cached API Response\n";
$cachedApiData = $cacheManager->getCachedAPIResponse($apiKey);
echo "Retrieve API Response: " . ($cachedApiData ? "SUCCESS" : "FAILED") . "\n";
if ($cachedApiData) {
    echo "Status: " . $cachedApiData['status'] . "\n";
}

// Test 5: Cache Image Search
echo "\nTest 5: Caching Image Search\n";
$imageQuery = 'business office';
$imageResults = [
    ['url' => 'https://example.com/image1.jpg', 'alt' => 'Office 1'],
    ['url' => 'https://example.com/image2.jpg', 'alt' => 'Office 2']
];

$result = $cacheManager->cacheImageSearch($imageQuery, $imageResults, 'test_source');
echo "Cache Image Search: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test 6: Retrieve Cached Image Search
echo "\nTest 6: Retrieving Cached Image Search\n";
$cachedImageResults = $cacheManager->getCachedImageSearch($imageQuery, 'test_source');
echo "Retrieve Image Search: " . ($cachedImageResults ? "SUCCESS" : "FAILED") . "\n";
if ($cachedImageResults) {
    echo "Images found: " . count($cachedImageResults) . "\n";
}

// Test 7: Cache Business Data
echo "\nTest 7: Caching Business Data\n";
$taxCode = '1234567890';
$businessData = [
    'tax_code' => $taxCode,
    'company_name' => 'Test Company Ltd',
    'address' => '123 Test Street, Hanoi',
    'status' => 'Active'
];

$result = $cacheManager->cacheBusinessData($taxCode, $businessData, 'test_source');
echo "Cache Business Data: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test 8: Retrieve Cached Business Data
echo "\nTest 8: Retrieving Cached Business Data\n";
$cachedBusinessData = $cacheManager->getCachedBusinessData($taxCode);
echo "Retrieve Business Data: " . ($cachedBusinessData ? "SUCCESS" : "FAILED") . "\n";
if ($cachedBusinessData) {
    echo "Company: " . $cachedBusinessData['company_name'] . "\n";
}

// Test 9: Cache Website Session
echo "\nTest 9: Caching Website Session\n";
$sessionId = 'test_session_123';
$sessionData = [
    'tax_code' => '1234567890',
    'color_palette' => 'professional',
    'website_style' => 'modern',
    'created_at' => date('Y-m-d H:i:s')
];

$result = $cacheManager->cacheWebsiteSession($sessionId, $sessionData);
echo "Cache Website Session: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test 10: Retrieve Cached Website Session
echo "\nTest 10: Retrieving Cached Website Session\n";
$cachedSessionData = $cacheManager->getCachedWebsiteSession($sessionId);
echo "Retrieve Website Session: " . ($cachedSessionData ? "SUCCESS" : "FAILED") . "\n";
if ($cachedSessionData) {
    echo "Session Color Palette: " . $cachedSessionData['color_palette'] . "\n";
}

// Test 11: Get Cache Statistics
echo "\nTest 11: Cache Statistics\n";
$stats = $cacheManager->getCacheStats();
echo "Cache Statistics: " . ($stats ? "SUCCESS" : "FAILED") . "\n";
if ($stats) {
    echo "Cache Enabled: " . ($stats['enabled'] ? "YES" : "NO") . "\n";
    echo "Cache Directory: " . $stats['cache_dir'] . "\n";
    echo "Cache Types:\n";
    foreach ($stats['types'] as $type => $typeStats) {
        echo "  - $type: " . $typeStats['files_count'] . " files, " . 
             formatBytes($typeStats['total_size']) . "\n";
    }
}

// Test 12: Test Cache Expiration (simulate expired cache)
echo "\nTest 12: Cache Expiration Test\n";
$expiredKey = 'expired_test';
$expiredData = ['test' => 'expired data'];

// Create a cache entry with very short TTL
$cacheData = [
    'data' => [
        'response' => $expiredData,
        'metadata' => [
            'cached_at' => date('Y-m-d H:i:s'),
            'ttl' => 1,
            'type' => 'test'
        ]
    ],
    'expires_at' => time() + 1 // Expires in 1 second
];

$filename = $cacheManager->getCacheFilename($expiredKey, 'ai');
$result = file_put_contents($filename, serialize($cacheData));
echo "Create Expired Cache: " . ($result !== false ? "SUCCESS" : "FAILED") . "\n";

// Wait for expiration
sleep(2);

// Try to retrieve expired cache
$expiredResult = $cacheManager->getCachedAIResponse($expiredKey);
echo "Retrieve Expired Cache: " . ($expiredResult ? "FAILED (should be null)" : "SUCCESS (correctly expired)") . "\n";

// Test 13: Clear Cache
echo "\nTest 13: Clear Cache Test\n";
$result = $cacheManager->clearCache('ai');
echo "Clear AI Cache: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Verify cache is cleared
$cachedAiDataAfterClear = $cacheManager->getCachedAIResponse($aiKey);
echo "Retrieve After Clear: " . ($cachedAiDataAfterClear ? "FAILED (should be null)" : "SUCCESS (correctly cleared)") . "\n";

echo "\n=== Cache System Test Complete ===\n";

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Helper function to get cache filename (if accessible)
 */
function getCacheFilename($key, $type) {
    $cacheDir = CACHE_DIR . '/' . $type . '/';
    $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    $hash = md5($key);
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    return $cacheDir . $safeKey . '_' . substr($hash, 0, 8) . '.cache';
}
?> 