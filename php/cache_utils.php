<?php
/**
 * Cache Management Utilities
 * 
 * This script provides utilities for managing and inspecting the cache system
 * for debugging, maintenance, and development purposes.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/CacheManager.php';

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

$cacheManager = new CacheManager();

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

// Route requests to appropriate handlers
switch ($action) {
    case 'stats':
        handleGetStats();
        break;
        
    case 'list':
        handleListCache($input['type'] ?? null, $input['limit'] ?? 50);
        break;
        
    case 'view':
        handleViewCache($input['key'] ?? '', $input['type'] ?? '');
        break;
        
    case 'clear':
        handleClearCache($input['type'] ?? null);
        break;
        
    case 'export':
        handleExportCache($input['type'] ?? null, $input['format'] ?? 'json');
        break;
        
    case 'search':
        handleSearchCache($input['query'] ?? '', $input['type'] ?? null);
        break;
        
    case 'cleanup':
        handleCleanupCache();
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified. Available actions: stats, list, view, clear, export, search, cleanup'
        ]);
        break;
}

/**
 * Handle get cache statistics
 */
function handleGetStats() {
    global $cacheManager;
    
    try {
        $stats = $cacheManager->getCacheStats();
        
        // Add additional statistics
        $stats['cache_info'] = [
            'enabled' => CACHE_ENABLED,
            'type' => CACHE_TYPE,
            'default_ttl' => CACHE_DEFAULT_TTL,
            'image_cache_duration' => IMAGE_CACHE_DURATION
        ];
        
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
 * Handle list cache entries
 */
function handleListCache($type = null, $limit = 50) {
    global $cacheManager;
    
    try {
        $cacheDir = CACHE_DIR;
        $entries = [];
        
        if ($type) {
            $typeDir = $cacheDir . '/' . $type . '/';
            if (is_dir($typeDir)) {
                $entries = listCacheEntries($typeDir, $limit);
            }
        } else {
            // List all types
            $cacheTypes = ['ai', 'api', 'images', 'business', 'sessions'];
            foreach ($cacheTypes as $cacheType) {
                $typeDir = $cacheDir . '/' . $cacheType . '/';
                if (is_dir($typeDir)) {
                    $entries[$cacheType] = listCacheEntries($typeDir, $limit);
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'type' => $type,
                'limit' => $limit,
                'entries' => $entries
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
 * Handle view specific cache entry
 */
function handleViewCache($key, $type) {
    global $cacheManager;
    
    if (empty($key) || empty($type)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Key and type are required'
        ]);
        return;
    }
    
    try {
        $cacheDir = CACHE_DIR . '/' . $type . '/';
        $filename = findCacheFile($cacheDir, $key);
        
        if (!$filename) {
            throw new Exception('Cache entry not found');
        }
        
        $cacheData = readCacheFile($filename);
        
        if (!$cacheData) {
            throw new Exception('Failed to read cache file');
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'key' => $key,
                'type' => $type,
                'filename' => basename($filename),
                'cache_data' => $cacheData,
                'file_size' => filesize($filename),
                'modified' => date('Y-m-d H:i:s', filemtime($filename))
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
 * Handle clear cache
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
 * Handle export cache
 */
function handleExportCache($type = null, $format = 'json') {
    global $cacheManager;
    
    try {
        $cacheDir = CACHE_DIR;
        $exportData = [];
        
        if ($type) {
            $typeDir = $cacheDir . '/' . $type . '/';
            if (is_dir($typeDir)) {
                $exportData[$type] = exportCacheType($typeDir);
            }
        } else {
            // Export all types
            $cacheTypes = ['ai', 'api', 'images', 'business', 'sessions'];
            foreach ($cacheTypes as $cacheType) {
                $typeDir = $cacheDir . '/' . $cacheType . '/';
                if (is_dir($typeDir)) {
                    $exportData[$cacheType] = exportCacheType($typeDir);
                }
            }
        }
        
        if ($format === 'json') {
            echo json_encode([
                'success' => true,
                'data' => $exportData,
                'exported_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // For other formats, you could implement CSV, XML, etc.
            throw new Exception('Export format not supported');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle search cache
 */
function handleSearchCache($query, $type = null) {
    global $cacheManager;
    
    if (empty($query)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Search query is required'
        ]);
        return;
    }
    
    try {
        $cacheDir = CACHE_DIR;
        $results = [];
        
        if ($type) {
            $typeDir = $cacheDir . '/' . $type . '/';
            if (is_dir($typeDir)) {
                $results = searchCacheEntries($typeDir, $query);
            }
        } else {
            // Search all types
            $cacheTypes = ['ai', 'api', 'images', 'business', 'sessions'];
            foreach ($cacheTypes as $cacheType) {
                $typeDir = $cacheDir . '/' . $cacheType . '/';
                if (is_dir($typeDir)) {
                    $searchResults = searchCacheEntries($typeDir, $query);
                    if (!empty($searchResults)) {
                        $results[$cacheType] = $searchResults;
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'query' => $query,
                'type' => $type,
                'results' => $results,
                'total_found' => count($results)
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
 * Handle cleanup expired cache entries
 */
function handleCleanupCache() {
    global $cacheManager;
    
    try {
        $cacheDir = CACHE_DIR;
        $cleanedCount = 0;
        
        $cacheTypes = ['ai', 'api', 'images', 'business', 'sessions'];
        foreach ($cacheTypes as $cacheType) {
            $typeDir = $cacheDir . '/' . $cacheType . '/';
            if (is_dir($typeDir)) {
                $cleanedCount += cleanupExpiredEntries($typeDir);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Cleaned up $cleanedCount expired cache entries",
            'cleaned_count' => $cleanedCount
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
 * List cache entries in a directory
 */
function listCacheEntries($dir, $limit = 50) {
    $entries = [];
    $files = glob($dir . '*.cache');
    
    foreach (array_slice($files, 0, $limit) as $file) {
        $cacheData = readCacheFile($file);
        if ($cacheData) {
            $entries[] = [
                'filename' => basename($file),
                'file_size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
                'expires_at' => date('Y-m-d H:i:s', $cacheData['expires_at']),
                'is_expired' => $cacheData['expires_at'] < time(),
                'metadata' => $cacheData['data']['metadata'] ?? []
            ];
        }
    }
    
    return $entries;
}

/**
 * Find cache file by key
 */
function findCacheFile($dir, $key) {
    $files = glob($dir . '*.cache');
    
    foreach ($files as $file) {
        $cacheData = readCacheFile($file);
        if ($cacheData && isset($cacheData['data']['metadata'])) {
            // Check if this file contains the key we're looking for
            $metadata = $cacheData['data']['metadata'];
            if (isset($metadata['tax_code']) && $metadata['tax_code'] === $key) {
                return $file;
            }
            if (isset($metadata['query']) && $metadata['query'] === $key) {
                return $file;
            }
            if (isset($metadata['session_id']) && $metadata['session_id'] === $key) {
                return $file;
            }
        }
    }
    
    return null;
}

/**
 * Read cache file
 */
function readCacheFile($filename) {
    if (!file_exists($filename)) {
        return null;
    }
    
    try {
        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }
        
        $cacheData = unserialize($content);
        if ($cacheData === false) {
            return null;
        }
        
        return $cacheData;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Export cache type
 */
function exportCacheType($dir) {
    $export = [];
    $files = glob($dir . '*.cache');
    
    foreach ($files as $file) {
        $cacheData = readCacheFile($file);
        if ($cacheData) {
            $export[basename($file)] = [
                'data' => $cacheData['data'],
                'expires_at' => $cacheData['expires_at'],
                'file_size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }
    }
    
    return $export;
}

/**
 * Search cache entries
 */
function searchCacheEntries($dir, $query) {
    $results = [];
    $files = glob($dir . '*.cache');
    
    foreach ($files as $file) {
        $cacheData = readCacheFile($file);
        if ($cacheData) {
            $content = json_encode($cacheData['data']);
            if (stripos($content, $query) !== false) {
                $results[] = [
                    'filename' => basename($file),
                    'file_size' => filesize($file),
                    'modified' => date('Y-m-d H:i:s', filemtime($file)),
                    'metadata' => $cacheData['data']['metadata'] ?? []
                ];
            }
        }
    }
    
    return $results;
}

/**
 * Cleanup expired cache entries
 */
function cleanupExpiredEntries($dir) {
    $cleanedCount = 0;
    $files = glob($dir . '*.cache');
    
    foreach ($files as $file) {
        $cacheData = readCacheFile($file);
        if ($cacheData && $cacheData['expires_at'] < time()) {
            if (unlink($file)) {
                $cleanedCount++;
            }
        }
    }
    
    return $cleanedCount;
}
?> 