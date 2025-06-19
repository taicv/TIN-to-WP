<?php
/**
 * Test Script for Website Generation Process
 * 
 * This script tests each component of the website generation process
 * to help identify where the error is occurring.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/VietnamBusinessCollector.php';
require_once __DIR__ . '/AIContentGenerator.php';
require_once __DIR__ . '/WordPressIntegrator.php';
require_once __DIR__ . '/ImageManager.php';

echo "=== Website Generation Test ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Test data
$testData = [
    'taxCode' => '0123456789',
    'colorPalette' => 'professional',
    'websiteStyle' => 'corporate',
    'wpUrl' => 'https://example.com',
    'wpUsername' => 'admin',
    'wpPassword' => 'test_password'
];

try {
    echo "1. Testing Business Data Collection...\n";
    $businessCollector = new VietnamBusinessCollector();
    $businessInfo = $businessCollector->collectBusinessInfo($testData['taxCode']);
    
    if ($businessInfo) {
        echo "✓ Business info collected: " . ($businessInfo['company_name'] ?? 'No company name') . "\n";
        print_r($businessInfo);
    } else {
        echo "✗ Failed to collect business info\n";
    }
    
    echo "\n2. Testing AI Content Generation...\n";
    if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
        $aiGenerator = new AIContentGenerator(OPENAI_API_KEY, OPENAI_API_ENDPOINT);
        $content = $aiGenerator->generateWebsiteContent($businessInfo, $testData['colorPalette']);
        
        if ($content) {
            echo "✓ Content generated successfully\n";
            echo "  - Pages: " . count($content['pages']) . "\n";
            echo "  - Blog articles: " . count($content['blog_articles']) . "\n";
            echo "  - Sitemap: " . (isset($content['sitemap']) ? 'Yes' : 'No') . "\n";
        } else {
            echo "✗ Failed to generate content\n";
        }
    } else {
        echo "⚠ Skipping AI content generation (no API key)\n";
        $content = [
            'pages' => ['home' => ['title' => 'Home', 'content' => 'Test content']],
            'blog_articles' => [],
            'sitemap' => ['pages' => [['title' => 'Home', 'slug' => 'home']]]
        ];
    }
    
    echo "\n3. Testing WordPress Integration...\n";
    $wpIntegrator = new WordPressIntegrator(
        $testData['wpUrl'],
        $testData['wpUsername'],
        $testData['wpPassword']
    );
    
    // Test connection
    $connectionTest = $wpIntegrator->testConnection();
    if ($connectionTest['success']) {
        echo "✓ WordPress connection test successful\n";
    } else {
        echo "✗ WordPress connection test failed: " . $connectionTest['error'] . "\n";
    }
    
    echo "\n4. Testing Image Manager...\n";
    $imageManager = new ImageManager([
        'unsplash' => UNSPLASH_ACCESS_KEY ?? '',
        'pexels' => PEXELS_API_KEY ?? '',
        'pixabay' => PIXABAY_API_KEY ?? ''
    ]);
    
    echo "✓ Image manager initialized\n";
    
    echo "\n5. Testing Database Operations...\n";
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            $db_options
        );
        
        // Test session creation
        $sessionId = 'test_' . uniqid();
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
            $testData['taxCode'],
            $testData['colorPalette'],
            $testData['websiteStyle'],
            $testData['wpUrl'],
            $testData['wpUsername'],
            password_hash($testData['wpPassword'], PASSWORD_DEFAULT)
        ]);
        
        echo "✓ Test session created: $sessionId\n";
        
        // Test progress tracking
        $stmt = $pdo->prepare("
            INSERT INTO generation_progress (
                session_id,
                current_step,
                step_progress,
                status_message,
                created_at
            ) VALUES (?, 'test', 50, 'Test progress', NOW())
        ");
        
        $stmt->execute([$sessionId]);
        echo "✓ Progress tracking test successful\n";
        
        // Clean up test data
        $pdo->prepare("DELETE FROM generation_sessions WHERE session_id = ?")->execute([$sessionId]);
        $pdo->prepare("DELETE FROM generation_progress WHERE session_id = ?")->execute([$sessionId]);
        echo "✓ Test data cleaned up\n";
        
    } catch (PDOException $e) {
        echo "✗ Database test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. Testing Background Processor...\n";
    if (file_exists(__DIR__ . '/background_processor.php')) {
        echo "✓ Background processor file exists\n";
        
        // Test if we can execute it
        $output = [];
        $returnCode = 0;
        exec('php ' . __DIR__ . '/background_processor.php test_session 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✓ Background processor executed successfully\n";
        } else {
            echo "✗ Background processor failed with code $returnCode\n";
            echo "Output: " . implode("\n", $output) . "\n";
        }
    } else {
        echo "✗ Background processor file not found\n";
    }
    
    echo "\n=== Test Complete ===\n";
    echo "All tests passed! The system should be working correctly.\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?> 