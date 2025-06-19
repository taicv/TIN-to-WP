<?php
/**
 * Cache Manager for WordPress Website Generator
 * 
 * Handles caching of AI responses, external API content, and images
 * for debugging, resuming, and performance optimization purposes.
 */

class CacheManager {
    private $cacheDir;
    private $imageCacheDir;
    private $defaultTtl;
    private $imageCacheDuration;
    private $enabled;
    
    public function __construct($config = []) {
        $this->cacheDir = $config['cache_dir'] ?? CACHE_DIR;
        $this->imageCacheDir = $config['image_cache_dir'] ?? IMAGE_CACHE_DIR;
        $this->defaultTtl = $config['default_ttl'] ?? CACHE_DEFAULT_TTL;
        $this->imageCacheDuration = $config['image_cache_duration'] ?? IMAGE_CACHE_DURATION;
        $this->enabled = $config['enabled'] ?? CACHE_ENABLED;
        
        // Create cache directories if they don't exist
        $this->ensureCacheDirectories();
    }
    
    /**
     * Cache AI response with metadata
     */
    public function cacheAIResponse($key, $response, $metadata = [], $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheData = [
            'response' => $response,
            'metadata' => array_merge($metadata, [
                'cached_at' => date('Y-m-d H:i:s'),
                'ttl' => $ttl ?? $this->defaultTtl,
                'type' => 'ai_response'
            ])
        ];
        
        $filename = $this->getCacheFilename($key, 'ai');
        return $this->writeCacheFile($filename, $cacheData, $ttl);
    }
    
    /**
     * Get cached AI response
     */
    public function getCachedAIResponse($key) {
        if (!$this->enabled) {
            return null;
        }
        
        $filename = $this->getCacheFilename($key, 'ai');
        $cacheData = $this->readCacheFile($filename);
        
        if ($cacheData && $this->isCacheValid($cacheData)) {
            return $cacheData['response'];
        }
        
        return null;
    }
    
    /**
     * Cache external API response
     */
    public function cacheAPIResponse($key, $response, $metadata = [], $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheData = [
            'response' => $response,
            'metadata' => array_merge($metadata, [
                'cached_at' => date('Y-m-d H:i:s'),
                'ttl' => $ttl ?? $this->defaultTtl,
                'type' => 'api_response'
            ])
        ];
        
        $filename = $this->getCacheFilename($key, 'api');
        return $this->writeCacheFile($filename, $cacheData, $ttl);
    }
    
    /**
     * Get cached API response
     */
    public function getCachedAPIResponse($key) {
        if (!$this->enabled) {
            return null;
        }
        
        $filename = $this->getCacheFilename($key, 'api');
        $cacheData = $this->readCacheFile($filename);
        
        if ($cacheData && $this->isCacheValid($cacheData)) {
            return $cacheData['response'];
        }
        
        return null;
    }
    
    /**
     * Cache image search results
     */
    public function cacheImageSearch($query, $results, $source = 'unknown', $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $key = $this->generateImageSearchKey($query, $source);
        $cacheData = [
            'results' => $results,
            'metadata' => [
                'query' => $query,
                'source' => $source,
                'count' => count($results),
                'cached_at' => date('Y-m-d H:i:s'),
                'ttl' => $ttl ?? $this->defaultTtl,
                'type' => 'image_search'
            ]
        ];
        
        $filename = $this->getCacheFilename($key, 'images');
        return $this->writeCacheFile($filename, $cacheData, $ttl);
    }
    
    /**
     * Get cached image search results
     */
    public function getCachedImageSearch($query, $source = 'unknown') {
        if (!$this->enabled) {
            return null;
        }
        
        $key = $this->generateImageSearchKey($query, $source);
        $filename = $this->getCacheFilename($key, 'images');
        $cacheData = $this->readCacheFile($filename);
        
        if ($cacheData && $this->isCacheValid($cacheData)) {
            return $cacheData['results'];
        }
        
        return null;
    }
    
    /**
     * Cache downloaded image
     */
    public function cacheImage($imageUrl, $localPath, $metadata = []) {
        if (!$this->enabled) {
            return false;
        }
        
        $key = $this->generateImageKey($imageUrl);
        $cacheData = [
            'local_path' => $localPath,
            'original_url' => $imageUrl,
            'metadata' => array_merge($metadata, [
                'cached_at' => date('Y-m-d H:i:s'),
                'ttl' => $this->imageCacheDuration,
                'type' => 'image_file'
            ])
        ];
        
        $filename = $this->getCacheFilename($key, 'images');
        return $this->writeCacheFile($filename, $cacheData, $this->imageCacheDuration);
    }
    
    /**
     * Get cached image path
     */
    public function getCachedImage($imageUrl) {
        if (!$this->enabled) {
            return null;
        }
        
        $key = $this->generateImageKey($imageUrl);
        $filename = $this->getCacheFilename($key, 'images');
        $cacheData = $this->readCacheFile($filename);
        
        if ($cacheData && $this->isCacheValid($cacheData)) {
            // Check if the actual image file still exists
            if (file_exists($cacheData['local_path'])) {
                return $cacheData['local_path'];
            }
        }
        
        return null;
    }
    
