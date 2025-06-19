<?php

require_once 'CacheManager.php';

class ImageManager {
    private $unsplashApiKey;
    private $pexelsApiKey;
    private $pixabayApiKey;
    private $downloadDir;
    private $timeout = 30;
    private $cacheManager;
    
    public function __construct($config = []) {
        $this->unsplashApiKey = $config['unsplash'] ?? '';
        $this->pexelsApiKey = $config['pexels'] ?? '';
        $this->pixabayApiKey = $config['pixabay'] ?? '';
        $this->downloadDir = $config['download_dir'] ?? './downloads/images';
        
        // Create download directory if it doesn't exist
        if (!is_dir($this->downloadDir)) {
            mkdir($this->downloadDir, 0755, true);
        }
        
        // Initialize cache manager
        $this->cacheManager = new CacheManager();
    }
    
    /**
     * Search and assign images for website content
     * @param array $content Content structure from AIContentGenerator
     * @param WordPressIntegrator $wpIntegrator WordPress integrator instance
     * @return array Results of image assignment
     */
    public function assignImagesForWebsite($content, $wpIntegrator) {
        $results = [
            'success' => false,
            'featured_images' => [],
            'content_images' => [],
            'errors' => [],
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            // Assign featured images for blog posts
            foreach ($content['blog_articles'] as $index => $article) {
                $imageResult = $this->assignFeaturedImage($article, $wpIntegrator);
                $results['featured_images'][$index] = $imageResult;
            }
            
            // Insert content images for pages
            foreach ($content['pages'] as $slug => $page) {
                $imageResult = $this->insertContentImages($page, $wpIntegrator);
                $results['content_images'][$slug] = $imageResult;
            }
            
            $results['success'] = true;
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            error_log("Image management error: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Assign featured image for a blog post
     */
    public function assignFeaturedImage($article, $wpIntegrator) {
        try {
            // Generate search query based on article content
            $searchQuery = $this->generateImageSearchQuery($article['title'], $article['category']);
            
            // Search for images
            $images = $this->searchImages($searchQuery, 5);
            
            if (empty($images)) {
                return [
                    'success' => false,
                    'error' => 'No images found for: ' . $searchQuery
                ];
            }
            
            // Select best image
            $selectedImage = $this->selectBestImage($images, $article);
            
            // Download image
            $downloadedImage = $this->downloadImage($selectedImage);
            
            if (!$downloadedImage) {
                return [
                    'success' => false,
                    'error' => 'Failed to download image'
                ];
            }
            
            // Upload to WordPress
            $mediaResult = $wpIntegrator->uploadMedia(
                $downloadedImage['local_path'],
                $article['title'] . ' - Featured Image',
                $selectedImage['alt_text']
            );
            
            if (!$mediaResult) {
                return [
                    'success' => false,
                    'error' => 'Failed to upload image to WordPress'
                ];
            }
            
            return [
                'success' => true,
                'image_url' => $selectedImage['url'],
                'media_id' => $mediaResult['id'],
                'local_path' => $downloadedImage['local_path']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Insert content images for pages
     */
    public function insertContentImages($page, $wpIntegrator) {
        try {
            // Extract headings from content to generate image searches
            $headings = $this->extractHeadings($page['content']);
            $insertedImages = [];
            
            foreach ($headings as $heading) {
                $searchQuery = $this->generateImageSearchQuery($heading, $page['title']);
                $images = $this->searchImages($searchQuery, 3);
                
                if (!empty($images)) {
                    $selectedImage = $images[0]; // Select first image
                    $downloadedImage = $this->downloadImage($selectedImage);
                    
                    if ($downloadedImage) {
                        $mediaResult = $wpIntegrator->uploadMedia(
                            $downloadedImage['local_path'],
                            $heading . ' - Content Image',
                            $selectedImage['alt_text']
                        );
                        
                        if ($mediaResult) {
                            $insertedImages[] = [
                                'heading' => $heading,
                                'image_url' => $selectedImage['url'],
                                'media_id' => $mediaResult['id']
                            ];
                        }
                    }
                }
            }
            
            return [
                'success' => true,
                'images' => $insertedImages
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Search for images across multiple APIs
     */
    public function searchImages($query, $limit = 10) {
        $allImages = [];
        
        // Search Unsplash
        if ($this->unsplashApiKey) {
            $unsplashImages = $this->searchUnsplash($query, $limit);
            $allImages = array_merge($allImages, $unsplashImages);
        }
        
        // Search Pexels
        if ($this->pexelsApiKey) {
            $pexelsImages = $this->searchPexels($query, $limit);
            $allImages = array_merge($allImages, $pexelsImages);
        }
        
        // Search Pixabay
        if ($this->pixabayApiKey) {
            $pixabayImages = $this->searchPixabay($query, $limit);
            $allImages = array_merge($allImages, $pixabayImages);
        }
        
        // Remove duplicates and limit results
        $uniqueImages = $this->removeDuplicateImages($allImages);
        return array_slice($uniqueImages, 0, $limit);
    }
    
    /**
     * Search Unsplash API
     */
    private function searchUnsplash($query, $limit = 10) {
        // Check cache first
        $cachedResults = $this->cacheManager->getCachedImageSearch($query, 'unsplash');
        if ($cachedResults) {
            logInfo("Using cached Unsplash results for query: " . $query);
            return array_slice($cachedResults, 0, $limit);
        }
        
        $url = "https://api.unsplash.com/search/photos";
        $params = [
            'query' => $query,
            'per_page' => $limit,
            'orientation' => 'landscape'
        ];
        
        $headers = [
            'Authorization' => 'Client-ID ' . $this->unsplashApiKey
        ];
        
        $response = $this->makeApiRequest($url, $params, $headers);
        
        if (!$response) {
            return [];
        }
        
        $data = json_decode($response, true);
        $images = [];
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $photo) {
                $images[] = [
                    'url' => $photo['urls']['regular'],
                    'thumb_url' => $photo['urls']['thumb'],
                    'alt_text' => $photo['alt_description'] ?? $query,
                    'width' => $photo['width'],
                    'height' => $photo['height'],
                    'source' => 'unsplash',
                    'photographer' => $photo['user']['name'] ?? '',
                    'download_url' => $photo['links']['download']
                ];
            }
        }
        
        // Cache the results
        $this->cacheManager->cacheImageSearch($query, $images, 'unsplash');
        
        return $images;
    }
    
    /**
     * Search Pexels API
     */
    private function searchPexels($query, $limit = 10) {
        // Check cache first
        $cachedResults = $this->cacheManager->getCachedImageSearch($query, 'pexels');
        if ($cachedResults) {
            logInfo("Using cached Pexels results for query: " . $query);
            return array_slice($cachedResults, 0, $limit);
        }
        
        $url = "https://api.pexels.com/v1/search";
        $params = [
            'query' => $query,
            'per_page' => $limit,
            'orientation' => 'landscape'
        ];
        
        $headers = [
            'Authorization' => $this->pexelsApiKey
        ];
        
        $response = $this->makeApiRequest($url, $params, $headers);
        
        if (!$response) {
            return [];
        }
        
        $data = json_decode($response, true);
        $images = [];
        
        if (isset($data['photos'])) {
            foreach ($data['photos'] as $photo) {
                $images[] = [
                    'url' => $photo['src']['large'],
                    'thumb_url' => $photo['src']['medium'],
                    'alt_text' => $photo['alt'] ?? $query,
                    'width' => $photo['width'],
                    'height' => $photo['height'],
                    'source' => 'pexels',
                    'photographer' => $photo['photographer'] ?? '',
                    'download_url' => $photo['src']['original']
                ];
            }
        }
        
        // Cache the results
        $this->cacheManager->cacheImageSearch($query, $images, 'pexels');
        
        return $images;
    }
    
    /**
     * Search Pixabay API
     */
    private function searchPixabay($query, $limit = 10) {
        // Check cache first
        $cachedResults = $this->cacheManager->getCachedImageSearch($query, 'pixabay');
        if ($cachedResults) {
            logInfo("Using cached Pixabay results for query: " . $query);
            return array_slice($cachedResults, 0, $limit);
        }
        
        $url = "https://pixabay.com/api/";
        $params = [
            'key' => $this->pixabayApiKey,
            'q' => $query,
            'per_page' => $limit,
            'image_type' => 'photo',
            'orientation' => 'horizontal'
        ];
        
        $response = $this->makeApiRequest($url, $params);
        
        if (!$response) {
            return [];
        }
        
        $data = json_decode($response, true);
        $images = [];
        
        if (isset($data['hits'])) {
            foreach ($data['hits'] as $photo) {
                $images[] = [
                    'url' => $photo['webformatURL'],
                    'thumb_url' => $photo['previewURL'],
                    'alt_text' => $photo['tags'] ?? $query,
                    'width' => $photo['webformatWidth'],
                    'height' => $photo['webformatHeight'],
                    'source' => 'pixabay',
                    'photographer' => $photo['user'] ?? '',
                    'download_url' => $photo['largeImageURL']
                ];
            }
        }
        
        // Cache the results
        $this->cacheManager->cacheImageSearch($query, $images, 'pixabay');
        
        return $images;
    }
    
    /**
     * Download image from URL
     */
    public function downloadImage($imageData) {
        // Check cache first
        $cachedPath = $this->cacheManager->getCachedImage($imageData['url']);
        if ($cachedPath) {
            logInfo("Using cached image: " . basename($cachedPath));
            return [
                'local_path' => $cachedPath,
                'cached' => true
            ];
        }
        
        $downloadUrl = $imageData['download_url'] ?? $imageData['url'];
        $fileName = $this->generateFileName($imageData);
        $localPath = $this->downloadDir . '/' . $fileName;
        
        $imageContent = $this->downloadFile($downloadUrl);
        
        if ($imageContent === false) {
            return false;
        }
        
        if (file_put_contents($localPath, $imageContent) === false) {
            return false;
        }
        
        // Optimize image if possible
        $this->optimizeImage($localPath);
        
        // Cache the image
        $this->cacheManager->cacheImage($imageData['url'], $localPath, [
            'source' => $imageData['source'] ?? 'unknown',
            'width' => $imageData['width'] ?? 0,
            'height' => $imageData['height'] ?? 0,
            'file_size' => filesize($localPath)
        ]);
        
        return [
            'local_path' => $localPath,
            'cached' => false
        ];
    }
    
    /**
     * Generate search query for images
     */
    private function generateImageSearchQuery($title, $context = '') {
        // Clean and extract keywords
        $keywords = $this->extractKeywords($title . ' ' . $context);
        
        // Remove common words
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an'];
        $keywords = array_diff($keywords, $stopWords);
        
        // Take top 3 keywords
        $topKeywords = array_slice($keywords, 0, 3);
        
        return implode(' ', $topKeywords);
    }
    
    /**
     * Extract keywords from text
     */
    private function extractKeywords($text) {
        // Simple keyword extraction
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $words = explode(' ', $text);
        $words = array_filter($words, function($word) {
            return strlen($word) > 3;
        });
        
        // Count word frequency
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        return array_keys($wordCount);
    }
    
    /**
     * Select best image from search results
     */
    private function selectBestImage($images, $context = []) {
        if (empty($images)) {
            return null;
        }
        
        // Score images based on various factors
        $scoredImages = [];
        
        foreach ($images as $image) {
            $score = 0;
            
            // Prefer landscape orientation
            if ($image['width'] > $image['height']) {
                $score += 10;
            }
            
            // Prefer higher resolution
            $resolution = $image['width'] * $image['height'];
            if ($resolution > 1000000) { // > 1MP
                $score += 5;
            }
            
            // Prefer certain sources (can be customized)
            if ($image['source'] === 'unsplash') {
                $score += 3;
            } elseif ($image['source'] === 'pexels') {
                $score += 2;
            }
            
            $scoredImages[] = [
                'image' => $image,
                'score' => $score
            ];
        }
        
        // Sort by score
        usort($scoredImages, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return $scoredImages[0]['image'];
    }
    
    /**
     * Extract headings from markdown content
     */
    private function extractHeadings($content) {
        $headings = [];
        
        // Extract H2 and H3 headings
        if (preg_match_all('/^#{2,3}\s+(.+)$/m', $content, $matches)) {
            $headings = $matches[1];
        }
        
        // Clean headings
        $headings = array_map('trim', $headings);
        $headings = array_filter($headings);
        
        return array_slice($headings, 0, 3); // Limit to 3 headings
    }
    
    /**
     * Generate unique filename for downloaded image
     */
    private function generateFileName($imageData) {
        $extension = $this->getImageExtension($imageData['download_url']);
        $baseName = 'image_' . $imageData['source'] . '_' . $imageData['id'];
        return $baseName . '.' . $extension;
    }
    
    /**
     * Get image extension from URL
     */
    private function getImageExtension($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        // Default to jpg if no extension found
        return $extension ?: 'jpg';
    }
    
    /**
     * Remove duplicate images from results
     */
    private function removeDuplicateImages($images) {
        $unique = [];
        $seen = [];
        
        foreach ($images as $image) {
            $key = $image['source'] . '_' . $image['id'];
            if (!isset($seen[$key])) {
                $unique[] = $image;
                $seen[$key] = true;
            }
        }
        
        return $unique;
    }
    
    /**
     * Download file from URL
     */
    private function downloadFile($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'WordPress Website Generator/1.0',
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $content !== false) {
            return $content;
        }
        
        return null;
    }
    
    /**
     * Make API request
     */
    private function makeApiRequest($url, $params = [], $headers = []) {
        $fullUrl = $url . '?' . http_build_query($params);
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response !== false) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Basic image optimization
     */
    private function optimizeImage($imagePath) {
        // Basic optimization - resize if too large
        $maxWidth = 1920;
        $maxHeight = 1080;
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return;
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Check if resize is needed
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return;
        }
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagejpeg($newImage, $imagePath, 85);
                break;
                
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagepng($newImage, $imagePath, 8);
                break;
        }
        
        if (isset($source)) {
            imagedestroy($source);
        }
        imagedestroy($newImage);
    }
}

?>

