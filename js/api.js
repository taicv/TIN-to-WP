// API communication module
const API = {
    baseUrl: 'php/',
    
    // Make HTTP request
    async request(endpoint, options = {}, queryParams = {}) {
        let url = `${this.baseUrl}${endpoint}`;
        
        // Add query parameters for GET requests
        if (options.method === 'GET' && Object.keys(queryParams).length > 0) {
            const urlObj = new URL(url, window.location.origin);
            Object.keys(queryParams).forEach(key => urlObj.searchParams.append(key, queryParams[key]));
            url = urlObj.pathname + urlObj.search;
        }
        
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            
            // Log response details for debugging
            console.log('API Response:', {
                url: url,
                status: response.status,
                statusText: response.statusText,
                headers: Object.fromEntries(response.headers.entries())
            });
            
            // Log to debug panel
            if (Utils.debug) {
                Utils.debug.log('api', `API Response: ${response.status} ${response.statusText}`, {
                    url: url,
                    status: response.status,
                    statusText: response.statusText
                });
            }
            
            let data;
            const contentType = response.headers.get('content-type');
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                console.warn('Non-JSON response received:', text);
                throw new Error('Invalid response format - expected JSON');
            }
            
            if (!response.ok) {
                const errorMessage = data.message || data.error || `HTTP error! status: ${response.status}`;
                const error = new Error(errorMessage);
                error.status = response.status;
                error.response = data;
                throw error;
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', {
                url: url,
                error: error.message,
                status: error.status,
                response: error.response
            });
            
            // Log to debug panel
            if (Utils.debug) {
                Utils.debug.log('error', 'API request failed', {
                    url: url,
                    error: error.message,
                    status: error.status,
                    response: error.response
                });
            }
            
            // Enhance error with more context
            if (!error.status) {
                error.status = 'NETWORK_ERROR';
            }
            
            throw error;
        }
    },
    
    // GET request
    async get(endpoint, params = {}) {
        return this.request(endpoint, {
            method: 'GET'
        }, params);
    },
    
    // POST request
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    // PUT request
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    // DELETE request
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    },
    
    // Website generation endpoints
    website: {
        // Start website generation process
        async generate(formData) {
            return API.post('process.php', {
                action: 'generate_website',
                ...formData
            });
        },
        
        // Get generation progress
        async getProgress(sessionId) {
            return API.get('process.php', {
                action: 'get_progress',
                session_id: sessionId
            });
        },
        
        // Get generation results
        async getResults(sessionId) {
            return API.get('process.php', {
                action: 'get_results',
                session_id: sessionId
            });
        },
        
        // Test WordPress connection
        async testWordPress(wpData) {
            return API.post('process.php', {
                action: 'test_wordpress',
                ...wpData
            });
        },
        
        // Validate tax code
        async validateTaxCode(taxCode) {
            return API.post('process.php', {
                action: 'validate_tax_code',
                tax_code: taxCode
            });
        }
    },
    
    // Business data endpoints
    business: {
        // Get business information by tax code
        async getInfo(taxCode) {
            return API.get('process.php', {
                action: 'get_business_info',
                tax_code: taxCode
            });
        },
        
        // Search business by name
        async search(query) {
            return API.get('process.php', {
                action: 'search_business',
                query: query
            });
        }
    },
    
    // Content generation endpoints
    content: {
        // Generate sitemap
        async generateSitemap(businessData, preferences) {
            return API.post('process.php', {
                action: 'generate_sitemap',
                business_data: businessData,
                preferences: preferences
            });
        },
        
        // Generate page content
        async generatePage(pageData, businessData) {
            return API.post('process.php', {
                action: 'generate_page',
                page_data: pageData,
                business_data: businessData
            });
        },
        
        // Generate blog articles
        async generateArticles(businessData, count = 5) {
            return API.post('process.php', {
                action: 'generate_articles',
                business_data: businessData,
                count: count
            });
        }
    },
    
    // Image management endpoints
    images: {
        // Search images
        async search(query, limit = 10) {
            return API.get('process.php', {
                action: 'search_images',
                query: query,
                limit: limit
            });
        },
        
        // Download image
        async download(imageData) {
            return API.post('process.php', {
                action: 'download_image',
                image_data: imageData
            });
        },
        
        // Get image suggestions
        async getSuggestions(content) {
            return API.post('process.php', {
                action: 'get_image_suggestions',
                content: content
            });
        }
    },
    
    // WordPress integration endpoints
    wordpress: {
        // Create page
        async createPage(pageData, wpConfig) {
            return API.post('process.php', {
                action: 'create_wp_page',
                page_data: pageData,
                wp_config: wpConfig
            });
        },
        
        // Create post
        async createPost(postData, wpConfig) {
            return API.post('process.php', {
                action: 'create_wp_post',
                post_data: postData,
                wp_config: wpConfig
            });
        },
        
        // Create menu
        async createMenu(menuData, wpConfig) {
            return API.post('process.php', {
                action: 'create_wp_menu',
                menu_data: menuData,
                wp_config: wpConfig
            });
        },
        
        // Upload media
        async uploadMedia(mediaData, wpConfig) {
            return API.post('process.php', {
                action: 'upload_wp_media',
                media_data: mediaData,
                wp_config: wpConfig
            });
        },
        
        // Get site info
        async getSiteInfo(wpConfig) {
            return API.post('process.php', {
                action: 'get_wp_site_info',
                wp_config: wpConfig
            });
        }
    }
};

