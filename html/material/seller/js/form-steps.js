// Form Steps - Step navigation and stepper management for Seller Form

const FormStepper = {
    currentStep: 1,
    totalSteps: 9,
    completedSteps: new Set(),

    // Step configuration
    steps: [
        { id: 1, title: 'Personal Details', icon: 'fas fa-user', description: 'Basic personal information' },
        { id: 2, title: 'Marital Status', icon: 'fas fa-heart', description: 'Marital status and spouse details' },
        { id: 3, title: 'Residential Address', icon: 'fas fa-map-marker-alt', description: 'Current residential address' },
        { id: 4, title: 'Next of Kin', icon: 'fas fa-users', description: 'Emergency contact information' },
        { id: 5, title: 'Sale Type Selection', icon: 'fas fa-building', description: 'Type of property sale' },
        { id: 6, title: 'Property Details', icon: 'fas fa-home', description: 'Property information and pricing' },
        { id: 7, title: 'Document Upload', icon: 'fas fa-file-upload', description: 'Required documents' },
        { id: 8, title: 'Property Images/Video', icon: 'fas fa-camera', description: 'Property media files' },
        { id: 9, title: 'Acknowledgment & Declaration', icon: 'fas fa-check-circle', description: 'Final agreements and signature' }
    ],

    // Initialize stepper
    init() {
        // The stepper UI is now the same static progress-bar + step-label
        // row markup as the buyer form (see seller/index.php) rather than
        // JS-generated - createStepper()/updateStepper() (further down)
        // targeted the old .stepper-item markup and are unused now.
        this.setupNavigation();
        this.updateProgress();
        this.loadSavedProgress();
    },

    // Create stepper UI
    createStepper() {
        const stepperContainer = document.getElementById('formStepper');
        if (!stepperContainer) return;

        stepperContainer.innerHTML = '';

        this.steps.forEach((step, index) => {
            const stepElement = document.createElement('a');
            stepElement.href = '#';
            stepElement.className = 'stepper-item list-group-item list-group-item-action';
            stepElement.dataset.step = step.id;
            
            // Add state classes
            if (step.id === this.currentStep) {
                stepElement.classList.add('active');
            }
            if (this.completedSteps.has(step.id)) {
                stepElement.classList.add('completed');
            }
            if (step.id > this.currentStep && !this.completedSteps.has(step.id)) {
                stepElement.classList.add('disabled');
            }

            stepElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="step-icon">
                        ${this.completedSteps.has(step.id) ? 
                            '<i class="fas fa-check"></i>' : 
                            `<i class="${step.icon}"></i>`
                        }
                    </div>
                    <div class="step-content">
                        <div class="step-title">${step.title}</div>
                        <div class="step-subtitle">${step.description}</div>
                    </div>
                </div>
            `;

            // Add click handler
            stepElement.addEventListener('click', (e) => {
                e.preventDefault();
                const targetStep = parseInt(step.id);
                
                // Allow navigation to completed steps or current step
                if (this.canNavigateToStep(targetStep)) {
                    this.goToStep(targetStep);
                }
            });

            stepperContainer.appendChild(stepElement);
        });
    },

    // Setup navigation buttons
    setupNavigation() {
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        const navigationContainer = document.getElementById('navigationButtons');

        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextStep());
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.previousStep());
        }

        // Allow clicking on progress steps (matches the buyer form's stepper)
        document.querySelectorAll('.step-label').forEach(label => {
            label.addEventListener('click', (e) => {
                const targetStep = parseInt(e.target.getAttribute('data-step'));
                if (this.canNavigateToStep(targetStep)) {
                    this.goToStep(targetStep);
                }
            });
        });

        // Hide navigation on last step
        this.updateNavigationVisibility();
    },

    // Update navigation button visibility
    updateNavigationVisibility() {
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        const navigationContainer = document.getElementById('navigationButtons');

        if (this.currentStep === this.totalSteps) {
            // Hide navigation on last step (form has submit button)
            if (navigationContainer) {
                navigationContainer.classList.add('d-none');
            }
        } else {
            if (navigationContainer) {
                navigationContainer.classList.remove('d-none');
            }

            if (prevBtn) {
                prevBtn.disabled = this.currentStep === 1;
            }

            if (nextBtn) {
                if (this.currentStep === this.totalSteps) {
                    nextBtn.textContent = 'Submit Application';
                    nextBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Application';
                    nextBtn.className = 'btn btn-success';
                } else {
                    nextBtn.innerHTML = 'Next<i class="fas fa-arrow-right ms-2"></i>';
                    nextBtn.className = 'btn btn-primary';
                }
            }
        }
    },

    // Check if can navigate to step
    canNavigateToStep(stepNumber) {
        // Can navigate to current step, completed steps, or next step if current step is valid
        return stepNumber === this.currentStep || 
               this.completedSteps.has(stepNumber) || 
               (stepNumber === this.currentStep + 1 && this.isCurrentStepValid());
    },

    // Go to specific step
    goToStep(stepNumber, options = {}) {
        const isBackwardNavigation = stepNumber < this.currentStep;
        if (!(options.skipValidation && isBackwardNavigation) && !this.canNavigateToStep(stepNumber)) {
            return false;
        }

        // Hide current step
        this.hideCurrentStep();
        
        // Update current step
        const previousStep = this.currentStep;
        this.currentStep = stepNumber;

        // Show new step with animation
        this.showCurrentStep(stepNumber > previousStep ? 'right' : 'left');

        // Update UI
        this.updateStepper();
        this.updateProgress();
        this.updateNavigationVisibility();
        this.updateStepNumbers();

        // Save progress
        this.saveProgress();

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });

        return true;
    },

    // Go to next step
    nextStep() {
        if (this.currentStep >= this.totalSteps) {
            return false;
        }

        // Validate current step
        if (!this.validateCurrentStep()) {
            return false;
        }

        // Mark current step as completed
        this.markStepComplete(this.currentStep);

        // Move to next step
        return this.goToStep(this.currentStep + 1);
    },

    // Go to previous step
    previousStep() {
        if (this.currentStep <= 1) {
            return false;
        }

        // Going back is always a recovery/navigation action. It must never be
        // blocked by validation errors on the step the applicant is leaving.
        return this.goToStep(this.currentStep - 1, { skipValidation: true });
    },

    // Validate current step
    validateCurrentStep() {
        let isValid = FormValidation.validateStep(this.currentStep);

        // Step 5 (Sale Type Selection): validateStep() doesn't cover the
        // dynamically-created Developments/House Types fields (static name
        // lookup only), so validate them separately when that path is active.
        if (this.currentStep === 5) {
            const saleType = document.querySelector('input[name="saleType"]:checked')?.value;
            if (saleType === 'Property Development') {
                isValid = SellerForm.validateDevelopmentsSection() && isValid;
            }
        }

        return isValid;
    },

    // Check if current step is valid
    isCurrentStepValid() {
        return FormValidation.validateStep(this.currentStep);
    },

    // Mark step as completed
    markStepComplete(stepNumber) {
        this.completedSteps.add(stepNumber);
        this.updateStepper();
        this.updateProgress();
        this.saveProgress();
    },

    // Hide current step
    hideCurrentStep() {
        const currentStepElement = document.getElementById(`step-${this.currentStep}`);
        if (currentStepElement) {
            currentStepElement.classList.remove('active', 'slide-in-right', 'slide-in-left');
        }
    },

    // Show current step with animation
    showCurrentStep(direction = 'right') {
        const newStepElement = document.getElementById(`step-${this.currentStep}`);
        if (newStepElement) {
            newStepElement.classList.add('active');
            
            // Add slide animation
            setTimeout(() => {
                newStepElement.classList.add(direction === 'right' ? 'slide-in-right' : 'slide-in-left');
            }, 50);

            // Focus first input in new step
            setTimeout(() => {
                const firstInput = newStepElement.querySelector('input, select, textarea');
                if (firstInput && !firstInput.disabled) {
                    firstInput.focus();
                }
            }, 300);
        }
    },

    // Update stepper UI
    updateStepper() {
        const stepperItems = document.querySelectorAll('.stepper-item');
        
        stepperItems.forEach((item, index) => {
            const stepNumber = index + 1;
            const stepIcon = item.querySelector('.step-icon');
            
            // Remove all state classes
            item.classList.remove('active', 'completed', 'disabled');
            
            // Add appropriate state classes
            if (stepNumber === this.currentStep) {
                item.classList.add('active');
            } else if (this.completedSteps.has(stepNumber)) {
                item.classList.add('completed');
                // Update icon to checkmark
                if (stepIcon) {
                    stepIcon.innerHTML = '<i class="fas fa-check"></i>';
                }
            } else if (stepNumber > this.currentStep && !this.completedSteps.has(stepNumber)) {
                item.classList.add('disabled');
            }

            // Restore original icon if not completed
            if (!this.completedSteps.has(stepNumber) && stepIcon) {
                const originalIcon = this.steps[index].icon;
                stepIcon.innerHTML = `<i class="${originalIcon}"></i>`;
            }
        });
    },

    // Update progress indicators
    updateProgress() {
        const progressPercentage = (this.currentStep / this.totalSteps) * 100;
        const completedPercentage = (this.completedSteps.size / this.totalSteps) * 100;

        // Update main progress bar
        const progressBar = document.getElementById('progressBar');
        if (progressBar) {
            progressBar.style.width = progressPercentage + '%';
        }
        const headerProgressBar = document.getElementById('headerProgressBar');
        if (headerProgressBar) {
            headerProgressBar.style.width = progressPercentage + '%';
        }
        const mobileProgressBar = document.getElementById('mobileProgressBar');
        if (mobileProgressBar) {
            mobileProgressBar.style.width = progressPercentage + '%';
        }

        // Update sidebar progress
        const sidebarProgress = document.getElementById('sidebarProgress');
        if (sidebarProgress) {
            sidebarProgress.style.width = completedPercentage + '%';
        }

        // Update completed count
        const completedCount = document.getElementById('completedCount');
        if (completedCount) {
            completedCount.textContent = this.completedSteps.size;
        }

        // Update step labels in progress indicator
        const stepLabels = document.querySelectorAll('.step-label');
        stepLabels.forEach((label, index) => {
            const stepNumber = index + 1;
            label.classList.remove('active', 'completed');
            
            if (stepNumber === this.currentStep) {
                label.classList.add('active');
            } else if (this.completedSteps.has(stepNumber)) {
                label.classList.add('completed');
            }
        });
    },

    // Update step numbers in UI
    updateStepNumbers() {
        const stepNumberElements = document.querySelectorAll('#currentStepNumber, #headerStepNumber, #mobileStepNumber');
        stepNumberElements.forEach(element => {
            element.textContent = this.currentStep;
        });
    },

    // Save progress only for the current tab/session.
    saveProgress() {
        const progressData = {
            currentStep: this.currentStep,
            completedSteps: Array.from(this.completedSteps),
            timestamp: Date.now()
        };
        
        sessionStorage.setItem('sellerFormProgress', JSON.stringify(progressData));
    },

    // Load saved progress
    loadSavedProgress() {
        try {
            localStorage.removeItem('sellerFormProgress');
            const savedProgress = sessionStorage.getItem('sellerFormProgress');
            if (savedProgress) {
                const progressData = JSON.parse(savedProgress);
                
                // Check if progress is recent (within 24 hours)
                const isRecent = (Date.now() - progressData.timestamp) < 24 * 60 * 60 * 1000;
                
                if (isRecent && progressData.currentStep && progressData.completedSteps) {
                    this.currentStep = Math.min(progressData.currentStep, this.totalSteps);
                    this.completedSteps = new Set(progressData.completedSteps);
                    
                    // Show current step
                    this.showCurrentStep();
                    this.updateStepper();
                    this.updateProgress();
                    this.updateNavigationVisibility();
                    this.updateStepNumbers();
                }
            }
        } catch (error) {
            console.error('Error loading saved progress:', error);
        }
    },

    // Clear saved progress
    clearProgress() {
        sessionStorage.removeItem('sellerFormProgress');
        localStorage.removeItem('sellerFormProgress');
        // Hide the step the applicant just left before resetting the index.
        // Otherwise the completed declaration step remains visible underneath
        // step 1 after a successful submission.
        this.hideCurrentStep();
        this.currentStep = 1;
        this.completedSteps.clear();
        
        // Reset UI
        this.showCurrentStep();
        this.updateStepper();
        this.updateProgress();
        this.updateNavigationVisibility();
        this.updateStepNumbers();
    },

    // Get current step data
    getCurrentStepData() {
        const currentStepElement = document.getElementById(`step-${this.currentStep}`);
        if (!currentStepElement) return null;

        const formData = {};
        const inputs = currentStepElement.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (input.type === 'radio' || input.type === 'checkbox') {
                if (input.checked) {
                    formData[input.name] = input.value;
                }
            } else if (input.type === 'file') {
                if (input.files.length > 0) {
                    formData[input.name] = Array.from(input.files);
                }
            } else {
                formData[input.name] = input.value;
            }
        });

        return {
            stepNumber: this.currentStep,
            stepTitle: this.steps[this.currentStep - 1].title,
            data: formData,
            isValid: this.isCurrentStepValid(),
            isComplete: this.completedSteps.has(this.currentStep)
        };
    },

    // Get all step data
    getAllStepData() {
        return this.steps.map(step => {
            const stepElement = document.getElementById(`step-${step.id}`);
            if (!stepElement) return null;

            const formData = {};
            const inputs = stepElement.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                if (input.type === 'radio' || input.type === 'checkbox') {
                    if (input.checked) {
                        formData[input.name] = input.value;
                    }
                } else if (input.type === 'file') {
                    if (input.files.length > 0) {
                        formData[input.name] = Array.from(input.files);
                    }
                } else {
                    formData[input.name] = input.value;
                }
            });

            return {
                stepNumber: step.id,
                stepTitle: step.title,
                data: formData,
                isValid: FormValidation.validateStep(step.id),
                isComplete: this.completedSteps.has(step.id)
            };
        }).filter(step => step !== null);
    },

    // Check if form is ready for submission
    isFormReadyForSubmission() {
        // All steps must be completed
        const allStepsCompleted = this.completedSteps.size === this.totalSteps - 1; // -1 because last step is submit
        
        // Current step must be the last step
        const onLastStep = this.currentStep === this.totalSteps;
        
        // Last step must be valid
        const lastStepValid = FormValidation.validateStep(this.totalSteps);
        
        return allStepsCompleted && onLastStep && lastStepValid;
    },

    // Navigate to step with error
    navigateToStepWithError() {
        for (let stepNumber = 1; stepNumber <= this.totalSteps; stepNumber++) {
            if (!FormValidation.validateStep(stepNumber)) {
                this.goToStep(stepNumber);
                
                // Highlight first error field
                setTimeout(() => {
                    const stepElement = document.getElementById(`step-${stepNumber}`);
                    const firstError = stepElement.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 300);
                
                return stepNumber;
            }
        }
        return null;
    },

    // Show step summary
    showStepSummary() {
        const summaryData = this.getAllStepData();
        return summaryData;
    }
};

// Initialize stepper when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    FormStepper.init();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormStepper;
}
