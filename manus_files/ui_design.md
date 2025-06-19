# User Interface and Main Application

## Overview

This module provides a modern, responsive web interface for the WordPress Website Generator application. Users can input their Vietnam Business License number, select color palettes, and monitor the website creation process in real-time.

## Features

1. **Modern UI Design**: Clean, professional interface with smooth animations
2. **Responsive Layout**: Works on desktop, tablet, and mobile devices
3. **Color Palette Selection**: Interactive color scheme picker
4. **Progress Tracking**: Real-time progress updates during website creation
5. **Form Validation**: Client-side validation for user inputs
6. **Error Handling**: User-friendly error messages and recovery options

## File Structure

```
/website-generator/
├── index.html              # Main application page
├── css/
│   ├── style.css          # Main stylesheet
│   └── components.css     # Component-specific styles
├── js/
│   ├── app.js            # Main application logic
│   ├── api.js            # API communication
│   └── utils.js          # Utility functions
├── assets/
│   ├── images/           # UI images and icons
│   └── fonts/            # Custom fonts
└── php/
    ├── index.php         # Main PHP application
    ├── process.php       # Website generation processor
    └── config.php        # Configuration file
```

## Implementation

### HTML Structure (index.html)

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Website Generator - Vietnam Business</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <i class="fas fa-globe"></i>
                        <span>Website Generator</span>
                    </div>
                    <nav class="nav-menu">
                        <a href="#features">Features</a>
                        <a href="#how-it-works">How It Works</a>
                        <a href="#contact">Contact</a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">
                            Create Professional WordPress Websites
                            <span class="highlight">Automatically</span>
                        </h1>
                        <p class="hero-description">
                            Enter your Vietnam Business License number and let AI create a complete, 
                            professional website with content, images, and design tailored to your business.
                        </p>
                        <button class="cta-button" onclick="scrollToGenerator()">
                            <i class="fas fa-rocket"></i>
                            Start Creating
                        </button>
                    </div>
                    <div class="hero-visual">
                        <div class="website-preview">
                            <div class="browser-frame">
                                <div class="browser-header">
                                    <div class="browser-dots">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </div>
                                <div class="browser-content">
                                    <div class="preview-website">
                                        <div class="preview-header"></div>
                                        <div class="preview-content">
                                            <div class="preview-text"></div>
                                            <div class="preview-text short"></div>
                                            <div class="preview-image"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Generator Section -->
            <section class="generator-section" id="generator">
                <div class="container">
                    <div class="generator-content">
                        <h2 class="section-title">Generate Your Website</h2>
                        <p class="section-description">
                            Fill in the details below to create your professional WordPress website
                        </p>

                        <!-- Generator Form -->
                        <div class="generator-form">
                            <form id="websiteForm" class="form-container">
                                <!-- Step 1: Business Information -->
                                <div class="form-step active" data-step="1">
                                    <h3 class="step-title">
                                        <span class="step-number">1</span>
                                        Business Information
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label for="taxCode">Vietnam Business License Number (Tax Code)</label>
                                        <input 
                                            type="text" 
                                            id="taxCode" 
                                            name="taxCode" 
                                            placeholder="Enter your tax code (e.g., 0123456789)"
                                            required
                                        >
                                        <div class="form-help">
                                            Enter your 10-digit Vietnam business tax code
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="button" class="btn-next" onclick="nextStep()">
                                            Next Step
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 2: Design Preferences -->
                                <div class="form-step" data-step="2">
                                    <h3 class="step-title">
                                        <span class="step-number">2</span>
                                        Design Preferences
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label>Choose Color Palette</label>
                                        <div class="color-palettes">
                                            <div class="palette-option" data-palette="professional">
                                                <div class="palette-colors">
                                                    <span style="background: #2563eb"></span>
                                                    <span style="background: #1e40af"></span>
                                                    <span style="background: #1e3a8a"></span>
                                                    <span style="background: #f8fafc"></span>
                                                </div>
                                                <span class="palette-name">Professional Blue</span>
                                            </div>
                                            
                                            <div class="palette-option" data-palette="modern">
                                                <div class="palette-colors">
                                                    <span style="background: #059669"></span>
                                                    <span style="background: #047857"></span>
                                                    <span style="background: #065f46"></span>
                                                    <span style="background: #f0fdf4"></span>
                                                </div>
                                                <span class="palette-name">Modern Green</span>
                                            </div>
                                            
                                            <div class="palette-option" data-palette="elegant">
                                                <div class="palette-colors">
                                                    <span style="background: #7c3aed"></span>
                                                    <span style="background: #6d28d9"></span>
                                                    <span style="background: #5b21b6"></span>
                                                    <span style="background: #faf5ff"></span>
                                                </div>
                                                <span class="palette-name">Elegant Purple</span>
                                            </div>
                                            
                                            <div class="palette-option" data-palette="warm">
                                                <div class="palette-colors">
                                                    <span style="background: #ea580c"></span>
                                                    <span style="background: #dc2626"></span>
                                                    <span style="background: #991b1b"></span>
                                                    <span style="background: #fff7ed"></span>
                                                </div>
                                                <span class="palette-name">Warm Orange</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="websiteStyle">Website Style</label>
                                        <select id="websiteStyle" name="websiteStyle">
                                            <option value="corporate">Corporate & Professional</option>
                                            <option value="modern">Modern & Minimalist</option>
                                            <option value="creative">Creative & Artistic</option>
                                            <option value="ecommerce">E-commerce Focused</option>
                                        </select>
                                    </div>

                                    <div class="form-actions">
                                        <button type="button" class="btn-back" onclick="prevStep()">
                                            <i class="fas fa-arrow-left"></i>
                                            Back
                                        </button>
                                        <button type="button" class="btn-next" onclick="nextStep()">
                                            Next Step
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 3: WordPress Configuration -->
                                <div class="form-step" data-step="3">
                                    <h3 class="step-title">
                                        <span class="step-number">3</span>
                                        WordPress Configuration
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label for="wpUrl">WordPress Site URL</label>
                                        <input 
                                            type="url" 
                                            id="wpUrl" 
                                            name="wpUrl" 
                                            placeholder="https://your-wordpress-site.com"
                                            required
                                        >
                                    </div>

                                    <div class="form-group">
                                        <label for="wpUsername">WordPress Username</label>
                                        <input 
                                            type="text" 
                                            id="wpUsername" 
                                            name="wpUsername" 
                                            placeholder="WordPress admin username"
                                            required
                                        >
                                    </div>

                                    <div class="form-group">
                                        <label for="wpPassword">WordPress Application Password</label>
                                        <input 
                                            type="password" 
                                            id="wpPassword" 
                                            name="wpPassword" 
                                            placeholder="Application password"
                                            required
                                        >
                                        <div class="form-help">
                                            <i class="fas fa-info-circle"></i>
                                            Use an application password, not your regular WordPress password
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="button" class="btn-back" onclick="prevStep()">
                                            <i class="fas fa-arrow-left"></i>
                                            Back
                                        </button>
                                        <button type="submit" class="btn-generate">
                                            <i class="fas fa-magic"></i>
                                            Generate Website
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Progress Section -->
            <section class="progress-section" id="progressSection" style="display: 
(Content truncated due to size limit. Use line ranges to read in chunks)