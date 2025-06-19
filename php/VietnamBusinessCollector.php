<?php

require_once 'CacheManager.php';

class VietnamBusinessCollector {
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    private $timeout = 30;
    private $maxRetries = 3;
    private $cacheManager;
    
    public function __construct() {
        // Initialize cache manager
        $this->cacheManager = new CacheManager();
    }
    
    /**
     * Main function to collect business information by TAX code
     * @param string $taxCode The Vietnam business TAX code
     * @return array Business information array
     */
    public function collectBusinessInfo($taxCode) {
        // Check cache first
        $cachedData = $this->cacheManager->getCachedBusinessData($taxCode);
        if ($cachedData) {
            logInfo("Using cached business data for tax code: " . $taxCode);
            return $cachedData;
        }
        
        $businessInfo = [
            'tax_code' => $taxCode,
            'company_name' => '',
            'address' => '',
            'phone' => '',
            'email' => '',
            'website' => '',
            'business_type' => '',
            'industry' => '',
            'services' => [],
            'registration_date' => '',
            'status' => '',
            'source' => '',
            'collected_at' => date('Y-m-d H:i:s')
        ];
        
        // Try multiple sources
        $sources = [
            'official_portal' => [$this, 'collectFromOfficialPortal'],
            'web_search' => [$this, 'collectFromWebSearch'],
            'business_directories' => [$this, 'collectFromBusinessDirectories']
        ];
        
        foreach ($sources as $sourceName => $method) {
            try {
                $data = call_user_func($method, $taxCode);
                if (!empty($data['company_name'])) {
                    $businessInfo = array_merge($businessInfo, $data);
                    $businessInfo['source'] = $sourceName;
                    break;
                }
            } catch (Exception $e) {
                error_log("Error collecting from {$sourceName}: " . $e->getMessage());
                continue;
            }
        }
        
        // Enhance data with web search if basic info is available
        if (!empty($businessInfo['company_name'])) {
            $enhancedData = $this->enhanceWithWebSearch($businessInfo);
            $businessInfo = array_merge($businessInfo, $enhancedData);
        }
        
        // Cache the business data
        $this->cacheManager->cacheBusinessData($taxCode, $businessInfo, $businessInfo['source']);
        
        return $businessInfo;
    }
    
    /**
     * Collect data from official Vietnam business portal
     */
    private function collectFromOfficialPortal($taxCode) {
        $cacheKey = 'official_portal_' . $taxCode;
        
        // Check cache first
        $cachedResponse = $this->cacheManager->getCachedAPIResponse($cacheKey);
        if ($cachedResponse) {
            logInfo("Using cached official portal response for tax code: " . $taxCode);
            return $this->parseOfficialPortalResponse($cachedResponse, $taxCode);
        }
        
        $data = [];
        
        // Note: The official portal search functionality appears to have issues
        // This is a placeholder for when the portal is accessible
        $searchUrl = "https://dangkykinhdoanh.gov.vn/en/Pages/default.aspx";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $searchUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIEJAR => tempnam(sys_get_temp_dir(), 'cookies'),
            CURLOPT_COOKIEFILE => tempnam(sys_get_temp_dir(), 'cookies')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            // Cache the API response
            $this->cacheManager->cacheAPIResponse($cacheKey, $response, [
                'source' => 'official_portal',
                'tax_code' => $taxCode,
                'http_code' => $httpCode
            ]);
            
            // Parse the response and extract business information
            $data = $this->parseOfficialPortalResponse($response, $taxCode);
        }
        
