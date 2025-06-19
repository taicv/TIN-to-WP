<?php
/**
 * Debug Script for WordPress Website Generator
 * 
 * This script helps identify configuration and environment issues.
 * Run this script to check if all components are properly configured.
 */

echo "=== WordPress Website Generator Debug Report ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Check if config file exists
echo "1. Configuration Check:\n";
if (file_exists(__DIR__ . '/config.php')) {
    echo "✓ config.php exists\n";
    require_once __DIR__ . '/config.php';
} else {
    echo "✗ config.php not found\n";
    exit(1);
}

// Check PHP version
echo "\n2. PHP Environment:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";

// Check required extensions
echo "\n3. Required Extensions:\n";
$required_extensions = ['curl', 'json', 'pdo', 'pdo_mysql', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ $ext extension loaded\n";
    } else {
        echo "✗ $ext extension not loaded\n";
    }
}

// Check database connection
echo "\n4. Database Connection:\n";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $db_options
    );
    echo "✓ Database connection successful\n";
    
    // Check if required tables exist
    $tables = ['generation_sessions', 'generation_progress', 'generation_results'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' not found\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Check API keys
echo "\n5. API Configuration:\n";
if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
    echo "✓ OpenAI API key configured\n";
} else {
    echo "✗ OpenAI API key not configured\n";
}

if (defined('UNSPLASH_ACCESS_KEY') && !empty(UNSPLASH_ACCESS_KEY)) {
    echo "✓ Unsplash API key configured\n";
} else {
    echo "✗ Unsplash API key not configured\n";
}

if (defined('PEXELS_API_KEY') && !empty(PEXELS_API_KEY)) {
    echo "✓ Pexels API key configured\n";
} else {
    echo "✗ Pexels API key not configured\n";
}

if (defined('PIXABAY_API_KEY') && !empty(PIXABAY_API_KEY)) {
    echo "✓ Pixabay API key configured\n";
} else {
    echo "✗ Pixabay API key not configured\n";
}

// Check required files
echo "\n6. Required Files:\n";
$required_files = [
    'VietnamBusinessCollector.php',
    'AIContentGenerator.php',
    'WordPressIntegrator.php',
    'ImageManager.php',
    'process.php',
    'background_processor.php'
];

foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file not found\n";
    }
}

// Check directories
echo "\n7. Directory Permissions:\n";
$directories = [
    'logs' => '0755',
    'cache' => '0755',
    'uploads' => '0755',
    'downloads' => '0755'
];

foreach ($directories as $dir => $permission) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "✓ Directory '$dir' exists\n";
        if (is_writable($path)) {
            echo "✓ Directory '$dir' is writable\n";
        } else {
            echo "✗ Directory '$dir' is not writable\n";
        }
    } else {
        echo "✗ Directory '$dir' not found\n";
    }
}

// Check composer dependencies
echo "\n8. Composer Dependencies:\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✓ Composer autoloader exists\n";
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Check if OpenAI client is available
    if (class_exists('OpenAI\Client')) {
        echo "✓ OpenAI client available\n";
    } else {
        echo "✗ OpenAI client not available\n";
    }
} else {
    echo "✗ Composer autoloader not found\n";
}

// Test basic functionality
echo "\n9. Basic Functionality Tests:\n";

// Test VietnamBusinessCollector
try {
    require_once __DIR__ . '/VietnamBusinessCollector.php';
    $collector = new VietnamBusinessCollector();
    echo "✓ VietnamBusinessCollector instantiated successfully\n";
} catch (Exception $e) {
    echo "✗ VietnamBusinessCollector failed: " . $e->getMessage() . "\n";
}

// Test AIContentGenerator (if API key is available)
if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
    try {
        require_once __DIR__ . '/AIContentGenerator.php';
        $generator = new AIContentGenerator(OPENAI_API_KEY, OPENAI_API_ENDPOINT);
        echo "✓ AIContentGenerator instantiated successfully\n";
    } catch (Exception $e) {
        echo "✗ AIContentGenerator failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ AIContentGenerator test skipped (no API key)\n";
}

// Test WordPressIntegrator
try {
    require_once __DIR__ . '/WordPressIntegrator.php';
    $integrator = new WordPressIntegrator('https://example.com', 'test', 'test');
    echo "✓ WordPressIntegrator instantiated successfully\n";
} catch (Exception $e) {
    echo "✗ WordPressIntegrator failed: " . $e->getMessage() . "\n";
}

// Test ImageManager
try {
    require_once __DIR__ . '/ImageManager.php';
    $imageManager = new ImageManager([]);
    echo "✓ ImageManager instantiated successfully\n";
} catch (Exception $e) {
    echo "✗ ImageManager failed: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Report Complete ===\n";
echo "If you see any ✗ marks above, please fix those issues before proceeding.\n";
?> 