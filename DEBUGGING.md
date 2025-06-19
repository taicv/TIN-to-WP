# Debugging Guide for WordPress Website Generator

## Issue: "An error occurred during website generation: undefined"

This error occurs when the error object doesn't have a proper `message` property. I've implemented several improvements to help debug this issue.

## Quick Debugging Steps

### 1. Run the Debug Script

First, run the debug script to check your environment:

```bash
php php/debug.php
```

This will check:
- PHP configuration
- Required extensions
- Database connection
- API keys
- File permissions
- Required files

### 2. Run the Test Script

Test the website generation process step by step:

```bash
php php/test_website_generation.php
```

This will test each component individually to identify where the error occurs.

### 3. Check Browser Console

Open your browser's developer tools (F12) and check the Console tab for detailed error information. The improved error handling will now show:

- API response details
- WebSocket connection status
- Progress polling errors
- Detailed error messages

### 4. Check Server Logs

Look for errors in:
- PHP error logs
- Web server logs (Apache/Nginx)
- Application logs in `php/logs/` directory

## Common Issues and Solutions

### 1. Missing Background Processor

**Issue**: The `background_processor.php` file was missing.

**Solution**: ✅ Fixed - The file has been created.

### 2. Database Connection Issues

**Symptoms**: Database connection errors in logs.

**Solutions**:
- Check database credentials in `php/config.php`
- Ensure database server is running
- Verify database and tables exist

### 3. Missing API Keys

**Symptoms**: AI content generation fails.

**Solutions**:
- Add OpenAI API key to `php/config.php`
- Add image service API keys (Unsplash, Pexels, Pixabay)

### 4. File Permission Issues

**Symptoms**: Cannot write to logs or cache directories.

**Solutions**:
- Ensure `php/logs/`, `php/cache/`, `php/uploads/` are writable
- Set permissions: `chmod 755 php/logs/ php/cache/ php/uploads/`

### 5. WebSocket Connection Issues

**Symptoms**: Real-time updates don't work.

**Solutions**:
- Check if WebSocket server is running
- Verify WebSocket URL configuration
- Check firewall settings

## Improved Error Handling

I've enhanced error handling in several areas:

### Frontend (JavaScript)

1. **Better WebSocket Error Handling**:
   - Handles different error types
   - Provides detailed error information
   - Graceful fallback to polling

2. **Enhanced API Error Handling**:
   - Detailed logging of API responses
   - Better error message extraction
   - Network error detection

3. **Progress Polling Improvements**:
   - Error counting to prevent spam
   - Better error messages
   - Graceful degradation

### Backend (PHP)

1. **Background Processor**:
   - Comprehensive error handling
   - Progress tracking
   - Detailed logging

2. **Process.php**:
   - Better error responses
   - Validation improvements
   - Session management

## Debugging Commands

### Check PHP Configuration
```bash
php -m | grep -E "(curl|json|pdo|openssl)"
```

### Test Database Connection
```bash
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;dbname=your_db', 'user', 'pass');
    echo 'Database connection successful\n';
} catch (PDOException \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
}
"
```

### Test API Keys
```bash
curl -H "Authorization: Bearer YOUR_OPENAI_API_KEY" \
     https://api.openai.com/v1/models
```

## Log Files to Monitor

1. **PHP Error Log**: Check your web server's PHP error log
2. **Application Log**: `php/logs/` directory
3. **Browser Console**: Network tab and Console tab
4. **WebSocket Logs**: Check WebSocket server logs if using external server

## Next Steps

1. Run the debug script and fix any issues it identifies
2. Run the test script to verify each component works
3. Check browser console for detailed error information
4. Monitor logs during website generation
5. If issues persist, check the specific error messages in the logs

## Support

If you continue to experience issues:

1. Run both debug scripts and share the output
2. Check browser console and share any error messages
3. Check server logs and share relevant error messages
4. Provide the specific steps that trigger the error

The improved error handling should now provide much more detailed information about what's going wrong during the website generation process. 