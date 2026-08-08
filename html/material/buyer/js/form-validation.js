// Form Validation - Comprehensive validation logic for buyer application form

const FormValidation = {
    fieldMaxLengths: {
        firstName: 100,
        lastName: 100,
        idNumber: 13,
        spouseFullName: 150,
        spouseIdPassport: 20,
        spouseContactNumber: 20,
        spouseEmail: 150,
        erfNumber: 50,
        streetName: 200,
        suburb: 100,
        location: 150,
        emailAddress: 150,
        mobileNumber: 20,
        poBox: 100,
        nokFullName: 150,
        nokContactNumber: 20,
        nokEmailAddress: 150,
        nokErfNumber: 50,
        nokSuburb: 100,
        nokLocation: 150,
        employerName: 150,
        jobTitle: 100,
        workAddress: 200,
        workPhone: 20,
        employmentDuration: 100,
        supervisorName: 150,
        supervisorContact: 20,
        'preferredLocation[]': 150,
        'preferredSuburb[]': 100,
        additionalRequirements: 2000,
        signatureLocation: 100
    },
    // Validation patterns
    patterns: {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        phone: /^(\+264|0)\s?\d{2}\s?\d{3,4}\s?\d{3}$/,
        idNumber: /^[0-9]{10,13}$/,
        passportNumber: /^[A-Z0-9]{6,9}$/,
        erfNumber: /^[0-9]+$/,
        postalCode: /^[0-9]{4,5}$/
    },

    // Validation messages
    messages: {
        required: 'This field is required',
        email: 'Please enter a valid email address',
        phone: 'Please enter a valid Namibian phone number',
        idNumber: 'ID number must be 10-13 digits',
        passportNumber: 'Passport number must be 6-9 alphanumeric characters',
        dateOfBirth: 'You must be at least 18 years old',
        currency: 'Please enter a valid amount',
        fileSize: 'File size must not exceed {maxSize}MB',
        fileType: 'Please select a file of type: {types}',
        minLength: 'Must be at least {min} characters',
        maxLength: 'Must not exceed {max} characters',
        numeric: 'Please enter a valid number',
        positiveNumber: 'Amount must be greater than zero',
        erfNumber: 'ERF number must contain only numbers',
        future: 'Date cannot be in the future',
        past: 'Date must be in the past'
    },

    // Current step being validated
    currentStep: 1,

    // Validation state for each step
    stepValidation: {
        1: false, 2: false, 3: false, 4: false,
        5: false, 6: false, 7: false, 8: false
    },

    // Initialize validation
    init() {
        this.applyLengthConstraints();
        this.setupValidationEvents();
    },

    applyLengthConstraints() {
        document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(field => {
            const fallback = field.tagName === 'TEXTAREA' ? 2000 : 255;
            const limit = this.fieldMaxLengths[field.name] || this.fieldMaxLengths[field.id] || fallback;
            if (!field.hasAttribute('maxlength')) field.maxLength = limit;
        });
    },

    characterLength(value) {
        return Array.from(String(value).normalize('NFC')).length;
    },

    // Setup validation events
    setupValidationEvents() {
        // Add real-time validation to all form inputs
        const formInputs = document.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            if (input.dataset.validationBound === 'true') {
                return;
            }
            input.dataset.validationBound = 'true';

            // Validate on blur
            input.addEventListener('blur', (e) => {
                this.validateField(e.target);
            });

            // Keep invalid styling visible until the applicant actually edits
            // the value, then revalidate immediately.
            input.addEventListener('input', (e) => {
                if (
                    e.target.classList.contains('is-invalid')
                    || e.target.type === 'email'
                    || e.target.type === 'tel'
                    || e.target.name === 'idNumber'
                ) {
                    this.validateField(e.target);
                }
            });

            if (input.matches('select, input[type="checkbox"], input[type="file"]')) {
                input.addEventListener('change', (e) => {
                    this.validateField(e.target);
                });
            }
        });
    },

    // Setup conditional validation
    setupConditionalValidation() {
        // Marital status conditional validation
        const maritalStatusSelect = document.getElementById('maritalStatus');
        if (maritalStatusSelect) {
            maritalStatusSelect.addEventListener('change', (e) => {
                this.toggleSpouseFields(e.target.value === 'married');
            });
        }

        // Employment type conditional validation
        const employmentTypeSelect = document.getElementById('employmentType');
        if (employmentTypeSelect) {
            employmentTypeSelect.addEventListener('change', (e) => {
                this.setupEmploymentValidation(e.target.value);
            });
        }

        // Region change updates towns
        const regionSelects = document.querySelectorAll('select[name="region"], select[name="nokRegion"]');
        regionSelects.forEach(select => {
            select.addEventListener('change', (e) => {
                this.updateTownOptions(e.target);
            });
        });
    },

    // Validate individual field
    validateField(field) {
        const value = field.type === 'checkbox'
            ? (field.checked ? field.value : '')
            : field.value.trim();
        const fieldName = field.name || field.id;
        let isValid = true;
        let errorMessage = '';

        // Skip validation if field is not required and empty
        if (!field.hasAttribute('required') && !value) {
            this.clearFieldValidation(field);
            return true;
        }

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = this.messages.required;
        }

        if (value && isValid && field.maxLength > 0 && this.characterLength(value) > field.maxLength) {
            isValid = false;
            errorMessage = this.messages.maxLength.replace('{max}', field.maxLength);
        }

        // Specific field validations
        if (value && isValid) {
            switch (field.type) {
                case 'email':
                    isValid = this.patterns.email.test(value);
                    errorMessage = isValid ? '' : this.messages.email;
                    break;
                
                case 'tel':
					// Remove spaces for validation
					const normalized = value.replace(/\s+/g, '');

					// Handle +264 format
					let testValue = normalized;
					if (normalized.startsWith('+264')) {
						testValue = '0' + normalized.substring(4);
					}

					// Validate as 10-digit Namibian number starting with 0
					isValid = /^0\d{9}$/.test(testValue);
					errorMessage = isValid ? '' : this.messages.phone;
					break;
                
                case 'date':
                    if (fieldName.includes('dateOfBirth') || fieldName === 'spouseDateOfBirth') {
                        const age = DateUtils.calculateAge(value);
                        isValid = age >= 18;
                        errorMessage = isValid ? '' : this.messages.dateOfBirth;
                    } else if (fieldName.includes('Date') && fieldName !== 'signatureDate') {
                        isValid = new Date(value) <= new Date();
                        errorMessage = isValid ? '' : this.messages.future;
                    }
                    break;
                
                case 'number':
                    const numValue = parseFloat(value);
                    const allowsZero = fieldName === 'downPayment';
                    isValid = !isNaN(numValue) && (allowsZero ? numValue >= 0 : numValue > 0);
                    errorMessage = isValid
                        ? ''
                        : (allowsZero ? this.messages.currency : this.messages.positiveNumber);
                    break;
            }

            // Custom field validations
            if (isValid) {
                switch (fieldName) {
                    case 'idNumber':
                        if (field.form && field.form.idType) {
                            const idType = field.form.idType.value;
                            if (idType === 'passport') {
                                isValid = this.patterns.passportNumber.test(value);
                                errorMessage = isValid ? '' : this.messages.passportNumber;
                            } else {
                                isValid = this.patterns.idNumber.test(value);
                                errorMessage = isValid ? '' : this.messages.idNumber;
                            }
                        }
                        break;
                    
                    case 'spouseIdPassport':
                        isValid = this.patterns.idNumber.test(value) || this.patterns.passportNumber.test(value);
                        errorMessage = isValid ? '' : 'Please enter a valid ID or passport number';
                        break;
                    
                    case 'erfNumber':
                    case 'nokErfNumber':
                        isValid = this.patterns.erfNumber.test(value);
                        errorMessage = isValid ? '' : this.messages.erfNumber;
                        break;
                    
                    case 'monthlyIncome':
                        const income = parseFloat(value);
                        isValid = !isNaN(income) && income >= 0;
                        errorMessage = isValid ? '' : 'Please enter a valid income amount';
                        break;
                }
            }
        }

        // Apply validation styling
        if (isValid) {
            this.setFieldValid(field);
        } else {
            this.setFieldInvalid(field, errorMessage);
        }

        return isValid;
    },

    // Validate entire step
    validateStep(stepNumber, options = {}) {
        const stepElement = document.getElementById(`step-${stepNumber}`);
        if (!stepElement) return false;

        const shouldFocus = options.focus !== false;
        const requiredFields = stepElement.querySelectorAll('input[required], select[required], textarea[required]');
        let isStepValid = true;
        let firstInvalidField = null;

        // Validate all required fields in the step
        requiredFields.forEach(field => {
            const isFieldValid = this.validateField(field);
            if (!isFieldValid) {
                isStepValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            }
        });

        // Additional step-specific validations
        switch (stepNumber) {
            case 2: // Marital Status
                if (document.getElementById('maritalStatus').value === 'married') {
                    const spouseFields = ['spouseFullName', 'spouseIdPassport', 'spouseDateOfBirth'];
                    spouseFields.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        if (field && !this.validateField(field)) {
                            isStepValid = false;
                            if (!firstInvalidField) firstInvalidField = field;
                        }
                    });
                }
                break;
            
            case 5: // Employment Details
                const employmentType = document.getElementById('employmentType').value;
                if (employmentType && employmentType !== 'unemployed' && employmentType !== 'pensioner') {
                    const empFields = ['employerName', 'monthlyIncome'];
                    empFields.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        if (field && !this.validateField(field)) {
                            isStepValid = false;
                            if (!firstInvalidField) firstInvalidField = field;
                        }
                    });
                }
                break;
            
            case 6: // Property Purchase
                const propertyValue = parseFloat(document.getElementById('propertyValue').value || 0);
                const downPayment = parseFloat(document.getElementById('downPayment').value || 0);
                
                if (downPayment > propertyValue) {
                    const field = document.getElementById('downPayment');
                    this.setFieldInvalid(field, 'Down payment cannot exceed property value');
                    isStepValid = false;
                    if (!firstInvalidField) firstInvalidField = field;
                }
                break;
            
            case 7: // Document Upload
                const requiredDocs = ['id_passport', 'proof_of_income', 'bank_statements'];
                if (document.getElementById('maritalStatus')?.value === 'married') {
                    requiredDocs.push('marriage_certificate');
                }

                stepElement.querySelectorAll('input[type="file"]').forEach(fileInput => {
                    const docType = fileInput.name;
                    const files = Array.from(fileInput.files || []);
                    const errors = [];

                    if (requiredDocs.includes(docType) && files.length === 0) {
                        errors.push('This document is required');
                    }

                    files.forEach(file => {
                        const validation = this.validateFile(file, docType);
                        errors.push(...validation.errors.map(error => `${file.name}: ${error}`));
                    });

                    if (errors.length > 0) {
                        isStepValid = false;
                        this.showDocumentError(docType, errors.join(' '));
                        if (!firstInvalidField) {
                            firstInvalidField = fileInput
                                .closest('[data-doc-type]')
                                ?.querySelector('.file-upload-container');
                        }
                    } else {
                        this.clearDocumentError(docType);
                    }
                });
                break;
            
            case 8: // Declaration
                document.querySelectorAll('input[name="declarations"]').forEach(field => {
                    if (!this.validateField(field)) {
                        isStepValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    }
                });
                break;
        }

        // Update step validation state
        this.stepValidation[stepNumber] = isStepValid;

        // Scroll to first invalid field if validation failed
        if (!isStepValid && firstInvalidField && shouldFocus) {
            this.scrollToField(firstInvalidField);
        }

        return isStepValid;
    },

    // Set field as valid
    setFieldValid(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        field.setAttribute('aria-invalid', 'false');
        this.clearFieldError(field);
    },

    // Set field as invalid
    setFieldInvalid(field, message) {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        this.showFieldError(field, message);
    },

    // Clear field validation
    clearFieldValidation(field) {
        field.classList.remove('is-valid', 'is-invalid');
        field.removeAttribute('aria-invalid');
        this.clearFieldError(field);
    },

    // Show field error
    showFieldError(field, message) {
        let errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            field.parentNode.appendChild(errorElement);
        }
        if (!errorElement.id) {
            errorElement.id = `${field.id || field.name}-error`;
        }
        field.setAttribute('aria-describedby', errorElement.id);
        errorElement.setAttribute('role', 'alert');
        if (errorElement) {
            errorElement.textContent = message;
        }
    },

    // Clear field error
    clearFieldError(field) {
        const errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.textContent = '';
        }
    },

    // Show document error
    showDocumentError(docType, message) {
        const container = document.querySelector(`[data-doc-type="${docType}"]`);
        if (container) {
            let errorElement = container.querySelector('.document-error');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'document-error text-danger small mt-2';
                container.appendChild(errorElement);
            }
            errorElement.textContent = message;
            errorElement.hidden = false;

            const uploadContainer = container.querySelector('.file-upload-container');
            uploadContainer?.classList.add('is-invalid');
            uploadContainer?.classList.remove('has-files');
            uploadContainer?.setAttribute('aria-invalid', 'true');
        }
    },

    // Clear a stale document error as soon as a valid file is selected.
    clearDocumentError(docType) {
        const container = document.querySelector(`[data-doc-type="${docType}"]`);
        if (!container) {
            return;
        }

        const errorElement = container.querySelector('.document-error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.hidden = true;
        }

        const uploadContainer = container.querySelector('.file-upload-container');
        uploadContainer?.classList.remove('is-invalid');
        uploadContainer?.setAttribute('aria-invalid', 'false');
    },

    // Toggle spouse fields based on marital status
    toggleSpouseFields(showFields) {
        const spouseSection = document.getElementById('spouseDetails');
        const spouseFields = spouseSection.querySelectorAll('input, select');
        
        if (showFields) {
            spouseSection.classList.remove('d-none');
            spouseFields.forEach(field => {
                field.setAttribute('required', 'required');
            });
        } else {
            spouseSection.classList.add('d-none');
            spouseFields.forEach(field => {
                field.removeAttribute('required');
                field.value = '';
                this.clearFieldValidation(field);
            });
        }
    },

    // Setup employment-specific validation
    setupEmploymentValidation(employmentType) {
        const employmentFields = ['employerName', 'jobTitle', 'workAddress', 'monthlyIncome'];
        
        employmentFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                if (employmentType === 'unemployed' || employmentType === 'pensioner') {
                    field.removeAttribute('required');
                    this.clearFieldValidation(field);
                } else {
                    field.setAttribute('required', 'required');
                }
            }
        });
    },

    // Update town options based on selected region
    updateTownOptions(regionSelect) {
        const selectedRegion = regionSelect.value;
        const townSelectName = regionSelect.name === 'region' ? 'town' : 'nokTown';
        const townSelect = document.getElementById(townSelectName);
        
        if (townSelect && selectedRegion) {
            // Clear existing options
            townSelect.innerHTML = '<option value="">Select Town</option>';
            
            // Add towns for selected region
            const towns = FormData.townsByRegion[selectedRegion] || [];
            towns.forEach(town => {
                const option = document.createElement('option');
                option.value = town;
                option.textContent = town;
                townSelect.appendChild(option);
            });
        }
    },

    // Validate file upload
    validateFile(file, docType) {
        const docConfig = FormData.documentTypes[docType];
        if (!docConfig) return { isValid: true };

        const errors = [];

        // Check file size
        if (file.size > docConfig.maxSize * 1024 * 1024) {
            errors.push(this.messages.fileSize.replace('{maxSize}', docConfig.maxSize));
        }

        // Check file type
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        if (!docConfig.acceptedTypes.includes(fileExtension)) {
            errors.push(this.messages.fileType.replace('{types}', docConfig.acceptedTypes.join(', ')));
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    },

    // Scroll to field with error
    scrollToField(field) {
        field.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        
        // Add shake animation
        field.classList.add('shake');
        setTimeout(() => {
            field.classList.remove('shake');
        }, 500);
        
        // Focus the field
        setTimeout(() => {
            field.focus();
        }, 300);
    },

    // Show alert message
    showAlert(message, type = 'info') {
        // Create or update alert element
        let alertElement = document.querySelector('.validation-alert');
        if (!alertElement) {
            alertElement = document.createElement('div');
            alertElement.className = 'alert validation-alert alert-dismissible fade show';
            alertElement.innerHTML = `
                <span class="alert-message"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert at top of current step
            const currentStep = document.getElementById(`step-${this.currentStep}`);
            if (currentStep) {
                currentStep.insertBefore(alertElement, currentStep.firstChild);
            }
        }
        
        // Update alert content and type
        alertElement.className = `alert validation-alert alert-${type} alert-dismissible fade show`;
        alertElement.querySelector('.alert-message').textContent = message;
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertElement.parentNode) {
                alertElement.remove();
            }
        }, 5000);
    },

    // Get step validation summary
    getValidationSummary() {
        const summary = {
            totalSteps: 8,
            validSteps: Object.values(this.stepValidation).filter(valid => valid).length,
            invalidSteps: []
        };

        Object.entries(this.stepValidation).forEach(([step, isValid]) => {
            if (!isValid) {
                summary.invalidSteps.push(parseInt(step));
            }
        });

        return summary;
    },

    // Reset validation for step
    resetStepValidation(stepNumber) {
        const stepElement = document.getElementById(`step-${stepNumber}`);
        if (stepElement) {
            const fields = stepElement.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                this.clearFieldValidation(field);
            });
        }
        this.stepValidation[stepNumber] = false;
    }
};

// Initialize validation when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    FormValidation.init();
});
