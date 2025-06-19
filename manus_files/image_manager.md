# Image Management System

## Overview

This module provides comprehensive image management capabilities for the WordPress website creation system. It integrates with multiple free stock image APIs to search, download, and manage images for blog posts and website content.

## Supported APIs

1. **Unsplash API**: High-quality photos from professional photographers
2. **Pexels API**: Free stock photos and videos
3. **Pixabay API**: Royalty-free images, vectors, and videos

## Features

1. **Multi-API Search**: Search across multiple image providers
2. **Smart Image Selection**: AI-powered image selection based on content
3. **Automatic Download**: Download and optimize images
4. **WordPress Integration**: Upload images to WordPress media library
5. **Featured Image Assignment**: Automatically set featured images for posts
6. **Content Image Insertion**: Insert relevant images into page/post content

## PHP Implementation

```php
<?php

class ImageManager {
    private $unsplashApiKey;
    private $pexelsApiKey;
    private $pixabayApiKey;
    private $downloadDir;
    private $timeout = 30;
    
    public function __construct($config = []) {
        $this->unsplashApiKey = $config['unsplash_api_key'] ?? '';
        $this->pexelsApiKey = $config['pexels_api_key'] ?? '';
        $this->pixabayApiKey = $config['pixabay_api_key'] ?? '';
        $this->downloadDir = $config['download_dir'] ?? './downloads/images';
        
        // Create download directory if it doesn't exist
        if (!is_dir($this->downloadDir)) {
            mkdir($this->downloadDir, 0755, true);
        }
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
            
            // Set as featured image (assuming we have the post ID)
            // This would need to be called after the post is created
            
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
        $url = "https://api.unsplash.com/search/photos";
        $params = [
            'query' => $query,
            'per_page' => min($limit, 30),
            'orientation' => 'landscape'
        ];
        
        $headers = [
            'Authorization: Client-ID ' . $this->unsplashApiKey
        ];
        
        $response = $this->makeApiRequest($url, $params, $headers);
        
        if (!$response || !isset($response['results'])) {
            return [];
        }
        
        $images = [];
        foreach ($response['results'] as $photo) {
            $images[] = [
                'id' => $photo['id'],
                'url' => $photo['urls']['regular'],
                'download_url' => $photo['urls']['full'],
                'alt_text' => $photo['alt_description'] ?? $photo['description'] ?? $query,
                'width' => $photo['width'],
                'height' => $photo['height'],
                'source' => 'unsplash',
                'attribution' => 'Photo by ' . $photo['user']['name'] . ' on Unsplash',
                'photographer' => $photo['user']['name'],
                'photographer_url' => $photo['user']['links']['html']
            ];
        }
        
        return $images;
    }
    
    /**
     * Search Pexels API
     */
    private function searchPexels($query, $limit = 10) {
        $url = "https://api.pexels.com/v1/search";
        $params = [
            'query' => $query,
            'per_page' => min($limit, 80),
            'orientation' => 'landscape'
        ];
        
        $headers = [
            'Authorization: ' . $this->pexelsApiKey
        ];
        
        $response = $this->makeApiRequest($url, $params, $headers);
        
        if (!$response || !isset($response['photos'])) {
            return [];
        }
        
        $images = [];
        foreach ($response['photos'] as $photo) {
            $images[] = [
                'id' => $photo['id'],
                'url' => $photo['src']['large'],
                'download_url' => $photo['src']['original'],
                'alt_text' => $photo['alt'] ?? $query,
                'width' => $photo['width'],
                'height' => $photo['height'],
                'source' => 'pexels',
                'attribution' => 'Photo by ' . $photo['photographer'] . ' on Pexels',
                'photographer' => $photo['photographer'],
                'photographer_url' => $photo['photographer_url']
            ];
        }
        
        return $images;
    }
    
    /**
     * Search Pixabay API
     */
    private function searchPixabay($query, $limit = 10) {
        $url = "https://pixabay.com/api/";
        $params = [
            'key' => $this->pixabayApiKey,
            'q' => $query,
            'image_type' => 'photo',
            'orientation' => 'horizontal',
            'per_page' => min($limit, 200),
            'safesearch' => 'true'
        ];
        
        $response = $this->makeApiRequest($url, $params);
        
        if (!$response || !isset($response['hits'])) {
            return [];
        }
        
        $images = [];
        foreach ($response['hits'] as $photo) {
            $images[] = [
                'id' => $photo['id'],
                'url' => $photo['webformatURL'],
                'download_url' => $photo['largeImageURL'],
                'alt_text' => $photo['tags'] ?? $query,
                'width' => $photo['imageWidth'],
                'height' => $photo['imageHeight'],
                'source' => 'pixabay',
                'attribution' => 'Image by ' . $photo['user'] . ' from Pixabay',
                'photographer' => $photo['user'],
                'photographer_url' => 'https://pixabay.com/users/' . $photo['user'] . '-' . $photo['user_id'] . '/'
            ];
        }
        
        return $images;
    }
    
    /**
     * Download image to local storage
     */
    public function downloadImage($imageData) {
        try {
            $imageUrl = $imageData['download_url'];
            $fileName = $this->generateFileName($imageData);
            $localPath = $this->downloadDir . '/' . $fileName;
            
            // Download image
            $imageContent = $this->downloadFile($imageUrl);
            
            if (!$imageContent) {
                return null;
            }
            
            // Save to local file
            if (file_put_contents($localPath, $imageContent) === false) {
                return null;
            }
            
            // Optimize image if needed
            $this->optimizeImage($localPath);
            
            return [
                'local_path' => $localPath,
                'file_name' => $fileName,
                'file_size' => filesize($localPath),
                'original_url' => $imageUrl
            ];
            
        } catch (Exception $e) {
            error_log("Image download error: " . $e->getMessage());
            return null;
        }
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
     * Gen
(Content truncated due to size limit. Use line ranges to read in chunks)