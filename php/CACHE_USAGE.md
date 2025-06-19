# Cache System Documentation

## Overview

The WordPress Website Generator now includes a comprehensive caching system that stores all AI responses, external API content, and images in the `./cache` directory. This system is designed for debugging, resuming interrupted processes, and improving performance.

## Cache Structure

The cache is organized into the following directories:

```
php/cache/
├── ai/           # AI responses (sitemaps, page content, blog articles)
├── api/          # External API responses (business data, web searches)
├── images/       # Image search results and downloaded images
├── business/     # Business data collection results
└── sessions/     # Complete website generation sessions
```

## Cache Configuration

Cache settings are defined in `config.php`:

```php
// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_TYPE', 'file'); // 'file', 'redis', 'memcached'
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_DEFAULT_TTL', 3600); // seconds (1 hour)
define('IMAGE_CACHE_DURATION', 86400); // seconds (24 hours)
```

## What Gets Cached

### 1. AI Responses (`ai/`)
- **Sitemap generation**: Website structure and navigation
- **Page content**: Individual page content for each page
- **Blog articles**: Complete blog post content
- **Blog topics**: Generated blog topic ideas

**Cache Key Format**: `{type}_{hash_of_input_data}`

### 2. External API Responses (`api/`)
- **Business portal responses**: Official Vietnam business portal data
- **Web search results**: Search engine results for business information
- **Business directory data**: Directory website responses
- **Enhancement searches**: Additional company information searches

**Cache Key Format**: `{source}_{identifier}`

### 3. Image Search Results (`images/`)
- **Unsplash search results**: Image search from Unsplash API
- **Pexels search results**: Image search from Pexels API
- **Pixabay search results**: Image search from Pixabay API
- **Downloaded images**: Local paths to downloaded images

**Cache Key Format**: `search_{hash_of_query_and_source}` or `image_{hash_of_url}`

### 4. Business Data (`business/`)
- **Complete business information**: All collected business data by tax code
- **Source tracking**: Which source provided the data

**Cache Key Format**: `{tax_code}`

### 5. Website Sessions (`sessions/`)
- **Initial session data**: Session parameters and settings
- **Content generation results**: Complete AI-generated content
- **WordPress creation results**: WordPress integration results
- **Image assignment results**: Image processing results
- **Final results**: Complete website generation summary

**Cache Key Format**: `{session_id}` or `{session_id}_{type}`

## Cache Management API

### Main Process Endpoints

#### Get Cache Statistics
```http
GET /php/process.php?action=get_cache_stats
```

#### Clear Cache
```http
GET /php/process.php?action=clear_cache&type=ai
```

#### Get Cached Session
```http
GET /php/process.php?action=get_cached_session&session_id=ws_123456
```

### Cache Utilities Endpoints

#### Get Detailed Statistics
```http
GET /php/cache_utils.php?action=stats
```

#### List Cache Entries
```http
GET /php/cache_utils.php?action=list&type=ai&limit=20
```

#### View Specific Cache Entry
```http
GET /php/cache_utils.php?action=view&key=1234567890&type=business
```

#### Search Cache
```http
GET /php/cache_utils.php?action=search&query=company&type=api
```

#### Export Cache
```http
GET /php/cache_utils.php?action=export&type=ai&format=json
```

#### Cleanup Expired Entries
```http
GET /php/cache_utils.php?action=cleanup
```

## Debugging with Cache

### 1. Resume Interrupted Generation

If a website generation process is interrupted, you can resume it using cached data:

```php
// Get the cached session data
$sessionData = $cacheManager->getCachedWebsiteSession($sessionId);

// Get cached content
$content = $cacheManager->getCachedWebsiteSession($sessionId . '_content');

// Get cached WordPress results
$wpResults = $cacheManager->getCachedWebsiteSession($sessionId . '_wordpress');
```

### 2. Debug AI Responses

To debug AI content generation issues:

```php
// Check cached sitemap
$sitemapKey = 'sitemap_' . md5(json_encode($businessInfo));
$cachedSitemap = $cacheManager->getCachedAIResponse($sitemapKey);

// Check cached page content
$pageKey = 'page_content_' . md5(json_encode($businessInfo) . json_encode($pageInfo) . $colorPalette);
$cachedPage = $cacheManager->getCachedAIResponse($pageKey);
```

### 3. Debug External API Issues

To debug business data collection:

```php
// Check cached business data
$businessData = $cacheManager->getCachedBusinessData($taxCode);

// Check cached API responses
$apiKey = 'official_portal_' . $taxCode;
$apiResponse = $cacheManager->getCachedAPIResponse($apiKey);
```

### 4. Debug Image Issues

To debug image search and download issues:

```php
// Check cached image search results
$searchResults = $cacheManager->getCachedImageSearch($query, 'unsplash');

// Check cached image files
$imagePath = $cacheManager->getCachedImage($imageUrl);
```

## Cache File Format

Cache files are stored as serialized PHP data with the following structure:

```php
[
    'data' => [
        'response' => $actual_data,
        'metadata' => [
            'cached_at' => '2024-01-01 12:00:00',
            'ttl' => 3600,
            'type' => 'ai_response',
            // ... additional metadata
        ]
    ],
    'expires_at' => 1704110400 // Unix timestamp
]
```

## Cache Maintenance

### Automatic Cleanup

The cache system automatically:
- Checks expiration times before serving cached data
- Removes expired entries during cleanup operations
- Creates necessary directories on initialization

### Manual Maintenance

Use the cache utilities for manual maintenance:

```bash
# Get cache statistics
curl "http://localhost/php/cache_utils.php?action=stats"

# List all AI cache entries
curl "http://localhost/php/cache_utils.php?action=list&type=ai"

# Clear all cache
curl "http://localhost/php/cache_utils.php?action=clear"

# Cleanup expired entries
curl "http://localhost/php/cache_utils.php?action=cleanup"
```

## Performance Benefits

1. **Faster Development**: No need to regenerate AI content during development
2. **Reduced API Costs**: Avoid repeated API calls for the same data
3. **Faster Testing**: Use cached data for testing different scenarios
4. **Resume Capability**: Continue interrupted processes from where they left off

## Security Considerations

1. **Cache files contain sensitive data**: Ensure proper file permissions
2. **API keys in metadata**: Be careful when sharing cache files
3. **Business data privacy**: Consider data retention policies
4. **Cache directory access**: Restrict web access to cache directory

## Troubleshooting

### Common Issues

1. **Cache not working**: Check `CACHE_ENABLED` setting
2. **Permission errors**: Ensure cache directory is writable
3. **Expired cache**: Check TTL settings
4. **Large cache size**: Use cleanup utilities regularly

### Debug Commands

```bash
# Check cache directory structure
ls -la php/cache/

# Check cache file permissions
find php/cache/ -type f -exec ls -la {} \;

# Check cache file sizes
du -sh php/cache/*/

# View cache file content (be careful with large files)
head -c 1000 php/cache/ai/some_file.cache
```

## Integration with Existing Code

The caching system is automatically integrated into:

- `AIContentGenerator.php`: Caches all AI responses
- `ImageManager.php`: Caches image searches and downloads
- `VietnamBusinessCollector.php`: Caches business data collection
- `process.php`: Caches session data and provides cache management endpoints
- `background_processor.php`: Caches generation results

No changes to existing code are required - caching happens transparently. 