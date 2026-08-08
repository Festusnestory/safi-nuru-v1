// Seller Form - Main form handling and API interactions

const SellerForm = {
    // window.NURU_API_BASE (set inline in seller/index.php) is an absolute
    // path so this still resolves correctly when the page is reached via
    // the clean /seller route, not just the legacy html/material/seller/
    // URL where the relative '../api' default was resolved from.
    apiBaseUrl: window.NURU_API_BASE || '../api',
    csrfToken: null,
    additionalDocumentCount: 0,
    isSubmitting: false,
    submissionKey: null,
    receiptStorageKey: 'nuru-seller-submission-receipt',

    // Initialize the seller form
    init() {
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
            return;
        }
        
        this.getCSRFToken();
        this.bindEvents();
        this.setupInteractions();
        this.setupFileHandling();
        this.setupDynamicSections();
        this.restoreSubmissionReceipt();
        document.getElementById('sellerReturnToLogin')?.addEventListener('click', () => {
            sessionStorage.removeItem(this.receiptStorageKey);
        });
        this.attachMoneyFormatting(document.getElementById('sellingPrice'));

    },

    // Get CSRF token
    getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            this.csrfToken = metaTag.getAttribute('content');
        } else {
            this.generateCSRFToken();
        }
    },

    // Generate CSRF token
    async generateCSRFToken() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/csrf-token`);
            const data = await response.json();
            this.csrfToken = data.token;
        } catch (error) {
            console.error('Error fetching CSRF token:', error);
        }
    },

    // Bind form events
    bindEvents() {
        
        // Set current date for signature
        const signatureDateInput = document.getElementById('signatureDate');
        if (signatureDateInput) {
            signatureDateInput.value = FormDataUtils.getCurrentDate();
        }

        // Phone number formatting
        this.setupPhoneFormatting();
        
        // Currency formatting
        this.setupCurrencyFormatting();
        
        // Region-town dependencies
        this.setupRegionTownDependencies();
        
        // Age calculation
        this.setupAgeCalculation();
        
        // CRITICAL: Attach form submission handler
        this.setupFormSubmission();
        
    },

    // Setup form interactions
    setupInteractions() {
        // Marital status interactions
        this.setupMaritalStatusInteractions();
        
        // Sale type interactions
        this.setupSaleTypeInteractions();

        // Individual seller Property Type pricing breakdown (Vacant Land /
        // Plot & Plan / Existing House) - also drives Existing Property
        // Details visibility now that the old Land Type field is gone.
        this.setupSalePricingTypeInteractions();

        // Signature type interactions
        this.setupSignatureTypeInteractions();
    },

    // Setup phone number formatting
    setupPhoneFormatting() {
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                const formatted = FormDataUtils.formatPhoneNumber(e.target.value);
                if (formatted !== e.target.value) {
                    e.target.value = formatted;
                }
            });
        });
    },

    // Setup currency formatting
    setupCurrencyFormatting() {
        const currencyInputs = document.querySelectorAll('#sellingPrice');
        currencyInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                // Remove all non-digit and non-decimal characters
                let value = e.target.value.replace(/[^\d.]/g, '');
                
                // Ensure only one decimal point
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                
                // Format with commas
                if (value) {
                    const number = parseFloat(value);
                    if (!isNaN(number)) {
                        // Format while typing
                        const formatted = number.toLocaleString('en-US');
                        e.target.value = formatted;
                    }
                }
            });

            input.addEventListener('blur', (e) => {
                const value = parseFloat(e.target.value.replace(/[^\d.]/g, ''));
                if (!isNaN(value)) {
                    e.target.value = value.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            });
        });
    },

    // Setup region-town dependencies
    setupRegionTownDependencies() {
        // Main residential address
        const regionSelect = document.getElementById('region');
        if (regionSelect) {
            regionSelect.addEventListener('change', (e) => {
                                FormDataUtils.populateTowns(e.target.value, 'town');
            });
        }

        // Next of kin address
        const nokRegionSelect = document.getElementById('nokRegion');
        if (nokRegionSelect) {
            nokRegionSelect.addEventListener('change', (e) => {
                                FormDataUtils.populateTowns(e.target.value, 'nokTown');
            });
        }

        // Property address
        const propertyRegionSelect = document.getElementById('propertyRegion');
        if (propertyRegionSelect) {
            propertyRegionSelect.addEventListener('change', (e) => {
                                FormDataUtils.populateTowns(e.target.value, 'propertyTown');
            });
        }
    },

    // Setup age calculation
    setupAgeCalculation() {
        const dobInput = document.getElementById('dateOfBirth');
        if (dobInput) {
            dobInput.addEventListener('change', (e) => {
                const age = FormDataUtils.calculateAge(e.target.value);
                let ageDisplay = document.getElementById('ageDisplay');
                if (!ageDisplay) {
                    ageDisplay = document.createElement('div');
                    ageDisplay.id = 'ageDisplay';
                    dobInput.parentNode.appendChild(ageDisplay);
                }
                
                ageDisplay.textContent = `Age: ${age} years`;
                ageDisplay.className = age >= 18 ? 'small text-success' : 'small text-danger';
            });
        }
    },

    // Setup marital status interactions
    setupMaritalStatusInteractions() {
        const maritalStatusSelect = document.getElementById('maritalStatus');
        if (maritalStatusSelect) {
            maritalStatusSelect.addEventListener('change', (e) => {
                const spouseSection = document.getElementById('spouseDetails');
                const marriageCertCard = document.getElementById('marriageCertificateCard');
                const marriageCertificate = document.getElementById('marriageCertificateDoc');
                const married = e.target.value === 'Married';
                
                if (married) {
                    spouseSection?.classList.remove('d-none');
                    marriageCertCard?.style.setProperty('display', 'block');
                    marriageCertificate?.setAttribute('required', 'required');
                } else {
                    spouseSection?.classList.add('d-none');
                    marriageCertCard?.style.setProperty('display', 'none');
                    marriageCertificate?.removeAttribute('required');
                }
                spouseSection?.querySelectorAll('input, select').forEach(field => {
                    if (married) {
                        field.setAttribute('required', 'required');
                        field.setAttribute('aria-required', 'true');
                    } else {
                        field.removeAttribute('required');
                        field.removeAttribute('aria-required');
                        FormValidation.clearFieldValidation(field);
                    }
                });
            });
        }
    },

    // Setup sale type interactions
    setupSaleTypeInteractions() {
        const saleTypeCards = document.querySelectorAll('.sale-type-card');
        const saleTypeRadios = document.querySelectorAll('input[name="saleType"]');
        
        saleTypeCards.forEach(card => {
            card.addEventListener('click', () => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            });
        });

        saleTypeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                // Update card selection visuals
                saleTypeCards.forEach(card => {
                    card.classList.remove('selected');
                    if (card.dataset.type === e.target.value) {
                        card.classList.add('selected');
                    }
                });

                // Show/hide property developer details
                const developerSection = document.getElementById('propertyDeveloperDetails');
                if (e.target.value === 'Property Development') {
                    developerSection?.classList.remove('d-none');
                    developerSection?.querySelectorAll('input, select, textarea').forEach(field => {
                        field.disabled = false;
                        if (field.dataset.wasRequired === 'true') {
                            field.setAttribute('required', 'required');
                        }
                    });
                    this.updateDevelopmentsUnlockState();
                } else {
                    developerSection?.classList.add('d-none');
                    developerSection?.querySelectorAll('input, select, textarea').forEach(field => {
                        if (field.hasAttribute('required')) {
                            field.dataset.wasRequired = 'true';
                            field.removeAttribute('required');
                        }
                        field.disabled = true;
                        FormValidation.clearFieldValidation(field);
                    });
                }

                // Step 6 (single-property fields): replaced entirely by the
                // Developments section when Property Development is chosen.
                this.toggleIndividualPropertyStep(e.target.value === 'Property Development');
            });
        });
    },

    // Show/hide Step 6's single-property fields and toggle their `required`
    // attribute so FormValidation.validateStep(6) doesn't block on hidden
    // fields (it validates every element inside #step-6 regardless of visibility).
    toggleIndividualPropertyStep(isDevelopment) {
        const individualSection = document.getElementById('individualPropertyDetails');
        const skipMessage = document.getElementById('developmentPropertySkipMessage');
        if (!individualSection) return;

        if (isDevelopment) {
            individualSection.classList.add('d-none');
            skipMessage?.classList.remove('d-none');
            individualSection.querySelectorAll('[required]').forEach(field => {
                field.dataset.wasRequired = 'true';
                field.removeAttribute('required');
                FormValidation.clearFieldValidation(field);
            });
        } else {
            individualSection.classList.remove('d-none');
            skipMessage?.classList.add('d-none');
            individualSection.querySelectorAll('[data-was-required="true"]').forEach(field => {
                field.setAttribute('required', 'required');
            });
        }
    },

    // Setup individual-seller Property Type pricing breakdown (Vacant Land /
    // Plot & Plan / Existing House) above Land Size / Total Selling Price.
    // Vacant Land has no breakdown to compute from - the seller types the
    // price directly, same as the rest of the app did before this feature
    // existed. Plot & Plan / Existing House keep computing the total live.
    // This field also now drives the "Existing Property Details" section
    // (house size/rooms/bathrooms) that the old, now-removed Land Type
    // field used to control.
    setupSalePricingTypeInteractions() {
        const typeSelect = document.getElementById('salePricingType');
        if (!typeSelect) return;

        const plotSection = document.getElementById('plotAndPlanPricing');
        const houseSection = document.getElementById('existingHousePricing');
        const existingPropertySection = document.getElementById('existingPropertyDetails');
        const totalField = document.getElementById('sellingPrice');

        [plotSection, houseSection].forEach(section => {
            section?.querySelectorAll('.sale-pricing-input').forEach(input => {
                this.attachMoneyFormatting(input);
                input.addEventListener('input', () => this.calculateIndividualTotalSellingPrice());
            });
        });

        if (totalField) {
            this.attachMoneyFormatting(totalField);
        }

        typeSelect.addEventListener('change', (e) => {
            const isVacantLand = e.target.value === 'vacant_land';
            const isPlotAndPlan = e.target.value === 'plot_and_plan';
            const isExistingHouse = e.target.value === 'existing_house';

            this.toggleSalePricingSection(plotSection, isPlotAndPlan);
            this.toggleSalePricingSection(houseSection, isExistingHouse);

            if (existingPropertySection) {
                if (isExistingHouse) {
                    existingPropertySection.classList.remove('d-none');
                } else {
                    existingPropertySection.classList.add('d-none');
                    existingPropertySection.querySelectorAll('input, select, textarea').forEach(field => {
                        field.value = '';
                        FormValidation.clearFieldValidation(field);
                    });
                }
            }

            if (totalField) {
                if (isVacantLand) {
                    totalField.readOnly = false;
                    totalField.value = '';
                    FormValidation.clearFieldValidation(totalField);
                } else {
                    totalField.readOnly = true;
                    this.calculateIndividualTotalSellingPrice();
                }
            }
        });
    },

    // Show/hide one pricing sub-section, toggling `required` on its inputs
    // (and clearing them when hidden) so hidden fields never block validation
    // or contribute to the total - mirrors toggleIndividualPropertyStep()'s pattern.
    toggleSalePricingSection(section, show) {
        if (!section) return;
        section.classList.toggle('d-none', !show);
        section.querySelectorAll('input').forEach(field => {
            if (show) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
                field.value = '';
                FormValidation.clearFieldValidation(field);
            }
        });
    },

    // Parse a formatted money string ("1,234.50") back to a number, treating
    // empty/invalid input as 0 so the total never becomes NaN.
    parseMoneyValue(value) {
        const parsed = parseFloat(String(value ?? '').replace(/[^\d.]/g, ''));
        return isNaN(parsed) ? 0 : parsed;
    },

    // Recompute the individual seller's Total Selling Price from whichever
    // Sale Type section is currently active. Never reads from a hidden section.
    calculateIndividualTotalSellingPrice() {
        const type = document.getElementById('salePricingType')?.value;
        const totalField = document.getElementById('sellingPrice');
        if (!totalField || type === 'vacant_land') return; // manually typed - never overwritten

        let total = 0;
        if (type === 'plot_and_plan') {
            total = this.parseMoneyValue(document.getElementById('plotSellingPrice')?.value)
                + this.parseMoneyValue(document.getElementById('constructionAmount')?.value)
                + this.parseMoneyValue(document.getElementById('agentCommissionFeesPP')?.value);
        } else if (type === 'existing_house') {
            total = this.parseMoneyValue(document.getElementById('propertySellingPrice')?.value)
                + this.parseMoneyValue(document.getElementById('agentCommissionFeesEH')?.value);
        }

        totalField.value = total > 0 ? this.formatMoneyValue(total.toFixed(2)) : '';
        FormValidation.clearFieldValidation(totalField);
    },

    // Wire a single House Type block's own Sale Type toggle + live total
    // calculation. Scoped to htBlock so multiple House Types (across one or
    // more Developments) never interfere with each other.
    wireHouseTypePricing(htBlock) {
        const typeSelect = htBlock.querySelector('.ht-sale-pricing-type');
        const plotSection = htBlock.querySelector('.ht-plot-and-plan-pricing');
        const houseSection = htBlock.querySelector('.ht-existing-house-pricing');
        if (!typeSelect) return;

        const totalField = htBlock.querySelector('[name="htSellingPrice"]');
        if (totalField) {
            this.attachMoneyFormatting(totalField);
        }

        [plotSection, houseSection].forEach(section => {
            section?.querySelectorAll('.ht-pricing-input').forEach(input => {
                this.attachMoneyFormatting(input);
                input.addEventListener('input', () => this.calculateHouseTypeTotalSellingPrice(htBlock));
            });
        });

        typeSelect.addEventListener('change', (e) => {
            const isVacantLand = e.target.value === 'vacant_land';
            const isPlotAndPlan = e.target.value === 'plot_and_plan';
            const isExistingHouse = e.target.value === 'existing_house';

            this.toggleSalePricingSection(plotSection, isPlotAndPlan);
            this.toggleSalePricingSection(houseSection, isExistingHouse);

            if (totalField) {
                if (isVacantLand) {
                    totalField.readOnly = false;
                    totalField.value = '';
                    FormValidation.clearFieldValidation(totalField);
                } else {
                    totalField.readOnly = true;
                    this.calculateHouseTypeTotalSellingPrice(htBlock);
                }
            }
        });
    },

    // Recompute one House Type block's Total Selling Price (includes Other
    // Fees, unlike the individual seller calculation). Vacant Land is
    // manually typed and never overwritten here.
    calculateHouseTypeTotalSellingPrice(htBlock) {
        const type = htBlock.querySelector('.ht-sale-pricing-type')?.value;
        const totalField = htBlock.querySelector('[name="htSellingPrice"]');
        if (!totalField || type === 'vacant_land') return;

        const value = (name) => this.parseMoneyValue(htBlock.querySelector(`[name="${name}"]`)?.value);

        let total = 0;
        if (type === 'plot_and_plan') {
            total = value('htPlotSellingPrice') + value('htConstructionAmount')
                + value('htAgentCommissionFeesPP') + value('htOtherFeesPP');
        } else if (type === 'existing_house') {
            total = value('htPropertySellingPrice') + value('htAgentCommissionFeesEH') + value('htOtherFeesEH');
        }

        totalField.value = total > 0 ? this.formatMoneyValue(total.toFixed(2)) : '';
        FormValidation.clearFieldValidation(totalField);
    },

    // Setup signature type interactions
    setupSignatureTypeInteractions() {
        const signatureMethodCards = document.querySelectorAll('.signature-method-card');
        const signatureTypeRadios = document.querySelectorAll('input[name="signatureType"]');
        
        signatureMethodCards.forEach(card => {
            card.addEventListener('click', () => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            });
        });

        signatureTypeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                // Update card selection visuals
                signatureMethodCards.forEach(card => {
                    card.classList.remove('selected');
                    if (card.dataset.method === e.target.value) {
                        card.classList.add('selected');
                    }
                });

                // Show/hide signature method containers
                const uploadContainer = document.getElementById('signatureUploadContainer');
                const otpContainer = document.getElementById('otpContainer');
                
                if (e.target.value === 'upload') {
                    uploadContainer?.classList.remove('d-none');
                    otpContainer?.classList.add('d-none');
                } else if (e.target.value === 'otp') {
                    uploadContainer?.classList.add('d-none');
                    otpContainer?.classList.remove('d-none');
                }
            });
        });

        // Setup OTP send button
        const sendOTPButton = document.getElementById('sendOTP');
        if (sendOTPButton) {
            sendOTPButton.addEventListener('click', () => {
                this.sendOTP();
            });
        }
    },

    // Setup file handling
    setupFileHandling() {
        // Drag and drop for upload zones
        this.setupDragAndDrop();
        
        // File input changes
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file') {
                this.handleFileSelection(e.target);
            }
        });
    },

    // Setup drag and drop
    setupDragAndDrop() {
        const uploadZones = document.querySelectorAll('.upload-zone');
        
        uploadZones.forEach(zone => {
            const fileInput = zone.querySelector('input[type="file"]');
            if (!fileInput) return;

            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('dragover');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('dragover');
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('dragover');
                
                const files = Array.from(e.dataTransfer.files);
                this.handleDroppedFiles(fileInput, files);
            });

            zone.addEventListener('click', (event) => {
                if (event.target !== fileInput && !event.target.closest('button')) {
                    fileInput.click();
                }
            });
        });
    },

    // Handle file selection
    handleFileSelection(fileInput) {
        const files = Array.from(fileInput.files);
        this.processFiles(fileInput, files);
    },

    // Handle dropped files
    handleDroppedFiles(fileInput, files) {
        // Create new DataTransfer to set files
        const dt = new DataTransfer();
        files.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
        
        this.processFiles(fileInput, files);
    },

    // Process files
    processFiles(fileInput, files) {
        if (files.length === 0) {
            fileInput.closest('.document-upload-card')?.classList.remove('has-file');
            const preview = document.getElementById(fileInput.id + 'Preview')
                || (fileInput.id === 'propertyImages' ? document.getElementById('imagePreviewContainer') : null)
                || (fileInput.id === 'propertyVideos' ? document.getElementById('videoPreviewContainer') : null);
            preview?.replaceChildren();
            if (fileInput.hasAttribute('required')) {
                FormValidation.validateFileField(fileInput);
            } else {
                FormValidation.clearFieldValidation(fileInput);
            }
            return;
        }

        if (!FormValidation.validateFileField(fileInput)) {
            return;
        }

        // Update document upload card visual state
        const card = fileInput.closest('.document-upload-card');
        if (card && files.length > 0) {
            card.classList.add('has-file');
        }

        // Handle different file input types
        if (fileInput.id === 'propertyImages') {
            this.handlePropertyImages(files);
        } else if (fileInput.id === 'propertyVideos') {
            this.handlePropertyVideos(files);
        } else {
            this.handleDocumentUpload(fileInput, files);
        }
    },

    // Handle property images
    handlePropertyImages(files) {
        const container = document.getElementById('imagePreviewContainer');
        if (!container) return;

        container.innerHTML = '';
        
        files.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const preview = this.createImagePreview(file, index, 'propertyImages');
                container.appendChild(preview);
            }
        });
    },

    // Handle property videos
    handlePropertyVideos(files) {
        const container = document.getElementById('videoPreviewContainer');
        if (!container) return;

        container.innerHTML = '';
        
        files.forEach((file, index) => {
            if (file.type.startsWith('video/')) {
                const preview = this.createVideoPreview(file, index, 'propertyVideos');
                container.appendChild(preview);
            }
        });
    },

    // Handle document upload
    handleDocumentUpload(fileInput, files) {
        const previewContainer = document.getElementById(fileInput.id + 'Preview');
        if (!previewContainer) return;

        previewContainer.innerHTML = '';
        
        files.forEach((file, index) => {
            const preview = this.createDocumentPreview(file, index, fileInput.id);
            previewContainer.appendChild(preview);
        });
    },

    // Create image preview
    createImagePreview(file, index, inputId) {
        const preview = document.createElement('div');
        preview.className = 'col-md-3 mb-3';
        const wrapper = document.createElement('div');
        wrapper.className = 'position-relative';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.className = 'img-fluid rounded';
        img.alt = file.name;
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-2';
        removeBtn.setAttribute('aria-label', `Remove ${file.name}`);
        removeBtn.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        removeBtn.onclick = () => this.removeFile(inputId, index);
        const name = document.createElement('div');
        name.className = 'small text-muted mt-1';
        name.textContent = file.name;
        wrapper.append(img, removeBtn, name);
        preview.appendChild(wrapper);
        return preview;
    },

    // Create video preview
    createVideoPreview(file, index, inputId) {
        const preview = document.createElement('div');
        preview.className = 'col-md-4 mb-3';
        const wrapper = document.createElement('div');
        wrapper.className = 'position-relative';
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.className = 'w-100 rounded';
        video.controls = true;
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-2';
        removeBtn.setAttribute('aria-label', `Remove ${file.name}`);
        removeBtn.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        removeBtn.onclick = () => this.removeFile(inputId, index);
        const name = document.createElement('div');
        name.className = 'small text-muted mt-1';
        name.textContent = file.name;
        wrapper.append(video, removeBtn, name);
        preview.appendChild(wrapper);
        return preview;
    },

    // Create document preview
    createDocumentPreview(file, index, inputId) {
        const preview = document.createElement('div');
        preview.className = 'file-preview d-flex align-items-center p-2 border rounded mb-2';
        
        const icon = file.type.includes('pdf') ? 'fa-file-pdf text-danger' :
                    file.type.includes('image') ? 'fa-file-image text-primary' : 
                    'fa-file text-secondary';
        const iconElement = document.createElement('i');
        iconElement.className = `fas ${icon} fa-2x me-3`;
        iconElement.setAttribute('aria-hidden', 'true');
        const details = document.createElement('div');
        details.className = 'flex-grow-1';
        const name = document.createElement('div');
        name.className = 'fw-bold';
        name.textContent = file.name;
        const size = document.createElement('small');
        size.className = 'text-muted';
        size.textContent = this.formatFileSize(file.size);
        details.append(name, size);
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-outline-danger btn-sm';
        removeButton.setAttribute('aria-label', `Remove ${file.name}`);
        removeButton.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
        removeButton.addEventListener('click', () => this.removeFile(inputId, index));
        preview.append(iconElement, details, removeButton);
        return preview;
    },

    // Remove file
    removeFile(inputId, index) {
        const fileInput = document.getElementById(inputId);
        if (!fileInput) return;
        
        const dt = new DataTransfer();
        const files = Array.from(fileInput.files);
        
        files.forEach((file, i) => {
            if (i !== index) dt.items.add(file);
        });
        
        fileInput.files = dt.files;
        
        // Refresh previews
        this.processFiles(fileInput, Array.from(fileInput.files));
        
        // Update validation
        FormValidation.validateFileField(fileInput);
    },

    // Format file size
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    // Setup dynamic sections
    setupDynamicSections() {
        // Additional documents
        const addDocButton = document.getElementById('addAdditionalDocument');
        if (addDocButton) {
            addDocButton.addEventListener('click', () => {
                this.addAdditionalDocument();
            });
        }

        // Developments (Property Development sale type)
        const addDevelopmentButton = document.getElementById('addDevelopment');
        if (addDevelopmentButton) {
            addDevelopmentButton.addEventListener('click', () => {
                this.addDevelopment();
            });
        }

        const developmentsContainer = document.getElementById('developmentsContainer');
        if (developmentsContainer) {
            developmentsContainer.addEventListener('input', () => this.updateDevelopmentsUnlockState());
            developmentsContainer.addEventListener('change', () => this.updateDevelopmentsUnlockState());
        }
    },

    // Add a new Development block (Development 1, 2, 3...)
    addDevelopment() {
        const container = document.getElementById('developmentsContainer');
        if (!container) return;

        const devIndex = container.children.length + 1;

        const devBlock = document.createElement('div');
        devBlock.className = 'card mb-3 development-block';
        devBlock.dataset.devIndex = devIndex;
        devBlock.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 development-label">Development ${devIndex}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-development" aria-label="Remove Development ${devIndex}" onclick="SellerForm.removeDevelopment(this)">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Development Name</label>
                        <input type="text" class="form-control" name="devName" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Region</label>
                        <select class="form-select" name="devRegion" required>
                            <option value="">Select Region</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Town</label>
                        <select class="form-select" name="devTown" required>
                            <option value="">Select Town</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="devLocation">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Suburb</label>
                        <input type="text" class="form-control" name="devSuburb">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <h6 class="mt-3 mb-2">House Types</h6>
                <div class="house-types-list"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm add-house-type" disabled>
                    <i class="fas fa-plus me-2"></i>Add House Type
                </button>
            </div>
        `;

        container.appendChild(devBlock);
        this.assignDynamicFieldLabels(devBlock, `development-${devIndex}`);

        // Populate region select and wire region -> town cascade
        const regionSelect = devBlock.querySelector('[name="devRegion"]');
        const townSelect = devBlock.querySelector('[name="devTown"]');
        FormDataUtils.populateRegionsInto(regionSelect);
        regionSelect.addEventListener('change', () => {
            FormDataUtils.populateTownsInto(regionSelect.value, townSelect);
        });

        // Wire this development's "Add House Type" button
        const addHouseTypeButton = devBlock.querySelector('.add-house-type');
        addHouseTypeButton.addEventListener('click', () => {
            this.addHouseType(devBlock);
        });

        // Auto-add House Type 1 immediately (matches the always-visible-first-item pattern)
        this.addHouseType(devBlock);

        this.updateDevelopmentsUnlockState();
    },

    // Add a new House Type block within a Development (House Type 1, 2, 3...)
    addHouseType(devBlock) {
        const list = devBlock.querySelector('.house-types-list');
        if (!list) return;

        const htIndex = list.children.length + 1;

        const htBlock = document.createElement('div');
        htBlock.className = 'card mb-2 house-type-block bg-light';
        htBlock.dataset.houseIndex = htIndex;
        htBlock.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="house-type-label">House Type ${htIndex}</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-house-type" aria-label="Remove House Type ${htIndex}" onclick="SellerForm.removeHouseType(this)">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label required">Property Zooning Status</label>
                        <select class="form-select" name="htPropertyType" required>
                            <option value="">Select Property Zooning Status</option>
                            ${FormDataUtils.developmentPropertyTypes.map(type => `<option value="${type}">${type}</option>`).join('')}
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label required">Number of House Units</label>
                        <input type="number" class="form-control" name="htUnits" min="1" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label required">House Type</label>
                        <select class="form-select ht-sale-pricing-type" name="htSalePricingType" required>
                            <option value="">Select House Type</option>
                            <option value="vacant_land">Vacant Land</option>
                            <option value="plot_and_plan">Plot &amp; Plan</option>
                            <option value="existing_house">Existing House</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>

                <div class="ht-plot-and-plan-pricing d-none">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label class="form-label required">Plot Selling Price</label>
                            <div class="input-group">
                                <span class="input-group-text">N$</span>
                                <input type="text" class="form-control money-input ht-pricing-input" name="htPlotSellingPrice" inputmode="decimal">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label required">Construction Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">N$</span>
                                <input type="text" class="form-control money-input ht-pricing-input" name="htConstructionAmount" inputmode="decimal">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label required">Agent Commission Fees</label>
                            <div class="input-group">
                                <span class="input-group-text">N$</span>
                                <input type="text" class="form-control money-input ht-pricing-input" name="htAgentCommissionFeesPP" inputmode="decimal">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label required">Other Fees</label>
                            <div class="input-group">
                                <span class="input-group-text">N$</span>
                                <input type="text" class="form-control money-input ht-pricing-input" name="htOtherFeesPP" inputmode="decimal">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <div class="ht-existing-house-pricing d-none">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="form-label required">Property Selling Price</label>
                            <div class="input-group">
                                <span class="input-group-text">N$</span>
                                <input type="text" class="form-control money-input ht-pricing-input" name="htPropertySellingPrice" inputmode="decimal">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label required">Agent Commission Fees</label>
                            <div class="input-group">
                                <span class="input-group-text">N$</span>
                                <input type="text" class="form-control money-input ht-pricing-input" name="htAgentCommissionFeesEH" inputmode="decimal">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label required">Other Fees</label>
                            <div class="input-group">
                                <span class="input-group-text">N$</span>
                                <input type="text" class="form-control money-input ht-pricing-input" name="htOtherFeesEH" inputmode="decimal">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label required">Land Size</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="htLandSize" required>
                            <span class="input-group-text">m&sup2;</span>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label required">House Size</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="htHouseSize" required>
                            <span class="input-group-text">m&sup2;</span>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label required">Total Selling Price</label>
                        <div class="input-group">
                            <span class="input-group-text">N$</span>
                            <input type="text" class="form-control money-input" name="htSellingPrice" inputmode="decimal" required readonly>
                        </div>
                        <div class="invalid-feedback"></div>
                        <div class="form-text">Calculated automatically from the House Type fields above.</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label required">No. of Rooms</label>
                        <input type="number" class="form-control" name="htRooms" min="1" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label required">No. of Bathrooms</label>
                        <input type="number" class="form-control" name="htBathrooms" min="1" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label required">Additional Features</label>
                    <textarea class="form-control" name="htAdditionalFeatures" rows="2" required></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        `;

        list.appendChild(htBlock);
        this.assignDynamicFieldLabels(htBlock, `development-${devBlock.dataset.devIndex}-house-${htIndex}`);
        this.wireHouseTypePricing(htBlock);
        this.updateDevelopmentsUnlockState();
    },

    // Remove a house type, but never the last remaining one in its development
    removeHouseType(button) {
        const htBlock = button.closest('.house-type-block');
        const list = htBlock.closest('.house-types-list');
        if (list.querySelectorAll('.house-type-block').length <= 1) {
            return; // button is disabled in this state, but guard anyway
        }
        htBlock.remove();
        this.renumberHouseTypes(list.closest('.development-block'));
        this.updateDevelopmentsUnlockState();
    },

    // Remove a development, but never the last remaining one
    removeDevelopment(button) {
        const devBlock = button.closest('.development-block');
        const container = document.getElementById('developmentsContainer');
        if (container.querySelectorAll(':scope > .development-block').length <= 1) {
            return;
        }
        devBlock.remove();
        this.renumberDevelopments();
        this.updateDevelopmentsUnlockState();
    },

    // Renumber "House Type N" labels/indices after an add/remove so they stay
    // sequential with no gaps
    renumberHouseTypes(devBlock) {
        devBlock.querySelectorAll('.house-type-block').forEach((block, i) => {
            block.dataset.houseIndex = i + 1;
            const label = block.querySelector('.house-type-label');
            if (label) label.textContent = `House Type ${i + 1}`;
        });
    },

    // Renumber "Development N" labels/indices after an add/remove
    renumberDevelopments() {
        document.querySelectorAll('#developmentsContainer > .development-block').forEach((block, i) => {
            block.dataset.devIndex = i + 1;
            const label = block.querySelector('.development-label');
            if (label) label.textContent = `Development ${i + 1}`;
        });
    },

    assignDynamicFieldLabels(container, prefix) {
        container.querySelectorAll('input, select, textarea').forEach((field, index) => {
            if (!field.id) {
                field.id = `${prefix}-field-${index + 1}`;
            }
            const label = field.closest('.mb-2, .mb-3')?.querySelector('label');
            if (label) {
                label.htmlFor = field.id;
            }
        });
    },

    // Format a raw string as "456,960.78" - comma thousands separators added
    // automatically, decimal point typed manually, max 2 decimal digits
    formatMoneyValue(rawValue) {
        let value = rawValue.replace(/[^\d.]/g, '');
        const firstDot = value.indexOf('.');
        if (firstDot !== -1) {
            value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
        }
        let [intPart, decPart] = value.split('.');
        intPart = (intPart || '').replace(/^0+(?=\d)/, '');
        if (decPart !== undefined) {
            decPart = decPart.slice(0, 2);
        }
        const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        if (decPart !== undefined) {
            return `${withCommas}.${decPart}`;
        }
        return value.endsWith('.') ? `${withCommas}.` : withCommas;
    },

    // Attach live comma-formatting to a money input, preserving cursor
    // position (measured from the end of the string, which stays stable
    // when typing forward or backspacing near the end).
    attachMoneyFormatting(inputEl) {
        if (!inputEl) return;
        inputEl.addEventListener('input', (e) => {
            const input = e.target;
            const before = input.value;
            const cursorFromEnd = before.length - (input.selectionStart ?? before.length);
            const formatted = this.formatMoneyValue(before);
            input.value = formatted;
            const newPos = Math.max(0, formatted.length - cursorFromEnd);
            input.setSelectionRange(newPos, newPos);
        });
    },

    // A house-type block is "complete" once every required field in it is filled
    isHouseTypeBlockComplete(block) {
        const requiredFields = block.querySelectorAll('[required]');
        return Array.from(requiredFields).every(field => field.value.trim() !== '');
    },

    // Sequential-unlock: re-run after every input/change/remove within #developmentsContainer
    updateDevelopmentsUnlockState() {
        const container = document.getElementById('developmentsContainer');
        const addDevelopmentButton = document.getElementById('addDevelopment');
        if (!container || !addDevelopmentButton) return;

        const devBlocks = Array.from(container.querySelectorAll(':scope > .development-block'));

        devBlocks.forEach(devBlock => {
            const houseTypeBlocks = Array.from(devBlock.querySelectorAll('.house-type-block'));
            const addHouseTypeButton = devBlock.querySelector('.add-house-type');
            if (addHouseTypeButton) {
                const lastHouseType = houseTypeBlocks[houseTypeBlocks.length - 1];
                addHouseTypeButton.disabled = !lastHouseType || !this.isHouseTypeBlockComplete(lastHouseType);
            }

            // Never allow removing the only house type left in a development
            const onlyHouseType = houseTypeBlocks.length <= 1;
            houseTypeBlocks.forEach(htBlock => {
                const removeBtn = htBlock.querySelector('.remove-house-type');
                if (removeBtn) removeBtn.disabled = onlyHouseType;
            });
        });

        // Never allow removing the only development left
        const onlyDevelopment = devBlocks.length <= 1;
        devBlocks.forEach(devBlock => {
            const removeBtn = devBlock.querySelector('.remove-development');
            if (removeBtn) removeBtn.disabled = onlyDevelopment;
        });

        // Unlock the next Development once the current last development's House Type 1 is complete
        if (devBlocks.length === 0) {
            addDevelopmentButton.disabled = false;
        } else {
            const lastDev = devBlocks[devBlocks.length - 1];
            const firstHouseType = lastDev.querySelector('.house-type-block');
            addDevelopmentButton.disabled = !firstHouseType || !this.isHouseTypeBlockComplete(firstHouseType);
        }
    },

    // Collect the full nested developments/house-types structure for submission
    collectDevelopmentsData() {
        const developments = [];
        document.querySelectorAll('#developmentsContainer > .development-block').forEach((devBlock) => {
            const dev = {
                development_name: devBlock.querySelector('[name="devName"]')?.value || '',
                region: devBlock.querySelector('[name="devRegion"]')?.value || '',
                town: devBlock.querySelector('[name="devTown"]')?.value || '',
                location: devBlock.querySelector('[name="devLocation"]')?.value || '',
                suburb: devBlock.querySelector('[name="devSuburb"]')?.value || '',
                house_types: []
            };
            devBlock.querySelectorAll('.house-type-block').forEach((htBlock) => {
                const saleType = htBlock.querySelector('[name="htSalePricingType"]')?.value || '';
                dev.house_types.push({
                    property_type: htBlock.querySelector('[name="htPropertyType"]')?.value || '',
                    number_of_units: htBlock.querySelector('[name="htUnits"]')?.value || '',
                    // land_type no longer collected here - the backend derives
                    // an equivalent value from sale_pricing_type instead.
                    land_size: htBlock.querySelector('[name="htLandSize"]')?.value || '',
                    house_size: htBlock.querySelector('[name="htHouseSize"]')?.value || '',
                    selling_price: htBlock.querySelector('[name="htSellingPrice"]')?.value || '',
                    rooms: htBlock.querySelector('[name="htRooms"]')?.value || '',
                    bathrooms: htBlock.querySelector('[name="htBathrooms"]')?.value || '',
                    additional_features: htBlock.querySelector('[name="htAdditionalFeatures"]')?.value || '',
                    sale_pricing_type: saleType,
                    plot_selling_price: saleType === 'plot_and_plan' ? (htBlock.querySelector('[name="htPlotSellingPrice"]')?.value || '') : '',
                    construction_amount: saleType === 'plot_and_plan' ? (htBlock.querySelector('[name="htConstructionAmount"]')?.value || '') : '',
                    property_selling_price: saleType === 'existing_house' ? (htBlock.querySelector('[name="htPropertySellingPrice"]')?.value || '') : '',
                    agent_commission_fees: saleType === 'plot_and_plan'
                        ? (htBlock.querySelector('[name="htAgentCommissionFeesPP"]')?.value || '')
                        : (htBlock.querySelector('[name="htAgentCommissionFeesEH"]')?.value || ''),
                    other_fees: saleType === 'plot_and_plan'
                        ? (htBlock.querySelector('[name="htOtherFeesPP"]')?.value || '')
                        : (htBlock.querySelector('[name="htOtherFeesEH"]')?.value || '')
                });
            });
            developments.push(dev);
        });
        return developments;
    },

    // Validate the Developments section before allowing the user past Step 5
    // when Property Development is selected. FormValidation.validateStep()
    // does not cover these dynamically-created fields (static name lookup only).
    validateDevelopmentsSection() {
        const feedback = document.getElementById('developmentsFeedback');
        const devBlocks = document.querySelectorAll('#developmentsContainer > .development-block');

        if (devBlocks.length === 0) {
            if (feedback) {
                feedback.textContent = 'Please add at least one Development.';
                feedback.style.display = 'block';
            }
            return false;
        }

        let isValid = true;
        let firstInvalidField = null;

        devBlocks.forEach(devBlock => {
            const houseTypeBlocks = devBlock.querySelectorAll('.house-type-block');
            if (houseTypeBlocks.length === 0) {
                isValid = false;
            }
            devBlock.querySelectorAll('[required]').forEach(field => {
                if (field.value.trim() === '') {
                    isValid = false;
                    FormValidation.setFieldInvalid(field, FormValidation.errorMessages.required);
                    if (!firstInvalidField) firstInvalidField = field;
                } else {
                    FormValidation.setFieldValid(field);
                }
            });
        });

        if (feedback) {
            feedback.textContent = isValid ? '' : 'Please complete all required Development and House Type fields.';
            feedback.style.display = isValid ? 'none' : 'block';
        }

        if (!isValid && firstInvalidField) {
            firstInvalidField.focus();
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return isValid;
    },

    // Add additional document
    addAdditionalDocument() {
        this.additionalDocumentCount++;
        const container = document.getElementById('additionalDocuments');
        
        const docItem = document.createElement('div');
        docItem.className = 'additional-doc-item';
        docItem.innerHTML = `
            <button type="button" class="remove-additional-doc" aria-label="Remove additional document ${this.additionalDocumentCount}" onclick="this.closest('.additional-doc-item').remove()">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Document Name</label>
                    <input type="text" class="form-control" name="additionalDocName[]" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Document File</label>
                    <input type="file" class="form-control" name="additionalDocFile[]" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            </div>
        `;
        
        container.appendChild(docItem);
        this.assignDynamicFieldLabels(docItem, `additional-document-${this.additionalDocumentCount}`);
    },

    // Send OTP
    async sendOTP() {
        FormValidation.showAlert('SMS verification is not available. Please select a drawn or uploaded signature.', 'warning');
    },

    // Start OTP countdown
    startOTPCountdown() {
        const sendButton = document.getElementById('sendOTP');
        let countdown = 60;
        
        sendButton.disabled = true;
        
        const interval = setInterval(() => {
            sendButton.textContent = `Resend OTP (${countdown}s)`;
            countdown--;
            
            if (countdown < 0) {
                clearInterval(interval);
                sendButton.disabled = false;
                sendButton.textContent = 'Send OTP';
            }
        }, 1000);
    },

    // Prepare form data safely with proper field mapping
    async prepareFormDataSafe() {
        try {
            // Collect all form data
            const formData = await this.collectAllFormData();

            // Map field names for proper database compatibility
            const mappedData = this.mapSellerFormFieldsToDatabase(formData);

            return { data: mappedData };
        } catch (error) {
            console.error('Error preparing form data:', error);
            return null;
        }
    },

    // Collect all form data from the entire form
    async collectAllFormData() {
        const data = {};
        
        // Personal details - FIXED: Proper radio button handling for gender
        data.firstName = document.getElementById('firstName')?.value || '';
        data.middleName = document.getElementById('middleName')?.value || '';
        data.surname = document.getElementById('surname')?.value || '';
        data.maidenName = document.getElementById('maidenName')?.value || '';
        data.dateOfBirth = document.getElementById('dateOfBirth')?.value || '';
        data.idType = document.getElementById('idType')?.value || '';
        data.idNumber = document.getElementById('idNumber')?.value || '';
        data.nationality = document.getElementById('nationality')?.value || '';
        data.gender = this.getRadioButtonValue('gender'); // FIXED: Use radio button handler
        
        // Marital status
        data.maritalStatus = document.getElementById('maritalStatus')?.value || '';
        data.spouseFirstName = document.getElementById('spouseFirstName')?.value || '';
        data.spouseSurname = document.getElementById('spouseSurname')?.value || '';
        data.spouseDateOfBirth = document.getElementById('spouseDateOfBirth')?.value || '';
        data.spouseIdType = document.getElementById('spouseIdType')?.value || '';
        data.spouseIdNumber = document.getElementById('spouseIdNumber')?.value || '';
        data.spouseNationality = document.getElementById('spouseNationality')?.value || '';
        data.spouseGender = this.getRadioButtonValue('spouseGender'); // FIXED: Use radio button handler
        
        // Residential address
        data.erfNo = document.getElementById('erfNo')?.value || '';
        data.streetName = document.getElementById('streetName')?.value || '';
        data.suburb = document.getElementById('suburb')?.value || '';
        data.location = document.getElementById('location')?.value || '';
        data.region = document.getElementById('region')?.value || '';
        data.town = document.getElementById('town')?.value || '';
        data.email = document.getElementById('email')?.value || '';
        data.mobileNumber = document.getElementById('mobileNumber')?.value || '';
        data.poBox = document.getElementById('poBox')?.value || '';
        
        // Next of kin
        data.nokFirstName = document.getElementById('nokFirstName')?.value || '';
        data.nokSurname = document.getElementById('nokSurname')?.value || '';
        data.nokContactNumber = document.getElementById('nokContactNumber')?.value || '';
        data.nokEmail = document.getElementById('nokEmail')?.value || '';
        data.nokErfNo = document.getElementById('nokErfNo')?.value || '';
        data.nokStreetName = document.getElementById('nokStreetName')?.value || '';
        data.nokSuburb = document.getElementById('nokSuburb')?.value || '';
        data.nokLocation = document.getElementById('nokLocation')?.value || '';
        data.nokRegion = document.getElementById('nokRegion')?.value || '';
        data.nokTown = document.getElementById('nokTown')?.value || '';
        
        // Sale type - FIXED: Proper radio button handling for saleType
        data.saleType = this.getRadioButtonValue('saleType'); // FIXED: Use radio button handler
        if (data.saleType === 'Property Development') {
            data.developments = this.collectDevelopmentsData();
        } else {
            // propertyDetailType/landType are no longer collected here - the
            // backend derives an equivalent land_type from salePricingType
            // (vacant_land/plot_and_plan/existing_house) instead.
            data.landSize = document.getElementById('landSize')?.value || '';
            data.sellingPrice = document.getElementById('sellingPrice')?.value || '';
            data.salePricingType = document.getElementById('salePricingType')?.value || '';
            data.plotSellingPrice = document.getElementById('plotSellingPrice')?.value || '';
            data.constructionAmount = document.getElementById('constructionAmount')?.value || '';
            data.propertySellingPrice = document.getElementById('propertySellingPrice')?.value || '';
            data.agentCommissionFees = data.salePricingType === 'plot_and_plan'
                ? (document.getElementById('agentCommissionFeesPP')?.value || '')
                : (document.getElementById('agentCommissionFeesEH')?.value || '');
            data.houseSize = document.getElementById('houseSize')?.value || '';
            data.rooms = document.getElementById('rooms')?.value || '';
            data.bathrooms = document.getElementById('bathrooms')?.value || '';
            data.additionalFeatures = document.getElementById('additionalFeatures')?.value || '';
            data.propertyErfNo = document.getElementById('propertyErfNo')?.value || '';
            data.propertyStreetName = document.getElementById('propertyStreetName')?.value || '';
            data.propertySuburb = document.getElementById('propertySuburb')?.value || '';
            data.propertyLocation = document.getElementById('propertyLocation')?.value || '';
            data.propertyRegion = document.getElementById('propertyRegion')?.value || '';
            data.propertyTown = document.getElementById('propertyTown')?.value || '';
        }
        
        // Declarations - explicit id-to-key map (checkbox names end in "Declaration",
        // so a prefix-based selector/derivation never matches them - see PHP's
        // expected keys: certification, authorization, indemnification, commission, property_rights)
        const declarationFieldMap = {
            certificationDeclaration: 'certification',
            authorizationDeclaration: 'authorization',
            indemnificationDeclaration: 'indemnification',
            commissionFeesDeclaration: 'commission',
            propertyRightsDeclaration: 'property_rights'
        };
        const declarations = [];
        Object.keys(declarationFieldMap).forEach(checkboxId => {
            const checkbox = document.getElementById(checkboxId);
            if (checkbox && checkbox.checked) {
                declarations.push(declarationFieldMap[checkboxId]);
            }
        });
        data.declarations = declarations;
        
        // Signature - FIXED: Proper radio button handling for signatureType
        data.signatureLocation = document.getElementById('signatureLocation')?.value || '';
        data.signatureDate = document.getElementById('signatureDate')?.value || '';
        data.signatureType = this.getRadioButtonValue('signatureType'); // FIXED: Use radio button handler
        
        // File inputs - FIXED: Using correct field names from HTML
        data.id_document = await this.getFileInputData('idDocument');
        data.proof_of_residence = await this.getFileInputData('proofOfResidence');
        data.title_deed = await this.getFileInputData('titleDeed');
        data.marriage_certificate = await this.getFileInputData('marriageCertificateDoc');
        data.signatureFile = await this.getFileInputData('signatureFile');
        data.propertyImages = await this.getFileInputData('propertyImages');
        data.propertyVideos = await this.getFileInputData('propertyVideos');

        // Additional documents
        const additionalDocNames = [];
        const additionalDocNameElements = document.querySelectorAll('input[name="additionalDocName[]"]');
        additionalDocNameElements.forEach(element => {
            if (element.value.trim()) {
                additionalDocNames.push(element.value.trim());
            }
        });
        data.additionalDocName = additionalDocNames;

        const additionalDocFiles = [];
        const additionalDocFileElements = document.querySelectorAll('input[name="additionalDocFile[]"]');
        for (const element of additionalDocFileElements) {
            if (element.files && element.files.length > 0) {
                for (let i = 0; i < element.files.length; i++) {
                    const file = element.files[i];
                    const content = await this.readFileAsBase64(file);
                    additionalDocFiles.push({
                        name: file.name,
                        type: file.type,
                        size: file.size,
                        content: content
                    });
                }
            }
        }
        if (additionalDocFiles.length > 0) {
            data.additionalDocFile = { files: additionalDocFiles };
        }

        return data;
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

    // Get file input data - includes actual base64 file content, not just metadata,
    // since the server only ever saved 0-byte files without it
    async getFileInputData(inputName) {
        const input = document.getElementById(inputName);
        if (!input || !input.files || input.files.length === 0) {
            return { files: [], hasFiles: false, fileCount: 0 };
        }

        const files = [];
        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const content = await this.readFileAsBase64(file);
            files.push({
                name: file.name,
                type: file.type,
                size: file.size,
                content: content
            });
        }

        return {
            files: files,
            hasFiles: true,
            fileCount: files.length
        };
    },

    // Get radio button group value
    getRadioButtonValue(radioName) {
        const radios = document.querySelectorAll(`input[name="${radioName}"]`);
        for (let i = 0; i < radios.length; i++) {
            if (radios[i].checked) {
                return radios[i].value;
            }
        }
        return '';
    },

    // Map form fields to database field names
    mapSellerFormFieldsToDatabase(data) {
        // Log all collected data for debugging
        
        // Clean up the data - remove empty strings
        const cleaned = {};
        for (const [key, value] of Object.entries(data)) {
            if (value !== '' && value !== null && value !== undefined) {
                cleaned[key] = value;
            }
        }
        
        // Log cleaned data
        
        // Add CSRF token
        cleaned.csrf_token = this.csrfToken;
        
        return cleaned;
    },

    // Submit exactly once. The previous fallback retried the same application
    // as multipart data after any JSON-path error, which could create two
    // accounts/applications and also logged the applicant's full PII and file
    // payloads to the browser console.
    async submitApplication() {
        if (this.isSubmitting) {
            return;
        }
        const submitButton = document.querySelector('#sellerApplicationForm button[type="submit"]');
        try {
            if (!FormValidation.validateForm()) {
                FormStepper.navigateToStepWithError();
                this.handleSubmissionError('Please complete the highlighted required fields.');
                return;
            }

            const turnstileToken = document.querySelector('[name="cf-turnstile-response"]')?.value || '';
            if (document.querySelector('.cf-turnstile') && !turnstileToken) {
                this.handleSubmissionError('Please complete the CAPTCHA challenge.');
                return;
            }

            this.isSubmitting = true;
            submitButton?.setAttribute('disabled', 'disabled');
            if (submitButton) {
                submitButton.dataset.originalHtml ||= submitButton.innerHTML;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting…';
            }
            this.showLoadingOverlay();

            const structuredData = await this.prepareFormDataSafe();
            const submissionData = structuredData?.data || {};
            submissionData.csrf_token = this.csrfToken;
            submissionData['cf-turnstile-response'] = turnstileToken;
            this.submissionKey ||= (
                typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
                    ? crypto.randomUUID()
                    : `${Date.now()}-${Math.random().toString(16).slice(2)}`
            );
            submissionData.idempotency_key = this.submissionKey;

            const response = await fetch(`${this.apiBaseUrl}/applications/sellers/index.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Idempotency-Key': this.submissionKey
                },
                body: JSON.stringify(submissionData)
            });

            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (error) {
                throw new Error('The server returned an invalid response.');
            }

            if (!response.ok || !result.success) {
                this.handleSubmissionError(result.error || 'Unable to submit the application.');
                return;
            }

            this.handleSubmissionSuccess(result);
            this.submissionKey = null;
        } catch (error) {
            this.handleSubmissionError(error instanceof Error ? error.message : 'A network error occurred. Please try again.');
        } finally {
            this.isSubmitting = false;
            this.hideLoadingOverlay();
            submitButton?.removeAttribute('disabled');
            if (submitButton?.dataset.originalHtml) {
                submitButton.innerHTML = submitButton.dataset.originalHtml;
            }
        }
    },

    // Handle submission success
    handleSubmissionSuccess(result) {
        // Populate the durable receipt before clearing the form. This ensures a
        // successful server response is never hidden by a presentation failure.
        const modalElement = document.getElementById('successModal');
        const applicationNumber = document.getElementById('applicationNumber');
        const submittedNumber = result?.data?.application_number || result?.application_number || '';
        if (applicationNumber) {
            applicationNumber.textContent = submittedNumber;
        }
        const status = document.getElementById('applicationStatus');
        const submittedAt = document.getElementById('applicationSubmittedAt');
        if (status) status.textContent = result?.data?.status || 'Submitted';
        if (submittedAt) {
            const value = result?.data?.submission_date;
            submittedAt.textContent = value ? new Date(value.replace(' ', 'T')).toLocaleString() : new Date().toLocaleString();
        }

        const receipt = {
            application_number: submittedNumber,
            status: result?.data?.status || 'Submitted',
            submitted_at: result?.data?.submission_date || new Date().toISOString()
        };
        sessionStorage.setItem(this.receiptStorageKey, JSON.stringify(receipt));
        FormDataUtils.clearFormData();
        FormStepper.clearProgress();
        this.showSubmissionReceipt(receipt, modalElement);
    },

    restoreSubmissionReceipt() {
        const stored = sessionStorage.getItem(this.receiptStorageKey);
        if (!stored) return;
        try {
            this.showSubmissionReceipt(JSON.parse(stored), document.getElementById('successModal'));
        } catch (error) {
            sessionStorage.removeItem(this.receiptStorageKey);
        }
    },

    showSubmissionReceipt(receipt, modalElement = document.getElementById('successModal')) {
        const applicationNumber = document.getElementById('applicationNumber');
        const status = document.getElementById('applicationStatus');
        const submittedAt = document.getElementById('applicationSubmittedAt');
        if (applicationNumber) applicationNumber.textContent = receipt.application_number || '';
        if (status) status.textContent = receipt.status || 'Submitted';
        if (submittedAt) {
            const value = receipt.submitted_at;
            submittedAt.textContent = value ? new Date(String(value).replace(' ', 'T')).toLocaleString() : new Date().toLocaleString();
        }
        if (!modalElement) return;

        try {
            // The bundled Bootstrap is 5.0.0, which predates
            // Modal.getOrCreateInstance(). Constructing the modal works across
            // the supported Bootstrap 5 releases.
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
    handleSubmissionError(error) {
        if (typeof turnstile !== 'undefined') {
            try { turnstile.reset(); } catch (resetError) { /* widget may not be present */ }
        }
        document.querySelectorAll('.seller-submission-error').forEach(element => element.remove());
        const alertBox = document.createElement('div');
        alertBox.className = 'alert alert-danger seller-submission-error mt-3';
        alertBox.setAttribute('role', 'alert');
        alertBox.textContent = String(error || 'Unable to submit the application.');
        const submitButton = document.querySelector('#sellerApplicationForm button[type="submit"]');
        submitButton?.parentElement?.prepend(alertBox);
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

    // Setup form submission event handler
    setupFormSubmission() {
        
        const form = document.getElementById('sellerApplicationForm');
        if (!form) {
            console.error('CRITICAL ERROR: Form element #sellerApplicationForm not found!');
            return;
        }
        
        
        // Remove any existing listeners to avoid duplicates
        form.removeEventListener('submit', this.handleFormSubmit);
        
        // Bind the submit handler
        this.handleFormSubmit = this.handleFormSubmit.bind(this);
        
        // Add form submission listener
        form.addEventListener('submit', this.handleFormSubmit);
        
    },

    // Handle form submission event
    handleFormSubmit(event) {
        
        // Prevent default form submission
        event.preventDefault();
        event.stopPropagation();
        
        
        // Call the main submission method
        this.submitApplication();
    },
};

// Initialize seller form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    SellerForm.init();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SellerForm;
}
