# WordPress Website Generator

A comprehensive PHP application that automatically creates professional WordPress websites for Vietnamese businesses using AI-powered content generation.

## Features

- **Automated Business Data Collection**: Retrieves company information using Vietnam Business License numbers
- **AI-Powered Content Generation**: Creates professional website content using OpenAI GPT models
- **Intelligent Image Integration**: Automatically selects and integrates relevant images from stock photo APIs
- **WordPress Integration**: Seamlessly creates pages, posts, and menus in existing WordPress installations
- **Modern Web Interface**: Responsive design with real-time progress tracking
- **Multi-Language Support**: Supports both English and Vietnamese content generation

## Quick Start

### Prerequisites

- PHP 8.0+ with required extensions
- MySQL 8.0+ or PostgreSQL 12+
- Web server (Apache/Nginx)
- OpenAI API key
- Image API keys (Unsplash, Pexels, or Pixabay)

### Installation

1. **Download and extract files**:
```bash
git clone https://github.com/your-repo/wordpress-website-generator.git
cd wordpress-website-generator
```

2. **Install dependencies**:
```bash
composer install --no-dev
```

3. **Configure database**:
```bash
mysql -u root -p < database_schema.sql
```

4. **Set up configuration**:
```bash
cp php/config.php.example php/config.php
# Edit config.php with your settings
```

5. **Set permissions**:
```bash
chmod -R 755 .
chown -R www-data:www-data .
```

6. **Access the application**:
Open your web browser and navigate to your domain.

## Usage

1. **Enter Business Information**: Input your Vietnam Business License number (Tax Code)
2. **Choose Design Preferences**: Select color palette and website style
3. **Configure WordPress**: Provide your WordPress site credentials
4. **Generate Website**: Click "Generate Website" and monitor progress
5. **Review Results**: Access your newly created professional website

## Configuration

### Required API Keys

- **OpenAI API**: For content generation
- **Unsplash API**: For high-quality stock photos
- **Pexels API**: Alternative image source
- **Pixabay API**: Additional image options

### WordPress Requirements

- WordPress 4.7+ (for REST API support)
- Application passwords enabled
- Appropriate user permissions for content creation

## Architecture

The application follows a modular architecture:

- **Frontend**: Modern HTML5/CSS3/JavaScript interface
- **Backend**: PHP-based processing engine
- **Database**: MySQL/PostgreSQL for data storage
- **APIs**: Integration with external services
- **WordPress**: REST API and XML-RPC connectivity

## File Structure

```
website-generator/
├── index.html              # Main application interface
├── css/                    # Stylesheets
├── js/                     # JavaScript files
├── php/                    # Backend PHP files
├── assets/                 # Static assets
├── logs/                   # Application logs
├── cache/                  # Cached data
├── uploads/                # User uploads
├── database_schema.sql     # Database structure
├── INSTALLATION.md         # Installation guide
└── README.md              # This file
```

## API Documentation

### Main Endpoints

- `POST /php/process.php` - Main processing endpoint
- `GET /php/process.php?action=get_progress` - Progress tracking
- `GET /php/process.php?action=get_results` - Results retrieval

### Request Examples

**Generate Website**:
```json
{
  "action": "generate_website",
  "taxCode": "0123456789",
  "colorPalette": "professional",
  "websiteStyle": "corporate",
  "wpUrl": "https://your-site.com",
  "wpUsername": "admin",
  "wpPassword": "app_password"
}
```

**Test WordPress Connection**:
```json
{
  "action": "test_wordpress",
  "wp_url": "https://your-site.com",
  "wp_username": "admin",
  "wp_password": "app_password"
}
```

## Development

### Local Development Setup

1. **Install XAMPP/WAMP** or use Docker
2. **Clone repository** to web directory
3. **Install dependencies** with Composer
4. **Configure database** and API keys
5. **Start development server**

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Code Standards

- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Write unit tests for new features

## Security

### Best Practices

- Store API keys securely
- Use HTTPS for all communications
- Validate and sanitize all inputs
- Implement rate limiting
- Regular security updates

### Data Protection

- Encrypt sensitive data
- Secure database connections
- Implement proper access controls
- Regular security audits

## Performance

### Optimization Tips

- Enable PHP OPcache
- Use database indexing
- Implement caching strategies
- Optimize images
- Use CDN for static assets

### Monitoring

- Track API usage and costs
- Monitor server resources
- Log application performance
- Set up alerting for issues

## Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials
- Verify database server is running
- Test connection manually

**API Key Invalid**
- Verify API keys in configuration
- Check API usage limits
- Test API connectivity

**WordPress Connection Failed**
- Verify WordPress credentials
- Check application password setup
- Test WordPress API access

**Content Generation Slow**
- Check OpenAI API response times
- Monitor server resources
- Optimize database queries

### Support

For technical support:
- Check the documentation
- Review log files
- Search existing issues
- Contact support team

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Changelog

### Version 1.0.0
- Initial release
- Basic website generation functionality
- WordPress integration
- AI content generation
- Image management system

## Acknowledgments

- OpenAI for GPT models
- Unsplash, Pexels, Pixabay for image APIs
- WordPress community for REST API
- Contributors and testers

## Links

- [Documentation](https://your-docs-site.com)
- [Support](https://your-support-site.com)
- [GitHub Repository](https://github.com/your-repo/wordpress-website-generator)
- [Demo Site](https://demo.your-site.com)

---

**Note**: This application is designed for Vietnamese businesses but can be adapted for other regions by modifying the business data collection module.

