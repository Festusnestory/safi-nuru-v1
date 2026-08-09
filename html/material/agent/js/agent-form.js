/**
 * Agent Form JavaScript Handler
 * Handles multi-step form navigation, validation, and submission
 */

class AgentForm {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 5;
        this.formData = {};
        this.uploadedFiles = {}; // Temporarily store selected files
        this.agentId = null;
    }

    init() {
        this.applyLengthConstraints();
        this.setupEventListeners();
        this.populateDropdowns();
        this.updateProgressIndicator();
    }

    applyLengthConstraints() {
        const limits = {
            surname: 100, first_name: 100, middle_name: 100, id_number: 13,
            res_email: 190, res_mobile: 20, res_street: 200,
            kin_surname: 100, kin_first_name: 100, kin_contact: 20,
            employer_name: 150, position: 100, employment_number: 100,
            emp_street: 200
        };
        document.querySelectorAll('#agentForm input[type="text"], #agentForm input[type="email"], #agentForm input[type="tel"], #agentForm textarea').forEach(field => {
            if (!field.hasAttribute('maxlength')) {
                field.maxLength = limits[field.name] || (field.tagName === 'TEXTAREA' ? 2000 : 255);
            }
        });
    }

    populateDropdowns() {
        // Region-first: Region selects are populated up front from the
        // canonical Namibian region/town list (assets/js/nuru-regions.js);
        // each Town select starts empty and is filled in when its paired
        // Region changes (see setupEventListeners/updateTownDropdown).
        const regions = Object.keys(window.NURU_TOWNS_BY_REGION).sort();
        ['res_region', 'kin_region', 'emp_region'].forEach(id => {
            const select = document.getElementById(id);
            regions.forEach(region => {
                const option = document.createElement('option');
                option.value = region;
                option.textContent = region;
                select.appendChild(option);
            });
        });

        const nationalities = [
            'Afghan', 'Albanian', 'Algerian', 'American', 'Andorran', 'Angolan', 'Antiguans', 'Argentinean', 'Armenian', 'Australian',
            'Austrian', 'Azerbaijani', 'Bahamian', 'Bahraini', 'Bangladeshi', 'Barbadian', 'Barbudans', 'Batswana', 'Belarusian',
            'Belgian', 'Belizean', 'Beninese', 'Bhutanese', 'Bolivian', 'Bosnian', 'Brazilian', 'British', 'Bruneian', 'Bulgarian',
            'Burkinabe', 'Burmese', 'Burundian', 'Cambodian', 'Cameroonian', 'Canadian', 'Cape Verdean', 'Central African',
            'Chadian', 'Chilean', 'Chinese', 'Colombian', 'Comoran', 'Congolese', 'Costa Rican', 'Croatian', 'Cuban', 'Cypriot',
            'Czech', 'Danish', 'Djibouti', 'Dominican', 'Dutch', 'East Timorese', 'Ecuadorean', 'Egyptian', 'Emirian',
            'Equatorial Guinean', 'Eritrean', 'Estonian', 'Ethiopian', 'Fijian', 'Filipino', 'Finnish', 'French', 'Gabonese',
            'Gambian', 'Georgian', 'German', 'Ghanaian', 'Greek', 'Grenadian', 'Guatemalan', 'Guinea-Bissauan', 'Guinean',
            'Guyanese', 'Haitian', 'Herzegovinian', 'Honduran', 'Hungarian', 'I-Kiribati', 'Icelander', 'Indian', 'Indonesian',
            'Iranian', 'Iraqi', 'Irish', 'Israeli', 'Italian', 'Ivorian', 'Jamaican', 'Japanese', 'Jordanian', 'Kazakhstani',
            'Kenyan', 'Kittian and Nevisian', 'Kuwaiti', 'Kyrgyz', 'Laotian', 'Latvian', 'Lebanese', 'Liberian', 'Libyan',
            'Liechtensteiner', 'Lithuanian', 'Luxembourger', 'Macedonian', 'Malagasy', 'Malawian', 'Malaysian', 'Maldivan',
            'Malian', 'Maltese', 'Marshallese', 'Mauritanian', 'Mauritian', 'Mexican', 'Micronesian', 'Moldovan', 'Monacan',
            'Mongolian', 'Moroccan', 'Mosotho', 'Motswana', 'Mozambican', 'Namibian', 'Nauruan', 'Nepalese', 'New Zealander',
            'Nicaraguan', 'Nigerian', 'Nigerien', 'North Korean', 'Northern Irish', 'Norwegian', 'Omani', 'Pakistani', 'Palauan',
            'Panamanian', 'Papua New Guinean', 'Paraguayan', 'Peruvian', 'Polish', 'Portuguese', 'Qatari', 'Romanian', 'Russian',
            'Rwandan', 'Saint Lucian', 'Salvadoran', 'Samoan', 'San Marinese', 'Sao Tomean', 'Saudi', 'Scottish', 'Senegalese',
            'Serbian', 'Seychellois', 'Sierra Leonean', 'Singaporean', 'Slovakian', 'Slovenian', 'Solomon Islander', 'Somali',
            'South African', 'South Korean', 'Spanish', 'Sri Lankan', 'Sudanese', 'Surinamer', 'Swazi', 'Swedish', 'Swiss',
            'Syrian', 'Taiwanese', 'Tajik', 'Tanzanian', 'Thai', 'Togolese', 'Tongan', 'Trinidadian or Tobagonian', 'Tunisian',
            'Turkish', 'Tuvaluan', 'Ugandan', 'Ukrainian', 'Uruguayan', 'Uzbekistani', 'Venezuelan', 'Vietnamese', 'Welsh',
            'Yemenite', 'Zambian', 'Zimbabwean'
        ];
        const nationalitySelect = document.getElementById('nationality');
        nationalities.forEach(nat => {
            const option = document.createElement('option');
            option.value = nat;
            option.textContent = nat;
            nationalitySelect.appendChild(option);
        });
    }

    setupEventListeners() {
        document.getElementById('nextBtn').addEventListener('click', () => this.nextStep());
        document.getElementById('prevBtn').addEventListener('click', () => this.prevStep());
        document.getElementById('agentForm').addEventListener('submit', (e) => this.handleSubmit(e));
        document.getElementById('gross_income').addEventListener('input', () => this.calculateNetPay());
        document.getElementById('total_deductions').addEventListener('input', () => this.calculateNetPay());
        document.getElementById('res_region').addEventListener('change', (e) => this.updateTownDropdown(e.target.value, 'res_town'));
        document.getElementById('kin_region').addEventListener('change', (e) => this.updateTownDropdown(e.target.value, 'kin_town'));
        document.getElementById('emp_region').addEventListener('change', (e) => this.updateTownDropdown(e.target.value, 'emp_town'));
        this.setupFileUploadHandlers();
    }

    // Populate a Town select from the canonical region/town data once its
    // paired Region select has a value - same region-first pattern the
    // buyer and seller forms use.
    updateTownDropdown(regionValue, townSelectId) {
        const townSelect = document.getElementById(townSelectId);
        if (!townSelect) return;

        townSelect.innerHTML = '<option value="">Select Town</option>';
        const towns = window.NURU_TOWNS_BY_REGION[regionValue] || [];
        towns.forEach(town => {
            const option = document.createElement('option');
            option.value = town;
            option.textContent = town;
            townSelect.appendChild(option);
        });
    }

    setupFileUploadHandlers() {
        const fileInputs = ['id_document', 'proof_residence', 'agency_ffc', 'agent_neab', 'agent_ffc'];
        fileInputs.forEach(inputId => {
            document.getElementById(inputId).addEventListener('change', (e) => this.handleFileUpload(e));
        });
        document.querySelectorAll('.remove-file').forEach(button => {
            button.addEventListener('click', () => {
                const wrapper = button.closest('.file-upload-wrapper');
                const input = wrapper?.querySelector('input[type="file"]');
                if (!input) return;
                input.value = '';
                delete this.uploadedFiles[input.name];
                wrapper.querySelector('.file-info')?.classList.add('d-none');
                wrapper.querySelector('.file-upload-label')?.classList.remove('d-none');
                wrapper.querySelector('.upload-feedback')?.remove();
                this.showFieldError(input, 'This document is required');
                input.focus();
            });
        });
    }

    nextStep() {
        if (this.validateCurrentStep()) {
            this.saveCurrentStepData();
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.showStep(this.currentStep);
                this.updateProgressIndicator();
            }
        }
    }

    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.showStep(this.currentStep);
            this.updateProgressIndicator();
        }
    }

    showStep(step) {
        document.querySelectorAll('.form-step').forEach(el => el.classList.add('d-none'));
        document.getElementById(`step-${step}`).classList.remove('d-none');

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');

        prevBtn.disabled = step === 1;
        prevBtn.classList.toggle('d-none', step === 1);
        if (step === this.totalSteps) {
            nextBtn.classList.add('d-none');
            submitBtn.classList.remove('d-none');
        } else {
            nextBtn.classList.remove('d-none');
            submitBtn.classList.add('d-none');
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    updateProgressIndicator() {
        document.querySelectorAll('.step-item').forEach((item, index) => {
            const step = index + 1;
            item.classList.toggle('active', step === this.currentStep);
            item.classList.toggle('completed', step < this.currentStep);
        });
        const label = document.querySelector(`.step-item[data-step="${this.currentStep}"] .step-label`)?.textContent || '';
        const mobileStep = document.getElementById('mobileAgentStep');
        const mobileLabel = document.getElementById('mobileAgentStepLabel');
        const mobileProgress = document.getElementById('mobileAgentProgress');
        if (mobileStep) mobileStep.textContent = this.currentStep;
        if (mobileLabel) mobileLabel.textContent = label;
        if (mobileProgress) mobileProgress.style.width = `${(this.currentStep / this.totalSteps) * 100}%`;
    }

    validateCurrentStep() {
        const stepEl = document.getElementById(`step-${this.currentStep}`);
        const requiredFields = stepEl.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else this.clearFieldError(field);
        });
        stepEl.querySelectorAll('input[maxlength], textarea[maxlength]').forEach(field => {
            if (Array.from(field.value.normalize('NFC')).length > field.maxLength) {
                this.showFieldError(field, `Must not exceed ${field.maxLength} characters`);
                isValid = false;
            }
        });
        if (!isValid) {
            stepEl.querySelector('.is-invalid')?.focus();
        }

        if (this.currentStep === 1) isValid = this.validatePersonalDetails() && isValid;
        else if (this.currentStep === 2) isValid = this.validateContactInfo() && isValid;
        else if (this.currentStep === 4) isValid = this.validateEmployment() && isValid;

        return isValid;
    }

    validatePersonalDetails() {
        let isValid = true;
        const dob = document.getElementById('date_of_birth').value;
        if (dob) {
            const age = this.calculateAge(dob);
            if (age < 18 || age > 120) {
                this.showFieldError(document.getElementById('date_of_birth'), 'Age must be between 18 and 120');
                isValid = false;
            }
        }

        const idType = document.getElementById('id_type').value;
        const idNumber = document.getElementById('id_number').value;
        if (idType === 'National ID' && idNumber && !/^\d{11}$/.test(idNumber)) {
            this.showFieldError(document.getElementById('id_number'), 'National ID must be 11 digits');
            isValid = false;
        }
        return isValid;
    }

    validateContactInfo() {
        let isValid = true;
        const email = document.getElementById('res_email').value;
        if (email && !this.isValidEmail(email)) {
            this.showFieldError(document.getElementById('res_email'), 'Please enter a valid email address');
            isValid = false;
        }
        const phone = document.getElementById('res_mobile').value;
        if (phone && !this.isValidPhone(phone)) {
            this.showFieldError(document.getElementById('res_mobile'), 'Please enter a valid phone number');
            isValid = false;
        }
        return isValid;
    }

    validateEmployment() {
        let isValid = true;
        const grossField = document.getElementById('gross_income');
        const deductionsField = document.getElementById('total_deductions');
        const netField = document.getElementById('net_pay');
        const grossIncome = Number.parseFloat(grossField.value);
        const deductions = Number.parseFloat(deductionsField.value);
        const netPay = Number.parseFloat(netField.value);
        [grossField, deductionsField, netField].forEach(field => this.clearFieldError(field));

        if (!Number.isFinite(grossIncome) || grossIncome <= 0) {
            this.showFieldError(grossField, 'Gross income must be greater than zero');
            isValid = false;
        }
        if (!Number.isFinite(deductions) || deductions < 0) {
            this.showFieldError(deductionsField, 'Total deductions cannot be negative');
            isValid = false;
        } else if (Number.isFinite(grossIncome) && deductions > grossIncome) {
            this.showFieldError(deductionsField, 'Total deductions cannot exceed gross income');
            isValid = false;
        }
        const expectedNet = grossIncome - deductions;
        if (!Number.isFinite(netPay) || !Number.isFinite(expectedNet) || Math.abs(netPay - expectedNet) > 0.01) {
            this.showFieldError(netField, 'Net pay must equal gross income minus total deductions');
            isValid = false;
        }
        return isValid;
    }

    calculateAge(dateString) {
        const today = new Date();
        const birthDate = new Date(dateString);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) age--;
        return age;
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    isValidPhone(phone) {
        return /^[\+]?[0-9\s\-\(\)]{7,15}$/.test(phone);
    }

    calculateNetPay() {
        const grossIncome = Number.parseFloat(document.getElementById('gross_income').value);
        const deductions = Number.parseFloat(document.getElementById('total_deductions').value);
        const netField = document.getElementById('net_pay');
        if (!Number.isFinite(grossIncome) || !Number.isFinite(deductions)) {
            netField.value = '';
            return;
        }
        netField.value = (grossIncome - deductions).toFixed(2);
        this.validateEmployment();
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        const div = document.createElement('div');
        div.className = 'invalid-feedback';
        div.id = `${field.id}-error`;
        div.setAttribute('role', 'alert');
        div.textContent = message;
        field.parentNode.appendChild(div);
        field.setAttribute('aria-describedby', div.id);
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        field.setAttribute('aria-invalid', 'false');
        const div = field.parentNode.querySelector('.invalid-feedback');
        if (div) div.remove();
    }

    saveCurrentStepData() {
        const stepEl = document.getElementById(`step-${this.currentStep}`);
        const inputs = stepEl.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.type === 'file' || !input.name) return;
            if ((input.type === 'radio' || input.type === 'checkbox') && !input.checked) return;
            this.formData[input.name] = input.value;
        });
    }

    handleFileUpload(event) {
        const file = event.target.files[0];
        const wrapper = event.target.closest('.file-upload-wrapper');
        const fileInfo = wrapper?.querySelector('.file-info');
        const uploadLabel = wrapper?.querySelector('.file-upload-label');
        const filename = fileInfo?.querySelector('.filename');

        if (!file) {
            delete this.uploadedFiles[event.target.name];
            fileInfo?.classList.add('d-none');
            uploadLabel?.classList.remove('d-none');
            return;
        }

        const allowedTypes = [
            'application/pdf','application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg','image/jpg','image/png'
        ];

        if (!allowedTypes.includes(file.type)) {
            this.showMessage('Invalid file type. Only PDF, DOC, DOCX, JPG, and PNG are allowed.', 'danger');
            event.target.value = '';
            delete this.uploadedFiles[event.target.name];
            fileInfo?.classList.add('d-none');
            uploadLabel?.classList.remove('d-none');
            return;
        }

        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            this.showMessage('Each file must be 10MB or smaller.', 'danger');
            event.target.value = '';
            delete this.uploadedFiles[event.target.name];
            fileInfo?.classList.add('d-none');
            uploadLabel?.classList.remove('d-none');
            return;
        }

        this.uploadedFiles[event.target.name] = file;
        this.clearFieldError(event.target);
        if (filename) filename.textContent = file.name;
        fileInfo?.classList.remove('d-none');
        uploadLabel?.classList.add('d-none');
        this.showUploadProgress(event.target.name, 'File selected, will upload on submission', 'info');
    }

    showUploadProgress(fileType, message, status = 'info') {
        const fileInput = document.getElementById(fileType);
        const feedback = document.createElement('div');
        feedback.className = 'upload-feedback mt-1';
        feedback.innerHTML = `<small class="text-${status}">${message}</small>`;
        const existing = fileInput.parentNode.querySelector('.upload-feedback');
        if (existing) existing.remove();
        fileInput.parentNode.appendChild(feedback);
    }

    showMessage(message, type = 'danger') {
        const container = document.getElementById('toastContainer');
        const alertBox = document.createElement('div');
        alertBox.className = `alert alert-${type} shadow-sm`;
        alertBox.setAttribute('role', 'alert');
        alertBox.textContent = message;
        container.appendChild(alertBox);
        window.setTimeout(() => alertBox.remove(), 8000);
    }

    async handleSubmit(event) {
        event.preventDefault();

        // Validate all steps
        for (let step = 1; step <= this.totalSteps; step++) {
            this.currentStep = step;
            if (!this.validateCurrentStep()) {
                this.showStep(step);
                return;
            }
        }

        // Ensure required files
        const requiredFiles = ['id_document', 'proof_residence', 'agency_ffc', 'agent_neab', 'agent_ffc'];
        const missingFiles = requiredFiles.filter(ft => !this.uploadedFiles[ft]);
        if (missingFiles.length > 0) {
            this.showMessage('Please upload all required documents before submitting.');
            return;
        }

        const turnstileToken = document.querySelector('[name="cf-turnstile-response"]')?.value || '';
        if (document.querySelector('.cf-turnstile') && !turnstileToken) {
            this.showMessage('Please complete the CAPTCHA challenge.');
            return;
        }

        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';
        submitBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'submit_application');
            formData.append('csrf_token', document.getElementById('csrfToken').value);
            formData.append('cf-turnstile-response', turnstileToken);

            Object.keys(this.formData).forEach(key => formData.append(key, this.formData[key]));
            Object.keys(this.uploadedFiles).forEach(key => formData.append(key, this.uploadedFiles[key]));

            const apiBase = window.NURU_API_BASE || '../api';
            const response = await fetch(`${apiBase}/applications/agent/index.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessMessage({
                    application_number: result.application_number,
                    status: result.status || 'Submitted'
                });
            } else {
                throw new Error(result.error || 'Unknown server error');
            }
        } catch (error) {
            console.error('Submission error:', error);
            if (typeof turnstile !== 'undefined') {
                try { turnstile.reset(); } catch (resetError) { /* widget may not be present */ }
            }
            this.showMessage('Application submission failed: ' + error.message);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    showSuccessMessage(data) {
        const html = `
            <div class="alert alert-success">
                <h4><i class="bi bi-check-circle"></i> Application Submitted Successfully!</h4>
                <p>Your agent application has been submitted and is now pending admin review.</p>
                <div class="mt-3">
                    <strong>Application Number:</strong> ${data.application_number}<br>
                    <strong>Status:</strong> ${data.status}
                </div>
            </div>
        `;
        document.querySelector('.form-card').innerHTML = html;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Make globally accessible
window.AgentForm = AgentForm;

document.addEventListener('DOMContentLoaded', () => {
    window.agentFormInstance = new AgentForm();
    window.agentFormInstance.init();
});
