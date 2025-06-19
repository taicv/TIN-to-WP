<?php
/**
 * Test Business Data Collection
 * 
 * This script helps debug the business data collection process
 * by testing each component individually.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/CacheManager.php';
require_once __DIR__ . '/VietnamBusinessCollector.php';

echo "=== Business Data Collection Test ===\n\n";

// Test tax code (you can change this)
$taxCode = '0123456789'; // Replace with a real tax code for testing

echo "Testing with tax code: $taxCode\n\n";

try {
    // Initialize components
    $cacheManager = new CacheManager();
    $businessCollector = new VietnamBusinessCollector();
    
    echo "1. Testing cache manager...\n";
    $cachedData = $cacheManager->getCachedBusinessData($taxCode);
    if ($cachedData) {
        echo "   ✓ Found cached data\n";
        echo "   Company: " . ($cachedData['company_name'] ?? 'N/A') . "\n";
        echo "   Source: " . ($cachedData['source'] ?? 'N/A') . "\n";
    } else {
        echo "   ✗ No cached data found\n";
    }
    echo "\n";
    
    echo "2. Testing business collector initialization...\n";
    echo "   ✓ Business collector initialized successfully\n\n";
    
    echo "3. Testing individual collection methods...\n";
    
    // Test official portal
    echo "   Testing Official Portal...\n";
    try {
        $reflection = new ReflectionClass($businessCollector);
        $method = $reflection->getMethod('collectFromOfficialPortal');
        $method->setAccessible(true);
        
        $startTime = microtime(true);
        $data = $method->invoke($businessCollector, $taxCode);
        $endTime = microtime(true);
        
        echo "   ✓ Completed in " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
        echo "   Data found: " . (!empty($data['company_name']) ? 'Yes' : 'No') . "\n";
        
        if (!empty($data['company_name'])) {
            echo "   Company: " . $data['company_name'] . "\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test web search
    echo "   Testing Web Search...\n";
    try {
        $method = $reflection->getMethod('collectFromWebSearch');
        $method->setAccessible(true);
        
        $startTime = microtime(true);
        $data = $method->invoke($businessCollector, $taxCode);
        $endTime = microtime(true);
        
        echo "   ✓ Completed in " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
        echo "   Data found: " . (!empty($data['company_name']) ? 'Yes' : 'No') . "\n";
        
        if (!empty($data['company_name'])) {
            echo "   Company: " . $data['company_name'] . "\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test business directories
    echo "   Testing Business Directories...\n";
    try {
        $method = $reflection->getMethod('collectFromBusinessDirectories');
        $method->setAccessible(true);
        
        $startTime = microtime(true);
        $data = $method->invoke($businessCollector, $taxCode);
        $endTime = microtime(true);
        
        echo "   ✓ Completed in " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
        echo "   Data found: " . (!empty($data['company_name']) ? 'Yes' : 'No') . "\n";
        
        if (!empty($data['company_name'])) {
            echo "   Company: " . $data['company_name'] . "\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "4. Testing full collection process...\n";
    $startTime = microtime(true);
    $businessInfo = $businessCollector->collectBusinessInfo($taxCode);
    $endTime = microtime(true);
    
    echo "   ✓ Completed in " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
    echo "   Final result:\n";
    echo "   - Company: " . ($businessInfo['company_name'] ?? 'N/A') . "\n";
    echo "   - Address: " . ($businessInfo['address'] ?? 'N/A') . "\n";
    echo "   - Source: " . ($businessInfo['source'] ?? 'N/A') . "\n";
    echo "   - Debug info: " . (isset($businessInfo['debug_info']) ? 'Available' : 'Not available') . "\n";
    
    if (isset($businessInfo['debug_info'])) {
        echo "\n   Debug Information:\n";
        foreach ($businessInfo['debug_info']['sources_tried'] ?? [] as $source) {
            echo "   - {$source['source']}: {$source['status']}";
            if (isset($source['error'])) {
                echo " (Error: {$source['error']})";
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?> 