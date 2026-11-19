/**
 * AI-Powered Document Validation
 * 
 * Real-time document validation using Claude Vision AI
 * Validates: Certificates, ID Cards, Passports
 */

const DocumentValidation = {
    // Configuration
    config: {
        validateOnUpload: true,
        showConfidenceScore: false,
        apiEndpoint: '/portal/accreditation/validate-document',
        timeout: 30000, // 30 seconds
    },

    // Document type mapping
    documentTypes: {
        'educational_certificates[]': 'certificate',
        'id_scan': 'id_scan',
        'passport_biodata_page': 'passport_biodata_page'
    },

    // Validation cache to prevent duplicate validations
    validationCache: new Map(),

    /**
     * Initialize validation for all document upload fields
     */
    init() {
        console.log('[AI Validation] Initializing document validation');

        // Find all file inputs that need validation
        const fileInputs = document.querySelectorAll(
            'input[name="educational_certificates[]"], input[name="id_scan"], input[name="passport_biodata_page"]'
        );

        fileInputs.forEach(input => {
            this.attachValidator(input);
        });

        console.log(`[AI Validation] Attached to ${fileInputs.length} file inputs`);
    },

    /**
     * Attach change event listener to file input
     */
    attachValidator(input) {
        input.addEventListener('change', async (e) => {
            if (!e.target.files || e.target.files.length === 0) {
                return;
            }

            const file = e.target.files[0];
            const documentType = this.getDocumentType(input.name);

            if (!documentType) {
                console.warn('[AI Validation] Unknown document type:', input.name);
                return;
            }

            console.log('[AI Validation] Validating:', file.name, 'as', documentType);

            // Validate the document
            const isValid = await this.validateDocument(file, documentType, input);

            // If invalid, clear the input
            if (!isValid) {
                setTimeout(() => {
                    input.value = '';
                }, 100);
            }
        });
    },

    /**
     * Get document type from input name
     */
    getDocumentType(inputName) {
        // Handle array notation
        if (inputName.includes('[]')) {
            return this.documentTypes[inputName];
        }
        return this.documentTypes[inputName];
    },

    /**
     * Validate document using AI
     */
    async validateDocument(file, documentType, inputElement) {
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            this.showError('File too large', 'Maximum file size is 5MB. Please compress the document and try again.');
            return false;
        }

        // Check cache
        const cacheKey = `${file.name}_${file.size}_${file.lastModified}`;
        if (this.validationCache.has(cacheKey)) {
            console.log('[AI Validation] Using cached result');
            const cached = this.validationCache.get(cacheKey);
            if (cached.is_valid) {
                this.showSuccess(cached.message, cached.confidence);
                return true;
            } else {
                this.showError('Invalid Document', cached.message);
                return false;
            }
        }

        // Show loading state
        this.showLoading(inputElement);

        try {
            // Create FormData
            const formData = new FormData();
            formData.append('document', file);
            formData.append('document_type', documentType);

            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                            document.querySelector('input[name="_token"]')?.value;

            // Make API call
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);

            const response = await fetch(this.config.apiEndpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            // Hide loading state
            this.hideLoading(inputElement);

            // Parse response
            const result = await response.json();

            // Cache result
            this.validationCache.set(cacheKey, result);

            // Handle result
            if (result.is_valid) {
                this.showSuccess(result.message, result.confidence);
                return true;
            } else {
                this.showError('Invalid Document', result.message);
                return false;
            }

        } catch (error) {
            this.hideLoading(inputElement);

            if (error.name === 'AbortError') {
                this.showError('Validation Timeout', 'Document validation took too long. Please try again or contact support.');
            } else {
                console.error('[AI Validation] Error:', error);
                this.showError('Validation Failed', 'Unable to validate document. You may proceed, but manual review will be required.');
                
                // Allow upload on error (fallback mode)
                return true;
            }

            return false;
        }
    },

    /**
     * Show loading indicator
     */
    showLoading(inputElement) {
        const container = inputElement.closest('.form-field') || inputElement.closest('.upload-area')?.parentElement;
        
        if (container) {
            container.classList.add('validating');
            
            // Add loading spinner if not exists
            if (!container.querySelector('.validation-spinner')) {
                const spinner = document.createElement('div');
                spinner.className = 'validation-spinner';
                spinner.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center mt-2">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <small class="text-muted">Validating document with AI...</small>
                    </div>
                `;
                container.appendChild(spinner);
            }
        }
    },

    /**
     * Hide loading indicator
     */
    hideLoading(inputElement) {
        const container = inputElement.closest('.form-field') || inputElement.closest('.upload-area')?.parentElement;
        
        if (container) {
            container.classList.remove('validating');
            const spinner = container.querySelector('.validation-spinner');
            if (spinner) {
                spinner.remove();
            }
        }
    },

    /**
     * Show success message
     */
    showSuccess(message, confidence) {
        const confidenceText = this.config.showConfidenceScore && confidence
            ? `<br><small class="text-muted">Confidence: ${(confidence * 100).toFixed(1)}%</small>`
            : '';

        Swal.fire({
            icon: 'success',
            title: '✓ Document Validated',
            html: `${message}${confidenceText}`,
            toast: true,
            position: 'top-end',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-success-toast'
            }
        });
    },

    /**
     * Show error message
     */
    showError(title, message) {
        Swal.fire({
            icon: 'error',
            title: title,
            html: message,
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#dc3545',
            customClass: {
                popup: 'swal-error-modal'
            }
        });
    },

    /**
     * Get reference images for document types
     */
    async showReferenceImages(documentType) {
        try {
            const response = await fetch(`/portal/accreditation/document-references?type=${documentType}`);
            const data = await response.json();

            if (data.success && data.reference) {
                const ref = data.reference;

                Swal.fire({
                    title: ref.title,
                    html: `
                        <p class="text-muted">${ref.description}</p>
                        <div class="text-start mt-3">
                            <strong>Document Characteristics:</strong>
                            <ul class="mt-2">
                                ${ref.characteristics.map(c => `<li>${c}</li>`).join('')}
                            </ul>
                        </div>
                        ${ref.example_path ? `
                            <img src="${ref.example_path}" alt="${ref.title}" class="img-fluid mt-3 border rounded">
                        ` : ''}
                    `,
                    width: 600,
                    showCloseButton: true,
                    confirmButtonText: 'Got it',
                });
            }
        } catch (error) {
            console.error('[AI Validation] Error fetching references:', error);
        }
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Swal !== 'undefined') {
            DocumentValidation.init();
        } else {
            console.warn('[AI Validation] SweetAlert2 not loaded. Validation disabled.');
        }
    });
} else {
    if (typeof Swal !== 'undefined') {
        DocumentValidation.init();
    }
}

// Export for manual initialization if needed
window.DocumentValidation = DocumentValidation;
