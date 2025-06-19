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
        
        // Initialize debug panel
        Utils.debug.init();
        
        // Log app initialization
        Utils.debug.log('info', 'Website Generator initialized');
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
            console.error('WebSocket error details:', error);
            
            // Log to debug panel
            Utils.debug.log('error', 'WebSocket error occurred', error);
            
            // Check if this is a browser-generated WebSocket error (connection failed)
            if (error && typeof error === 'object' && error.isTrusted === true) {
                // This is a browser WebSocket connection error - server doesn't support WebSockets
                console.warn('WebSocket server not available - falling back to polling');
                Utils.debug.log('info', 'WebSocket server not available, using polling fallback');
                Utils.debug.log('info', 'Note: WebSocket errors are expected when the server doesn\'t support WebSockets. The app will use polling instead.');
                
                // Don't show error toast for WebSocket connection failures
                // The polling mechanism will handle progress updates
                return;
            }
            
            // Handle different error types with more detailed logging
            let errorMessage = 'An error occurred during website generation';
            
            if (error && typeof error === 'object') {
                // Log the full error object for debugging
                console.error('Full error object:', JSON.stringify(error, null, 2));
                
                if (error.message) {
                    errorMessage += ': ' + error.message;
                } else if (error.type) {
                    errorMessage += ': ' + error.type;
                } else if (error.code) {
                    errorMessage += ': Error code ' + error.code;
                } else if (error.data) {
                    errorMessage += ': ' + (error.data.message || error.data.error || JSON.stringify(error.data));
                } else if (error.error) {
                    errorMessage += ': ' + (error.error.message || error.error.type || error.error);
                } else {
                    // Try to extract any meaningful information from the error object
                    const errorKeys = Object.keys(error);
                    if (errorKeys.length > 0) {
                        const errorInfo = errorKeys.map(key => `${key}: ${error[key]}`).join(', ');
                        errorMessage += ': ' + errorInfo;
                    } else {
                        errorMessage += ': ' + JSON.stringify(error);
                    }
                }
            } else if (error && typeof error === 'string') {
                errorMessage += ': ' + error;
            } else {
                errorMessage += ': Unknown error occurred';
            }
            
            console.error('Final error message:', errorMessage);
            Utils.toast.error(errorMessage);
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
                
                // Try to connect WebSocket for real-time updates (optional)
                this.wsManager.checkWebSocketAvailability().then(isAvailable => {
                    if (isAvailable) {
                        try {
                            this.wsManager.connect(this.sessionId);
                            Utils.debug.log('info', 'WebSocket server available, connecting for real-time updates');
                        } catch (wsError) {
                            console.warn('WebSocket connection failed:', wsError);
                            Utils.debug.log('info', 'WebSocket connection failed, using polling fallback');
                        }
                    } else {
                        console.info('WebSocket server not available, using polling only');
                        Utils.debug.log('info', 'WebSocket server not available, using polling fallback');
                    }
                }).catch(error => {
                    console.warn('WebSocket availability check failed:', error);
                    Utils.debug.log('info', 'WebSocket availability check failed, using polling fallback');
                });
                
                Utils.toast.success('Website generation started successfully!');
            } else {
                throw new Error(response.message || 'Failed to start website generation');
            }
            
        } catch (error) {
            console.error('Form submission error:', error);
            
            // Log to debug panel
            Utils.debug.log('error', 'Form submission failed', error);
            
            // Handle different error types with more detailed logging
            let errorMessage = 'Failed to start website generation';
            
            if (error && typeof error === 'object') {
                // Log the full error object for debugging
                console.error('Full form submission error object:', JSON.stringify(error, null, 2));
                
                if (error.message) {
                    errorMessage += ': ' + error.message;
                } else if (error.response && error.response.data) {
                    const responseData = error.response.data;
                    if (responseData.message) {
                        errorMessage += ': ' + responseData.message;
                    } else if (responseData.error) {
                        errorMessage += ': ' + responseData.error;
                    } else {
                        errorMessage += ': ' + JSON.stringify(responseData);
                    }
                } else if (error.status) {
                    errorMessage += ': HTTP ' + error.status;
                } else if (error.type) {
                    errorMessage += ': ' + error.type;
                } else if (error.code) {
                    errorMessage += ': Error code ' + error.code;
                } else {
                    // Try to extract any meaningful information from the error object
                    const errorKeys = Object.keys(error);
                    if (errorKeys.length > 0) {
                        const errorInfo = errorKeys.map(key => `${key}: ${error[key]}`).join(', ');
                        errorMessage += ': ' + errorInfo;
                    } else {
                        errorMessage += ': ' + JSON.stringify(error);
                    }
                }
            } else if (error && typeof error === 'string') {
                errorMessage += ': ' + error;
            } else {
                errorMessage += ': Unknown error occurred';
            }
            
            console.error('Final form submission error message:', errorMessage);
            Utils.toast.error(errorMessage);
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
                    
                    // Log progress to debug panel
                    Utils.debug.log('progress', `Progress update: ${step} - ${progress}% - ${message}`, response.data);
                    
                    // Check if completed
                    if (response.data.completed) {
                        clearInterval(this.progressPolling);
                        this.progressTracker.complete();
                        
                        // Get final results
                        const resultsResponse = await API.website.getResults(this.sessionId);
                        if (resultsResponse.success) {
                            this.showResults(resultsResponse.data);
                        } else {
                            console.error('Failed to get results:', resultsResponse);
                            Utils.toast.error('Failed to retrieve website generation results');
                        }
                    }
                    
                    // Check if there's an error
                    if (response.data.error) {
                        clearInterval(this.progressPolling);
                        console.error('Progress error:', response.data.error);
                        Utils.toast.error('Website generation failed: ' + response.data.error);
                        
                        // Log error to debug panel
                        Utils.debug.log('error', 'Website generation failed', {
                            error: response.data.error,
                            step: step,
                            progress: progress
                        });
                    }
                } else {
                    console.error('Progress response error:', response);
                    Utils.toast.error('Failed to get progress: ' + (response.message || 'Unknown error'));
                    
                    // Log error to debug panel
                    Utils.debug.log('error', 'Progress polling failed', response);
                }
            } catch (error) {
                console.error('Progress polling error:', {
                    error: error.message,
                    status: error.status,
                    response: error.response,
                    fullError: error
                });
                
                // Log to debug panel
                Utils.debug.log('error', 'Progress polling failed', error);
                
                // Log the full error object for debugging
                console.error('Full progress polling error object:', JSON.stringify(error, null, 2));
                
                // Don't show error toast for every polling failure, just log it
                // Only show error if it's been failing for a while
                if (!this.pollingErrorCount) {
                    this.pollingErrorCount = 0;
                }
                this.pollingErrorCount++;
                
                if (this.pollingErrorCount > 5) {
                    let errorMessage = 'Lost connection to server. Please refresh the page.';
                    
                    // Add more specific error information if available
                    if (error && typeof error === 'object') {
                        if (error.message) {
                            errorMessage += ' Error: ' + error.message;
                        } else if (error.status) {
                            errorMessage += ' HTTP Status: ' + error.status;
                        } else if (error.type) {
                            errorMessage += ' Type: ' + error.type;
                        } else if (error.response && error.response.status) {
                            errorMessage += ' HTTP Status: ' + error.response.status;
                        } else if (error.response && error.response.data) {
                            const responseData = error.response.data;
                            if (responseData.message) {
                                errorMessage += ' Server Error: ' + responseData.message;
                            } else if (responseData.error) {
                                errorMessage += ' Server Error: ' + responseData.error;
                            }
                        }
                    }
                    
                    Utils.toast.error(errorMessage);
                    clearInterval(this.progressPolling);
                }
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

// Debug panel toggle function
function toggleDebugPanel() {
    const panel = document.getElementById('debugPanel');
    if (panel) {
        const isVisible = panel.style.display !== 'none';
        panel.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            Utils.debug.log('info', 'Debug panel opened');
        }
    }
}


