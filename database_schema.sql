-- WordPress Website Generator Database Schema
-- This script creates the necessary tables for the application

-- Create database (run this separately if needed)
-- CREATE DATABASE website_generator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE website_generator;

-- Generation sessions table
CREATE TABLE IF NOT EXISTS generation_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) UNIQUE NOT NULL,
    tax_code VARCHAR(20) NOT NULL,
    color_palette VARCHAR(50) NOT NULL,
    website_style VARCHAR(50) NOT NULL,
    wp_url VARCHAR(255) NOT NULL,
    wp_username VARCHAR(100) NOT NULL,
    wp_password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_tax_code (tax_code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generation progress tracking table
CREATE TABLE IF NOT EXISTS generation_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    current_step ENUM('business', 'content', 'images', 'wordpress') NOT NULL,
    step_progress INT DEFAULT 0,
    status_message TEXT,
    error_message TEXT,
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES generation_sessions(session_id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_current_step (current_step),
    INDEX idx_completed (completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Business data cache table
CREATE TABLE IF NOT EXISTS business_data_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_code VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(255),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(255),
    industry VARCHAR(100),
    business_type VARCHAR(100),
    registration_date DATE,
    status VARCHAR(50),
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tax_code (tax_code),
    INDEX idx_company_name (company_name),
    INDEX idx_industry (industry),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated content table
CREATE TABLE IF NOT EXISTS generated_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    content_type ENUM('sitemap', 'page', 'post', 'menu') NOT NULL,
    content_title VARCHAR(255),
    content_slug VARCHAR(255),
    content_body LONGTEXT,
    content_meta JSON,
    wp_post_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES generation_sessions(session_id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_content_type (content_type),
    INDEX idx_content_slug (content_slug),
    INDEX idx_wp_post_id (wp_post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Image cache table
CREATE TABLE IF NOT EXISTS image_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    image_url VARCHAR(500) NOT NULL,
    local_path VARCHAR(255),
    alt_text VARCHAR(255),
    caption TEXT,
    source_api VARCHAR(50),
    source_id VARCHAR(100),
    width INT,
    height INT,
    file_size INT,
    wp_media_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES generation_sessions(session_id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_image_url (image_url),
    INDEX idx_source_api (source_api),
    INDEX idx_wp_media_id (wp_media_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generation results table
CREATE TABLE IF NOT EXISTS generation_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) UNIQUE NOT NULL,
    website_url VARCHAR(255),
    pages_count INT DEFAULT 0,
    posts_count INT DEFAULT 0,
    images_count INT DEFAULT 0,
    generation_time INT, -- in seconds
    result_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES generation_sessions(session_id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_website_url (website_url),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error logs table
CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    error_type VARCHAR(100),
    error_message TEXT,
    error_context JSON,
    stack_trace TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES generation_sessions(session_id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_error_type (error_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API usage tracking table
CREATE TABLE IF NOT EXISTS api_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    api_name VARCHAR(50) NOT NULL,
    endpoint VARCHAR(255),
    request_count INT DEFAULT 1,
    response_time INT, -- in milliseconds
    status_code INT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES generation_sessions(session_id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_api_name (api_name),
    INDEX idx_created_at (created_at),
    INDEX idx_status_code (status_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table (for authentication if needed)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    user_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL, -- IP address or user ID
    action VARCHAR(100) NOT NULL,
    request_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_identifier_action (identifier, action),
    INDEX idx_identifier (identifier),
    INDEX idx_action (action),
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuration settings table
CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO app_settings (setting_key, setting_value, setting_type, description) VALUES
('maintenance_mode', 'false', 'boolean', 'Enable/disable maintenance mode'),
('max_concurrent_generations', '5', 'integer', 'Maximum number of concurrent website generations'),
('default_content_language', 'en', 'string', 'Default language for generated content'),
('image_optimization_enabled', 'true', 'boolean', 'Enable automatic image optimization'),
('cache_duration', '3600', 'integer', 'Default cache duration in seconds'),
('api_rate_limit', '100', 'integer', 'API requests per hour per IP'),
('max_generation_time', '1800', 'integer', 'Maximum generation time in seconds'),
('cleanup_old_sessions', 'true', 'boolean', 'Automatically cleanup old sessions'),
('session_retention_days', '7', 'integer', 'Number of days to retain session data');

-- Create indexes for better performance
CREATE INDEX idx_sessions_created_at ON generation_sessions(created_at);
CREATE INDEX idx_progress_updated_at ON generation_progress(updated_at);
CREATE INDEX idx_content_created_at ON generated_content(created_at);
CREATE INDEX idx_images_created_at ON image_cache(created_at);
CREATE INDEX idx_results_created_at ON generation_results(created_at);
CREATE INDEX idx_errors_created_at ON error_logs(created_at);

-- Create a view for session overview
CREATE VIEW session_overview AS
SELECT 
    s.session_id,
    s.tax_code,
    s.color_palette,
    s.website_style,
    s.wp_url,
    s.created_at as session_created,
    p.current_step,
    p.step_progress,
    p.status_message,
    p.completed,
    r.website_url,
    r.pages_count,
    r.posts_count,
    r.images_count,
    r.generation_time
FROM generation_sessions s
LEFT JOIN generation_progress p ON s.session_id = p.session_id
LEFT JOIN generation_results r ON s.session_id = r.session_id;

-- Create a stored procedure for cleanup
DELIMITER //
CREATE PROCEDURE CleanupOldSessions()
BEGIN
    DECLARE retention_days INT DEFAULT 7;
    
    -- Get retention setting
    SELECT CAST(setting_value AS UNSIGNED) INTO retention_days 
    FROM app_settings 
    WHERE setting_key = 'session_retention_days';
    
    -- Delete old sessions and related data
    DELETE FROM generation_sessions 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    -- Delete orphaned image cache entries
    DELETE FROM image_cache 
    WHERE session_id IS NULL 
    AND created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    -- Delete old error logs
    DELETE FROM error_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Delete old API usage logs
    DELETE FROM api_usage 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Delete expired user sessions
    DELETE FROM user_sessions 
    WHERE expires_at < NOW();
    
    -- Reset rate limits older than 24 hours
    DELETE FROM rate_limits 
    WHERE window_start < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
END //
DELIMITER ;

-- Create an event to run cleanup daily (requires event scheduler to be enabled)
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS daily_cleanup
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_TIMESTAMP
-- DO CALL CleanupOldSessions();

-- Grant appropriate permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON website_generator.* TO 'app_user'@'localhost';
-- FLUSH PRIVILEGES;

