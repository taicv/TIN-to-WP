<?php

require_once 'vendor/autoload.php';
require_once 'CacheManager.php';

use OpenAI\Client;
use OpenAI\Factory;

class AIContentGenerator {
    private $client;
    private $model = 'gpt-4o';
    private $maxTokens = 4000;
    private $cacheManager;
    
    public function __construct($apiKey, $endpoint = null) {
        // Create custom HTTP client to handle SSL certificate issues
        $httpClient = new \GuzzleHttp\Client([
            'verify' => false, // Disable SSL verification for development
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'WordPress Website Generator/1.0'
            ]
        ]);
        
        // Build the factory configuration
        $factory = (new Factory())
            ->withApiKey($apiKey)
            ->withHttpClient($httpClient);
        
        // Add custom endpoint if provided
        if ($endpoint) {
            $factory = $factory->withBaseUri($endpoint);
        }
        
        $this->client = $factory->make();
        
        // Initialize cache manager
        $this->cacheManager = new CacheManager();
    }
    
    /**
     * Generate complete website content based on business information
     * @param array $businessInfo Business information from VietnamBusinessCollector
     * @param string $colorPalette Selected color palette
     * @return array Complete website content structure
     */
    public function generateWebsiteContent($businessInfo, $colorPalette = 'professional-blue') {
        $content = [
            'business_info' => $businessInfo,
            'color_palette' => $colorPalette,
            'sitemap' => [],
            'pages' => [],
            'blog_articles' => [],
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            // Step 1: Generate sitemap
            $content['sitemap'] = $this->generateSitemap($businessInfo);
            
            // Step 2: Generate page content for each page in sitemap
            foreach ($content['sitemap']['pages'] as $page) {
                $pageContent = $this->generatePageContent($businessInfo, $page, $colorPalette);
                $content['pages'][$page['slug']] = $pageContent;
            }
            
            // Step 3: Generate blog articles
            $content['blog_articles'] = $this->generateBlogArticles($businessInfo);
            
        } catch (Exception $e) {
            error_log("Error generating website content: " . $e->getMessage());
            throw $e;
        }
        
        return $content;
    }
    
    /**
     * Generate sitemap structure based on business information
     */
    public function generateSitemap($businessInfo) {
        $cacheKey = 'sitemap_' . md5(json_encode($businessInfo));
        
        // Check cache first
        $cachedSitemap = $this->cacheManager->getCachedAIResponse($cacheKey);
        if ($cachedSitemap) {
            logInfo("Using cached sitemap for business: " . ($businessInfo['company_name'] ?? 'Unknown'));
            return $cachedSitemap;
        }
        
        $prompt = $this->buildSitemapPrompt($businessInfo);
        
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional web developer and UX designer specializing in creating optimal website structures for businesses. Generate sitemaps in valid JSON format only.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 2000,
            'temperature' => 0.7
        ]);
        
        $sitemapJson = $response->choices[0]->message->content;
        
        // Clean and parse JSON response
        $sitemapJson = $this->cleanJsonResponse($sitemapJson);
        $sitemap = json_decode($sitemapJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse sitemap JSON: ' . json_last_error_msg());
        }
        
        // Cache the sitemap response
        $this->cacheManager->cacheAIResponse($cacheKey, $sitemap, [
            'business_info' => $businessInfo,
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
            'response_length' => strlen($sitemapJson)
        ]);
        
        return $sitemap;
    }
    
    /**
     * Generate content for a specific page
     */
    public function generatePageContent($businessInfo, $pageInfo, $colorPalette) {
        $cacheKey = 'page_content_' . md5(json_encode($businessInfo) . json_encode($pageInfo) . $colorPalette);
        
        // Check cache first
        $cachedContent = $this->cacheManager->getCachedAIResponse($cacheKey);
        if ($cachedContent) {
            logInfo("Using cached page content for: " . $pageInfo['title']);
            return $cachedContent;
        }
        
        $prompt = $this->buildPageContentPrompt($businessInfo, $pageInfo, $colorPalette);
        
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional copywriter specializing in business websites. Create engaging, SEO-optimized content in Markdown format. Include proper headings, paragraphs, and structure.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.8
        ]);
        
        $content = $response->choices[0]->message->content;
        
        $pageContent = [
            'title' => $pageInfo['title'],
            'slug' => $pageInfo['slug'],
            'meta_description' => $this->generateMetaDescription($businessInfo, $pageInfo),
            'content' => $content,
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        // Cache the page content response
        $this->cacheManager->cacheAIResponse($cacheKey, $pageContent, [
            'business_info' => $businessInfo,
            'page_info' => $pageInfo,
            'color_palette' => $colorPalette,
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
            'response_length' => strlen($content)
        ]);
        
        return $pageContent;
    }
    
    /**
     * Generate blog articles
     */
    public function generateBlogArticles($businessInfo) {
        $cacheKey = 'blog_articles_' . md5(json_encode($businessInfo));
        
        // Check cache first
        $cachedArticles = $this->cacheManager->getCachedAIResponse($cacheKey);
        if ($cachedArticles) {
            logInfo("Using cached blog articles for business: " . ($businessInfo['company_name'] ?? 'Unknown'));
            return $cachedArticles;
        }
        
        $articles = [];
        $topics = $this->generateBlogTopics($businessInfo);
        
        foreach ($topics as $topic) {
            $article = $this->generateSingleBlogArticle($businessInfo, $topic);
            $articles[] = $article;
        }
        
        // Cache the blog articles response
        $this->cacheManager->cacheAIResponse($cacheKey, $articles, [
            'business_info' => $businessInfo,
            'topics_count' => count($topics),
            'articles_count' => count($articles)
        ]);
        
        return $articles;
    }
    
    /**
     * Generate blog topics based on business information
     */
    private function generateBlogTopics($businessInfo) {
        $cacheKey = 'blog_topics_' . md5(json_encode($businessInfo));
        
        // Check cache first
        $cachedTopics = $this->cacheManager->getCachedAIResponse($cacheKey);
        if ($cachedTopics) {
            logInfo("Using cached blog topics for business: " . ($businessInfo['company_name'] ?? 'Unknown'));
            return $cachedTopics;
        }
        
        $prompt = $this->buildBlogTopicsPrompt($businessInfo);
        
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a content marketing specialist. Generate blog topic ideas in JSON format only.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.8
        ]);
        
        $topicsJson = $response->choices[0]->message->content;
        $topicsJson = $this->cleanJsonResponse($topicsJson);
        $topics = json_decode($topicsJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback topics if JSON parsing fails
            $topics = $this->getFallbackBlogTopics($businessInfo);
        }
        
        // Cache the blog topics response
        $this->cacheManager->cacheAIResponse($cacheKey, $topics, [
            'business_info' => $businessInfo,
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
            'response_length' => strlen($topicsJson)
        ]);
        
        return $topics;
    }
    
    /**
     * Generate a single blog article
     */
    private function generateSingleBlogArticle($businessInfo, $topic) {
        $cacheKey = 'blog_article_' . md5(json_encode($businessInfo) . json_encode($topic));
        
        // Check cache first
        $cachedArticle = $this->cacheManager->getCachedAIResponse($cacheKey);
        if ($cachedArticle) {
            logInfo("Using cached blog article: " . $topic['title']);
            return $cachedArticle;
        }
        
        $prompt = $this->buildBlogArticlePrompt($businessInfo, $topic);
        
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional blog writer. Create engaging, informative blog articles in Markdown format with proper SEO optimization.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.8
        ]);
        
        $content = $response->choices[0]->message->content;
        
        $article = [
            'title' => $topic['title'],
            'slug' => $this->generateSlug($topic['title']),
            'meta_description' => $topic['meta_description'] ?? $this->generateBlogMetaDescription($topic['title']),
            'content' => $content,
            'category' => $topic['category'] ?? 'General',
            'tags' => $topic['tags'] ?? [],
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        // Cache the blog article response
        $this->cacheManager->cacheAIResponse($cacheKey, $article, [
            'business_info' => $businessInfo,
            'topic' => $topic,
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
            'response_length' => strlen($content)
        ]);
        
        return $article;
    }
    
    /**
     * Build sitemap generation prompt
     */
    private function buildSitemapPrompt($businessInfo) {
        $companyName = $businessInfo['company_name'] ?? 'Business';
        $industry = $businessInfo['industry'] ?? 'General Business';
        $services = is_array($businessInfo['services']) ? implode(', ', $businessInfo['services']) : ($businessInfo['services'] ?? 'Various services');
        
        return "Create a professional website sitemap for a Vietnamese business with the following information:

Company Name: {$companyName}
Industry: {$industry}
Services: {$services}
Business Type: {$businessInfo['business_type']}

Generate a sitemap with 5-8 main pages that would be appropriate for this business. Include:
- Homepage
- About Us
- Services/Products pages
- Contact page
- Other relevant pages based on the industry

Return the response in this exact JSON format:
{
    \"website_title\": \"Company Name - Brief Description\",
    \"pages\": [
        {
            \"title\": \"Page Title\",
            \"slug\": \"page-slug\",
            \"description\": \"Brief description of page purpose\",
            \"priority\": 1-10,
            \"in_main_menu\": true/false
        }
    ],
    \"navigation_structure\": {
        \"main_menu\": [\"slug1\", \"slug2\"],
        \"footer_menu\": [\"slug3\", \"slug4\"]
    }
}