    /**
     * Cache business data collection results
     */
    public function cacheBusinessData($taxCode, $data, $source = 'unknown', $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheData = [
            'data' => $data,
            'metadata' => [
                'tax_code' => $taxCode,
                'source' => $source,
                'cached_at' => date('Y-m-d H:i:s'),
                'ttl' => $ttl ?? $this->defaultTtl,
                'type' => 'business_data'
            ]
        ];
        
        $filename = $this->getCacheFilename($taxCode, 'business');
        return $this->writeCacheFile($filename, $cacheData, $ttl);
    }
    
    /**
     * Get cached business data
     */
    public function getCachedBusinessData($taxCode) {
        if (!$this->enabled) {
            return null;
        }
        
        $filename = $this->getCacheFilename($taxCode, 'business');
        $cacheData = $this->readCacheFile($filename);
        
        if ($cacheData && $this->isCacheValid($cacheData)) {
            return $cacheData['data'];
        }
        
        return null;
    }
    
    /**
     * Cache complete website generation session
     */
    public function cacheWebsiteSession($sessionId, $data, $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheData = [
            'data' => $data,
            'metadata' => [
                'session_id' => $sessionId,
                'cached_at' => date('Y-m-d H:i:s'),
                'ttl' => $ttl ?? $this->defaultTtl,
                'type' => 'website_session'
            ]
        ];
        
        $filename = $this->getCacheFilename($sessionId, 'sessions');
        return $this->writeCacheFile($filename, $cacheData, $ttl);
    }
    
    /**
     * Get cached website session
     */
    public function getCachedWebsiteSession($sessionId) {
        if (!$this->enabled) {
            return null;
        }
        
        $filename = $this->getCacheFilename($sessionId, 'sessions');
        $cacheData = $this->readCacheFile($filename);
        
        if ($cacheData && $this->isCacheValid($cacheData)) {
            return $cacheData['data'];
        }
        
        return null;
    }
    
    /**
     * Clear cache by type
     */
    public function clearCache($type = null) {
        if ($type) {
            $typeDir = $this->cacheDir . '/' . $type . '/';
            if (is_dir($typeDir)) {
                $this->removeDirectory($typeDir);
                mkdir($typeDir, 0755, true);
            }
        } else {
            // Clear all cache
            $this->removeDirectory($this->cacheDir);
            $this->ensureCacheDirectories();
        }
        
        return true;
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats() {
        $stats = [
            'enabled' => $this->enabled,
            'cache_dir' => $this->cacheDir,
            'image_cache_dir' => $this->imageCacheDir,
            'types' => []
        ];
        
        $cacheTypes = ['ai', 'api', 'images', 'business', 'sessions'];
        
        foreach ($cacheTypes as $type) {
            $typeDir = $this->cacheDir . '/' . $type . '/';
            if (is_dir($typeDir)) {
                $files = glob($typeDir . '*.cache');
                $stats['types'][$type] = [
                    'files_count' => count($files),
                    'total_size' => $this->getDirectorySize($typeDir)
                ];
            } else {
                $stats['types'][$type] = [
                    'files_count' => 0,
                    'total_size' => 0
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Generate cache filename
     */
    public function getCacheFilename($key, $type) {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $hash = md5($key);
        $typeDir = $this->cacheDir . '/' . $type . '/';
        
        if (!is_dir($typeDir)) {
            mkdir($typeDir, 0755, true);
        }
        
        return $typeDir . $safeKey . '_' . substr($hash, 0, 8) . '.cache';
    }
    
    /**
     * Write cache file
     */
    private function writeCacheFile($filename, $data, $ttl = null) {
        try {
            $cacheData = [
                'data' => $data,
                'expires_at' => time() + ($ttl ?? $this->defaultTtl)
            ];
            
            $result = file_put_contents($filename, serialize($cacheData));
            return $result !== false;
        } catch (Exception $e) {
            error_log("Cache write error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read cache file
     */
    private function readCacheFile($filename) {
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
            error_log("Cache read error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if cache is still valid
     */
    private function isCacheValid($cacheData) {
        return isset($cacheData['expires_at']) && $cacheData['expires_at'] > time();
    }
    
    /**
     * Generate image search cache key
     */
    private function generateImageSearchKey($query, $source) {
        return 'search_' . md5($query . '_' . $source);
    }
    
    /**
     * Generate image cache key
     */
    private function generateImageKey($imageUrl) {
        return 'image_' . md5($imageUrl);
    }
    
    /**
     * Ensure cache directories exist
     */
    private function ensureCacheDirectories() {
        $directories = [
            $this->cacheDir,
            $this->cacheDir . '/ai/',
            $this->cacheDir . '/api/',
            $this->cacheDir . '/images/',
            $this->cacheDir . '/business/',
            $this->cacheDir . '/sessions/',
            $this->imageCacheDir
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Remove directory recursively
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Get directory size in bytes
     */
    private function getDirectorySize($dir) {
        $size = 0;
        $files = glob($dir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            } elseif (is_dir($file)) {
                $size += $this->getDirectorySize($file);
            }
        }
        
        return $size;
    }
}
?> 