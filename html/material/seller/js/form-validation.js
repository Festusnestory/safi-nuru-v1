// Form Validation - Comprehensive validation logic for Seller Form

const FormValidation = {
    // Validation rules for each field
    validationRules: {
        // Personal Details
        surname: { required: true, minLength: 2, maxLength: 100, pattern: /^[\p{L}\p{M}\p{Zs}'’ʼ\-‐‑]+$/u, patternMessage: 'Use letters, spaces, apostrophes, or hyphens only.' },
        firstName: { required: true, minLength: 2, maxLength: 100, pattern: /^[\p{L}\p{M}\p{Zs}'’ʼ\-‐‑]+$/u, patternMessage: 'Use letters, spaces, apostrophes, or hyphens only.' },
        middleName: { maxLength: 100, pattern: /^[\p{L}\p{M}\p{Zs}'’ʼ\-‐‑]*$/u, patternMessage: 'Use letters, spaces, apostrophes, or hyphens only.' },
        maidenName: { maxLength: 100, pattern: /^[\p{L}\p{M}\p{Zs}'’ʼ\-‐‑]*$/u, patternMessage: 'Use letters, spaces, apostrophes, or hyphens only.' },
        dateOfBirth: { required: true, type: 'date', minAge: 18, maxAge: 120 },
        idType: { required: true },
        idNumber: { required: true, custom: 'validateIdNumber' },
        nationality: { required: true },
        gender: { required: true },

        // Marital Status
        maritalStatus: { required: true },

        // Spouse Details (conditional)
        spouseSurname: { conditional: 'maritalStatus=Married', required: true, minLength: 2, maxLength: 100, pattern: /^[\p{L}\p{M}\p{Zs}'’ʼ\-‐‑]+$/u, patternMessage: 'Use letters, spaces, apostrophes, or hyphens only.' },
        spouseFirstName: { conditional: 'maritalStatus=Married', required: true, minLength: 2, maxLength: 100, pattern: /^[\p{L}\p{M}\p{Zs}'’ʼ\-‐‑]+$/u, patternMessage: 'Use letters, spaces, apostrophes, or hyphens only.' },
        spouseDateOfBirth: { conditional: 'maritalStatus=Married', required: true, type: 'date', minAge: 18, maxAge: 120 },
        spouseIdType: { conditional: 'maritalStatus=Married', required: true },
        spouseIdNumber: { conditional: 'maritalStatus=Married', required: true, custom: 'validateSpouseIdNumber' },
        spouseNationality: { conditional: 'maritalStatus=Married', required: true },
        spouseGender: { conditional: 'maritalStatus=Married', required: true },

        // Residential Address
        streetName: { required: true, minLength: 3, maxLength: 200 },
        region: { required: true },
        town: { required: true },
        email: { required: true, maxLength: 190, type: 'email' },
        mobileNumber: { required: true, maxLength: 20, custom: 'validatePhoneNumber' },

        // Next of Kin
        nokSurname: { required: true, minLength: 2, maxLength: 100, pattern: /^[\p{L}\p{M}\p{Zs}'’ʼ\-‐‑]+$/u, patternMessage: 'Use letters, spaces, apostrophes, or hyphens only.' },
        nokFirstName: { required: true, minLength: 2, maxLength: 100, pattern: /^[\p{L}\p{M}\p{Zs}'’ʼ\-‐‑]+$/u, patternMessage: 'Use letters, spaces, apostrophes, or hyphens only.' },
        nokContactNumber: { required: true, maxLength: 20, custom: 'validatePhoneNumber' },
        nokEmail: { required: true, maxLength: 190, type: 'email' },
        nokStreetName: { required: true, minLength: 3, maxLength: 200 },
        nokRegion: { required: true },
        nokTown: { required: true },

        // Sale Type
        saleType: { required: true },

        // Property Developer (conditional)

        // Property Details - only required for Individual sales. When
        // saleType=Property Development, #individualPropertyDetails is
        // hidden and cleared by SellerForm.toggleIndividualPropertyStep(),
        // but that only touches the DOM's required attribute; validateStep()
        // checks this static rules dictionary instead, so without the
        // conditional here these fields stayed unconditionally required and
        // permanently blocked every Property Development submission from
        // ever passing step 6 in the real wizard.
        propertyDetailType: { conditional: 'saleType=Individual', required: true },
        landType: { conditional: 'saleType=Individual', required: true },
        landSize: { conditional: 'saleType=Individual', required: true, type: 'number', min: 1 },
        sellingPrice: { conditional: 'saleType=Individual', required: true, custom: 'validateCurrency' },
        propertyStreetName: { conditional: 'saleType=Individual', required: true, minLength: 3, maxLength: 200 },
        propertyRegion: { conditional: 'saleType=Individual', required: true },
        propertyTown: { conditional: 'saleType=Individual', required: true },

        // Existing Property Details (conditional)
        houseSize: { conditional: 'landType=Existing Property', type: 'number', min: 1 },
        rooms: { conditional: 'landType=Existing Property', type: 'number', min: 1, max: 50 },
        bathrooms: { conditional: 'landType=Existing Property', type: 'number', min: 1, max: 20 },

        // Documents (handled separately for file validation)
        idDocument: { required: true, type: 'file' },
        proofOfResidence: { required: true, type: 'file' },
        titleDeed: { required: true, type: 'file' },
        marriageCertificateDoc: { conditional: 'maritalStatus=Married', required: true, type: 'file' },

        // Property Images
        propertyImages: { required: true, type: 'file', multiple: true },

        // Declaration
        certificationDeclaration: { required: true, type: 'checkbox' },
        authorizationDeclaration: { required: true, type: 'checkbox' },
        indemnificationDeclaration: { required: true, type: 'checkbox' },
        commissionFeesDeclaration: { required: true, type: 'checkbox' },
        propertyRightsDeclaration: { required: true, type: 'checkbox' },
        signatureLocation: { required: true, minLength: 2, maxLength: 100 },
        signatureDate: { required: true, type: 'date' },
        signatureType: { required: true },
        signatureFile: { conditional: 'signatureType=upload', required: true, type: 'file' }
    },

    // Error messages
    errorMessages: {
        required: 'This field is required',
        minLength: 'Must be at least {min} characters long',
        maxLength: 'Must be no more than {max} characters long',
        pattern: 'Please enter a valid value',
        email: 'Please enter a valid email address',
        number: 'Please enter a valid number',
        min: 'Value must be at least {min}',
        max: 'Value must be no more than {max}',
        minAge: 'Must be at least {min} years old',
        maxAge: 'Must be no more than {max} years old',
        date: 'Please enter a valid date',
        file: 'Please select a file',
        fileSize: 'File size must be less than {max}MB',
        fileType: 'File type not allowed. Allowed types: {types}',
        phone: 'Please enter a valid Namibian phone number',
        currency: 'Please enter a valid amount',
        idNumber: 'Please enter a valid ID number',
        passport: 'Please enter a valid passport number',
        checkbox: 'You must agree to this declaration',
        otp: 'Please enter a 6-digit OTP code'
    },

    // File type restrictions
    allowedFileTypes: {
        documents: ['pdf', 'jpg', 'jpeg', 'png'],
        images: ['jpg', 'jpeg', 'png', 'webp'],
        videos: ['mp4', 'mov', 'avi'],
        signature: ['jpg', 'jpeg', 'png']
    },

    // File size limits (in MB)
    fileSizeLimits: {
        documents: 10,
        images: 10,
        videos: 25,
        signature: 5
    },

    // Initialize validation
    init() {
        this.applyLengthConstraints();
        this.setupRealTimeValidation();
        this.setupFileValidation();
        this.setupConditionalValidation();
    },

    applyLengthConstraints() {
        const form = document.getElementById('sellerApplicationForm');
        if (!form) return;
        form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(field => {
            const rule = this.validationRules[field.name] || {};
            const fallback = field.tagName === 'TEXTAREA' ? 2000 : 255;
            if (!field.hasAttribute('maxlength')) {
                field.maxLength = rule.maxLength || fallback;
            }
        });
    },

    characterLength(value) {
        return Array.from(String(value).normalize('NFC')).length;
    },

    // Setup real-time validation
    setupRealTimeValidation() {
        const form = document.getElementById('sellerApplicationForm');
        if (!form) return;

        // Add event listeners for real-time validation
        form.addEventListener('blur', (e) => {
            // Leaving a field via the Previous button is navigation, not an
            // attempt to complete the current step. Avoid changing the DOM
            // during that click; doing so could consume the first click and
            // made backward navigation appear blocked.
            if (e.relatedTarget?.id === 'prevBtn') {
                return;
            }
            if (e.target.matches('input, select, textarea')) {
                this.validateField(e.target);
            }
        }, true);

        form.addEventListener('change', (e) => {
            if (e.target.matches('input[type="radio"]')) {
                // For radio buttons, validate the entire group
                if (e.target.name && this.validationRules[e.target.name]) {
                    this.validateRadioGroup(e.target.name);
                }
            } else if (e.target.matches('input[type="checkbox"], select')) {
                this.validateField(e.target);
            }
        });

        form.addEventListener('input', (e) => {
            if (e.target.matches('input[type="text"], input[type="email"], input[type="tel"], textarea')) {
                // Debounce validation for text inputs
                clearTimeout(e.target.validationTimeout);
                e.target.validationTimeout = setTimeout(() => {
                    this.validateField(e.target);
                }, 500);
            }
        });
    },

    // Setup file validation
    setupFileValidation() {
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file') {
                this.validateFileField(e.target);
            }
        });
    },

    // Setup conditional validation
    setupConditionalValidation() {
        // Marital status changes
        document.getElementById('maritalStatus')?.addEventListener('change', (e) => {
            this.updateConditionalFields('maritalStatus', e.target.value);
        });

        // Sale type changes
        const saleTypeRadios = document.querySelectorAll('input[name="saleType"]');
        saleTypeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.updateConditionalFields('saleType', e.target.value);
            });
        });

        // Land type changes
        document.getElementById('landType')?.addEventListener('change', (e) => {
            this.updateConditionalFields('landType', e.target.value);
        });

        // Signature type changes
        const signatureTypeRadios = document.querySelectorAll('input[name="signatureType"]');
        signatureTypeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.updateConditionalFields('signatureType', e.target.value);
            });
        });
    },

    // Update conditional field requirements
    updateConditionalFields(triggerField, value) {
        Object.keys(this.validationRules).forEach(fieldName => {
            const rule = this.validationRules[fieldName];
            if (rule.conditional) {
                const [conditionField, conditionValue] = rule.conditional.split('=');
                const field = document.querySelector(`[name="${fieldName}"]`);
                
                if (field && conditionField === triggerField) {
                    if (value === conditionValue) {
                        field.setAttribute('required', 'required');
                        field.setAttribute('aria-required', 'true');
                        field.closest('.form-group, .mb-3')?.classList.remove('d-none');
                    } else {
                        field.removeAttribute('required');
                        field.removeAttribute('aria-required');
                        this.clearFieldValidation(field);
                    }
                }
            }
        });

        // Special handling for spouse section
        if (triggerField === 'maritalStatus') {
            const spouseSection = document.getElementById('spouseDetails');
            if (spouseSection) {
                if (value === 'Married') {
                    spouseSection.classList.remove('d-none');
                } else {
                    spouseSection.classList.add('d-none');
                    // Clear spouse fields
                    spouseSection.querySelectorAll('input, select').forEach(field => {
                        field.value = '';
                        field.removeAttribute('aria-required');
                        this.clearFieldValidation(field);
                    });
                }
            }
        }

        // Special handling for property developer section
        if (triggerField === 'saleType') {
            const developerSection = document.getElementById('propertyDeveloperDetails');
            if (developerSection) {
                if (value === 'Property Development') {
                    developerSection.classList.remove('d-none');
                } else {
                    developerSection.classList.add('d-none');
                }
            }
            if (typeof SellerForm !== 'undefined') {
                SellerForm.toggleIndividualPropertyStep(value === 'Property Development');
            }
        }

        // Special handling for existing property details
        if (triggerField === 'landType') {
            const existingPropertySection = document.getElementById('existingPropertyDetails');
            if (existingPropertySection) {
                if (value === 'Existing Property') {
                    existingPropertySection.classList.remove('d-none');
                } else {
                    existingPropertySection.classList.add('d-none');
                    existingPropertySection.querySelectorAll('input, select, textarea').forEach(field => {
                        field.value = '';
                        this.clearFieldValidation(field);
                    });
                }
            }
        }
    },

    // Validate individual field
    validateField(field) {
        if (field.disabled) {
            this.clearFieldValidation(field);
            return true;
        }
        const fieldName = field.name;
        const rule = this.validationRules[fieldName];
        
        if (!rule) {
            const value = this.getFieldValue(field);
            if (field.hasAttribute('required') && this.isEmpty(value)) {
                this.displayFieldError(field, this.errorMessages.required);
                return false;
            }
            if (field.maxLength > 0 && this.characterLength(value) > field.maxLength) {
                this.displayFieldError(field, this.errorMessages.maxLength.replace('{max}', field.maxLength));
                return false;
            }
            this.clearFieldValidation(field);
            return true;
        }

        // SPECIAL HANDLING FOR RADIO BUTTONS
        if (field.type === 'radio') {
            
            // For radio buttons, check if ANY radio button in the group is checked
            const radioGroup = document.querySelectorAll(`input[name="${fieldName}"]`);
            
            let anyChecked = false;
            let checkedValue = '';
            
            radioGroup.forEach(radio => {
                if (radio.checked) {
                    anyChecked = true;
                    checkedValue = radio.value;
                }
            });
            
            
            // Check if field is conditionally required
            if (rule.conditional && !this.isConditionalFieldRequired(rule.conditional)) {
                this.clearRadioGroupValidation(field.name);
                return true;
            }
            
            // Required validation for radio groups
            if (rule.required && !anyChecked) {
                this.displayFieldError(field, this.errorMessages.required);
                return false;
            }
            
            // If required and has selection, or not required, it's valid
            this.displayFieldSuccess(field);
            return true;
        }

        // Check if field is conditionally required (non-radio fields)
        if (rule.conditional && !this.isConditionalFieldRequired(rule.conditional)) {
            this.clearFieldValidation(field);
            return true;
        }

        const value = this.getFieldValue(field);
        const errors = [];

        // Required validation
        if (rule.required && this.isEmpty(value)) {
            errors.push(this.errorMessages.required);
        }

        // Skip other validations if field is empty and not required
        if (this.isEmpty(value) && !rule.required) {
            this.clearFieldValidation(field);
            return true;
        }

        // Type-specific validation
        if (rule.type) {
            const typeError = this.validateFieldType(field, value, rule.type);
            if (typeError) errors.push(typeError);
        }

        // Length validation
        const valueLength = this.characterLength(value);
        if (rule.minLength && valueLength < rule.minLength) {
            errors.push(this.errorMessages.minLength.replace('{min}', rule.minLength));
        }
        if (rule.maxLength && valueLength > rule.maxLength) {
            errors.push(this.errorMessages.maxLength.replace('{max}', rule.maxLength));
        }

        // Pattern validation
        if (rule.pattern && !rule.pattern.test(value)) {
            errors.push(rule.patternMessage || this.errorMessages.pattern);
        }

        // Numeric validation
        if (rule.type === 'number') {
            const numValue = parseFloat(value);
            if (rule.min !== undefined && numValue < rule.min) {
                errors.push(fieldName === 'landSize'
                    ? 'Land size must be at least 1 square metre.'
                    : this.errorMessages.min.replace('{min}', rule.min));
            }
            if (rule.max !== undefined && numValue > rule.max) {
                errors.push(this.errorMessages.max.replace('{max}', rule.max));
            }
        }

        // Age validation for date fields
        if (rule.minAge || rule.maxAge) {
            const age = FormDataUtils.calculateAge(value);
            if (rule.minAge && age < rule.minAge) {
                errors.push(this.errorMessages.minAge.replace('{min}', rule.minAge));
            }
            if (rule.maxAge && age > rule.maxAge) {
                errors.push(this.errorMessages.maxAge.replace('{max}', rule.maxAge));
            }
        }

        // Custom validation
        if (rule.custom) {
            const customError = this[rule.custom](field, value);
            if (customError) errors.push(customError);
        }

        // Display validation result
        if (errors.length > 0) {
            this.displayFieldError(field, errors[0]);
            return false;
        } else {
            this.displayFieldSuccess(field);
            return true;
        }
    },

    // Validate file field
    validateFileField(field, options = {}) {
        const files = Array.from(field.files);
        if (files.length === 0) {
            if (field.hasAttribute('required')) {
                this.displayFieldError(field, this.errorMessages.file);
                return false;
            }
            this.clearFieldValidation(field);
            return true;
        }

        const fieldType = this.getFileFieldType(field.name);
        const allowedTypes = this.allowedFileTypes[fieldType] || this.allowedFileTypes.documents;
        const maxSize = this.fileSizeLimits[fieldType] || this.fileSizeLimits.documents;
        const maxCount = fieldType === 'images' ? 20 : (fieldType === 'videos' ? 3 : 1);
        if (files.length > maxCount) {
            this.displayFieldError(field, `Select no more than ${maxCount} file${maxCount === 1 ? '' : 's'}.`);
            if (options.clearInvalid !== false) {
                field.value = '';
                this.clearFilePreview(field);
            }
            return false;
        }

        for (let file of files) {
            // File size validation
            if (file.size > maxSize * 1024 * 1024) {
                this.displayFieldError(field, this.errorMessages.fileSize.replace('{max}', maxSize));
                if (options.clearInvalid !== false) {
                    field.value = '';
                    this.clearFilePreview(field);
                }
                return false;
            }

            // File type validation
            const fileExtension = file.name.split('.').pop().toLowerCase();
            if (!allowedTypes.includes(fileExtension)) {
                this.displayFieldError(field, this.errorMessages.fileType.replace('{types}', allowedTypes.join(', ')));
                if (options.clearInvalid !== false) {
                    field.value = '';
                    this.clearFilePreview(field);
                }
                return false;
            }
        }

        this.displayFieldSuccess(field);
        return true;
    },

    clearFilePreview(field) {
        const previewContainer = document.getElementById(field.id + 'Preview')
            || (field.id === 'propertyImages' ? document.getElementById('imagePreviewContainer') : null)
            || (field.id === 'propertyVideos' ? document.getElementById('videoPreviewContainer') : null);
        if (previewContainer) {
            previewContainer.replaceChildren();
        }
        field.closest('.document-upload-card')?.classList.remove('has-file');
    },

    // Get file field type for validation
    getFileFieldType(fieldName) {
        if (fieldName.includes('Image') || fieldName === 'propertyImages') return 'images';
        if (fieldName.includes('Video') || fieldName === 'propertyVideos') return 'videos';
        if (fieldName.includes('signature') || fieldName === 'signatureFile') return 'signature';
        return 'documents';
    },

    // Update file preview
    updateFilePreview(field, files) {
        const previewContainer = document.getElementById(field.id + 'Preview');
        if (!previewContainer) return;

        previewContainer.innerHTML = '';

        files.forEach((file, index) => {
            const preview = document.createElement('div');
            preview.className = 'file-preview';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                preview.appendChild(img);
            } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(file);
                video.controls = true;
                preview.appendChild(video);
            } else {
                preview.innerHTML = `
                    <div class="file-info">
                        <i class="fas fa-file-pdf fa-2x mb-2"></i>
                        <div>${file.name}</div>
                        <small>${this.formatFileSize(file.size)}</small>
                    </div>
                `;
            }

            // Add remove button
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = () => this.removeFilePreview(field, index);
            preview.appendChild(removeBtn);

            previewContainer.appendChild(preview);
        });
    },

    // Remove file preview
    removeFilePreview(field, index) {
        // Create new FileList without the removed file
        const dt = new DataTransfer();
        const files = Array.from(field.files);
        
        files.forEach((file, i) => {
            if (i !== index) dt.items.add(file);
        });
        
        field.files = dt.files;
        this.updateFilePreview(field, Array.from(field.files));
        
        if (field.files.length === 0 && field.hasAttribute('required')) {
            this.displayFieldError(field, this.errorMessages.file);
        }
    },

    // Format file size for display
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    // Custom validation methods
    validateIdNumber(field, value) {
        const idType = document.getElementById('idType')?.value;
        if (idType === 'National ID') {
            return FormDataUtils.isValidNamibianId(value) ? null : this.errorMessages.idNumber;
        } else if (idType === 'Passport') {
            return FormDataUtils.isValidPassport(value) ? null : this.errorMessages.passport;
        }
        return null;
    },

    validateSpouseIdNumber(field, value) {
        const idType = document.getElementById('spouseIdType')?.value;
        if (idType === 'National ID') {
            return FormDataUtils.isValidNamibianId(value) ? null : this.errorMessages.idNumber;
        } else if (idType === 'Passport') {
            return FormDataUtils.isValidPassport(value) ? null : this.errorMessages.passport;
        }
        return null;
    },

    validatePhoneNumber(field, value) {
        // Remove all non-digit characters
        const cleaned = value.replace(/\D/g, '');
        
        // Namibian phone number patterns
        const patterns = [
            /^264\d{8,9}$/, // +264 format without +
            /^0\d{8,9}$/, // Local format with 0
            /^\d{8,9}$/ // Without country code or leading 0
        ];
        
        const isValid = patterns.some(pattern => pattern.test(cleaned));
        return isValid ? null : this.errorMessages.phone;
    },

    validateCurrency(field, value) {
        const numValue = parseFloat(value.replace(/[^\d.]/g, ''));
        if (isNaN(numValue) || numValue <= 0) {
            return field.id === 'sellingPrice'
                ? 'Selling price must be greater than NAD 0.'
                : this.errorMessages.currency;
        }
        return null;
    },

    // Helper methods
    getFieldValue(field) {
        if (field.type === 'checkbox' || field.type === 'radio') {
            return field.checked ? field.value : '';
        }
        return field.value.trim();
    },

    isEmpty(value) {
        return value === null || value === undefined || value === '';
    },

    isConditionalFieldRequired(condition) {
        const [conditionField, conditionValue] = condition.split('=');
        const triggerField = document.querySelector(`[name="${conditionField}"]`);
        
        if (!triggerField) return false;
        
        if (triggerField.type === 'radio') {
            const checkedRadio = document.querySelector(`[name="${conditionField}"]:checked`);
            return checkedRadio && checkedRadio.value === conditionValue;
        }
        
        return triggerField.value === conditionValue;
    },

    validateFieldType(field, value, type) {
        switch (type) {
            case 'email':
                return FormDataUtils.isValidEmail(value) ? null : this.errorMessages.email;
            case 'number':
                return !isNaN(parseFloat(value)) ? null : this.errorMessages.number;
            case 'date':
                const date = new Date(value);
                return !isNaN(date.getTime()) ? null : this.errorMessages.date;
            case 'checkbox':
                return field.checked ? null : this.errorMessages.checkbox;
            default:
                return null;
        }
    },

    // Display validation results
    displayFieldError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        field.setAttribute('aria-invalid', 'true');

        const feedback = this.getFeedbackElement(field);
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = 'block';
            feedback.setAttribute('role', 'alert');
            if (!feedback.id) {
                feedback.id = `${field.id || field.name}-error`;
            }
            field.setAttribute('aria-describedby', feedback.id);
        }
    },

    displayFieldSuccess(field) {
        field.classList.add('is-valid');
        field.classList.remove('is-invalid');
        field.setAttribute('aria-invalid', 'false');

        const feedback = this.getFeedbackElement(field);
        if (feedback) {
            feedback.style.display = 'none';
        }
    },

    clearFieldValidation(field) {
        if (!field) return;
        
        field.classList.remove('is-valid', 'is-invalid');
        field.removeAttribute('aria-invalid');

        const feedback = this.getFeedbackElement(field);
        if (feedback) {
            feedback.style.display = 'none';
            feedback.textContent = '';
        }
    },

    getFeedbackElement(field) {
        const direct = field.parentElement?.querySelector(':scope > .invalid-feedback');
        if (direct) return direct;
        return field.closest('.mb-3, .mb-4, .card-body, fieldset')
            ?.querySelector('.invalid-feedback') || null;
    },

    // Clear validation for all radios in a group
    clearRadioGroupValidation(name) {
        const radios = document.querySelectorAll(`input[name="${name}"]`);
        radios.forEach(radio => {
            this.clearFieldValidation(radio);
        });
    },

    // Validate entire form
    // Helper method to find which step a field belongs to
    getStepForField(field) {
        const fieldElement = field.closest('.form-step');
        if (fieldElement && fieldElement.id) {
            const stepId = fieldElement.id.replace('step-', '');
            return parseInt(stepId);
        }
        return 'unknown';
    },

    validateForm() {
        const form = document.getElementById('sellerApplicationForm');
        if (!form) {
            return false;
        }

        let isValid = true;
        const fields = form.querySelectorAll('input, select, textarea');
        
        let invalidFieldCount = 0;
        const invalidFields = [];
        const validatedRadioGroups = new Set(); // Track which radio groups we've validated
        
        let firstInvalidField = null;
        fields.forEach(field => {
            // For radio buttons, only validate the first one in each group
            if (field.type === 'radio') {
                if (validatedRadioGroups.has(field.name)) {
                    return; // Skip this radio button, already validated
                }
                validatedRadioGroups.add(field.name);
            }
            
            if (!this.validateField(field)) {
                isValid = false;
                firstInvalidField ||= field;
                invalidFieldCount++;
                invalidFields.push({
                    name: field.name,
                    type: field.type,
                    step: this.getStepForField(field),
                    value: field.value
                });
            }
        });

        // Validate file fields
        const fileFields = form.querySelectorAll('input[type="file"]');
        
        fileFields.forEach(field => {
            if (!this.validateFileField(field)) {
                isValid = false;
                firstInvalidField ||= field;
                invalidFieldCount++;
                invalidFields.push({
                    name: field.name,
                    type: 'file',
                    step: this.getStepForField(field),
                    value: field.files.length > 0 ? field.files[0].name : 'No file'
                });
            }
        });


        // Add form validation class
        form.classList.add('was-validated');
        if (!isValid && firstInvalidField) {
            firstInvalidField.focus({ preventScroll: true });
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return isValid;
    },

    // Validate specific step
    validateStep(stepNumber) {
        const step = document.getElementById(`step-${stepNumber}`);
        if (!step) return true;

        let isValid = true;
        
        // Get all unique field names to handle radio button groups properly
        const fieldNames = new Set();
        const fields = step.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            if (field.type === 'radio') {
                // For radio buttons, use the name to group them
                fieldNames.add(field.name);
            } else {
                // For other fields, use the field itself
                fieldNames.add(field);
            }
        });
        
        // Validate each unique field name/group
        fieldNames.forEach(fieldOrName => {
            const isFieldValid = typeof fieldOrName === 'string'
                ? this.validateRadioGroup(fieldOrName)
                : (fieldOrName.type === 'file'
                    ? this.validateFileField(fieldOrName)
                    : this.validateField(fieldOrName));
            if (!isFieldValid) {
                isValid = false;
            }
        });

        return isValid;
    },

    // Validate a radio button group
    validateRadioGroup(name) {
        const radios = document.querySelectorAll(`input[name="${name}"]`);
        if (radios.length === 0) return true;
        
        const rule = this.validationRules[name];
        if (!rule || !rule.required) return true;

        if (rule.conditional && !this.isConditionalFieldRequired(rule.conditional)) {
            this.clearRadioGroupValidation(name);
            return true;
        }
        
        const checkedRadio = document.querySelector(`input[name="${name}"]:checked`);
        const isValid = !!checkedRadio;
        
        if (!isValid) {
            // Show error on all radios in the group
            radios.forEach(radio => {
                this.setFieldInvalid(radio, this.errorMessages.required);
            });
        } else {
            // Clear error from all radios in the group
            radios.forEach(radio => {
                this.setFieldValid(radio);
            });
        }
        
        return isValid;
    },

    // Set field as valid
    setFieldValid(field) {
        if (!field) return;
        
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        field.setAttribute('aria-invalid', 'false');

        const feedbackElement = this.getFeedbackElement(field);
        if (feedbackElement) {
            feedbackElement.textContent = '';
            feedbackElement.style.display = 'none';
        }
    },

    // Set field as invalid
    setFieldInvalid(field, message) {
        if (!field) return;
        
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');

        const feedbackElement = this.getFeedbackElement(field);
        if (feedbackElement) {
            feedbackElement.textContent = message || this.errorMessages.required;
            feedbackElement.style.display = 'block';
            feedbackElement.setAttribute('role', 'alert');
            if (!feedbackElement.id) {
                feedbackElement.id = `${field.name || field.id}-error`;
            }
            field.setAttribute('aria-describedby', feedbackElement.id);
        }
    },

    // Clear all validation
    clearAllValidation() {
        const form = document.getElementById('sellerApplicationForm');
        if (!form) return;

        form.classList.remove('was-validated');
        
        // Clear validation for radio groups
        const radioNames = new Set();
        const fields = form.querySelectorAll('input');
        
        fields.forEach(field => {
            if (field.type === 'radio') {
                radioNames.add(field.name);
            }
        });
        
        radioNames.forEach(name => {
            this.clearRadioGroupValidation(name);
        });
        
        // Clear validation for other fields
        fields.forEach(field => {
            if (field.type !== 'radio') {
                this.clearFieldValidation(field);
            }
        });
        
        // Clear validation for select and textarea elements
        form.querySelectorAll('select, textarea').forEach(field => {
            this.clearFieldValidation(field);
        });
    }
};

// Initialize validation when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    FormValidation.init();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormValidation;
}
