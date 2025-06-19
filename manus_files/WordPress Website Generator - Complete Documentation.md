# WordPress Website Generator - Complete Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [System Requirements](#system-requirements)
3. [Installation Guide](#installation-guide)
4. [Configuration](#configuration)
5. [Usage Instructions](#usage-instructions)
6. [API Documentation](#api-documentation)
7. [Troubleshooting](#troubleshooting)
8. [Development Guide](#development-guide)
9. [Security Considerations](#security-considerations)
10. [Performance Optimization](#performance-optimization)
11. [Maintenance and Updates](#maintenance-and-updates)
12. [Support and Resources](#support-and-resources)

## Introduction

The WordPress Website Generator is a comprehensive PHP application designed to automatically create professional WordPress websites for Vietnamese businesses. By simply entering a Vietnam Business License number (Tax Code) and selecting design preferences, users can generate a complete website with AI-powered content, professional images, and optimized structure.

### Key Features

The application provides an extensive range of features that streamline the website creation process. The core functionality revolves around automated data collection, where the system retrieves business information from Vietnam's official business registries using the provided tax code. This information forms the foundation for all subsequent content generation and website customization.

The AI-powered content generation system leverages OpenAI's advanced language models to create contextually relevant and professionally written content. This includes generating comprehensive sitemaps that reflect the business's industry and services, creating detailed page content that accurately represents the company's offerings, and producing engaging blog articles that can help with search engine optimization and customer engagement.

The image management system automatically searches and integrates relevant, high-quality images from multiple free stock photo APIs including Unsplash, Pexels, and Pixabay. The system intelligently selects images that match the business type, content themes, and overall aesthetic preferences specified by the user.

The WordPress integration component provides seamless connectivity with existing WordPress installations through both REST API and XML-RPC protocols. This ensures compatibility with a wide range of WordPress versions and hosting environments. The system can create pages, posts, menus, and media uploads while maintaining proper WordPress standards and best practices.

The user interface features a modern, responsive design that works seamlessly across desktop, tablet, and mobile devices. The multi-step form guides users through the process with clear instructions and real-time validation, while the progress tracking system provides transparent updates on the website generation process.

### Business Benefits

Organizations implementing this solution can expect significant time and cost savings in website development. Traditional website creation for small to medium businesses can take weeks or months and cost thousands of dollars. This automated solution reduces the timeline to minutes while maintaining professional quality standards.

The system eliminates the need for technical expertise in web development, content creation, or design. Business owners can focus on their core operations while the system handles the complexities of modern website development. The AI-generated content is optimized for search engines and follows current best practices for user engagement and conversion optimization.

The automated approach ensures consistency in quality and structure across all generated websites. This is particularly valuable for agencies or organizations that need to create multiple websites with similar requirements. The system maintains brand consistency while allowing for customization based on individual business needs.

### Technical Architecture

The application follows a modular architecture that separates concerns and enables easy maintenance and expansion. The frontend utilizes modern web technologies including HTML5, CSS3, and vanilla JavaScript to ensure broad compatibility and optimal performance. The responsive design framework ensures consistent user experience across all device types.

The backend PHP components are organized into distinct modules, each handling specific aspects of the website generation process. The business data collection module manages API integrations and web scraping functionality. The AI integration module handles communication with OpenAI services and content generation workflows. The WordPress integration module manages all interactions with target WordPress installations.

The system implements robust error handling and logging mechanisms to ensure reliable operation and facilitate troubleshooting. Database integration supports both MySQL and PostgreSQL for storing session data, progress tracking, and generated content. The modular design allows for easy integration of additional data sources, AI services, or content management systems.



## System Requirements

### Server Requirements

The WordPress Website Generator requires a modern web server environment with specific software components and configurations. The server infrastructure must support both the application's processing requirements and the external API integrations necessary for data collection and content generation.

**Operating System**: The application is designed to run on Linux-based systems, with Ubuntu 20.04 LTS or newer being the recommended platform. CentOS 8, Red Hat Enterprise Linux 8, or Debian 10 are also supported. Windows Server environments can be used but may require additional configuration for optimal performance.

**Web Server**: Apache HTTP Server 2.4 or newer is the primary supported web server, with mod_rewrite enabled for URL rewriting functionality. Nginx 1.18 or newer can also be used with appropriate PHP-FPM configuration. The web server must support HTTPS connections for secure API communications and should be configured with appropriate security headers.

**PHP Requirements**: PHP 8.0 or newer is required, with PHP 8.1 being the recommended version for optimal performance and security. The following PHP extensions must be installed and enabled: curl for API communications, json for data processing, mbstring for multi-byte string handling, openssl for secure connections, pdo_mysql or pdo_pgsql for database connectivity, gd or imagick for image processing, zip for file compression, and xml for XML parsing.

**Database**: MySQL 8.0 or newer, or PostgreSQL 12 or newer. The database server should be configured with appropriate character set support (utf8mb4 for MySQL) and sufficient memory allocation for handling concurrent sessions and content storage.

**Memory and Storage**: Minimum 2GB RAM with 4GB recommended for optimal performance. At least 10GB of available disk space for application files, temporary content storage, and image caching. SSD storage is recommended for improved I/O performance during content generation processes.

### Client Requirements

**Web Browser**: Modern web browsers with JavaScript enabled are required for the user interface. Supported browsers include Chrome 90+, Firefox 88+, Safari 14+, and Edge 90+. Internet Explorer is not supported due to its lack of modern JavaScript features.

**Internet Connection**: Stable internet connection with minimum 10 Mbps bandwidth for optimal performance during website generation. The application makes multiple API calls to external services, requiring reliable connectivity throughout the process.

**Screen Resolution**: Minimum 1024x768 resolution, with 1920x1080 or higher recommended for the best user experience. The responsive design adapts to various screen sizes, but larger displays provide better visibility of progress tracking and results.

### External Service Requirements

**OpenAI API Access**: Valid OpenAI API key with sufficient credits for content generation. The application uses GPT-3.5-turbo or GPT-4 models depending on configuration. Estimated API costs range from $0.50 to $2.00 per website generation, depending on content complexity and model selection.

**Image API Access**: API keys for at least one of the supported image services (Unsplash, Pexels, or Pixabay). Free tier access is sufficient for most use cases, but paid plans may be required for high-volume usage or commercial applications.

**WordPress Target Site**: The target WordPress installation must have REST API enabled (default in WordPress 4.7+) or XML-RPC enabled for older versions. Application passwords or authentication tokens must be configured for secure API access. The WordPress site should have appropriate themes and plugins installed to support the generated content structure.

## Installation Guide

### Pre-Installation Preparation

Before beginning the installation process, ensure that all system requirements are met and that you have administrative access to the target server. Create a dedicated directory for the application, typically within the web server's document root or a subdirectory configured for web access.

Download or clone the application files to the target directory. If using version control, ensure that the .git directory and other development files are excluded from the production environment. Set appropriate file permissions, with web-accessible files having 644 permissions and directories having 755 permissions. PHP files should be owned by the web server user (typically www-data on Ubuntu systems).

Create a dedicated database for the application using your preferred database management tool. Record the database name, username, password, and host information for use during configuration. If using MySQL, ensure that the character set is configured as utf8mb4 to support international characters and emoji.

### Step-by-Step Installation

**Step 1: File Deployment**
Extract or copy all application files to the target directory on your web server. The directory structure should include the main index.html file, css and js directories for frontend assets, php directory for backend components, and assets directory for images and other media files.

Verify that all files are present and accessible by the web server. Test basic connectivity by accessing the index.html file through a web browser. You should see the application's landing page, though functionality will not be available until configuration is complete.

**Step 2: Database Setup**
Connect to your database server using the administrative credentials and create a new database for the application. Execute the provided SQL schema file to create the necessary tables for session management, progress tracking, and content storage.

The database schema includes tables for user sessions, generation progress, business data cache, generated content, and error logs. Each table is designed with appropriate indexes for optimal query performance and includes foreign key constraints to maintain data integrity.

**Step 3: PHP Configuration**
Copy the provided config.php.example file to config.php and edit it with your specific configuration values. This includes database connection parameters, API keys for external services, WordPress default settings, and application-specific options such as content generation parameters and image processing settings.

Ensure that all required PHP extensions are installed and enabled. You can verify this by creating a temporary PHP file with phpinfo() function and accessing it through your web browser. Remove this file after verification for security purposes.

**Step 4: Web Server Configuration**
Configure your web server to properly handle the application's URL structure and security requirements. For Apache servers, ensure that the provided .htaccess file is in place and that mod_rewrite is enabled. For Nginx servers, configure the appropriate location blocks and rewrite rules.

Set up SSL/TLS certificates for secure communication, especially important for API communications and user data protection. Configure appropriate security headers including Content Security Policy, X-Frame-Options, and X-Content-Type-Options.

**Step 5: API Integration Setup**
Register for API keys with the required external services. For OpenAI, create an account and generate an API key with appropriate usage limits. For image services, register with Unsplash, Pexels, and/or Pixabay to obtain API keys.

Test each API integration using the provided test scripts or the application's built-in connection testing functionality. Verify that API calls are successful and that rate limiting is properly configured to avoid service interruptions.

**Step 6: Permissions and Security**
Configure file and directory permissions according to security best practices. Web-accessible files should be readable by the web server but not writable. Configuration files containing sensitive information should be protected from web access.

Set up appropriate firewall rules to restrict access to administrative functions and database connections. Configure fail2ban or similar intrusion prevention systems to protect against brute force attacks and automated scanning.

### Post-Installation Verification

After completing the installation steps, perform comprehensive testing to ensure all components are functioning correctly. Access the application through a web browser and verify that the user interface loads properly with all styling and interactive elements working as expected.

Test the form validation by entering various types of data and verifying that appropriate error messages are displayed for invalid inputs. Test the API connections by using the built-in testing functionality or by initiating a test website generation process with sample data.

Monitor the application logs during testing to identify any errors or warnings that may indicate configuration issues. Pay particular attention to API communication logs, database connection logs, and PHP error logs.

Verify that the generated content meets quality standards by reviewing sample outputs from the AI content generation system. Test the WordPress integration by connecting to a test WordPress installation and verifying that pages, posts, and media are created correctly.

## Configuration

### Database Configuration

The database configuration is managed through the config.php file and requires careful attention to connection parameters, character encoding, and performance settings. The application supports both MySQL and PostgreSQL databases, with specific configuration options for each.

For MySQL configurations, set the charset parameter to 'utf8mb4' to ensure proper handling of international characters and emoji. Configure the collation as 'utf8mb4_unicode_ci' for accurate sorting and comparison operations. Set appropriate connection timeout values to handle long-running content generation processes without database disconnections.

The database connection pool should be configured to handle concurrent user sessions effectively. For high-traffic installations, consider implementing connection pooling through tools like PgBouncer for PostgreSQL or ProxySQL for MySQL. Monitor connection usage and adjust pool sizes based on actual usage patterns.

Database performance can be optimized through proper indexing strategies and query optimization. The provided schema includes recommended indexes, but additional indexes may be beneficial based on specific usage patterns and query analysis. Regular database maintenance including statistics updates and index rebuilding should be scheduled for optimal performance.

### API Configuration

The API configuration section manages connections to external services including OpenAI for content generation, image services for media collection, and various data sources for business information retrieval. Each API integration requires specific configuration parameters and error handling strategies.

**OpenAI Configuration**: Set your OpenAI API key in the configuration file, ensuring that it has appropriate permissions and usage limits. Configure the model selection (GPT-3.5-turbo or GPT-4) based on your quality requirements and budget constraints. Set appropriate timeout values for API calls, considering that content generation can take several seconds per request.

Configure rate limiting parameters to comply with OpenAI's usage policies and to prevent service interruptions. Implement exponential backoff strategies for handling temporary API failures or rate limit exceeded responses. Set up monitoring and alerting for API usage to track costs and identify potential issues.

**Image API Configuration**: Configure API keys for Unsplash, Pexels, and Pixabay services. Set up fallback mechanisms so that if one service is unavailable, the system can automatically switch to alternative providers. Configure image quality and size preferences to balance visual appeal with loading performance.

Implement caching strategies for image metadata to reduce API calls and improve response times. Configure local image storage and processing parameters, including compression settings and format conversion options. Set up cleanup processes to manage disk space usage for cached images.

**Business Data APIs**: Configure connections to Vietnam business registry services and other data sources used for business information collection. Set up appropriate authentication mechanisms and error handling for cases where business information is not available or incomplete.

### WordPress Integration Configuration

The WordPress integration configuration manages how the application connects to and interacts with target WordPress installations. This includes authentication methods, content creation parameters, and error handling strategies.

Configure default WordPress connection settings including preferred authentication methods (application passwords vs. traditional authentication), default user roles for content creation, and timeout values for API operations. Set up template configurations for different types of content including page layouts, post formats, and menu structures.

Implement content validation and sanitization rules to ensure that generated content meets WordPress standards and security requirements. Configure media upload settings including file size limits, allowed file types, and storage locations. Set up backup and rollback mechanisms for cases where content creation fails or produces unexpected results.

Configure SEO optimization settings including meta tag generation, URL structure preferences, and sitemap integration. Set up analytics integration for tracking website performance and user engagement metrics. Configure caching and performance optimization settings to ensure that generated websites load quickly and efficiently.

### Security Configuration

Security configuration encompasses multiple layers of protection including data encryption, access control, input validation, and audit logging. Proper security configuration is essential for protecting user data and preventing unauthorized access to the application and connected services.

Configure encryption settings for sensitive data including API keys, database passwords, and user session information. Use strong encryption algorithms and regularly rotate encryption keys. Implement secure session management with appropriate timeout values and session invalidation mechanisms.

Set up input validation and sanitization rules for all user inputs, including form data, file uploads, and API parameters. Configure Content Security Policy headers to prevent cross-site scripting attacks and other injection vulnerabilities. Implement rate limiting and abuse prevention mechanisms to protect against automated attacks.

Configure audit logging to track all significant actions including user logins, content generation requests, API calls, and administrative changes. Set up log rotation and retention policies to manage disk space while maintaining adequate audit trails. Configure alerting mechanisms for security events and suspicious activities.

### Performance Configuration

Performance configuration involves optimizing various aspects of the application to ensure fast response times and efficient resource utilization. This includes caching strategies, database optimization, and resource management.

Configure caching mechanisms for frequently accessed data including business information, generated content templates, and image metadata. Implement both in-memory caching using tools like Redis or Memcached and file-based caching for larger data sets. Set appropriate cache expiration times based on data volatility and update frequency.

Configure database connection pooling and query optimization settings to handle concurrent users efficiently. Implement database query caching and result set caching where appropriate. Monitor database performance metrics and adjust configuration parameters based on actual usage patterns.

Set up content delivery network (CDN) integration for serving static assets including CSS files, JavaScript libraries, and cached images. Configure compression settings for web server responses to reduce bandwidth usage and improve loading times. Implement lazy loading and progressive enhancement techniques for optimal user experience.

Configure resource monitoring and alerting to track system performance metrics including CPU usage, memory consumption, disk I/O, and network bandwidth. Set up automated scaling mechanisms for cloud deployments to handle varying load levels efficiently.


## Usage Instructions

### Getting Started

The WordPress Website Generator provides an intuitive interface that guides users through the website creation process. The application is designed to be accessible to users without technical expertise while providing powerful automation capabilities for professional website development.

**Initial Setup and Access**: Users begin by accessing the application through their web browser. The landing page presents a clean, professional interface with clear instructions and a prominent call-to-action button. The responsive design ensures optimal viewing across desktop, tablet, and mobile devices, allowing users to initiate website generation from any device.

The application employs a progressive disclosure approach, presenting information and options in a logical sequence that prevents user overwhelm while ensuring all necessary data is collected. The multi-step form design breaks the process into manageable segments, each with specific validation and feedback mechanisms.

**Step 1: Business Information Collection**: The first step requires users to enter their Vietnam Business License number, also known as the Tax Code. This 10-digit identifier serves as the primary key for retrieving comprehensive business information from official Vietnamese government databases and business registries.

The system provides real-time validation of the tax code format, ensuring that users enter a properly formatted 10-digit number. Upon entering a valid tax code, the application can optionally provide a preview of the business information that will be retrieved, allowing users to verify that the correct business entity has been identified.

The business data collection process operates in the background, accessing multiple data sources to compile comprehensive information about the company. This includes basic registration details such as company name, registered address, and contact information, as well as more detailed business intelligence such as industry classification, business activities, and operational status.

**Step 2: Design Preferences Selection**: The second step focuses on aesthetic and functional preferences that will guide the website's visual design and content structure. Users are presented with carefully curated color palette options, each designed to convey specific brand personalities and industry appropriateness.

The color palette selection interface displays visual representations of each option, showing how the colors work together in a cohesive design scheme. The available palettes include Professional Blue for corporate and financial services, Modern Green for technology and environmental companies, Elegant Purple for creative and luxury brands, and Warm Orange for hospitality and retail businesses.

Beyond color selection, users can specify their preferred website style from options including Corporate & Professional for traditional business presentations, Modern & Minimalist for contemporary and tech-focused companies, Creative & Artistic for design and media businesses, and E-commerce Focused for retail and sales-oriented organizations.

These preferences directly influence the AI content generation process, ensuring that the resulting website content, structure, and presentation align with the business's brand identity and target audience expectations. The system uses these preferences to select appropriate content templates, writing styles, and structural elements.

**Step 3: WordPress Integration Configuration**: The final step requires users to provide connection details for their target WordPress installation. This includes the complete WordPress site URL, administrative username, and application password for secure API access.

The application provides detailed guidance on creating application passwords in WordPress, which offer enhanced security compared to traditional password authentication. Users are guided through the WordPress admin interface to generate these specialized passwords, which can be revoked independently without affecting the main account password.

The system includes a built-in connection testing feature that verifies WordPress accessibility and authentication before proceeding with website generation. This proactive validation prevents failures during the generation process and provides immediate feedback if configuration adjustments are needed.

### Website Generation Process

**Initiation and Progress Tracking**: Once all required information is provided and validated, users can initiate the website generation process by clicking the "Generate Website" button. The application immediately transitions to a progress tracking interface that provides real-time updates on the generation status.

The progress tracking system displays four main phases: Business Data Collection, Content Generation, Image Integration, and WordPress Site Building. Each phase includes detailed status messages and progress indicators, allowing users to understand exactly what the system is accomplishing at each stage.

The visual progress interface includes animated elements and status icons that provide engaging feedback during the potentially lengthy generation process. Users can see which phase is currently active, which phases have been completed, and receive estimated time remaining for the overall process.

**Business Data Collection Phase**: During this initial phase, the system retrieves and processes business information from multiple sources. The application accesses Vietnamese business registries, performs web searches for additional company information, and compiles a comprehensive business profile that will inform all subsequent content generation.

The system employs intelligent data validation and enrichment techniques to ensure accuracy and completeness of the business information. This may include cross-referencing data from multiple sources, validating contact information, and identifying relevant industry keywords and business categories.

Progress updates during this phase inform users about specific data sources being accessed and the types of information being retrieved. This transparency helps users understand the thoroughness of the data collection process and builds confidence in the resulting website content.

**Content Generation Phase**: The content generation phase represents the core AI-powered functionality of the application. Using the collected business information and user preferences, the system generates a comprehensive sitemap that outlines the optimal website structure for the specific business type and industry.

The AI content generation process creates multiple types of content including homepage content that effectively introduces the business and its value proposition, detailed service or product pages that explain offerings and benefits, an about page that tells the company's story and establishes credibility, contact information pages with appropriate calls-to-action, and a series of blog articles that provide value to potential customers while supporting search engine optimization efforts.

Each piece of content is generated with specific attention to search engine optimization principles, including appropriate keyword density, meta descriptions, and heading structures. The content is also optimized for user engagement, with clear calls-to-action and persuasive language that encourages visitor interaction.

**Image Integration Phase**: The image integration phase automatically selects and incorporates relevant, high-quality images throughout the website. The system searches multiple stock photo APIs to find images that match the business type, content themes, and overall aesthetic preferences specified by the user.

The intelligent image selection process considers factors such as visual style consistency, color harmony with the selected palette, cultural appropriateness for the Vietnamese market, and relevance to specific content sections. The system prioritizes images that enhance the professional appearance of the website while supporting the content narrative.

All selected images are automatically optimized for web performance, including appropriate compression, sizing, and format selection. The system also generates appropriate alt text for accessibility compliance and SEO benefits.

**WordPress Site Building Phase**: The final phase involves creating the actual WordPress website using the generated content and selected images. The system uses WordPress REST API or XML-RPC protocols to create pages, posts, navigation menus, and media uploads in the target WordPress installation.

The WordPress integration process maintains proper content hierarchy, establishes appropriate internal linking structures, and configures basic SEO settings. The system also sets up navigation menus that provide intuitive user experience and ensure all generated content is easily accessible to website visitors.

During this phase, the system provides detailed feedback about each WordPress operation, including successful page creation, media uploads, and menu configuration. Any errors or issues are immediately reported with specific guidance for resolution.

### Results and Post-Generation Activities

**Results Presentation**: Upon successful completion of the website generation process, users are presented with a comprehensive results summary that includes direct links to the newly created website, detailed statistics about the generated content including number of pages created, blog posts published, and images integrated, performance metrics such as total generation time and system resource usage, and recommendations for next steps to optimize and maintain the website.

The results interface provides immediate access to the live website, allowing users to review the generated content and overall presentation. The system also provides administrative links for WordPress backend access, enabling users to make immediate customizations or additions as needed.

**Quality Assurance and Review**: The application includes built-in quality assurance mechanisms that verify content accuracy, check for broken links or missing images, validate HTML structure and accessibility compliance, and ensure proper SEO configuration. Users receive a detailed quality report highlighting any issues that may require attention.

The quality assurance process also includes recommendations for content enhancement, such as suggestions for additional pages that could benefit the business, opportunities for content expansion or improvement, and strategies for ongoing content marketing and SEO optimization.

**Ongoing Maintenance and Support**: The system provides guidance for ongoing website maintenance, including recommendations for regular content updates, security best practices for WordPress management, performance optimization techniques, and strategies for search engine optimization improvement.

Users receive documentation and resources for managing their new website independently, including tutorials for common WordPress tasks, best practices for content creation and management, and guidance for integrating additional functionality such as contact forms, e-commerce capabilities, or social media integration.

## API Documentation

### Overview and Authentication

The WordPress Website Generator provides a comprehensive REST API that enables programmatic access to all application functionality. The API follows RESTful design principles and returns JSON-formatted responses for all endpoints. Authentication is handled through API keys and session tokens, ensuring secure access to sensitive operations.

**Base URL Structure**: All API endpoints are accessed through the base URL pattern `https://your-domain.com/php/process.php` with specific actions and parameters determining the functionality accessed. The API supports both GET and POST methods depending on the operation type and data requirements.

**Authentication Methods**: The API supports multiple authentication methods including API key authentication for server-to-server communications, session token authentication for web application integration, and OAuth 2.0 for third-party application access. Each method provides appropriate security levels for different use cases and integration scenarios.

**Rate Limiting and Usage Policies**: To ensure fair usage and system stability, the API implements rate limiting based on IP address and authentication credentials. Standard rate limits allow up to 100 requests per hour for authenticated users, with higher limits available for premium accounts or enterprise integrations.

### Core API Endpoints

**Website Generation Endpoint**: The primary website generation endpoint accepts comprehensive configuration data and initiates the automated website creation process. This endpoint requires business information, design preferences, and WordPress connection details as input parameters.

```http
POST /php/process.php
Content-Type: application/json

{
  "action": "generate_website",
  "taxCode": "0123456789",
  "colorPalette": "professional",
  "websiteStyle": "corporate",
  "wpUrl": "https://your-wordpress-site.com",
  "wpUsername": "admin",
  "wpPassword": "application_password"
}
```

The response includes a unique session identifier that can be used to track progress and retrieve results. The session ID remains valid for 24 hours, allowing sufficient time for complex website generation processes to complete.

**Progress Tracking Endpoint**: The progress tracking endpoint provides real-time status updates for ongoing website generation processes. This endpoint supports both polling and webhook-based notifications, allowing client applications to choose the most appropriate update mechanism.

```http
GET /php/process.php?action=get_progress&session_id=ws_12345
```

Progress responses include current phase information, completion percentage, detailed status messages, and estimated time remaining. The endpoint also provides error information if any issues occur during the generation process.

**Results Retrieval Endpoint**: Upon completion of the website generation process, the results endpoint provides comprehensive information about the created website, including direct URLs, content statistics, and performance metrics.

```http
GET /php/process.php?action=get_results&session_id=ws_12345
```

Results include the complete website URL, detailed content inventory, generation performance metrics, quality assurance reports, and recommendations for ongoing optimization and maintenance.

### Business Data API

**Tax Code Validation**: The tax code validation endpoint provides immediate verification of Vietnam Business License number format and basic validity checks. This endpoint can be used for real-time form validation in client applications.

```http
POST /php/process.php
{
  "action": "validate_tax_code",
  "tax_code": "0123456789"
}
```

**Business Information Retrieval**: The business information endpoint retrieves comprehensive company data based on the provided tax code. This endpoint accesses multiple data sources and returns normalized business information suitable for content generation.

```http
GET /php/process.php?action=get_business_info&tax_code=0123456789
```

Business information responses include company name and registration details, contact information including address, phone, and email, industry classification and business activities, operational status and registration dates, and additional metadata that supports content generation processes.

### WordPress Integration API

**Connection Testing**: The WordPress connection testing endpoint verifies accessibility and authentication for target WordPress installations. This endpoint should be used before initiating website generation to ensure successful integration.

```http
POST /php/process.php
{
  "action": "test_wordpress",
  "wp_url": "https://your-site.com",
  "wp_username": "admin",
  "wp_password": "app_password"
}
```

Connection test responses include authentication status, WordPress version information, available themes and plugins, user permission levels, and API capability assessment.

**Content Creation Endpoints**: Individual content creation endpoints allow for granular control over WordPress content generation. These endpoints can be used to create specific pages, posts, or media uploads independently of the full website generation process.

```http
POST /php/process.php
{
  "action": "create_wp_page",
  "page_data": {
    "title": "About Us",
    "content": "Generated page content...",
    "slug": "about-us",
    "status": "publish"
  },
  "wp_config": {
    "url": "https://your-site.com",
    "username": "admin",
    "password": "app_password"
  }
}
```

### Image Management API

**Image Search**: The image search endpoint provides access to multiple stock photo APIs through a unified interface. Search results include images from Unsplash, Pexels, and Pixabay with consistent metadata formatting.

```http
GET /php/process.php?action=search_images&query=business+office&limit=10
```

Image search responses include high-resolution image URLs, thumbnail previews, photographer attribution information, licensing details, and relevance scoring for search optimization.

**Image Download and Processing**: The image download endpoint handles automatic image retrieval, optimization, and local storage. This endpoint ensures that all images are properly sized, compressed, and formatted for web use.

```http
POST /php/process.php
{
  "action": "download_image",
  "image_data": {
    "url": "https://images.unsplash.com/photo-123",
    "alt_text": "Professional office environment",
    "caption": "Modern workspace design"
  }
}
```

### Error Handling and Response Formats

**Standard Response Format**: All API endpoints return responses in a consistent JSON format that includes success status, data payload, error messages when applicable, and additional metadata such as request timestamps and processing times.

```json
{
  "success": true,
  "data": {
    "session_id": "ws_12345",
    "status": "initiated"
  },
  "message": "Website generation started successfully",
  "timestamp": "2025-06-17T16:53:00Z",
  "processing_time": 0.245
}
```

**Error Response Handling**: Error responses provide detailed information about the nature of the problem, suggested resolution steps, and relevant error codes for programmatic handling. Common error scenarios include invalid authentication credentials, malformed request data, external service unavailability, and resource limit exceeded conditions.

**Webhook Integration**: For applications requiring real-time updates, the API supports webhook notifications that can be configured to send progress updates and completion notifications to specified endpoints. Webhook payloads include the same information available through polling endpoints but are delivered automatically as events occur.

