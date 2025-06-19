# Debugging Business Data Collection

## Overview

The business data collection process can sometimes get stuck or take a long time. This guide helps you identify exactly where the process is hanging and what's causing the issue.

## Enhanced Debug Features

### 1. Debug Popup Panel

The debug popup now includes a new "Progress Tracking" section that shows:

- **Current Step**: Which step is currently running (business, content, wordpress, images)
- **Progress Percentage**: How far along the current step is (0-100%)
- **Status Message**: Detailed description of what's happening
- **Step Details**: Specific information about the current step
- **API Sources**: Status of each data source being tried
- **Recent Logs**: Latest log entries related to the session
- **Cache Status**: Whether data is being retrieved from cache or external sources

### 2. How to Access Debug Information

1. **Open Debug Panel**: Click the bug icon (🐛) in the bottom-right corner
2. **Check Progress Tracking**: Look at the "Progress Tracking" section
3. **Monitor Real-time**: The panel updates automatically as progress is made

### 3. Understanding Business Step Debug Info

When stuck at "Collecting Business Data", the debug panel will show:

```
Step: business (15%)
Status: Trying Official Vietnam Business Portal...
Step Details: Collecting business information from various sources
Cache Status: miss
API Sources: official_portal: not_tried, web_search: not_tried, business_directories: not_tried
```

### 4. Common Issues and Solutions

#### Issue: Stuck at "Trying Official Vietnam Business Portal..."
**Cause**: The official portal might be slow or unreachable
**Solution**: 
- Check your internet connection
- The system will automatically try other sources after a timeout
- Look for error messages in the debug panel

#### Issue: Stuck at "Trying Web Search Engines..."
**Cause**: Web search might be blocked or rate-limited
**Solution**:
- Check if your server can access external websites
- Look for timeout errors in the debug panel
- The system will try business directories next

#### Issue: Stuck at "Trying Business Directories..."
**Cause**: Business directory websites might be slow
**Solution**:
- Check the debug panel for specific error messages
- The system will try web search enhancement as a fallback

#### Issue: Stuck at "No data found from primary sources, trying web search enhancement..."
**Cause**: All primary sources failed, trying fallback method
**Solution**:
- This is normal behavior when primary sources don't have data
- The enhancement process might take a few minutes
- Check for any error messages in the debug panel

### 5. Command Line Testing

You can test the business data collection process directly using the test script:

```bash
php php/test_business_collection.php
```

This script will:
- Test each data source individually
- Show timing information for each step
- Display detailed error messages
- Help identify which source is causing delays

### 6. Manual Debugging Steps

1. **Check the Debug Panel**: Look for error messages and timing information
2. **Run the Test Script**: Use `test_business_collection.php` to isolate the issue
3. **Check Server Logs**: Look at PHP error logs for additional details
4. **Monitor Network**: Check if your server can access external websites
5. **Check Cache**: Verify if cached data is being used correctly

### 7. Performance Optimization

If the process is consistently slow:

1. **Enable Caching**: Make sure the cache system is working properly
2. **Check Timeouts**: Verify that timeout settings are appropriate
3. **Monitor Resources**: Ensure the server has adequate resources
4. **Use Real Tax Codes**: Test with actual Vietnamese business tax codes

### 8. Debug Panel Features

The debug panel now shows:

- **Real-time Progress**: Live updates as the process runs
- **Step-by-step Details**: Information about each substep
- **Error Tracking**: Detailed error messages and stack traces
- **Cache Information**: Whether data is coming from cache or external sources
- **API Response Status**: Status of each external API call
- **Timing Information**: How long each step takes

### 9. Troubleshooting Checklist

- [ ] Debug panel is open and showing progress
- [ ] No error messages in the debug panel
- [ ] Server can access external websites
- [ ] Cache system is working properly
- [ ] Test script runs without errors
- [ ] PHP error logs are clean
- [ ] Server has adequate resources

### 10. Getting Help

If you're still having issues:

1. **Collect Debug Information**: Take screenshots of the debug panel
2. **Run Test Script**: Include the output of `test_business_collection.php`
3. **Check Logs**: Include relevant PHP error logs
4. **Describe the Issue**: Explain exactly where the process gets stuck

The enhanced debugging system should help you quickly identify and resolve any issues with the business data collection process. 