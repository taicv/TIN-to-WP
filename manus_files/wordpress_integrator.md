# WordPress Integration Module

## Overview

This module provides comprehensive WordPress integration capabilities using the WordPress REST API. It can create posts, pages, menus, categories, and manage media uploads to build a complete WordPress website from generated content.

## Features

1. **Page Management**: Create and manage WordPress pages
2. **Post Management**: Create blog posts with categories and tags
3. **Menu Management**: Create navigation menus and menu items
4. **Media Management**: Upload and manage images
5. **Category/Tag Management**: Create and assign taxonomies
6. **Theme Customization**: Basic theme customization support

## PHP Implementation

```php
<?php

class WordPressIntegrator {
    private $siteUrl;
    private $username;
    private $password;
    private $timeout = 30;
    
    public function __construct($siteUrl, $username, $password) {
        $this->siteUrl = rtrim($siteUrl, '/');
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * Create complete WordPress website from generated content
     * @param array $content Content structure from AIContentGenerator
     * @return array Results of website creation
     */
    public function createWebsite($content) {
        $results = [
            'success' => false,
            'pages' => [],
            'posts' => [],
            'menus' => [],
            'categories' => [],
            'errors' => [],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            // Step 1: Create categories for blog posts
            $results['categories'] = $this->createCategories($content['blog_articles']);
            
            // Step 2: Create pages
            foreach ($content['pages'] as $slug => $pageData) {
                $pageResult = $this->createPage($pageData);
                $results['pages'][$slug] = $pageResult;
            }
            
            // Step 3: Create blog posts
            foreach ($content['blog_articles'] as $article) {
                $postResult = $this->createPost($article, $results['categories']);
                $results['posts'][] = $postResult;
            }
            
            // Step 4: Create navigation menus
            if (isset($content['sitemap']['navigation_structure'])) {
                $menuResult = $this->createNavigationMenus($content['sitemap'], $results['pages']);
                $results['menus'] = $menuResult;
            }
            
            // Step 5: Set homepage
            $this->setHomepage($results['pages']);
            
            $results['success'] = true;
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            error_log("WordPress integration error: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Create a WordPress page
     */
    public function createPage($pageData) {
        $endpoint = '/wp-json/wp/v2/pages';
        
        $data = [
            'title' => $pageData['title'],
            'content' => $this->convertMarkdownToHTML($pageData['content']),
            'slug' => $pageData['slug'],
            'status' => 'publish',
            'meta' => [
                'description' => $pageData['meta_description']
            ]
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        if ($response && isset($response['id'])) {
            return [
                'success' => true,
                'id' => $response['id'],
                'url' => $response['link'],
                'slug' => $response['slug']
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to create page: ' . $pageData['title']
        ];
    }
    
    /**
     * Create a WordPress post
     */
    public function createPost($articleData, $categories = []) {
        $endpoint = '/wp-json/wp/v2/posts';
        
        // Find category ID
        $categoryIds = [];
        if (isset($articleData['category'])) {
            foreach ($categories as $category) {
                if ($category['name'] === $articleData['category']) {
                    $categoryIds[] = $category['id'];
                    break;
                }
            }
        }
        
        // Create tags if they don't exist
        $tagIds = [];
        if (isset($articleData['tags']) && is_array($articleData['tags'])) {
            $tagIds = $this->createTags($articleData['tags']);
        }
        
        $data = [
            'title' => $articleData['title'],
            'content' => $this->convertMarkdownToHTML($articleData['content']),
            'slug' => $articleData['slug'],
            'status' => 'publish',
            'categories' => $categoryIds,
            'tags' => $tagIds,
            'meta' => [
                'description' => $articleData['meta_description']
            ]
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        if ($response && isset($response['id'])) {
            return [
                'success' => true,
                'id' => $response['id'],
                'url' => $response['link'],
                'slug' => $response['slug']
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to create post: ' . $articleData['title']
        ];
    }
    
    /**
     * Create categories from blog articles
     */
    public function createCategories($articles) {
        $categories = [];
        $uniqueCategories = [];
        
        // Extract unique categories
        foreach ($articles as $article) {
            if (isset($article['category']) && !in_array($article['category'], $uniqueCategories)) {
                $uniqueCategories[] = $article['category'];
            }
        }
        
        // Create categories
        foreach ($uniqueCategories as $categoryName) {
            $category = $this->createCategory($categoryName);
            if ($category) {
                $categories[] = $category;
            }
        }
        
        return $categories;
    }
    
    /**
     * Create a single category
     */
    private function createCategory($name) {
        $endpoint = '/wp-json/wp/v2/categories';
        
        $data = [
            'name' => $name,
            'slug' => $this->generateSlug($name)
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        if ($response && isset($response['id'])) {
            return [
                'id' => $response['id'],
                'name' => $response['name'],
                'slug' => $response['slug']
            ];
        }
        
        return null;
    }
    
    /**
     * Create tags
     */
    private function createTags($tagNames) {
        $tagIds = [];
        
        foreach ($tagNames as $tagName) {
            $tag = $this->createTag($tagName);
            if ($tag) {
                $tagIds[] = $tag['id'];
            }
        }
        
        return $tagIds;
    }
    
    /**
     * Create a single tag
     */
    private function createTag($name) {
        $endpoint = '/wp-json/wp/v2/tags';
        
        $data = [
            'name' => $name,
            'slug' => $this->generateSlug($name)
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        if ($response && isset($response['id'])) {
            return [
                'id' => $response['id'],
                'name' => $response['name'],
                'slug' => $response['slug']
            ];
        }
        
        return null;
    }
    
    /**
     * Create navigation menus
     */
    public function createNavigationMenus($sitemap, $pages) {
        $menus = [];
        
        if (isset($sitemap['navigation_structure']['main_menu'])) {
            $mainMenu = $this->createMenu('Main Menu', $sitemap['navigation_structure']['main_menu'], $pages);
            if ($mainMenu) {
                $menus['main'] = $mainMenu;
            }
        }
        
        if (isset($sitemap['navigation_structure']['footer_menu'])) {
            $footerMenu = $this->createMenu('Footer Menu', $sitemap['navigation_structure']['footer_menu'], $pages);
            if ($footerMenu) {
                $menus['footer'] = $footerMenu;
            }
        }
        
        return $menus;
    }
    
    /**
     * Create a single menu
     */
    private function createMenu($menuName, $menuItems, $pages) {
        // First, create the menu
        $menuEndpoint = '/wp-json/wp/v2/menus';
        
        $menuData = [
            'name' => $menuName,
            'slug' => $this->generateSlug($menuName)
        ];
        
        $menuResponse = $this->makeRequest('POST', $menuEndpoint, $menuData);
        
        if (!$menuResponse || !isset($menuResponse['id'])) {
            return null;
        }
        
        $menuId = $menuResponse['id'];
        $menuItemsCreated = [];
        
        // Create menu items
        foreach ($menuItems as $pageSlug) {
            if (isset($pages[$pageSlug]) && $pages[$pageSlug]['success']) {
                $menuItem = $this->createMenuItem($menuId, $pages[$pageSlug]);
                if ($menuItem) {
                    $menuItemsCreated[] = $menuItem;
                }
            }
        }
        
        return [
            'id' => $menuId,
            'name' => $menuName,
            'slug' => $menuResponse['slug'],
            'items' => $menuItemsCreated
        ];
    }
    
    /**
     * Create a menu item
     */
    private function createMenuItem($menuId, $pageData) {
        $endpoint = '/wp-json/wp/v2/menu-items';
        
        $data = [
            'title' => $pageData['title'] ?? 'Page',
            'object' => 'page',
            'object_id' => $pageData['id'],
            'menu_order' => 0,
            'menus' => $menuId
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        if ($response && isset($response['id'])) {
            return [
                'id' => $response['id'],
                'title' => $response['title']['rendered'],
                'url' => $response['url']
            ];
        }
        
        return null;
    }
    
    /**
     * Set homepage
     */
    private function setHomepage($pages) {
        // Find homepage
        $homepageId = null;
        foreach ($pages as $slug => $page) {
            if ($slug === 'home' || $slug === 'homepage' || $slug === 'index') {
                $homepageId = $page['id'];
                break;
            }
        }
        
        if ($homepageId) {
            // Set static front page
            $this->updateSiteOption('show_on_front', 'page');
            $this->updateSiteOption('page_on_front', $homepageId);
        }
    }
    
    /**
     * Update site option
     */
    private function updateSiteOption($option, $value) {
        $endpoint = '/wp-json/wp/v2/settings';
        
        $data = [
            $option => $value
        ];
        
        return $this->makeRequest('POST', $endpoint, $data);
    }
    
    /**
     * Upload media file
     */
    public function uploadMedia($filePath, $title = '', $altText = '') {
        if (!file_exists($filePath)) {
            return null;
        }
        
        $endpoint = '/wp-json/wp/v2/media';
        
        $fileData = file_get_contents($filePath);
        $fileName = basename($filePath);
        $mimeType = mime_content_type($filePath);
        
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ];
        
        if ($title) {
            $headers['Title'] = $title;
        }
        
        if ($altText) {
            $headers['Alt-Text'] = $altText;
        }
        
        $response = $this->makeRequest('POST', $endpoint, $fileData, $headers, true);
        
        if ($response && isset($response['id'])) {
            return [
                'id' => $response['id'],
                'url' => $response['source_url'],
                'title' => $response['title']['rendered']
            ];
        }
        
        return null;
    }
    
    /**
     * Set featured image for post
     */
    public function setFeaturedImage($postId, $mediaId) {
        $endpoint = "/wp-json/wp/v2/posts/{$postId}";
        
        $data = [
            'featured_media' => $mediaId
        ];
        
        return $this->makeRequest('POST', $endpoint, $data);
    }
    
    /**
     * Convert Markdown to HTML
     */
    private function convertMarkdownToHTML($markdown) {
        // Basic Markdown to HTML conversion
        // For production, consider using a proper Markdown parser like Parsedown
        
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        
        // Bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);
        
        // Line breaks
        $html = preg_replace('/\n\n/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';
        
        // Clean up empty paragraphs
        $html = preg_replace('/<p><\/p>/', '', $html);
        
        return $html;
    }
    
    /**
     * Generate URL-friendly slug
     */
    private function generateSlug($text) {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Make HTTP request to WordPress REST API
     */
    private function makeRequest($method, $endpoint, $data = null, $headers = [], $isFileUpload = false) {
        $url = $this->siteUrl . $endpoint;
        
        $ch = curl_init();
        
        $defaultHeaders = [
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password)
        ];
        
        if (!$isFileUpload) {
            $defaultHeaders[] = 'Content-Type: application/json';
        }
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    if ($isFileUpload) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
                }
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
       
(Content truncated due to size limit. Use line ranges to read in chunks)