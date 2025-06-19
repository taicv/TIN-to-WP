// Main application logic
class WebsiteGenerator {
    constructor() {
        this.currentStep = 1;
        this.maxSteps = 3;
        this.formData = {};
        this.selectedPalette = 'professional';
        this.progressTracker = new ProgressTracker();
        this.wsManager = new WebSocketManager();
        this.sessionId = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadSavedData();
        this.setupProgressTracking();
    }
    
    bindEvents() {
        // Form submission
        const form = document.getElementById('websiteForm');
        if (form) {
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
        }
        
        // Color palette selection
        const paletteOptions = document.querySelectorAll('.palette-option');
        paletteOptions.forEach(option => {
            option.addEventListener('click', this.handlePaletteSelection.bind(this));
        });
        
        // Input validation
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('blur', this.validateField.bind(this));
            input.addEventListener('input', Utils.debounce(this.validateField.bind(this), 500));
        });
        
        // Smooth scrolling for CTA button
        const ctaButton = document.querySelector('.cta-button');
        if (ctaButton) {
            ctaButton.addEventListener('click', this.scrollToGenerator.bind(this));
        }
        
        // Auto-save form data
        const formInputs = document.querySelectorAll('#websiteForm input, #websiteForm select');
        formInputs.forEach(input => {
            input.addEventListener('change', this.saveFormData.bind(this));
        });
    }
    
    setupProgressTracking() {
        this.progressTracker.onProgress((data) => {
            console.log('Progress update:', data);
        });
        
        // WebSocket event handlers
        this.wsManager.on('progress', (data) => {
            this.progressTracker.updateStep(data.step, data.progress, data.message);
        });
        
        this.wsManager.on('completed', (data) => {
            this.progressTracker.complete();
            this.showResults(data.results);
        });
        
        this.wsManager.on('error', (error) => {
            Utils.toast.error('An error occurred during website generation: ' + error.message);
        });
    }
    
    async handleFormSubmit(event) {
        event.preventDefault();
        
        if (!this.validateCurrentStep()) {
            return;
        }
        
        // Collect form data
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData.entries());
        data.colorPalette = this.selectedPalette;
        
        this.formData = { ...this.formData, ...data };
        
        try {
            // Show loading state
            this.showLoadingState();
            
            // Start website generation
            const response = await API.website.generate(this.formData);
            
            if (response.success) {
                this.sessionId = response.session_id;
                this.showProgressSection();
                this.startProgressTracking();
                
                // Connect WebSocket for real-time updates
                this.wsManager.connect(this.sessionId);
                
                Utils.toast.success('Website generation started successfully!');
            } else {
                throw new Error(response.message || 'Failed to start website generation');
            }
            
        } catch (error) {
            console.error('Form submission error:', error);
            Utils.toast.error('Failed to start website generation: ' + error.message);
            this.hideLoadingState();
        }
    }
    
    handlePaletteSelection(event) {
        // Remove previous selection
        document.querySelectorAll('.palette-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Add selection to clicked option
        const option = event.currentTarget;
        option.classList.add('selected');
        this.selectedPalette = option.dataset.palette;
        
        // Save selection
        this.saveFormData();
    }
    
    validateField(event) {
        const field = event.target;
        const fieldName = field.name;
        const value = field.value.trim();
        
        let isValid = true;
        let rules = [];
        
        // Define validation rules for each field
        switch (fieldName) {
            case 'taxCode':
                rules = [
                    { validator: Utils.validation.rules.required, message: 'Tax code is required' },
                    { validator: Utils.validation.rules.taxCode, message: 'Please enter a valid 10-digit tax code' }
                ];
                break;
                
            case 'wpUrl':
                rules = [
                    { validator: Utils.validation.rules.required, message: 'WordPress URL is required' },
                    { validator: Utils.validation.rules.url, message: 'Please enter a valid URL' }
                ];
                break;
                
            case 'wpUsername':
                rules = [
                    { validator: Utils.validation.rules.required, message: 'Username is required' },
                    { validator: Utils.validation.rules.minLength(3), message: 'Username must be at least 3 characters' }
                ];
                break;
                
            case 'wpPassword':
                rules = [
                    { validator: Utils.validation.rules.required, message: 'Password is required' },
                    { validator: Utils.validation.rules.minLength(8), message: 'Password must be at least 8 characters' }
                ];
                break;
        }
        
        // Validate field
        if (rules.length > 0) {
            isValid = Utils.validation.validate(field, rules);
        }
        
        return isValid;
    }
    
    validateCurrentStep() {
        const currentStepElement = document.querySelector(`.form-step[data-step="${this.currentStep}"]`);
        const inputs = currentStepElement.querySelectorAll('input[required], select[required]');
        
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField({ target: input })) {
                isValid = false;
            }
        });
        
        // Additional validation for step 2 (color palette selection)
        if (this.currentStep === 2 && !this.selectedPalette) {
            Utils.toast.warning('Please select a color palette');
            isValid = false;
        }
        
        return isValid;
    }
    
    nextStep() {
        if (!this.validateCurrentStep()) {
            return;
        }
        
        if (this.currentStep < this.maxSteps) {
            this.currentStep++;
            this.updateStepDisplay();
            this.saveFormData();
        }
    }
    
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepDisplay();
        }
    }
    
    updateStepDisplay() {
        // Hide all steps
        document.querySelectorAll('.form-step').forEach(step => {
            step.classList.remove('active');
        });
        
        // Show current step
        const currentStepElement = document.querySelector(`.form-step[data-step="${this.currentStep}"]`);
        if (currentStepElement) {
            currentStepElement.classList.add('active');
        }
        
        // Update step indicators if they exist
        this.updateStepIndicators();
    }
    
    updateStepIndicators() {
        const indicators = document.querySelectorAll('.step-indicator');
        indicators.forEach((indicator, index) => {
            const stepNumber = index + 1;
            indicator.classList.remove('active', 'completed');
            
            if (stepNumber < this.currentStep) {
                indicator.classList.add('completed');
            } else if (stepNumber === this.currentStep) {
                indicator.classList.add('active');
            }
        });
    }
    
    scrollToGenerator() {
        const generatorSection = document.getElementById('generator');
        if (generatorSection) {
            Utils.scrollToElement(generatorSection, 100);
        }
    }
    
    saveFormData() {
        const form = document.getElementById('websiteForm');
        if (form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.colorPalette = this.selectedPalette;
            data.currentStep = this.currentStep;
            
            Utils.storage.set('websiteGeneratorData', data);
        }
    }
    
    loadSavedData() {
        const savedData = Utils.storage.get('websiteGeneratorData');
        if (savedData) {
            // Restore form values
            Object.keys(savedData).forEach(key => {
                const field = document.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = savedData[key];
                }
            });
            
            // Restore color palette selection
            if (savedData.colorPalette) {
                this.selectedPalette = savedData.colorPalette;
                const paletteOption = document.querySelector(`[data-palette="${savedData.colorPalette}"]`);
                if (paletteOption) {
                    paletteOption.classList.add('selected');
                }
            }
            
            // Restore current step
            if (savedData.currentStep) {
                this.currentStep = parseInt(savedData.currentStep);
                this.updateStepDisplay();
            }
        }
    }
    
    showLoadingState() {
        const submitButton = document.querySelector('.btn-generate');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        }
    }
    
    hideLoadingState() {
        const submitButton = document.querySelector('.btn-generate');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-magic"></i> Generate Website';
        }
    }
    
    showProgressSection() {
        const generatorSection = document.querySelector('.generator-section');
        const progressSection = document.getElementById('progressSection');
        
        if (generatorSection && progressSection) {
            Utils.animation.fadeOut(generatorSection, 300);
            setTimeout(() => {
                progressSection.style.display = 'block';
                Utils.animation.fadeIn(progressSection, 300);
                Utils.scrollToElement(progressSection, 100);
            }, 300);
        }
    }
    
    async startProgressTracking() {
        // Reset progress tracker
        this.progressTracker.reset();
        
        // Start polling for progress updates (fallback if WebSocket fails)
        this.progressPolling = setInterval(async () => {
            try {
                const response = await API.website.getProgress(this.sessionId);
                if (response.success) {
                    const { step, progress, message } = response.data;
                    this.progressTracker.updateStep(step, progress, message);
                    
                    // Check if completed
                    if (response.data.completed) {
                        clearInterval(this.progressPolling);
                        this.progressTracker.complete();
                        
                        // Get final results
                        const resultsResponse = await API.website.getResults(this.sessionId);
                        if (resultsResponse.success) {
                            this.showResults(resultsResponse.data);
                        }
                    }
                }
            } catch (error) {
                console.error('Progress polling error:', error);
            }
        }, 2000);
    }
    
    showResults(results) {
        const progressSection = document.getElementById('progressSection');
        const resultsSection = document.getElementById('resultsSection');
        
        if (progressSection && resultsSection) {
            // Stop progress polling
            if (this.progressPolling) {
                clearInterval(this.progressPolling);
            }
            
            // Disconnect WebSocket
            this.wsManager.disconnect();
            
            // Update results content
            this.updateResultsContent(results);
            
            // Show results section
            Utils.animation.fadeOut(progressSection, 300);
            setTimeout(() => {
                resultsSection.style.display = 'block';
                Utils.animation.fadeIn(resultsSection, 300);
                Utils.scrollToElement(resultsSection, 100);
            }, 300);
            
            // Clear saved form data
            Utils.storage.remove('websiteGeneratorData');
            
            Utils.toast.success('Website created successfully!');
        }
    }
    
    updateResultsContent(results) {
        const resultsDetails = document.getElementById('resultsDetails');
        const visitWebsiteButton = document.getElementById('visitWebsite');
        
        if (resultsDetails && results) {
            resultsDetails.innerHTML = `
                <div class="results-summary">
                    <h3>Website Summary</h3>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <i class="fas fa-file-alt"></i>
                            <span class="summary-label">Pages Created</span>
                            <span class="summary-value">${results.pages_count || 0}</span>
                        </div>
                        <div class="summary-item">
                            <i class="fas fa-blog"></i>
                            <span class="summary-label">Blog Posts</span>
                            <span class="summary-value">${results.posts_count || 0}</span>
                        </div>
                        <div class="summary-item">
                            <i class="fas fa-images"></i>
                            <span class="summary-label">Images Added</span>
                            <span class="summary-value">${results.images_count || 0}</span>
                        </div>
                        <div class="summary-item">
                            <i class="fas fa-clock"></i>
                            <span class="summary-label">Generation Time</span>
                            <span class="summary-value">${results.generation_time || 'N/A'}</span>
                        </div>
                    </div>
                </div>
                
                ${results.website_url ? `
                    <div class="website-preview-card">
                        <h4>Your New Website</h4>
                        <p class="website-url">${results.website_url}</p>
                        <p class="website-description">
                            Your professional WordPress website is now live and ready for visitors.
                            All content, images, and navigation have been automatically created based on your business information.
                        </p>
                    </div>
                ` : ''}
                
                ${results.next_steps ? `
                    <div class="next-steps">
                        <h4>Next Steps</h4>
                        <ul>
                            ${results.next_steps.map(step => `<li>${step}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            `;
        }
        
        if (visitWebsiteButton && results.website_url) {
            visitWebsiteButton.href = results.website_url;
        }
    }
    
    resetForm() {
        // Reset form data
        this.currentStep = 1;
        this.formData = {};
        this.selectedPalette = 'professional';
        this.sessionId = null;
        
        // Reset UI
        const form = document.getElementById('websiteForm');
        if (form) {
            form.reset();
        }
        
        // Clear palette selection
        document.querySelectorAll('.palette-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Reset step display
        this.updateStepDisplay();
        
        // Hide results and progress sections
        const progressSection = document.getElementById('progressSection');
        const resultsSection = document.getElementById('resultsSection');
        const generatorSection = document.querySelector('.generator-section');
        
        if (progressSection) progressSection.style.display = 'none';
        if (resultsSection) resultsSection.style.display = 'none';
        if (generatorSection) generatorSection.style.display = 'block';
        
        // Clear saved data
        Utils.storage.remove('websiteGeneratorData');
        
        // Scroll to generator
        this.scrollToGenerator();
        
        Utils.toast.info('Form reset successfully');
    }
    
    // Test WordPress connection
    async testWordPressConnection() {
        const wpUrl = document.getElementById('wpUrl').value;
        const wpUsername = document.getElementById('wpUsername').value;
        const wpPassword = document.getElementById('wpPassword').value;
        
        if (!wpUrl || !wpUsername || !wpPassword) {
            Utils.toast.warning('Please fill in all WordPress connection details');
            return;
        }
        
        try {
            const response = await API.website.testWordPress({
                wp_url: wpUrl,
                wp_username: wpUsername,
                wp_password: wpPassword
            });
            
            if (response.success) {
                Utils.toast.success('WordPress connection successful!');
                return true;
            } else {
                Utils.toast.error('WordPress connection failed: ' + response.message);
                return false;
            }
        } catch (error) {
            Utils.toast.error('Failed to test WordPress connection: ' + error.message);
            return false;
        }
    }
}

// Global functions for HTML onclick handlers
function nextStep() {
    if (window.websiteGenerator) {
        window.websiteGenerator.nextStep();
    }
}

function prevStep() {
    if (window.websiteGenerator) {
        window.websiteGenerator.prevStep();
    }
}

function resetForm() {
    if (window.websiteGenerator) {
        window.websiteGenerator.resetForm();
    }
}

function scrollToGenerator() {
    if (window.websiteGenerator) {
        window.websiteGenerator.scrollToGenerator();
    }
}

function testWordPressConnection() {
    if (window.websiteGenerator) {
        return window.websiteGenerator.testWordPressConnection();
    }
}

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.websiteGenerator = new WebsiteGenerator();
    
    // Add test WordPress connection button if needed
    const wpPasswordField = document.getElementById('wpPassword');
    if (wpPasswordField) {
        const testButton = document.createElement('button');
        testButton.type = 'button';
        testButton.className = 'btn-test-wp';
        testButton.innerHTML = '<i class="fas fa-plug"></i> Test Connection';
        testButton.onclick = testWordPressConnection;
        
        const formGroup = wpPasswordField.closest('.form-group');
        if (formGroup) {
            formGroup.appendChild(testButton);
        }
    }
});