        return $data;
    }
    
    /**
     * Collect data using web search engines
     */
    private function collectFromWebSearch($taxCode) {
        $cacheKey = 'web_search_' . $taxCode;
        
        // Check cache first
        $cachedResults = $this->cacheManager->getCachedAPIResponse($cacheKey);
        if ($cachedResults) {
            logInfo("Using cached web search results for tax code: " . $taxCode);
            return $this->extractBusinessInfoFromSearchResults($cachedResults, $taxCode);
        }
        
        $data = [];
        
        // Search for the tax code on various search engines
        $searchQueries = [
            "\"$taxCode\" Vietnam company business",
            "\"$taxCode\" Vietnam enterprise registration",
            "mã số thuế \"$taxCode\" Vietnam"
        ];
        
        $allSearchResults = '';
        
        foreach ($searchQueries as $query) {
            $searchResults = $this->performWebSearch($query);
            $allSearchResults .= $searchResults . "\n\n";
            
            $extractedData = $this->extractBusinessInfoFromSearchResults($searchResults, $taxCode);
            
            if (!empty($extractedData['company_name'])) {
                $data = array_merge($data, $extractedData);
                break;
            }
        }
        
        // Cache the web search results
        $this->cacheManager->cacheAPIResponse($cacheKey, $allSearchResults, [
            'source' => 'web_search',
            'tax_code' => $taxCode,
            'queries' => $searchQueries
        ]);
        
        return $data;
    }
    
    /**
     * Collect data from business directories
     */
    private function collectFromBusinessDirectories($taxCode) {
        $cacheKey = 'business_directories_' . $taxCode;
        
        // Check cache first
        $cachedResults = $this->cacheManager->getCachedAPIResponse($cacheKey);
        if ($cachedResults) {
            logInfo("Using cached business directory results for tax code: " . $taxCode);
            return $this->extractBusinessInfoFromSearchResults($cachedResults, $taxCode);
        }
        
        $data = [];
        
        // List of business directory websites to check
        $directories = [
            'https://www.yellowpages.vn/',
            'https://www.vietnamyp.com/',
            'https://www.vietbiz.com.vn/'
        ];
        
        $allDirectoryResults = '';
        
        foreach ($directories as $directory) {
            try {
                $directoryData = $this->searchBusinessDirectory($directory, $taxCode);
                $allDirectoryResults .= $directoryData . "\n\n";
                
                $extractedData = $this->extractBusinessInfoFromSearchResults($directoryData, $taxCode);
                if (!empty($extractedData['company_name'])) {
                    $data = array_merge($data, $extractedData);
                    break;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        // Cache the business directory results
        $this->cacheManager->cacheAPIResponse($cacheKey, $allDirectoryResults, [
            'source' => 'business_directories',
            'tax_code' => $taxCode,
            'directories' => $directories
        ]);
        
        return $data;
    }
    
    /**
     * Enhance business data with additional web search
     */
    private function enhanceWithWebSearch($businessInfo) {
        $companyName = $businessInfo['company_name'];
        
        if (empty($companyName)) {
            return [];
        }
        
        $cacheKey = 'enhancement_' . md5($companyName);
        
        // Check cache first
        $cachedResults = $this->cacheManager->getCachedAPIResponse($cacheKey);
        if ($cachedResults) {
            logInfo("Using cached enhancement data for company: " . $companyName);
            return $this->extractEnhancementData($cachedResults, $companyName);
        }
        
        $enhancedData = [];
        
        // Search for additional information about the company
        $enhancementQueries = [
            "\"$companyName\" Vietnam contact phone email",
            "\"$companyName\" Vietnam website",
            "\"$companyName\" Vietnam services products",
            "\"$companyName\" Vietnam address location"
        ];
        
        $allEnhancementResults = '';
        
        foreach ($enhancementQueries as $query) {
            $searchResults = $this->performWebSearch($query);
            $allEnhancementResults .= $searchResults . "\n\n";
            
            $extractedData = $this->extractEnhancementData($searchResults, $companyName);
            $enhancedData = array_merge($enhancedData, $extractedData);
        }
        
        // Cache the enhancement results
        $this->cacheManager->cacheAPIResponse($cacheKey, $allEnhancementResults, [
            'source' => 'enhancement_search',
            'company_name' => $companyName,
            'queries' => $enhancementQueries
        ]);
        
        return $enhancedData;
    }
    
    /**
     * Perform web search using search engines
     */
    private function performWebSearch($query) {
        $cacheKey = 'web_search_' . md5($query);
        
        // Check cache first
        $cachedResponse = $this->cacheManager->getCachedAPIResponse($cacheKey);
        if ($cachedResponse) {
            logInfo("Using cached web search response for query: " . $query);
            return $cachedResponse;
        }
        
        // Use DuckDuckGo or other search engines that allow programmatic access
        $searchUrl = "https://duckduckgo.com/html/?q=" . urlencode($query);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $searchUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            // Cache the search response
            $this->cacheManager->cacheAPIResponse($cacheKey, $response, [
                'source' => 'duckduckgo',
                'query' => $query,
                'http_code' => $httpCode
            ]);
        }
        
        return $response;
    }
    
    /**
     * Parse official portal response
     */
    private function parseOfficialPortalResponse($html, $taxCode) {
        $data = [];
        
        // Create DOM document
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        // Extract business information from the HTML
        // This would need to be customized based on the actual HTML structure
        $xpath = new DOMXPath($dom);
        
        // Example extraction (would need to be adjusted based on actual HTML)
        $companyNameNodes = $xpath->query("//td[contains(@class, 'company-name')]");
        if ($companyNameNodes->length > 0) {
            $data['company_name'] = trim($companyNameNodes->item(0)->textContent);
        }
        
        return $data;
    }
    
    /**
     * Extract business info from search results
     */
    private function extractBusinessInfoFromSearchResults($html, $taxCode) {
        $data = [];
        
        if (empty($html)) {
            return $data;
        }
        
        // Parse HTML and look for business information patterns
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Look for links that might contain business information
        $links = $xpath->query("//a[@href]");
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);
            
            // Check if this looks like a business listing
            if ($this->isBusinessListing($href, $text, $taxCode)) {
                $businessData = $this->extractFromBusinessListing($href);
                if (!empty($businessData)) {
                    $data = array_merge($data, $businessData);
                    break;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Check if a link appears to be a business listing
     */
    private function isBusinessListing($href, $text, $taxCode) {
        $businessIndicators = [
            'company', 'enterprise', 'business', 'corp', 'ltd', 'inc',
            'công ty', 'doanh nghiệp', 'tập đoàn'
        ];
        
        $lowerText = strtolower($text);
        $lowerHref = strtolower($href);
        
        // Check if the text or URL contains business indicators
        foreach ($businessIndicators as $indicator) {
            if (strpos($lowerText, $indicator) !== false || 
                strpos($lowerHref, $indicator) !== false) {
                return true;
            }
        }
        
        // Check if it contains the tax code
        if (strpos($text, $taxCode) !== false || strpos($href, $taxCode) !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Extract data from a business listing page
     */
    private function extractFromBusinessListing($url) {
        $data = [];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = $this->parseBusinessListingPage($response);
        }
        
        return $data;
    }
    
    /**
     * Parse business listing page
     */
    private function parseBusinessListingPage($html) {
        $data = [];
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Extract company name
        $namePatterns = [
            "//h1", "//h2", "//h3",
            "//*[contains(@class, 'company-name')]",
            "//*[contains(@class, 'business-name')]",
            "//*[contains(@id, 'company-name')]"
        ];
        
        foreach ($namePatterns as $pattern) {
            $nodes = $xpath->query($pattern);
            if ($nodes->length > 0) {
                $name = trim($nodes->item(0)->textContent);
                if (!empty($name) && strlen($name) > 3) {
                    $data['company_name'] = $name;
                    break;
                }
            }
        }
        
        // Extract contact information
        $text = $dom->textContent;
        
        // Phone number extraction
        if (preg_match('/(?:\+84|84|0)[\s\-]?[1-9][\d\s\-]{7,10}/', $text, $matches)) {
            $data['phone'] = trim($matches[0]);
        }
        
        // Email extraction
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            $data['email'] = trim($matches[0]);
        }
        
        // Website extraction
        if (preg_match('/https?:\/\/[^\s<>"]+/', $text, $matches)) {
            $data['website'] = trim($matches[0]);
        }
        
        return $data;
    }
    
    /**
     * Search business directory
     */
    private function searchBusinessDirectory($directoryUrl, $taxCode) {
        $data = [];
        
        // This would need to be implemented based on each directory's search functionality
        // For now, return empty data
        
        return $data;
    }
    
    /**
     * Extract enhancement data from search results
     */
    private function extractEnhancementData($html, $companyName) {
        $data = [];
        
        // Similar to extractBusinessInfoFromSearchResults but focused on enhancement
        // Implementation would extract additional details like services, industry, etc.
        
        return $data;
    }
    
    /**
     * Validate and clean business data
     */
    public function validateAndCleanData($data) {
        $cleanData = $data;
        
        // Clean company name
        if (!empty($cleanData['company_name'])) {
            $cleanData['company_name'] = $this->cleanCompanyName($cleanData['company_name']);
        }
        
        // Clean phone number
        if (!empty($cleanData['phone'])) {
            $cleanData['phone'] = $this->cleanPhoneNumber($cleanData['phone']);
        }
        
        // Clean email
        if (!empty($cleanData['email'])) {
            $cleanData['email'] = filter_var($cleanData['email'], FILTER_SANITIZE_EMAIL);
        }
        
        // Clean website URL
        if (!empty($cleanData['website'])) {
            $cleanData['website'] = filter_var($cleanData['website'], FILTER_SANITIZE_URL);
        }
        
        return $cleanData;
    }
    
    /**
     * Clean company name
     */
    private function cleanCompanyName($name) {
        // Remove extra whitespace
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        // Remove common prefixes/suffixes that might be artifacts
        $name = preg_replace('/^(Company:|Business:|Enterprise:)/i', '', $name);
        
        return $name;
    }
    
    /**
     * Clean phone number
     */
    private function cleanPhoneNumber($phone) {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Standardize Vietnam phone format
        if (preg_match('/^84/', $phone)) {
            $phone = '+' . $phone;
        } elseif (preg_match('/^0/', $phone)) {
            $phone = '+84' . substr($phone, 1);
        }
        
        return $phone;
    }
}

// Usage example
/*
$collector = new VietnamBusinessCollector();
$businessInfo = $collector->collectBusinessInfo('0123456789');
$cleanData = $collector->validateAndCleanData($businessInfo);

print_r($cleanData);
*/
?>

