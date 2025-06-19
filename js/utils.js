// Utility functions
const Utils = {
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Format date
    formatDate(date) {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    },

    // Generate random ID
    generateId() {
        return Math.random().toString(36).substr(2, 9);
    },

    // Validate email
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Validate URL
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    },

    // Validate Vietnam tax code
    isValidTaxCode(taxCode) {
        // Vietnam tax code is typically 10 digits
        const taxCodeRegex = /^\d{10}$/;
        return taxCodeRegex.test(taxCode);
    },

    // Sanitize HTML
    sanitizeHtml(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    },

    // Copy to clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                return true;
            } catch (err) {
                return false;
            } finally {
                document.body.removeChild(textArea);
            }
        }
    },

    // Format file size
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    // Smooth scroll to element
    scrollToElement(element, offset = 0) {
        const elementPosition = element.offsetTop - offset;
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    },

    // Get query parameter
    getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    },

    // Set query parameter
    setQueryParam(param, value) {
        const url = new URL(window.location);
        url.searchParams.set(param, value);
        window.history.pushState({}, '', url);
    },

    // Remove query parameter
    removeQueryParam(param) {
        const url = new URL(window.location);
        url.searchParams.delete(param);
        window.history.pushState({}, '', url);
    },

    // Local storage helpers
    storage: {
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (err) {
                console.error('Failed to save to localStorage:', err);
                return false;
            }
        },

        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (err) {
                console.error('Failed to read from localStorage:', err);
                return defaultValue;
            }
        },

        remove(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (err) {
                console.error('Failed to remove from localStorage:', err);
                return false;
            }
        },

        clear() {
            try {
                localStorage.clear();
                return true;
            } catch (err) {
                console.error('Failed to clear localStorage:', err);
                return false;
            }
        }
    },

    // Animation helpers
    animation: {
        fadeIn(element, duration = 300) {
            element.style.opacity = '0';
            element.style.display = 'block';
            
            const start = performance.now();
            
            function animate(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                element.style.opacity = progress;
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            }
            
            requestAnimationFrame(animate);
        },

        fadeOut(element, duration = 300) {
            const start = performance.now();
            const startOpacity = parseFloat(getComputedStyle(element).opacity);
            
            function animate(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                element.style.opacity = startOpacity * (1 - progress);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    element.style.display = 'none';
                }
            }
            
            requestAnimationFrame(animate);
        },

        slideDown(element, duration = 300) {
            element.style.height = '0';
            element.style.overflow = 'hidden';
            element.style.display = 'block';
            
            const targetHeight = element.scrollHeight;
            const start = performance.now();
            
            function animate(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                element.style.height = (targetHeight * progress) + 'px';
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    element.style.height = 'auto';
                    element.style.overflow = 'visible';
                }
            }
            
            requestAnimationFrame(animate);
        },

        slideUp(element, duration = 300) {
            const startHeight = element.offsetHeight;
            const start = performance.now();
            
            element.style.overflow = 'hidden';
            
            function animate(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                element.style.height = (startHeight * (1 - progress)) + 'px';
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    element.style.display = 'none';
                    element.style.height = 'auto';
                    element.style.overflow = 'visible';
                }
            }
            
            requestAnimationFrame(animate);
        }
    },

    // Toast notification system
    toast: {
        show(message, type = 'info', duration = 5000) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="toast-icon ${this.getIcon(type)}"></i>
                    <span class="toast-message">${Utils.sanitizeHtml(message)}</span>
                    <button class="toast-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Auto remove
            if (duration > 0) {
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }
            
            return toast;
        },

        getIcon(type) {
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            return icons[type] || icons.info;
        },

        success(message, duration) {
            return this.show(message, 'success', duration);
        },

        error(message, duration) {
            return this.show(message, 'error', duration);
        },

        warning(message, duration) {
            return this.show(message, 'warning', duration);
        },

        info(message, duration) {
            return this.show(message, 'info', duration);
        }
    },

    // Modal system
    modal: {
        show(title, content, actions = []) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">${Utils.sanitizeHtml(title)}</h3>
                        <button class="modal-close" onclick="this.closest('.modal').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    <div class="modal-actions">
                        ${actions.map(action => `
                            <button class="btn-${action.type || 'secondary'}" onclick="${action.onclick || ''}">
                                ${action.text}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
            // Close on escape key
            const escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
            
            // Trigger animation
            setTimeout(() => modal.classList.add('show'), 100);
            
            return modal;
        },

        confirm(title, message, onConfirm, onCancel) {
            return this.show(title, message, [
                {
                    text: 'Cancel',
                    type: 'secondary',
                    onclick: `this.closest('.modal').remove(); ${onCancel ? onCancel : ''}`
                },
                {
                    text: 'Confirm',
                    type: 'primary',
                    onclick: `this.closest('.modal').remove(); ${onConfirm || ''}`
                }
            ]);
        }
    },

    // Form validation helpers
    validation: {
        rules: {
            required: (value) => value.trim() !== '',
            email: (value) => Utils.isValidEmail(value),
            url: (value) => Utils.isValidUrl(value),
            taxCode: (value) => Utils.isValidTaxCode(value),
            minLength: (min) => (value) => value.length >= min,
            maxLength: (max) => (value) => value.length <= max,
            pattern: (regex) => (value) => regex.test(value)
        },

        validate(element, rules) {
            const value = element.value;
            const errors = [];
            
            for (const rule of rules) {
                if (typeof rule === 'function') {
                    if (!rule(value)) {
                        errors.push('Invalid value');
                    }
                } else if (typeof rule === 'object') {
                    if (!rule.validator(value)) {
                        errors.push(rule.message);
                    }
                }
            }
            
            const formGroup = element.closest('.form-group');
            const existingError = formGroup.querySelector('.form-error');
            
            if (errors.length > 0) {
                formGroup.classList.add('error');
                formGroup.classList.remove('success');
                
                if (!existingError) {
                    const errorElement = document.createElement('div');
                    errorElement.className = 'form-error';
                    errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errors[0]}`;
                    formGroup.appendChild(errorElement);
                }
            } else {
                formGroup.classList.remove('error');
                formGroup.classList.add('success');
                
                if (existingError) {
                    existingError.remove();
                }
            }
            
            return errors.length === 0;
        }
    },

    // Debug functionality
    debug: {
        panel: null,
        errors: [],
        apiResponses: [],
        webSocketEvents: [],
        progressData: [],
        maxEntries: 50,

        init() {
            this.panel = document.getElementById('debugPanel');
            this.errorsContainer = document.getElementById('debugErrors');
            this.apiContainer = document.getElementById('debugApi');
            this.webSocketContainer = document.getElementById('debugWebSocket');
            this.progressContainer = document.getElementById('debugProgress');
        },

        log(type, message, data = null) {
            const timestamp = new Date().toLocaleTimeString();
            const entry = {
                timestamp,
                type,
                message,
                data
            };

            switch (type) {
                case 'error':
                    this.errors.unshift(entry);
                    if (this.errors.length > this.maxEntries) {
                        this.errors.pop();
                    }
                    this.updateErrorsDisplay();
                    break;
                case 'api':
                    this.apiResponses.unshift(entry);
                    if (this.apiResponses.length > this.maxEntries) {
                        this.apiResponses.pop();
                    }
                    this.updateApiDisplay();
                    break;
                case 'websocket':
                    this.webSocketEvents.unshift(entry);
                    if (this.webSocketEvents.length > this.maxEntries) {
                        this.webSocketEvents.pop();
                    }
                    this.updateWebSocketDisplay();
                    break;
                case 'progress':
                    this.progressData.unshift(entry);
                    if (this.progressData.length > this.maxEntries) {
                        this.progressData.pop();
                    }
                    this.updateProgressDisplay();
                    break;
                case 'info':
                    // Log info to console only
                    console.log(`[DEBUG INFO] ${message}`, data);
                    break;
            }

            // Also log to console for all types
            if (type !== 'info') {
                console.log(`[DEBUG ${type.toUpperCase()}]`, message, data);
            }
        },

        updateErrorsDisplay() {
            if (!this.errorsContainer) return;
            
            this.errorsContainer.innerHTML = this.errors.map(entry => `
                <div class="error">
                    <strong>${entry.timestamp}</strong>: ${entry.message}
                    ${entry.data ? `<br><small>${JSON.stringify(entry.data, null, 2)}</small>` : ''}
                </div>
            `).join('');
        },

        updateApiDisplay() {
            if (!this.apiContainer) return;
            
            this.apiContainer.innerHTML = this.apiResponses.map(entry => `
                <div class="info">
                    <strong>${entry.timestamp}</strong>: ${entry.message}
                    ${entry.data ? `<br><small>${JSON.stringify(entry.data, null, 2)}</small>` : ''}
                </div>
            `).join('');
        },

        updateWebSocketDisplay() {
            if (!this.webSocketContainer) return;
            
            this.webSocketContainer.innerHTML = this.webSocketEvents.map(entry => `
                <div class="info">
                    <strong>${entry.timestamp}</strong>: ${entry.message}
                    ${entry.data ? `<br><small>${JSON.stringify(entry.data, null, 2)}</small>` : ''}
                </div>
            `).join('');
        },

        updateProgressDisplay() {
            if (!this.progressContainer) return;
            
            this.progressContainer.innerHTML = this.progressData.map(entry => {
                const data = entry.data;
                let html = `<div class="info">
                    <strong>${entry.timestamp}</strong>: ${entry.message}`;
                
                if (data && data.debug_info) {
                    const debugInfo = data.debug_info;
                    html += `<br><div class="debug-details">
                        <strong>Step:</strong> ${debugInfo.current_step} (${debugInfo.step_progress}%)<br>
                        <strong>Status:</strong> ${debugInfo.status_message}<br>
                        <strong>Last Updated:</strong> ${debugInfo.last_updated}`;
                    
                    if (debugInfo.step_details && debugInfo.step_details.step) {
                        const stepDetails = debugInfo.step_details;
                        html += `<br><strong>Step Details:</strong> ${stepDetails.description}`;
                        
                        if (stepDetails.step === 'business') {
                            html += `<br><strong>Cache Status:</strong> ${stepDetails.cache_status}`;
                            if (stepDetails.api_responses) {
                                html += `<br><strong>API Sources:</strong> `;
                                const sources = Object.entries(stepDetails.api_responses).map(([source, info]) => 
                                    `${source}: ${info.cached ? 'cached' : info.status}`
                                ).join(', ');
                                html += sources;
                            }
                        }
                    }
                    
                    if (debugInfo.recent_logs && debugInfo.recent_logs.length > 0) {
                        html += `<br><strong>Recent Logs:</strong><br>`;
                        debugInfo.recent_logs.slice(0, 3).forEach(log => {
                            html += `<small>${log.timestamp}: ${log.message}</small><br>`;
                        });
                    }
                    
                    html += `</div>`;
                }
                
                html += `</div>`;
                return html;
            }).join('');
        },

        clear() {
            this.errors = [];
            this.apiResponses = [];
            this.webSocketEvents = [];
            this.progressData = [];
            this.updateErrorsDisplay();
            this.updateApiDisplay();
            this.updateWebSocketDisplay();
            this.updateProgressDisplay();
        }
    }
};