Make sure the sitemap is logical, user-friendly, and appropriate for the business type and industry.";
    }
    
    /**
     * Build page content generation prompt
     */
    private function buildPageContentPrompt($businessInfo, $pageInfo, $colorPalette) {
        $companyName = $businessInfo['company_name'] ?? 'Our Company';
        $industry = $businessInfo['industry'] ?? 'business';
        
        return "Create professional website content for the '{$pageInfo['title']}' page of {$companyName}, a {$industry} company in Vietnam.

Business Information:
- Company: {$companyName}
- Industry: {$industry}
- Services: " . (is_array($businessInfo['services']) ? implode(', ', $businessInfo['services']) : $businessInfo['services']) . "
- Address: {$businessInfo['address']}
- Phone: {$businessInfo['phone']}
- Email: {$businessInfo['email']}

Page Purpose: {$pageInfo['description']}
Color Palette: {$colorPalette}

Create engaging, professional content in Markdown format that:
1. Is SEO-optimized with proper headings (H1, H2, H3)
2. Includes relevant keywords naturally
3. Is written for Vietnamese market but in English
4. Maintains professional tone
5. Includes call-to-action elements where appropriate
6. Is approximately 800-1200 words
7. Uses bullet points and formatting for readability

Do not include HTML tags, only Markdown formatting.";
    }
    
    /**
     * Build blog topics generation prompt
     */
    private function buildBlogTopicsPrompt($businessInfo) {
        $industry = $businessInfo['industry'] ?? 'business';
        $services = is_array($businessInfo['services']) ? implode(', ', $businessInfo['services']) : $businessInfo['services'];
        
        return "Generate 5 blog article topics for a {$industry} company in Vietnam that offers: {$services}

The topics should be:
1. Relevant to the industry and Vietnamese market
2. Valuable to potential customers
3. SEO-friendly
4. Educational or informative
5. Engaging and shareable

Return exactly 5 topics in this JSON format:
{
    \"topics\": [
        {
            \"title\": \"Blog Article Title\",
            \"meta_description\": \"SEO meta description (150-160 characters)\",
            \"category\": \"Category Name\",
            \"tags\": [\"tag1\", \"tag2\", \"tag3\"],
            \"target_keywords\": [\"keyword1\", \"keyword2\"]
        }
    ]
}";
    }
    
    /**
     * Build blog article generation prompt
     */
    private function buildBlogArticlePrompt($businessInfo, $topic) {
        $companyName = $businessInfo['company_name'] ?? 'Our Company';
        $industry = $businessInfo['industry'] ?? 'business';
        
        return "Write a comprehensive blog article for {$companyName}, a {$industry} company in Vietnam.

Article Title: {$topic['title']}
Target Keywords: " . (isset($topic['target_keywords']) ? implode(', ', $topic['target_keywords']) : '') . "
Category: {$topic['category']}

Create a well-structured blog article that:
1. Is 1000-1500 words long
2. Uses proper Markdown formatting with H1, H2, H3 headings
3. Includes an engaging introduction
4. Has 4-6 main sections with subheadings
5. Includes practical tips or actionable advice
6. Is SEO-optimized with natural keyword usage
7. Ends with a compelling conclusion
8. Is written for Vietnamese audience but in English
9. Maintains professional yet engaging tone
10. Includes relevant examples or case studies where appropriate

Do not include HTML tags, only Markdown formatting.";
    }
    
    /**
     * Generate meta description for a page
     */
    private function generateMetaDescription($businessInfo, $pageInfo) {
        $companyName = $businessInfo['company_name'] ?? 'Our Company';
        $description = substr($pageInfo['description'], 0, 120);
        
        return "{$description} - {$companyName}. Professional services in Vietnam.";
    }
    
    /**
     * Generate meta description for blog article
     */
    private function generateBlogMetaDescription($title) {
        return substr("Learn about {$title}. Expert insights and practical tips for businesses in Vietnam.", 0, 160);
    }
    
    /**
     * Generate URL-friendly slug
     */
    private function generateSlug($title) {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Clean JSON response from AI
     */
    private function cleanJsonResponse($response) {
        // Remove markdown code blocks
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        
        // Remove any text before the first {
        $firstBrace = strpos($response, '{');
        if ($firstBrace !== false) {
            $response = substr($response, $firstBrace);
        }
        
        // Remove any text after the last }
        $lastBrace = strrpos($response, '}');
        if ($lastBrace !== false) {
            $response = substr($response, 0, $lastBrace + 1);
        }
        
        return trim($response);
    }
    
    /**
     * Fallback blog topics if AI generation fails
     */
    private function getFallbackBlogTopics($businessInfo) {
        $industry = $businessInfo['industry'] ?? 'business';
        
        return [
            'topics' => [
                [
                    'title' => "Top Trends in {$industry} Industry in Vietnam 2025",
                    'meta_description' => "Discover the latest trends shaping the {$industry} industry in Vietnam this year.",
                    'category' => 'Industry Insights',
                    'tags' => ['trends', 'vietnam', $industry],
                    'target_keywords' => [$industry, 'vietnam trends']
                ],
                [
                    'title' => "How to Choose the Right {$industry} Service Provider",
                    'meta_description' => "Essential guide to selecting the best {$industry} service provider for your needs.",
                    'category' => 'Guides',
                    'tags' => ['guide', 'tips', $industry],
                    'target_keywords' => [$industry, 'service provider']
                ],
                [
                    'title' => "Benefits of Professional {$industry} Services",
                    'meta_description' => "Learn about the key benefits of investing in professional {$industry} services.",
                    'category' => 'Benefits',
                    'tags' => ['benefits', 'professional', $industry],
                    'target_keywords' => ['professional ' . $industry, 'benefits']
                ],
                [
                    'title' => "Common Mistakes to Avoid in {$industry}",
                    'meta_description' => "Avoid these common pitfalls when dealing with {$industry} matters.",
                    'category' => 'Tips',
                    'tags' => ['mistakes', 'tips', $industry],
                    'target_keywords' => [$industry . ' mistakes', 'avoid']
                ],
                [
                    'title' => "Future of {$industry} in Vietnam",
                    'meta_description' => "Explore what the future holds for the {$industry} industry in Vietnam.",
                    'category' => 'Future Trends',
                    'tags' => ['future', 'vietnam', $industry],
                    'target_keywords' => ['future ' . $industry, 'vietnam']
                ]
            ]
        ];
    }
    
    /**
     * Save content to files
     */
    public function saveContentToFiles($content, $outputDir) {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Save sitemap
        file_put_contents($outputDir . '/sitemap.json', json_encode($content['sitemap'], JSON_PRETTY_PRINT));
        
        // Save pages
        $pagesDir = $outputDir . '/pages';
        if (!is_dir($pagesDir)) {
            mkdir($pagesDir, 0755, true);
        }
        
        foreach ($content['pages'] as $slug => $page) {
            file_put_contents($pagesDir . '/' . $slug . '.md', $page['content']);
            
            // Save page metadata
            $metadata = [
                'title' => $page['title'],
                'slug' => $page['slug'],
                'meta_description' => $page['meta_description'],
                'generated_at' => $page['generated_at']
            ];
            file_put_contents($pagesDir . '/' . $slug . '.json', json_encode($metadata, JSON_PRETTY_PRINT));
        }
        
        // Save blog articles
        $blogDir = $outputDir . '/blog';
        if (!is_dir($blogDir)) {
            mkdir($blogDir, 0755, true);
        }
        
        foreach ($content['blog_articles'] as $article) {
            file_put_contents($blogDir . '/' . $article['slug'] . '.md', $article['content']);
            
            // Save article metadata
            $metadata = [
                'title' => $article['title'],
                'slug' => $article['slug'],
                'meta_description' => $article['meta_description'],
                'category' => $article['category'],
                'tags' => $article['tags'],
                'generated_at' => $article['generated_at']
            ];
            file_put_contents($blogDir . '/' . $article['slug'] . '.json', json_encode($metadata, JSON_PRETTY_PRINT));
        }
        
        // Save complete content structure
        file_put_contents($outputDir . '/complete_content.json', json_encode($content, JSON_PRETTY_PRINT));
        
        return $outputDir;
    }
    
    /**
     * Test API connection with a simple request
     * @return bool True if connection successful
     */
    public function testConnection() {
        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello! Please respond with "Connection test successful"']
                ],
                'max_tokens' => 50,
                'temperature' => 0.1
            ]);
            
            $content = $response->choices[0]->message->content;
            return !empty($content);
        } catch (Exception $e) {
            error_log("API connection test failed: " . $e->getMessage());
            return false;
        }
    }
}

// Usage example
/*
// Load configuration
require_once 'config.php';

$apiKey = OPENAI_API_KEY;
$endpoint = OPENAI_API_ENDPOINT;

// Initialize with custom endpoint
$generator = new AIContentGenerator($apiKey, $endpoint);

// Assuming $businessInfo comes from VietnamBusinessCollector
$businessInfo = [
    'company_name' => 'ABC Technology Solutions',
    'industry' => 'Information Technology',
    'services' => ['Web Development', 'Mobile Apps', 'IT Consulting'],
    'address' => 'Ho Chi Minh City, Vietnam',
    'phone' => '+84 123 456 789',
    'email' => 'info@abctech.vn'
];

$content = $generator->generateWebsiteContent($businessInfo, 'professional-blue');
$outputDir = $generator->saveContentToFiles($content, './generated_content');

echo "Content generated and saved to: " . $outputDir;
*/
?>