// WebSocket connection for real-time updates
class WebSocketManager {
    constructor() {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.listeners = new Map();
    }
    
    // Check if WebSocket server is available
    async checkWebSocketAvailability() {
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws`;
            
            return new Promise((resolve) => {
                const testWs = new WebSocket(wsUrl);
                const timeout = setTimeout(() => {
                    testWs.close();
                    resolve(false);
                }, 3000); // 3 second timeout
                
                testWs.onopen = () => {
                    clearTimeout(timeout);
                    testWs.close();
                    resolve(true);
                };
                
                testWs.onerror = () => {
                    clearTimeout(timeout);
                    resolve(false);
                };
            });
        } catch (error) {
            console.warn('WebSocket availability check failed:', error);
            return false;
        }
    }
    
    connect(sessionId) {
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws?session=${sessionId}`;
            
            console.log('Attempting WebSocket connection to:', wsUrl);
            
            // Check if WebSocket is supported
            if (!window.WebSocket) {
                console.warn('WebSocket not supported in this browser');
                this.emit('error', { message: 'WebSocket not supported in this browser' });
                return;
            }
            
            this.ws = new WebSocket(wsUrl);
            
            // Set a connection timeout
            const connectionTimeout = setTimeout(() => {
                if (this.ws && this.ws.readyState === WebSocket.CONNECTING) {
                    console.warn('WebSocket connection timeout');
                    this.ws.close();
                    this.emit('error', { message: 'WebSocket connection timeout' });
                }
            }, 5000); // 5 second timeout
            
            this.ws.onopen = () => {
                clearTimeout(connectionTimeout);
                console.log('WebSocket connected successfully');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                
                // Log to debug panel
                if (Utils.debug) {
                    Utils.debug.log('websocket', 'WebSocket connected', { sessionId });
                }
                
                this.emit('connected');
            };
            
            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log('WebSocket message received:', data);
                    
                    // Log to debug panel
                    if (Utils.debug) {
                        Utils.debug.log('websocket', 'Message received', data);
                    }
                    
                    this.emit(data.type || 'message', data);
                } catch (error) {
                    console.error('Failed to parse WebSocket message:', error);
                    
                    // Log to debug panel
                    if (Utils.debug) {
                        Utils.debug.log('error', 'Failed to parse WebSocket message', error);
                    }
                }
            };
            
            this.ws.onclose = (event) => {
                clearTimeout(connectionTimeout);
                console.log('WebSocket disconnected', { code: event.code, reason: event.reason });
                this.isConnected = false;
                this.emit('disconnected', event);
                
                // Only attempt reconnect if it wasn't a normal closure
                if (event.code !== 1000) {
                    this.attemptReconnect(sessionId);
                }
            };
            
            this.ws.onerror = (error) => {
                clearTimeout(connectionTimeout);
                console.error('WebSocket connection error:', error);
                this.isConnected = false;
                
                // Log to debug panel
                if (Utils.debug) {
                    Utils.debug.log('error', 'WebSocket connection error', error);
                }
                
                this.emit('error', error);
            };
            
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.emit('error', error);
        }
    }
    
    attemptReconnect(sessionId) {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            
            setTimeout(() => {
                this.connect(sessionId);
            }, this.reconnectDelay * this.reconnectAttempts);
        } else {
            console.error('Max reconnection attempts reached');
            this.emit('maxReconnectAttemptsReached');
        }
    }
    
    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.warn('WebSocket is not connected');
        }
    }
    
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
    }
    
    off(event, callback) {
        if (this.listeners.has(event)) {
            const callbacks = this.listeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }
    
    emit(event, data) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${event}:`, error);
                }
            });
        }
    }
    
    disconnect() {
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
    }
}

// Progress tracking
class ProgressTracker {
    constructor() {
        this.steps = [
            { id: 'business', name: 'Collecting Business Data', weight: 20 },
            { id: 'content', name: 'Generating Content', weight: 40 },
            { id: 'images', name: 'Adding Images', weight: 20 },
            { id: 'wordpress', name: 'Building WordPress Site', weight: 20 }
        ];
        this.currentStep = 0;
        this.currentProgress = 0;
        this.callbacks = [];
    }
    
    onProgress(callback) {
        this.callbacks.push(callback);
    }
    
    updateStep(stepId, progress = 100, message = '') {
        const stepIndex = this.steps.findIndex(step => step.id === stepId);
        if (stepIndex === -1) return;
        
        this.currentStep = stepIndex;
        
        // Calculate overall progress
        let totalProgress = 0;
        for (let i = 0; i < this.steps.length; i++) {
            if (i < stepIndex) {
                totalProgress += this.steps[i].weight;
            } else if (i === stepIndex) {
                totalProgress += (this.steps[i].weight * progress) / 100;
            }
        }
        
        this.currentProgress = Math.min(totalProgress, 100);
        
        // Update UI
        this.updateUI(stepId, progress, message);
        
        // Notify callbacks
        this.callbacks.forEach(callback => {
            callback({
                step: stepId,
                stepIndex: stepIndex,
                stepProgress: progress,
                totalProgress: this.currentProgress,
                message: message
            });
        });
    }
    
    updateUI(stepId, progress, message) {
        // Update progress bar
        const progressFill = document.getElementById('progressFill');
        if (progressFill) {
            progressFill.style.width = `${this.currentProgress}%`;
        }
        
        // Update progress details
        const progressDetails = document.getElementById('progressDetails');
        if (progressDetails && message) {
            progressDetails.innerHTML = `<p>${message}</p>`;
        }
        
        // Update step status
        const steps = document.querySelectorAll('.progress-step');
        steps.forEach((step, index) => {
            const stepElement = step.querySelector(`[data-step="${stepId}"]`);
            if (stepElement || step.dataset.step === stepId) {
                if (progress === 100) {
                    step.classList.remove('active');
                    step.classList.add('completed');
                    const statusIcon = step.querySelector('.step-status i');
                    if (statusIcon) {
                        statusIcon.className = 'fas fa-check-circle';
                    }
                } else {
                    step.classList.add('active');
                    step.classList.remove('completed');
                    const statusIcon = step.querySelector('.step-status i');
                    if (statusIcon) {
                        statusIcon.className = 'fas fa-spinner fa-spin';
                    }
                }
            } else if (index < this.currentStep) {
                step.classList.remove('active');
                step.classList.add('completed');
                const statusIcon = step.querySelector('.step-status i');
                if (statusIcon) {
                    statusIcon.className = 'fas fa-check-circle';
                }
            }
        });
    }
    
    complete() {
        this.currentProgress = 100;
        this.updateUI('wordpress', 100, 'Website generation completed!');
        
        // Mark all steps as completed
        const steps = document.querySelectorAll('.progress-step');
        steps.forEach(step => {
            step.classList.remove('active');
            step.classList.add('completed');
            const statusIcon = step.querySelector('.step-status i');
            if (statusIcon) {
                statusIcon.className = 'fas fa-check-circle';
            }
        });
    }
    
    reset() {
        this.currentStep = 0;
        this.currentProgress = 0;
        
        // Reset UI
        const progressFill = document.getElementById('progressFill');
        if (progressFill) {
            progressFill.style.width = '0%';
        }
        
        const progressDetails = document.getElementById('progressDetails');
        if (progressDetails) {
            progressDetails.innerHTML = '<p>Starting website generation...</p>';
        }
        
        const steps = document.querySelectorAll('.progress-step');
        steps.forEach(step => {
            step.classList.remove('active', 'completed');
            const statusIcon = step.querySelector('.step-status i');
            if (statusIcon) {
                statusIcon.className = 'fas fa-clock';
            }
        });
    }
}

// Export for use in other modules
window.API = API;
window.WebSocketManager = WebSocketManager;
window.ProgressTracker = ProgressTracker;

