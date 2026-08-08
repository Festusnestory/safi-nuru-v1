// Form Steps - Handles step navigation and form structure management

const FormSteps = {
    currentStep: 1,
    totalSteps: 8,
    formData: {},
    
    // Initialize the form steps
    init() {
        this.setupStepNavigation();
        this.setupProgressIndicator();
        this.setupFormStructure();
        this.loadSavedData();
        this.bindEvents();
    },

    // Setup step navigation
    setupStepNavigation() {
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');

        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextStep());
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.previousStep());
        }

        // Allow clicking on progress steps
        document.querySelectorAll('.step-label').forEach(label => {
            label.addEventListener('click', (e) => {
                const targetStep = parseInt(e.target.getAttribute('data-step'));
                if (this.canNavigateToStep(targetStep)) {
                    this.goToStep(targetStep);
                }
            });
        });
    },

    // Setup progress indicator
    setupProgressIndicator() {
        this.updateProgressIndicator();
    },

    // Setup complete form structure
    setupFormStructure() {
        this.createAllFormSteps();
        this.showStep(this.currentStep);
    },

    // Create all form steps dynamically
    createAllFormSteps() {
        const formContainer = document.getElementById('buyerApplicationForm');
        
        // Clear existing steps except step 1 and 2 which are already in HTML
        const existingSteps = formContainer.querySelectorAll('.form-step');
        existingSteps.forEach((step, index) => {
            if (index > 1) { // Keep first two steps
                step.remove();
            }
        });

        // Create remaining steps
        this.createStep3(); // Residential Address
        this.createStep4(); // Next of Kin
        this.createStep5(); // Employment Details
        this.createStep6(); // Property Purchase
        this.createStep7(); // Document Upload
        this.createStep8(); // Declaration
    },

    // Create Step 3: Residential Address
    createStep3() {
        const stepHtml = `
            <div class="form-step" id="step-3">
                <h4 class="mb-4 text-primary">
                    <i class="fas fa-home me-2"></i>
                    Residential Address
                </h4>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="erfNumber" class="form-label">ERF Number</label>
                        <input type="text" class="form-control" id="erfNumber" name="erfNumber">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="streetName" class="form-label">Street Name</label>
                        <input type="text" class="form-control" id="streetName" name="streetName">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="suburb" class="form-label">Suburb</label>
                        <input type="text" class="form-control" id="suburb" name="suburb">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="region" class="form-label required">Region</label>
                        <select class="form-select" id="region" name="region" required>
                            <option value="">Select Region</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="town" class="form-label required">Town</label>
                        <select class="form-select" id="town" name="town" required>
                            <option value="">Select Town</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="emailAddress" class="form-label required">Email Address</label>
                        <input type="email" class="form-control" id="emailAddress" name="emailAddress" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="mobileNumber" class="form-label required">Mobile Number</label>
                        <input type="tel" class="form-control" id="mobileNumber" name="mobileNumber" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="poBox" class="form-label">P.O. Box</label>
                    <input type="text" class="form-control" id="poBox" name="poBox">
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        `;
        this.appendStep(stepHtml);
    },

    // Create Step 4: Next of Kin
    createStep4() {
        const stepHtml = `
            <div class="form-step" id="step-4">
                <h4 class="mb-4 text-primary">
                    <i class="fas fa-users me-2"></i>
                    Next of Kin
                </h4>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nokFullName" class="form-label required">Full Name</label>
                        <input type="text" class="form-control" id="nokFullName" name="nokFullName" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nokRelationship" class="form-label required">Relationship</label>
                        <select class="form-select" id="nokRelationship" name="nokRelationship" required>
                            <option value="">Select Relationship</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nokContactNumber" class="form-label required">Contact Number</label>
                        <input type="tel" class="form-control" id="nokContactNumber" name="nokContactNumber" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nokEmailAddress" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="nokEmailAddress" name="nokEmailAddress">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <h5 class="mb-3 text-secondary">Address</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nokErfNumber" class="form-label">ERF Number</label>
                        <input type="text" class="form-control" id="nokErfNumber" name="nokErfNumber">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nokSuburb" class="form-label">Suburb</label>
                        <input type="text" class="form-control" id="nokSuburb" name="nokSuburb">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nokLocation" class="form-label">Location</label>
                        <input type="text" class="form-control" id="nokLocation" name="nokLocation">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nokRegion" class="form-label">Region</label>
                        <select class="form-select" id="nokRegion" name="nokRegion">
                            <option value="">Select Region</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nokTown" class="form-label">Town</label>
                        <select class="form-select" id="nokTown" name="nokTown">
                            <option value="">Select Town</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
        `;
        this.appendStep(stepHtml);
    },

    // Create Step 5: Employment Details
    createStep5() {
        const stepHtml = `
            <div class="form-step" id="step-5">
                <h4 class="mb-4 text-primary">
                    <i class="fas fa-briefcase me-2"></i>
                    Employment Details
                </h4>
                
                <div class="mb-3">
                    <label for="employmentType" class="form-label required">Employment Type</label>
                    <select class="form-select" id="employmentType" name="employmentType" required>
                        <option value="">Select Employment Type</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div id="employmentDetails" class="employment-type-section">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employerName" class="form-label">Employer Name</label>
                            <input type="text" class="form-control" id="employerName" name="employerName">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="jobTitle" class="form-label">Job Title</label>
                            <input type="text" class="form-control" id="jobTitle" name="jobTitle">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="workAddress" class="form-label">Work Address</label>
                            <textarea class="form-control" id="workAddress" name="workAddress" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="workPhone" class="form-label">Work Phone</label>
                            <input type="tel" class="form-control" id="workPhone" name="workPhone">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="monthlyIncome" class="form-label required">Monthly Income (NAD)</label>
                            <input type="number" class="form-control" id="monthlyIncome" name="monthlyIncome" min="0" step="0.01" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="employmentDuration" class="form-label">Employment Duration</label>
                            <input type="text" class="form-control" id="employmentDuration" name="employmentDuration" placeholder="e.g., 2 years 3 months">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supervisorName" class="form-label">Supervisor Name</label>
                            <input type="text" class="form-control" id="supervisorName" name="supervisorName">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="supervisorContact" class="form-label">Supervisor Contact</label>
                            <input type="tel" class="form-control" id="supervisorContact" name="supervisorContact">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        this.appendStep(stepHtml);
    },

    // Create Step 6: Property Purchase
    createStep6() {
        const stepHtml = `
            <div class="form-step" id="step-6">
                <h4 class="mb-4 text-primary">
                    <i class="fas fa-building me-2"></i>
                    Property Purchase Details
                </h4>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="propertyType" class="form-label required">Property Type</label>
                        <select class="form-select" id="propertyType" name="propertyType" required>
                            <option value="">Select Property Type</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="priceType" class="form-label required">Price Type</label>
                        <select class="form-select" id="priceType" name="priceType" required>
                            <option value="">Select Price Type</option>
                            <option value="fixed">Fixed Price</option>
                            <option value="negotiable">Negotiable</option>
                            <option value="auction">Auction</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="propertyValue" class="form-label required">Property Value (NAD)</label>
                        <input type="number" class="form-control" id="propertyValue" name="propertyValue" min="0" step="0.01" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="downPayment" class="form-label required">Down Payment (NAD)</label>
                        <input type="number" class="form-control" id="downPayment" name="downPayment" min="0" step="0.01" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="loanAmount" class="form-label">Loan Amount (NAD)</label>
                        <input type="number" class="form-control" id="loanAmount" name="loanAmount" min="0" step="0.01" readonly>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5 class="mb-3 text-secondary">Preferred Areas</h5>
                    <div id="preferredAreas">
                        <div class="preferred-area-item" data-area-id="1">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="preferred-region-1">Region</label>
                                    <select class="form-select preferred-region" id="preferred-region-1" name="preferredRegion[]">
                                        <option value="">Select Region</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="preferred-town-1">Town</label>
                                    <select class="form-select preferred-town" id="preferred-town-1" name="preferredTown[]">
                                        <option value="">Select Town</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="preferred-location-1">Location</label>
                                    <input type="text" class="form-control" id="preferred-location-1" name="preferredLocation[]">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="preferred-suburb-1">Suburb</label>
                                    <input type="text" class="form-control" id="preferred-suburb-1" name="preferredSuburb[]">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addPreferredArea">
                        <i class="fas fa-plus me-2"></i>Add Another Area
                    </button>
                </div>
                
                <div class="mb-3">
                    <label for="additionalRequirements" class="form-label">Additional Requirements</label>
                    <textarea class="form-control" id="additionalRequirements" name="additionalRequirements" rows="4" 
                              placeholder="Please describe any specific requirements or preferences for your property..."></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        `;
        this.appendStep(stepHtml);
    },

    // Create Step 7: Document Upload
    createStep7() {
        const stepHtml = `
            <div class="form-step" id="step-7">
                <h4 class="mb-4 text-primary">
                    <i class="fas fa-file-upload me-2"></i>
                    Document Upload
                </h4>
                
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Please upload the required documents. Maximum file size: 10MB per file and 40MB in total.
                    Accepted formats: PDF, DOC, DOCX, JPG, PNG.
                </div>
                
                <div class="document-upload-container">
                    ${this.createDocumentUploadSection('id_passport', 'ID/Passport Copy', true)}
                    ${this.createDocumentUploadSection('proof_of_income', 'Proof of Income', true)}
                    ${this.createDocumentUploadSection('bank_statements', 'Bank Statements (Last 3 months)', true)}
                    ${this.createDocumentUploadSection('employment_letter', 'Employment Letter/Contract', false)}
                    ${this.createDocumentUploadSection('marriage_certificate', 'Marriage Certificate (if married)', false)}
                    ${this.createDocumentUploadSection('additional_documents', 'Additional Documents', false)}
                </div>
            </div>
        `;
        this.appendStep(stepHtml);
    },

    // Create Step 8: Declaration
    createStep8() {
        const stepHtml = `
            <div class="form-step" id="step-8">
                <h4 class="mb-4 text-primary">
                    <i class="fas fa-signature me-2"></i>
                    Acknowledgment & Declaration
                </h4>
                
                <div class="declarations-section mb-4">
                    <h5 class="mb-3 text-secondary">Declarations</h5>
                    
                    <div class="declaration-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="declaration1" name="declarations" value="information_accurate" required>
                            <label class="form-check-label" for="declaration1">
                                I declare that all information provided in this application is true and correct to the best of my knowledge.
                            </label>
                            <div class="invalid-feedback">You must accept this declaration.</div>
                        </div>
                    </div>
                    
                    <div class="declaration-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="declaration2" name="declarations" value="false_information_warning" required>
                            <label class="form-check-label" for="declaration2">
                                I understand that providing false information may result in the rejection of my application.
                            </label>
                            <div class="invalid-feedback">You must accept this declaration.</div>
                        </div>
                    </div>
                    
                    <div class="declaration-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="declaration3" name="declarations" value="verification_consent" required>
                            <label class="form-check-label" for="declaration3">
                                I consent to Nuru Real Estate verifying the information provided and conducting necessary checks.
                            </label>
                            <div class="invalid-feedback">You must accept this declaration.</div>
                        </div>
                    </div>
                    
                    <div class="declaration-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="declaration4" name="declarations" value="terms_accepted" required>
                            <label class="form-check-label" for="declaration4">
                                I agree to the terms and conditions of Nuru Real Estate's buyer application process.
                            </label>
                            <div class="invalid-feedback">You must accept this declaration.</div>
                        </div>
                    </div>
                </div>
                
                <div class="signature-section">
                    <h5 class="mb-3 text-secondary">Signature</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="signatureLocation" class="form-label required">Location</label>
                            <input type="text" class="form-control" id="signatureLocation" name="signatureLocation" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="signatureDate" class="form-label required">Date</label>
                            <input type="date" class="form-control" id="signatureDate" name="signatureDate" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="signatureType" class="form-label required">Signature Method</label>
                            <select class="form-select" id="signatureType" name="signatureType" required>
                                <option value="">Select Method</option>
                                <option value="upload">Upload Signature</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <div id="signatureUpload" class="d-none mb-3">
                        <label for="signatureFile" class="form-label">Upload Signature</label>
                        <input type="file" class="form-control" id="signatureFile" name="signatureFile" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="form-text">Upload a clear image of your signature (JPG, PNG, PDF)</div>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                </div>

                ${TurnstileConfig.enabled
                    ? `<div class="cf-turnstile mb-3" data-sitekey="${TurnstileConfig.siteKey}"></div>`
                    : (TurnstileConfig.required
                        ? '<div class="alert alert-warning mb-3" role="alert">Security verification is temporarily unavailable. Please try again later.</div>'
                        : '')}
            </div>
        `;
        this.appendStep(stepHtml);
    },

    // Create document upload section
    createDocumentUploadSection(docType, label, required) {
        const config = FormData.documentTypes[docType] || {};
        const inputId = `buyer-document-${docType}`;
        const acceptedTypes = Array.isArray(config.acceptedTypes)
            ? config.acceptedTypes.join(',')
            : '.pdf,.doc,.docx,.jpg,.jpeg,.png';
        const maxSize = Number(config.maxSize) || 10;

        return `
            <div class="mb-4" data-doc-type="${docType}" data-required="${required ? 'true' : 'false'}">
                <h6 class="mb-3">${label} ${required ? '<span class="text-danger">*</span>' : ''}</h6>
                <label class="file-upload-container" for="${inputId}" tabindex="0" role="button"
                       aria-required="${required ? 'true' : 'false'}"
                       aria-describedby="${inputId}-help ${inputId}-error">
                    <span class="file-upload-icon d-block">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </span>
                    <span class="d-block mb-2">Click to upload or drag and drop files here</span>
                    <span class="text-muted small d-block" id="${inputId}-help">Maximum file size: ${maxSize}MB</span>
                </label>
                <input type="file" class="visually-hidden document-file-input" id="${inputId}"
                       name="${docType}" multiple accept="${acceptedTypes}"
                       tabindex="-1" aria-hidden="true">
                <div class="file-list mt-3" aria-live="polite"></div>
                <div class="document-error text-danger small mt-2" id="${inputId}-error"
                     role="alert" hidden></div>
            </div>
        `;
    },

    // Append step to form
    appendStep(stepHtml) {
        const form = document.getElementById('buyerApplicationForm');
        const navigationDiv = form.querySelector('.d-flex.justify-content-between');
        navigationDiv.insertAdjacentHTML('beforebegin', stepHtml);
    },

    // Bind additional events
    bindEvents() {
        // Property value calculation
        this.setupPropertyValueCalculation();
        
        // Document upload handlers
        this.setupDocumentUploads();
        
        // Signature method handler
        this.setupSignatureMethod();
        
        // Preferred areas management
        this.setupPreferredAreas();
        
        // Auto-save functionality
        this.setupAutoSave();
    },

    // Setup property value calculation
    setupPropertyValueCalculation() {
        const propertyValueInput = document.getElementById('propertyValue');
        const downPaymentInput = document.getElementById('downPayment');
        const loanAmountInput = document.getElementById('loanAmount');

        if (propertyValueInput && downPaymentInput && loanAmountInput) {
            const calculateLoan = () => {
                const propertyValue = parseFloat(propertyValueInput.value) || 0;
                const downPayment = parseFloat(downPaymentInput.value) || 0;
                const loanAmount = Math.max(0, propertyValue - downPayment);
                loanAmountInput.value = loanAmount.toFixed(2);
            };

            propertyValueInput.addEventListener('input', calculateLoan);
            downPaymentInput.addEventListener('input', calculateLoan);
        }
    },

    // Setup document upload functionality
    setupDocumentUploads() {
        document.querySelectorAll('.file-upload-container').forEach(container => {
            const section = container.closest('[data-doc-type]');
            const fileInput = section?.querySelector('input[type="file"]');
            const fileList = section?.querySelector('.file-list');

            if (!fileInput || !fileList) {
                return;
            }

            // The native label/input relationship handles taps reliably on
            // mobile. Add explicit keyboard activation for accessibility.
            container.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    fileInput.click();
                }
            });

            // File selection
            fileInput.addEventListener('change', (e) => {
                this.handleFileUpload(e.target, fileList);
            });

            // Drag and drop
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                container.classList.add('drag-over');
            });

            container.addEventListener('dragleave', () => {
                container.classList.remove('drag-over');
            });

            container.addEventListener('drop', (e) => {
                e.preventDefault();
                container.classList.remove('drag-over');
                fileInput.files = e.dataTransfer.files;
                this.handleFileUpload(fileInput, fileList);
            });
        });
    },

    // Handle file upload
    handleFileUpload(fileInput, fileListContainer) {
        const files = Array.from(fileInput.files);
        const docType = fileInput.name;
        const section = fileInput.closest('[data-doc-type]');
        const uploadContainer = section?.querySelector('.file-upload-container');
        const validationErrors = [];

        fileListContainer.innerHTML = '';

        files.forEach((file, index) => {
            const validation = FormValidation.validateFile(file, docType);
            
            const fileItem = document.createElement('div');
            fileItem.className = `file-item ${validation.isValid ? '' : 'border-danger'}`;
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info';
            fileInfo.innerHTML = '<i class="fas fa-file file-icon" aria-hidden="true"></i>';

            const fileName = document.createElement('span');
            fileName.textContent = `${file.name} (${this.formatFileSize(file.size)})`;
            fileInfo.appendChild(fileName);
            fileItem.appendChild(fileInfo);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-sm btn-link text-danger remove-file';
            removeButton.dataset.index = index;
            removeButton.setAttribute('aria-label', `Remove ${file.name}`);
            removeButton.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
            fileItem.appendChild(removeButton);

            if (!validation.isValid) {
                validationErrors.push(...validation.errors.map(error => `${file.name}: ${error}`));
                const errorDiv = document.createElement('div');
                errorDiv.className = 'text-danger small mt-1';
                errorDiv.textContent = validation.errors.join(', ');
                fileItem.appendChild(errorDiv);
            }

            fileListContainer.appendChild(fileItem);

            // Remove file functionality
            removeButton.addEventListener('click', () => {
                this.removeFile(fileInput, index);
            });
        });

        const requiresDocument = section?.dataset.required === 'true';
        if (files.length === 0 && requiresDocument) {
            FormValidation.showDocumentError(docType, 'This document is required');
        } else if (validationErrors.length > 0) {
            FormValidation.showDocumentError(docType, validationErrors.join(' '));
        } else {
            FormValidation.clearDocumentError(docType);
        }

        uploadContainer?.classList.toggle('has-files', files.length > 0 && validationErrors.length === 0);
    },

    // Remove file from input
    removeFile(fileInput, indexToRemove) {
        const dt = new DataTransfer();
        const files = Array.from(fileInput.files);
        
        files.forEach((file, index) => {
            if (index !== indexToRemove) {
                dt.items.add(file);
            }
        });
        
        fileInput.files = dt.files;
        
        // Refresh file list display
        const fileListContainer = fileInput.closest('[data-doc-type]')?.querySelector('.file-list');
        if (!fileListContainer) {
            return;
        }
        this.handleFileUpload(fileInput, fileListContainer);
    },

    // Format file size
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    // Setup signature method
    setupSignatureMethod() {
        const signatureTypeSelect = document.getElementById('signatureType');
        if (signatureTypeSelect) {
            const updateSignatureUpload = () => {
                const uploadDiv = document.getElementById('signatureUpload');
                const signatureFile = document.getElementById('signatureFile');

                uploadDiv.classList.add('d-none');
                signatureFile.removeAttribute('required');

                if (signatureTypeSelect.value === 'upload') {
                    uploadDiv.classList.remove('d-none');
                    signatureFile.setAttribute('required', 'required');
                }
            };

            signatureTypeSelect.addEventListener('change', updateSignatureUpload);
            updateSignatureUpload();
        }
    },

    // Setup preferred areas management
    setupPreferredAreas() {
        const addAreaBtn = document.getElementById('addPreferredArea');
        if (addAreaBtn) {
            addAreaBtn.addEventListener('click', () => {
                this.addPreferredArea();
            });
        }
        this.renumberPreferredAreas();
    },

    renumberPreferredAreas() {
        const items = document.querySelectorAll('#preferredAreas .preferred-area-item');
        items.forEach((item, index) => {
            const number = index + 1;
            item.dataset.areaId = String(number);
            const mappings = [
                ['.preferred-region', 'preferred-region'],
                ['.preferred-town', 'preferred-town'],
                ['input[name="preferredLocation[]"]', 'preferred-location'],
                ['input[name="preferredSuburb[]"]', 'preferred-suburb']
            ];
            mappings.forEach(([selector, prefix]) => {
                const field = item.querySelector(selector);
                const label = field?.closest('.mb-3')?.querySelector('label');
                if (field) field.id = `${prefix}-${number}`;
                if (label) label.htmlFor = `${prefix}-${number}`;
            });
            item.querySelector('.remove-area')
                ?.setAttribute('aria-label', `Remove preferred area ${number}`);
        });
    },

    // Add preferred area
    addPreferredArea() {
        const container = document.getElementById('preferredAreas');
        const areaCount = container.children.length;
        
        if (areaCount < 5) { // Limit to 5 areas
            const areaHtml = `
                <div class="preferred-area-item">
                    <button type="button" class="remove-area btn btn-sm btn-link text-danger" title="Remove area">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Region</label>
                            <select class="form-select preferred-region" name="preferredRegion[]">
                                <option value="">Select Region</option>
                                ${FormData.regions.map(region => `<option value="${region}">${region}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Town</label>
                            <select class="form-select preferred-town" name="preferredTown[]">
                                <option value="">Select Town</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="preferredLocation[]">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Suburb</label>
                            <input type="text" class="form-control" name="preferredSuburb[]">
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', areaHtml);
            
            // Add event listener for remove button
            const newArea = container.lastElementChild;
            newArea.querySelector('.remove-area').addEventListener('click', () => {
                newArea.remove();
                this.renumberPreferredAreas();
            });
            this.renumberPreferredAreas();

            // Add event listener for region change
            const regionSelect = newArea.querySelector('.preferred-region');
            regionSelect.addEventListener('change', (e) => {
                const townSelect = newArea.querySelector('.preferred-town');
                this.updateTownOptions(e.target, townSelect);
            });
        }
    },

    // Update town options for preferred areas
    updateTownOptions(regionSelect, townSelect) {
        const selectedRegion = regionSelect.value;
        townSelect.innerHTML = '<option value="">Select Town</option>';
        
        if (selectedRegion && FormData.townsByRegion[selectedRegion]) {
            FormData.townsByRegion[selectedRegion].forEach(town => {
                const option = document.createElement('option');
                option.value = town;
                option.textContent = town;
                townSelect.appendChild(option);
            });
        }
    },

    // Setup auto-save functionality
    setupAutoSave() {
        const formInputs = document.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.saveFormData();
            });
        });

        // Auto-save every 30 seconds
        setInterval(() => {
            this.saveFormData();
        }, 30000);
    },

    // Navigate to next step
    nextStep() {
        if (FormValidation.validateStep(this.currentStep)) {
            this.saveFormData();
            if (this.currentStep < this.totalSteps) {
                this.goToStep(this.currentStep + 1);
            } else {
                this.submitForm();
            }
        }
    },

    // Navigate to previous step
    previousStep() {
        if (this.currentStep > 1) {
            this.goToStep(this.currentStep - 1);
        }
    },

    // Go to specific step
    goToStep(stepNumber) {
        if (stepNumber >= 1 && stepNumber <= this.totalSteps) {
            this.hideStep(this.currentStep);
            this.currentStep = stepNumber;
            this.showStep(this.currentStep);
            this.updateProgressIndicator();
            this.updateNavigation();
            FormValidation.currentStep = this.currentStep;
        }
    },

    // Check if navigation to step is allowed
    canNavigateToStep(stepNumber) {
        // Allow backward navigation
        if (stepNumber <= this.currentStep) {
            return true;
        }
        
        // Check if all previous steps are valid
        for (let i = 1; i < stepNumber; i++) {
            if (!FormValidation.stepValidation[i]) {
                return false;
            }
        }
        
        return true;
    },

    // Show specific step
    showStep(stepNumber) {
        const stepElement = document.getElementById(`step-${stepNumber}`);
        if (stepElement) {
            stepElement.classList.add('active');
        }
    },

    // Hide specific step
    hideStep(stepNumber) {
        const stepElement = document.getElementById(`step-${stepNumber}`);
        if (stepElement) {
            stepElement.classList.remove('active');
        }
    },

    // Update progress indicator
    updateProgressIndicator() {
        const progressBar = document.getElementById('progressBar');
        const stepLabels = document.querySelectorAll('.step-label');
        
        if (progressBar) {
            const progress = (this.currentStep / this.totalSteps) * 100;
            progressBar.style.width = `${progress}%`;
        }

        stepLabels.forEach((label, index) => {
            const stepNum = index + 1;
            label.classList.remove('active', 'completed');
            
            if (stepNum === this.currentStep) {
                label.classList.add('active');
            } else if (stepNum < this.currentStep) {
                label.classList.add('completed');
            }
        });

        // Update step number display
        const stepNumberElement = document.getElementById('currentStepNumber');
        if (stepNumberElement) {
            stepNumberElement.textContent = this.currentStep;
        }
    },

    // Update navigation buttons
    updateNavigation() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if (prevBtn) {
            prevBtn.disabled = this.currentStep === 1;
        }

        if (nextBtn) {
            if (this.currentStep === this.totalSteps) {
                nextBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Application';
                nextBtn.className = 'btn btn-success';
                nextBtn.disabled = TurnstileConfig.required && !TurnstileConfig.configured;
            } else {
                nextBtn.innerHTML = 'Next<i class="fas fa-arrow-right ms-2"></i>';
                nextBtn.className = 'btn btn-primary';
                nextBtn.disabled = false;
            }
        }
    },

    // Save form data for this tab only. Applicant identity and financial data
    // must not persist indefinitely in origin-wide localStorage.
    saveFormData() {
       // const formData = new FormData(document.getElementById('buyerApplicationForm'));
		const form = document.getElementById('buyerApplicationForm');
		const formData = new window.FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                // Handle multiple values (like checkboxes)
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }
        
        sessionStorage.setItem('nuru-buyer-form-data', JSON.stringify(data));
        sessionStorage.setItem('nuru-buyer-current-step', this.currentStep.toString());
    },

    // Load data saved in the current tab.
    loadSavedData() {
        const savedData = sessionStorage.getItem('nuru-buyer-form-data');
        const savedStep = sessionStorage.getItem('nuru-buyer-current-step');
        
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                this.populateFormData(data);
            } catch (error) {
                console.error('Error loading saved data:', error);
            }
        }
        
        if (savedStep) {
            this.currentStep = parseInt(savedStep);
            this.goToStep(this.currentStep);
        }
    },

    // Populate form with saved data
    populateFormData(data) {
        Object.entries(data).forEach(([key, value]) => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element) {
                // Skip file inputs - they cannot be set programmatically
                if (element.type === 'file') {
                    return;
                }
                
                if (element.type === 'checkbox' || element.type === 'radio') {
                    if (Array.isArray(value)) {
                        value.forEach(val => {
                            const checkbox = document.querySelector(`[name="${key}"][value="${val}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    } else {
                        element.checked = element.value === value;
                    }
                } else {
                    element.value = value;
                }
            }
        });
    },

    // Submit form
    submitForm() {
        // Final validation
        const invalidSteps = [];
        for (let i = 1; i <= this.totalSteps; i++) {
            if (!FormValidation.validateStep(i, { focus: false })) {
                invalidSteps.push(i);
            }
        }

        if (invalidSteps.length > 0) {
            const firstInvalidStep = invalidSteps[0];
            this.goToStep(firstInvalidStep);
            FormValidation.validateStep(firstInvalidStep);
            FormValidation.showAlert(
                `Please review the highlighted fields in step ${firstInvalidStep} before submitting.`,
                'danger'
            );
            return;
        }

        // Submit the form via AJAX
        BuyerForm.submitApplication();
    }
};

// Initialize form steps when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    FormSteps.init();
});
