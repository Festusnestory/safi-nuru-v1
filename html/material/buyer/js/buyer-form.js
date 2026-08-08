// Buyer Form - Main form handling and API interactions

const BuyerForm = {
    // window.NURU_API_BASE (set inline in buyer/index.php) is absolute so
    // this still resolves correctly via the clean /buyer route, not just
    // the legacy html/material/buyer/ URL the relative default assumed.
    apiBaseUrl: window.NURU_API_BASE || '../api',
    csrfToken: null,
    isSubmitting: false,
    submissionKey: null,
    turnstileWidgetId: null,
    allowNavigation: false,
    pendingExitUrl: null,
    receiptStorageKey: 'nuru-buyer-submission-receipt',
    
    // Initialize the buyer form
    init() {
        this.getCSRFToken();
        this.bindEvents();
        this.initializeDropdowns();
        this.setupFormInteractions();
        this.setupTurnstile();
        this.setupExitNavigation();
        this.restoreSubmissionReceipt();
    },

    setupExitNavigation() {
        document.querySelectorAll('[data-buyer-exit]').forEach(link => {
            link.addEventListener('click', (event) => {
                if (!sessionStorage.getItem('nuru-buyer-form-data')) return;
                event.preventDefault();
                this.pendingExitUrl = link.href;
                const modalElement = document.getElementById('buyerExitModal');
                if (modalElement && window.bootstrap?.Modal) {
                    new window.bootstrap.Modal(modalElement).show();
                } else if (window.confirm('Leave this application and keep the saved draft in this browser tab?')) {
                    this.navigateToPendingExit(false);
                }
            });
        });

        document.getElementById('buyerKeepAndLeave')?.addEventListener('click', () => {
            this.navigateToPendingExit(false);
        });
        document.getElementById('buyerDiscardAndLeave')?.addEventListener('click', () => {
            this.navigateToPendingExit(true);
        });
        document.getElementById('buyerReturnToPortal')?.addEventListener('click', () => {
            this.allowNavigation = true;
            sessionStorage.removeItem(this.receiptStorageKey);
            sessionStorage.removeItem('nuru-buyer-form-data');
            sessionStorage.removeItem('nuru-buyer-current-step');
        });
    },

    navigateToPendingExit(discardDraft) {
        const destination = this.pendingExitUrl;
        if (!destination) return;
        if (discardDraft) {
            sessionStorage.removeItem('nuru-buyer-form-data');
            sessionStorage.removeItem('nuru-buyer-current-step');
        }
        this.allowNavigation = true;
        window.location.assign(destination);
    },

    // Get CSRF token
    getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            this.csrfToken = metaTag.getAttribute('content');
        } else {
            // Generate a new CSRF token if not found
            this.generateCSRFToken();
        }
    },

    // Generate CSRF token
    generateCSRFToken() {
        fetch(`${this.apiBaseUrl}/csrf-token`)
            .then(response => response.json())
            .then(data => {
                this.csrfToken = data.token;
            })
            .catch(error => {
                console.error('Error fetching CSRF token:', error);
            });
    },

    // Bind form events
    bindEvents() {
        const form = document.getElementById('buyerApplicationForm');
        form?.addEventListener('submit', (event) => {
            event.preventDefault();
            if (FormSteps.currentStep === FormSteps.totalSteps) {
                FormSteps.submitForm();
            }
        });

        window.addEventListener('pageshow', () => {
            this.isSubmitting = false;
            this.hideLoadingOverlay();
            const submitButton = document.getElementById('nextBtn');
            if (submitButton && FormSteps.currentStep === FormSteps.totalSteps) {
                submitButton.disabled = TurnstileConfig.required && !TurnstileConfig.configured;
                submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Application';
            }
        });

        // Set current date for signature
        const signatureDateInput = document.getElementById('signatureDate');
        if (signatureDateInput) {
            signatureDateInput.value = DateUtils.getCurrentDate();
        }

        // Phone number formatting
        this.setupPhoneFormatting();
        
        // Currency formatting
        this.setupCurrencyFormatting();
        
        // Real-time validation enhancements
        this.setupRealTimeValidation();
    },

    // Initialize dropdown data
    initializeDropdowns() {
        populateDropdowns();
        
        // Set up region-town dependencies
        this.setupRegionTownDependencies();
    },

    // Setup form interactions
    setupFormInteractions() {
        // Auto-populate spouse details based on marital status
        this.setupMaritalStatusInteractions();
        
        // Employment type specific handling
        this.setupEmploymentTypeInteractions();
        
        // Property value calculations
        this.setupPropertyCalculations();
        
        // Document upload previews
        this.setupDocumentPreviews();
    },

    // Step 8 is injected by JavaScript, so Turnstile's automatic page-load
    // scan can run before the widget container exists. Use explicit rendering
    // to make verification deterministic on both fast and slow connections.
    setupTurnstile() {
        if (!TurnstileConfig.enabled) {
            return;
        }
        const render = () => this.renderTurnstile();
        window.addEventListener('buyer-turnstile-ready', render);
        render();
    },

    renderTurnstile() {
        const container = document.querySelector('.cf-turnstile');
        if (!container || this.turnstileWidgetId !== null || typeof turnstile === 'undefined') {
            return;
        }
        this.turnstileWidgetId = turnstile.render(container, {
            sitekey: TurnstileConfig.siteKey
        });
    },

    // Setup phone number formatting
    setupPhoneFormatting() {
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                const formatted = formatPhoneNumber(e.target.value);
                if (formatted !== e.target.value) {
                    e.target.value = formatted;
                }
            });
        });
    },

    // Setup currency formatting
    setupCurrencyFormatting() {
        const currencyInputs = document.querySelectorAll('#propertyValue, #downPayment, #loanAmount, #monthlyIncome');
        currencyInputs.forEach(input => {
            input.addEventListener('blur', (e) => {
                const value = parseFloat(e.target.value.replace(/[^\d.]/g, ''));
                if (!isNaN(value)) {
                    e.target.value = value.toFixed(2);
                }
            });

            input.addEventListener('focus', (e) => {
                // Remove formatting for easier editing
                const value = e.target.value.replace(/[^\d.]/g, '');
                e.target.value = value;
            });
        });
    },

    // Setup real-time validation enhancements
    setupRealTimeValidation() {
        // ID number validation based on type
        const idTypeSelect = document.getElementById('idType');
        const idNumberInput = document.getElementById('idNumber');
        
        if (idTypeSelect && idNumberInput) {
            idTypeSelect.addEventListener('change', () => {
                FormValidation.validateField(idNumberInput);
            });
        }

        // Age calculation and display
        const dobInput = document.getElementById('dateOfBirth');
        if (dobInput) {
            dobInput.addEventListener('change', (e) => {
                const age = DateUtils.calculateAge(e.target.value);
                const ageDisplay = document.getElementById('ageDisplay');
                if (ageDisplay) {
                    ageDisplay.textContent = `Age: ${age} years`;
                    ageDisplay.className = age >= 18 ? 'text-success small' : 'text-danger small';
                }
            });
        }
    },

    // Setup region-town dependencies
    setupRegionTownDependencies() {
        // Main address region-town
        const regionSelect = document.getElementById('region');
        if (regionSelect) {
            regionSelect.addEventListener('change', (e) => {
                this.updateTownDropdown('town', e.target.value);
            });
        }

        // Next of kin region-town
        const nokRegionSelect = document.getElementById('nokRegion');
        if (nokRegionSelect) {
            nokRegionSelect.addEventListener('change', (e) => {
                this.updateTownDropdown('nokTown', e.target.value);
            });
        }

        // Preferred areas (dynamic)
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('preferred-region')) {
                const areaItem = e.target.closest('.preferred-area-item');
                const townSelect = areaItem.querySelector('.preferred-town');
                this.updateTownDropdown(townSelect, e.target.value);
            }
        });
    },

    // Update town dropdown based on region
    updateTownDropdown(townSelectOrId, regionValue) {
        let townSelect;
        if (typeof townSelectOrId === 'string') {
            townSelect = document.getElementById(townSelectOrId);
        } else {
            townSelect = townSelectOrId;
        }

        if (townSelect && regionValue) {
            townSelect.innerHTML = '<option value="">Select Town</option>';
            
            const towns = FormData.townsByRegion[regionValue] || [];
            towns.forEach(town => {
                const option = document.createElement('option');
                option.value = town;
                option.textContent = town;
                townSelect.appendChild(option);
            });
        }
    },

    // Setup marital status interactions
    setupMaritalStatusInteractions() {
        const maritalStatusSelect = document.getElementById('maritalStatus');
        if (maritalStatusSelect) {
            maritalStatusSelect.addEventListener('change', (e) => {
                const isMarried = e.target.value === 'married';
                this.toggleSpouseFields(isMarried);
                
                // Update document requirements
                this.updateDocumentRequirements();
            });

            this.toggleSpouseFields(maritalStatusSelect.value === 'married');
            this.updateDocumentRequirements();
        }
    },

    // Toggle spouse fields visibility and requirements
    toggleSpouseFields(show) {
        const spouseSection = document.getElementById('spouseDetails');
        const spouseFields = spouseSection.querySelectorAll('input, select');
        
        if (show) {
            spouseSection.classList.remove('d-none');
            spouseFields.forEach(field => {
                if (['spouseFullName', 'spouseIdPassport', 'spouseDateOfBirth'].includes(field.id)) {
                    field.setAttribute('required', 'required');
                }
            });
        } else {
            spouseSection.classList.add('d-none');
            spouseFields.forEach(field => {
                field.removeAttribute('required');
                field.value = '';
                FormValidation.clearFieldValidation(field);
            });
        }
    },

    // Setup employment type interactions
    setupEmploymentTypeInteractions() {
        const employmentTypeSelect = document.getElementById('employmentType');
        if (employmentTypeSelect) {
            employmentTypeSelect.addEventListener('change', (e) => {
                this.handleEmploymentTypeChange(e.target.value);
            });
            if (employmentTypeSelect.value) {
                this.handleEmploymentTypeChange(employmentTypeSelect.value);
            }
        }
    },

    // Handle employment type change
    handleEmploymentTypeChange(employmentType) {
        const employmentDetails = document.getElementById('employmentDetails');
        const employmentFields = employmentDetails.querySelectorAll('input, select, textarea');
        
        if (employmentType === 'unemployed') {
            employmentDetails.style.opacity = '0.6';
            employmentFields.forEach(field => {
                field.removeAttribute('required');
                if (field.id !== 'monthlyIncome') {
                    field.disabled = true;
                }
            });
        } else if (employmentType === 'pensioner') {
            employmentDetails.style.opacity = '1';
            employmentFields.forEach(field => {
                field.disabled = false;
                if (['monthlyIncome'].includes(field.id)) {
                    field.setAttribute('required', 'required');
                }
            });
        } else {
            employmentDetails.style.opacity = '1';
            employmentFields.forEach(field => {
                field.disabled = false;
                if (['employerName', 'monthlyIncome'].includes(field.id)) {
                    field.setAttribute('required', 'required');
                }
            });
        }
    },

    // Setup property calculations
    setupPropertyCalculations() {
        const propertyValueInput = document.getElementById('propertyValue');
        const downPaymentInput = document.getElementById('downPayment');
        const loanAmountInput = document.getElementById('loanAmount');

        if (propertyValueInput && downPaymentInput && loanAmountInput) {
            const calculateLoan = () => {
                const propertyValue = parseFloat(propertyValueInput.value) || 0;
                const downPayment = parseFloat(downPaymentInput.value) || 0;
                const loanAmount = Math.max(0, propertyValue - downPayment);
                
                loanAmountInput.value = loanAmount.toFixed(2);
                
                // Show loan percentage
                const loanPercentage = propertyValue > 0 ? (loanAmount / propertyValue * 100).toFixed(1) : 0;
                this.updateLoanInfo(loanAmount, loanPercentage);
            };

            propertyValueInput.addEventListener('input', calculateLoan);
            downPaymentInput.addEventListener('input', calculateLoan);
        }
    },

    // Update loan information display
    updateLoanInfo(loanAmount, loanPercentage) {
        let loanInfoElement = document.getElementById('loanInfo');
        if (!loanInfoElement) {
            loanInfoElement = document.createElement('div');
            loanInfoElement.id = 'loanInfo';
            loanInfoElement.className = 'mt-2 small text-muted';
            document.getElementById('loanAmount').parentNode.appendChild(loanInfoElement);
        }
        
        loanInfoElement.innerHTML = `
            <i class="fas fa-info-circle me-1"></i>
            Loan: ${formatCurrency(loanAmount)} (${loanPercentage}% of property value)
        `;
    },

    // Setup document previews
    setupDocumentPreviews() {
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file') {
                this.handleFilePreview(e.target);
            }
        });
    },

    // Handle file preview
    handleFilePreview(fileInput) {
        const files = Array.from(fileInput.files);
        files.forEach(file => {
            if (file.type.startsWith('image/')) {
                this.createImagePreview(file, fileInput);
            }
        });
    },

    // Create image preview
    createImagePreview(file, fileInput) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const documentSection = fileInput.closest('.mb-4[data-doc-type]');
            let previewContainer = documentSection?.querySelector('.file-list');

            if (!previewContainer && fileInput.id === 'signatureFile') {
                const signatureSection = document.getElementById('signatureUpload');
                previewContainer = document.getElementById('signaturePreview');
                if (signatureSection && !previewContainer) {
                    previewContainer = document.createElement('div');
                    previewContainer.id = 'signaturePreview';
                    previewContainer.className = 'mt-2';
                    signatureSection.appendChild(previewContainer);
                }
            }

            if (!previewContainer) {
                return;
            }

            previewContainer.querySelectorAll('.file-preview').forEach(existing => existing.remove());
            const preview = document.createElement('div');
            preview.className = 'file-preview mt-2';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 4px;">
            `;
            previewContainer.appendChild(preview);
        };
        reader.readAsDataURL(file);
    },

    // Update document requirements based on form data
    updateDocumentRequirements() {
        const maritalStatus = document.getElementById('maritalStatus').value;
        const marriageCertSection = document.querySelector('[data-doc-type="marriage_certificate"]');
        
        if (marriageCertSection) {
            const label = marriageCertSection.querySelector('h6');
            const uploadControl = marriageCertSection.querySelector('.file-upload-container');
            if (maritalStatus === 'married') {
                label.innerHTML = 'Marriage Certificate <span class="text-danger">*</span>';
                marriageCertSection.dataset.required = 'true';
                uploadControl?.setAttribute('aria-required', 'true');
            } else {
                label.innerHTML = 'Marriage Certificate (if married)';
                marriageCertSection.dataset.required = 'false';
                uploadControl?.setAttribute('aria-required', 'false');
                FormValidation.clearDocumentError('marriage_certificate');
            }
        }
    },

    // Submit exactly once. Retrying a POST after an interrupted response can
    // create duplicate applications even when the first request committed.
    async submitApplication() {
        if (this.isSubmitting) {
            return;
        }

        if (TurnstileConfig.required && !TurnstileConfig.configured) {
            this.handleSubmissionError('Security verification is temporarily unavailable. Please try again later.');
            return;
        }

        const turnstileToken = document.querySelector('[name="cf-turnstile-response"]')?.value;
        if (document.querySelector('.cf-turnstile') && !turnstileToken) {
            this.handleSubmissionError('Please complete the CAPTCHA challenge.');
            return;
        }

        const submitButton = document.getElementById('nextBtn');
        this.isSubmitting = true;
        submitButton?.setAttribute('disabled', 'disabled');
        if (submitButton) {
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting…';
        }

        try {
            this.showLoadingOverlay();
            const structuredData = await this.prepareFormDataSafe();
            if (!structuredData?.data || !this.csrfToken) {
                throw new Error('The form session is unavailable. Please reload the page and try again.');
            }

            const submissionData = structuredData.data;
            submissionData.csrf_token = this.csrfToken;
            submissionData['cf-turnstile-response'] = turnstileToken;
            this.submissionKey ||= (
                typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
                    ? crypto.randomUUID()
                    : `${Date.now()}-${Math.random().toString(16).slice(2)}`
            );
            submissionData.idempotency_key = this.submissionKey;

            const response = await fetch(`${this.apiBaseUrl}/applications/buyers/submit.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Idempotency-Key': this.submissionKey
                },
                body: JSON.stringify(submissionData)
            });

            let result;
            try {
                result = await response.json();
            } catch (parseError) {
                throw new Error('The server returned an invalid response. Please try again.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Unable to submit the application. Please review the form and try again.');
            }

            this.handleSubmissionSuccess(result);
            this.submissionKey = null;

        } catch (error) {
            console.error('Submission error:', error);
            this.handleSubmissionError(error.message || 'A network error occurred. Please try again.');
        } finally {
            this.isSubmitting = false;
            this.hideLoadingOverlay();
            if (submitButton) {
                submitButton.disabled = TurnstileConfig.required && !TurnstileConfig.configured;
                submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Application';
            }
        }
    },

    // Read a single File as base64 (no "data:mime;base64," prefix, since the
    // PHP processor calls base64_decode() directly on the raw string)
    readFileAsBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                const result = reader.result;
                const commaIndex = result.indexOf(',');
                resolve(commaIndex >= 0 ? result.slice(commaIndex + 1) : result);
            };
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(file);
        });
    },

    // Safe form data collection with comprehensive field mapping
    async prepareFormDataSafe() {
        const form = document.getElementById('buyerApplicationForm');
        if (!form) {
            console.error('Form element not found!');
            return null;
        }

        // Create a plain object representation using only manual collection
        const data = {};
        const formElements = form.querySelectorAll('input, select, textarea');
        let totalFileBytes = 0;
        const maxTotalFileBytes = 40 * 1024 * 1024;
        
        // Collect all form element values manually
        // For preferred areas, we need to collect all instances of the same field name
        const preferredAreaFields = ['preferredRegion[]', 'preferredTown[]', 'preferredLocation[]', 'preferredSuburb[]'];
        const multiValueFields = {};
        
        // First pass: collect all elements by field name for preferred areas
        preferredAreaFields.forEach(fieldName => {
            const elements = form.querySelectorAll(`[name="${fieldName}"]`);
            // Preserve row positions. Filtering each column independently can
            // combine a region from one row with a suburb from another.
            multiValueFields[fieldName] = Array.from(elements).map(el => el.value);
        });
        
        // Add preferred areas to data
        Object.keys(multiValueFields).forEach(fieldName => {
            if (multiValueFields[fieldName].length > 0) {
                data[fieldName] = multiValueFields[fieldName];
            }
        });
        
        for (const element of formElements) {
            if (element.name) {
                // Handle preferred areas specially - skip individual processing since we handled them above
                if (preferredAreaFields.includes(element.name)) {
                    continue;
                } else if (element.type === 'checkbox' || element.type === 'radio') {
                    if (element.checked) {
                        // Handle multiple checkboxes with same name
                        if (data[element.name]) {
                            if (Array.isArray(data[element.name])) {
                                data[element.name].push(element.value);
                            } else {
                                data[element.name] = [data[element.name], element.value];
                            }
                        } else {
                            data[element.name] = element.value;
                        }
                    }
                } else if (element.type.includes('file')) {
                    // Collect file metadata AND actual base64 content for file inputs -
                    // without content the server only ever saves 0-byte files
                    if (element.files && element.files.length > 0) {
                        const files = [];
                        for (const file of Array.from(element.files)) {
                            totalFileBytes += file.size;
                            if (totalFileBytes > maxTotalFileBytes) {
                                throw new Error('The combined document size exceeds 40MB. Please compress or remove files and try again.');
                            }
                            const content = await this.readFileAsBase64(file);
                            files.push({
                                name: file.name,
                                type: file.type,
                                size: file.size,
                                content: content
                            });
                        }
                        data[element.name] = {
                            files: files,
                            hasFiles: true,
                            fileCount: files.length
                        };
                    } else {
                        data[element.name] = { hasFiles: false, fileCount: 0 };
                    }
                } else if (element.value) {
                    // Handle text inputs, selects, textareas
                    data[element.name] = element.value;
                }
            }
        }

        // Apply comprehensive field mapping to match database schema
        this.mapFormFieldsToDatabase(data);
        
        // Process additional fields (like checkboxes or multi-selects)
        data.preferredAreas = this.processPreferredAreas(data);
        data.declarations = this.processDeclarations(data);
        this.cleanupFormData(data);

        return { data: data }; // Return the plain object with file metadata and mapped fields
    },

    // Map form field names to database column names and create related data structures
    mapFormFieldsToDatabase(data) {
        // CRITICAL: Ensure full_name is properly set from first and last name
        if (data.firstName && data.lastName) {
            data.full_name = `${data.firstName} ${data.lastName}`.trim();
        } else if (data.firstName) {
            data.full_name = data.firstName;
        } else if (data.lastName) {
            data.full_name = data.lastName;
        }
        
        // Build complete address from individual components for main address
        const addressParts = [];
        if (data.erfNumber) addressParts.push(`ERF ${data.erfNumber}`);
        if (data.streetName) addressParts.push(data.streetName);
        if (data.suburb) addressParts.push(data.suburb);
        if (data.location) addressParts.push(data.location);
        if (addressParts.length > 0) {
            data.address = addressParts.join(', ');
        }
        
        // Build next of kin address from individual components
        const nokAddressParts = [];
        if (data.nokErfNumber) nokAddressParts.push(`ERF ${data.nokErfNumber}`);
        if (data.nokStreetName) nokAddressParts.push(data.nokStreetName);
        if (data.nokSuburb) nokAddressParts.push(data.nokSuburb);
        if (data.nokLocation) nokAddressParts.push(data.nokLocation);
        if (nokAddressParts.length > 0) {
            data.next_of_kin_address = nokAddressParts.join(', ');
        }
        
        // Map field name variations for main buyer table
        const fieldMappings = {
            // Contact information
            'mobileNumber': 'phone',
            'phoneNumber': 'phone',
            'emailAddress': 'email',
            'contactEmail': 'email',
            
            // Address information (main address)
            'streetAddress': 'address',
            'streetName': 'address',
            'addressLine1': 'address',
            'residentialAddress': 'address',
            
            // Employment information
            'employerName': 'employer_name',
            'jobPosition': 'position',
            'workPosition': 'position',
            'jobTitle': 'position',
            
            // ID information
            'idType': 'id_type',
            'passportNumber': 'id_number',
            'nationalIdNumber': 'id_number',
            'idPassportNumber': 'id_number',
            
            // Property information
            'propertyType': 'property_type',
            'priceType': 'price_type',
            'downPayment': 'down_payment',
            'loanAmount': 'loan_amount',
            'propertyValue': 'property_value',
            'monthlyIncome': 'monthly_income',
            
            // Signature information
            'signatureLocation': 'signature_location',
            'signatureDate': 'signature_date',
            'signatureType': 'signature_type'
        };
        
        // Apply mappings for main buyer data
        Object.keys(fieldMappings).forEach(formField => {
            if (data[formField] !== undefined) {
                const dbField = fieldMappings[formField];
                if (data[dbField] === undefined) {
                    data[dbField] = data[formField];
                }
            }
        });
        
        // Create buyer_spouse data structure
        if (data.spouseFullName) {
            data.buyer_spouse = {
                full_name: data.spouseFullName,
                id_passport: data.spouseIdPassport,
                date_of_birth: data.spouseDateOfBirth,
                phone: data.spouseContactNumber || '',
                email: data.spouseEmail || ''
            };
        }
        
        // Create buyer_next_of_kin data structure
        if (data.nokFullName) {
            data.buyer_next_of_kin = {
                full_name: data.nokFullName,
                relationship: data.nokRelationship,
                phone: data.nokContactNumber || data.nokPhone,
                email: data.nokEmailAddress || '',
                region: data.nokRegion,
                town: data.nokTown,
                address: data.next_of_kin_address
            };
        }
        
        // Create buyer_documents data structure from file metadata
        const documentTypes = ['id_passport', 'proof_of_income', 'bank_statements', 'marriage_certificate', 'employment_letter', 'additional_documents', 'signatureFile'];
        const documentRecords = [];

        documentTypes.forEach(docType => {
            if (data[docType] && data[docType].hasFiles) {
                data[docType].files.forEach(file => {
                    documentRecords.push({
                        doc_type: docType === 'signatureFile' ? 'signature' : docType,
                        file_path: `uploads/buyers/${docType}/${file.name}`,
                        file_name: file.name,
                        file_type: file.type,
                        file_size: file.size,
                        content: file.content
                    });
                });
            }
        });
        
        if (documentRecords.length > 0) {
            data.buyer_documents = documentRecords;
        }
        
        // Handle specific data type conversions
        if (data.propertyValue) {
            data.property_value = parseFloat(data.propertyValue) || 0;
        }
        if (data.downPayment) {
            data.down_payment = parseFloat(data.downPayment) || 0;
        }
        if (data.loanAmount) {
            data.loan_amount = parseFloat(data.loanAmount) || 0;
        }
        if (data.monthlyIncome) {
            data.monthly_income = parseFloat(data.monthlyIncome) || 0;
        }
        
        // Ensure employment_type is properly mapped
        if (data.employmentType) {
            data.employment_type = data.employmentType;
        }
        
        // Ensure marital_status is properly mapped
        if (data.maritalStatus) {
            data.marital_status = data.maritalStatus;
        }
        
        // Clean up temporary individual address fields and temp fields
        const tempFields = ['erfNumber', 'streetName', 'suburb', 'location', 'nokErfNumber', 'nokStreetName', 'nokSuburb', 'nokLocation',
                           'next_of_kin_address'];
        tempFields.forEach(field => {
            if (data[field] !== undefined) {
                delete data[field];
            }
        });
        
    },

    // Process preferred areas data
    processPreferredAreas(data) {
        const areas = [];
        
        // Try multiple possible field name patterns
        const possibleFields = {
            regions: ['preferredRegion', 'preferredRegion[]', 'preferred-region', 'preferredRegions'],
            towns: ['preferredTown', 'preferredTown[]', 'preferred-town', 'preferredTowns'],
            locations: ['preferredLocation', 'preferredLocation[]', 'preferred-location', 'preferredLocations'],
            suburbs: ['preferredSuburb', 'preferredSuburb[]', 'preferred-suburb', 'preferredSuburbs']
        };
        
        // Find actual field names that exist in data
        const foundFields = {};
        Object.keys(possibleFields).forEach(fieldType => {
            for (const fieldName of possibleFields[fieldType]) {
                if (data[fieldName] !== undefined) {
                    foundFields[fieldType] = fieldName;
                    break;
                }
            }
        });
        
        
        // Get arrays of values (convert single values to arrays)
        const regions = foundFields.regions ? (Array.isArray(data[foundFields.regions]) ? data[foundFields.regions] : [data[foundFields.regions]]) : [];
        const towns = foundFields.towns ? (Array.isArray(data[foundFields.towns]) ? data[foundFields.towns] : [data[foundFields.towns]]) : [];
        const locations = foundFields.locations ? (Array.isArray(data[foundFields.locations]) ? data[foundFields.locations] : [data[foundFields.locations]]) : [];
        const suburbs = foundFields.suburbs ? (Array.isArray(data[foundFields.suburbs]) ? data[foundFields.suburbs] : [data[foundFields.suburbs]]) : [];
        
        
        // Get the maximum length to ensure we process all areas
        const maxLength = Math.max(regions.length, towns.length, locations.length, suburbs.length);
        
        for (let i = 0; i < maxLength; i++) {
            if (regions[i] || towns[i] || locations[i] || suburbs[i]) {
                areas.push({
                    region: regions[i] || '',
                    town: towns[i] || '',
                    location: locations[i] || '',
                    suburb: suburbs[i] || ''
                });
            }
        }

        return areas;
    },

    // Process declarations data
    processDeclarations(data) {
        const declarations = data['declarations'] || [];
        return Array.isArray(declarations) ? declarations : [declarations];
    },

    // Clean up form data
    cleanupFormData(data) {
        // Remove array notation from keys
        const arrayKeys = Object.keys(data).filter(key => key.includes('[]'));
        arrayKeys.forEach(key => {
            delete data[key];
        });

        // Convert string numbers to numbers
        const numericFields = ['propertyValue', 'downPayment', 'loanAmount', 'monthlyIncome'];
        numericFields.forEach(field => {
            if (data[field]) {
                data[field] = parseFloat(data[field]) || 0;
            }
        });
    },

    // Handle successful submission
    handleSubmissionSuccess(result) {
        const receipt = {
            application_number: result.application_number || '',
            status: result.status || 'Pending review',
            submitted_at: result.submitted_at || new Date().toISOString()
        };
        sessionStorage.setItem(this.receiptStorageKey, JSON.stringify(receipt));
        sessionStorage.removeItem('nuru-buyer-form-data');
        sessionStorage.removeItem('nuru-buyer-current-step');
        this.showSubmissionReceipt(receipt);
        this.trackSubmission(result);
    },

    restoreSubmissionReceipt() {
        const stored = sessionStorage.getItem(this.receiptStorageKey);
        if (!stored) return;
        try {
            this.showSubmissionReceipt(JSON.parse(stored));
        } catch (error) {
            sessionStorage.removeItem(this.receiptStorageKey);
        }
    },

    showSubmissionReceipt(receipt) {
        const modalElement = document.getElementById('successModal');
        const applicationNumber = document.getElementById('applicationNumber');
        const status = document.getElementById('applicationStatus');
        const submittedAt = document.getElementById('applicationSubmittedAt');
        if (applicationNumber) applicationNumber.textContent = receipt.application_number || '';
        if (status) status.textContent = receipt.status || 'Pending review';
        if (submittedAt) {
            submittedAt.textContent = receipt.submitted_at
                ? new Date(receipt.submitted_at).toLocaleString()
                : new Date().toLocaleString();
        }
        if (!modalElement) return;
        try {
            if (window.bootstrap?.Modal) {
                new window.bootstrap.Modal(modalElement).show();
                return;
            }
        } catch (presentationError) {
            console.error('Unable to initialise the receipt modal:', presentationError);
        }
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.removeAttribute('aria-hidden');
        modalElement.setAttribute('aria-modal', 'true');
        modalElement.setAttribute('role', 'dialog');
        document.body.classList.add('modal-open');
    },

    // Handle submission error
    handleSubmissionError(errorMessage) {
        if (typeof turnstile !== 'undefined' && this.turnstileWidgetId !== null) {
            turnstile.reset(this.turnstileWidgetId);
        }

        document.querySelectorAll('.buyer-submission-error').forEach(alert => alert.remove());
        const currentStep = document.getElementById(`step-${FormSteps.currentStep}`);
        if (!currentStep) {
            return;
        }
        const alertElement = document.createElement('div');
        alertElement.className = 'alert alert-danger alert-dismissible fade show buyer-submission-error';
        alertElement.setAttribute('role', 'alert');
        const icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-triangle me-2';
        icon.setAttribute('aria-hidden', 'true');
        const heading = document.createElement('strong');
        heading.textContent = 'Submission Failed: ';
        const message = document.createTextNode(String(errorMessage));
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close';
        closeButton.dataset.bsDismiss = 'alert';
        closeButton.setAttribute('aria-label', 'Dismiss submission error');
        alertElement.append(icon, heading, message, closeButton);
        currentStep.prepend(alertElement);
        
        // Scroll to top to show error
        currentStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    // Show loading overlay
    showLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('d-none');
        }
    },

    // Hide loading overlay
    hideLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('d-none');
        }
    },

    // Track submission for analytics
    trackSubmission(result) {
        // Implementation for analytics tracking
        // Could integrate with Google Analytics, etc.
        if (typeof gtag !== 'undefined') {
            gtag('event', 'application_submit', {
                'event_category': 'buyer_application',
                'event_label': result.application_number
            });
        }
    },

    // Export form data for debugging
    async exportFormData() {
        const formDataResult = await this.prepareFormDataSafe();
        if (!formDataResult || !formDataResult.data) {
            console.error('Failed to collect form data for export');
            return;
        }
        
        const formData = formDataResult.data;
        // Create downloadable JSON file
        const dataStr = JSON.stringify(formData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = 'buyer-application-data.json';
        link.click();
        
        URL.revokeObjectURL(url);
    },

    // Validate required documents before submission
    validateDocuments() {
        const requiredDocs = ['id_passport', 'proof_of_income', 'bank_statements'];
        const maritalStatus = document.getElementById('maritalStatus').value;
        
        if (maritalStatus === 'married') {
            requiredDocs.push('marriage_certificate');
        }
        
        const missingDocs = [];
        
        requiredDocs.forEach(docType => {
            const fileInput = document.querySelector(`input[name="${docType}"]`);
            if (!fileInput || fileInput.files.length === 0) {
                missingDocs.push(FormData.documentTypes[docType].label);
            }
        });
        
        if (missingDocs.length > 0) {
            FormValidation.showAlert(
                `Please upload the following required documents: ${missingDocs.join(', ')}`,
                'danger'
            );
            return false;
        }
        
        return true;
    },

    // Save form as draft with file metadata
    async saveAsDraft() {
        const formDataResult = await this.prepareFormDataSafe();
        if (!formDataResult || !formDataResult.data) {
            console.error('Failed to collect form data for draft');
            return;
        }
        
        const formData = formDataResult.data;
        formData.status = 'draft';
        
        fetch(`${this.apiBaseUrl}/applications/buyers/draft`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                FormValidation.showAlert('Application saved as draft successfully!', 'success');
            } else {
                FormValidation.showAlert('Error saving draft: ' + (result.error || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error saving draft:', error);
            FormValidation.showAlert('Error saving draft. Please try again.', 'danger');
        });
    }
};

// Initialize buyer form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    BuyerForm.init();
});

// Handle page unload warning if form has data
window.addEventListener('beforeunload', function(e) {
    const hasFormData = sessionStorage.getItem('nuru-buyer-form-data');
    if (hasFormData && !BuyerForm.allowNavigation) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        return e.returnValue;
    }
});
