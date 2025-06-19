# Custom OpenAI Endpoint Configuration

This document explains how to configure and use a custom OpenAI endpoint with the WordPress Website Generator.

## Overview

The application now supports custom OpenAI API endpoints through the `openai-php/client` library. This allows you to use proxy services or alternative OpenAI-compatible APIs.

## Configuration

### 1. Update config.php

In your `config.php` file, set the following values:

```php
// OpenAI API Configuration
define('OPENAI_API_KEY', 'your-api-key-here');
define('OPENAI_API_ENDPOINT', 'https://your-proxy-endpoint.com/v1');
define('OPENAI_MODEL', 'gpt-4o-mini'); // or your preferred model
```

### 2. Endpoint Format

The endpoint should be in the format:
- `https://your-proxy-domain.com/v1` (without trailing slash)
- The `/v1` path is required for OpenAI-compatible APIs

## Usage

### Basic Usage

```php
require_once 'config.php';
require_once 'AIContentGenerator.php';

// Initialize with custom endpoint
$generator = new AIContentGenerator(OPENAI_API_KEY, OPENAI_API_ENDPOINT);

// Use as normal
$content = $generator->generateWebsiteContent($businessInfo);
```

### Testing the Configuration

Run the test script to verify your setup:

```bash
php test_custom_endpoint.php
```

This will:
1. Test the AIContentGenerator initialization
2. Test the API connection
3. Test content generation with sample data

## Updated Files

The following files have been updated to support custom endpoints:

1. **AIContentGenerator.php**
   - Modified constructor to accept optional endpoint parameter
   - Added `withBaseUri()` configuration for custom endpoints
   - Added `testConnection()` method for debugging

2. **All instantiation files updated:**
   - `process.php`
   - `background_processor.php`
   - `test_website_generation.php`
   - `debug.php`

3. **New test file:**
   - `test_custom_endpoint.php` - Comprehensive endpoint testing

## How It Works

The `openai-php/client` library uses Guzzle HTTP client and supports custom base URIs through the Factory configuration:

```php
$factory = (new Factory())
    ->withApiKey($apiKey)
    ->withHttpClient($httpClient)
    ->withBaseUri($endpoint); // Custom endpoint

$this->client = $factory->make();
```

## Troubleshooting

### Common Issues

1. **SSL Certificate Issues**
   - The code already disables SSL verification for development
   - For production, ensure your proxy has valid SSL certificates

2. **Timeout Issues**
   - Increase timeout in the HTTP client configuration if needed
   - Current timeout is set to 30 seconds

3. **API Key Issues**
   - Ensure your API key is valid for the custom endpoint
   - Some proxy services may require different key formats

### Debug Mode

Enable debug mode in `config.php`:

```php
define('APP_DEBUG', true);
```

This will show detailed error messages and stack traces.

## Example Proxy Services

Some popular OpenAI proxy services that work with this configuration:

- **OpenRouter**: `https://openrouter.ai/api/v1`
- **Together AI**: `https://api.together.xyz/v1`
- **Anthropic Claude**: `https://api.anthropic.com/v1` (with appropriate model names)

## Security Notes

1. **API Key Security**
   - Never commit API keys to version control
   - Use environment variables or secure configuration files
   - Consider using a secrets management service

2. **Endpoint Security**
   - Ensure your proxy endpoint uses HTTPS
   - Verify the proxy service's security practices
   - Monitor API usage and costs

## Migration from Standard OpenAI

If you're migrating from standard OpenAI to a custom endpoint:

1. Update your `config.php` with the new endpoint
2. Test the connection using `test_custom_endpoint.php`
3. Verify that all existing functionality works
4. Monitor API responses and error rates

## Support

For issues with custom endpoints:

1. Check the test script output for specific error messages
2. Verify your endpoint URL format
3. Test with a simple API call first
4. Check the proxy service's documentation for compatibility 