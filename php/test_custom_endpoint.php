<?php
/**
 * Test script to verify custom OpenAI endpoint configuration
 */

require_once 'config.php';
require_once 'AIContentGenerator.php';

echo "Testing Custom OpenAI Endpoint Configuration\n";
echo "===========================================\n\n";

// Display configuration
echo "Configuration:\n";
echo "- API Key: " . substr(OPENAI_API_KEY, 0, 10) . "...\n";
echo "- Endpoint: " . OPENAI_API_ENDPOINT . "\n";
echo "- Model: " . OPENAI_MODEL . "\n\n";

try {
    // Test 1: Initialize AIContentGenerator with custom endpoint
    echo "1. Testing AIContentGenerator initialization...\n";
    $generator = new AIContentGenerator(OPENAI_API_KEY, OPENAI_API_ENDPOINT);
    echo "✓ AIContentGenerator initialized successfully\n\n";
    
    // Test 2: Simple API call to verify endpoint works
    echo "2. Testing API connection...\n";
    $connectionTest = $generator->testConnection();
    if ($connectionTest) {
        echo "✓ API connection successful\n\n";
    } else {
        echo "❌ API connection failed\n\n";
        return;
    }
    
    // Test 3: Test with business data
    echo "3. Testing with sample business data...\n";
    $businessInfo = [
        'company_name' => 'Test Company',
        'industry' => 'Technology',
        'services' => ['Web Development', 'Consulting'],
        'address' => 'Ho Chi Minh City, Vietnam',
        'phone' => '+84 123 456 789',
        'email' => 'test@company.vn'
    ];
    
    $sitemap = $generator->generateSitemap($businessInfo);
    echo "✓ Sitemap generated successfully\n";
    echo "  - Website Title: " . $sitemap['website_title'] . "\n";
    echo "  - Pages Count: " . count($sitemap['pages']) . "\n\n";
    
    echo "✅ All tests passed! Custom endpoint is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?> 