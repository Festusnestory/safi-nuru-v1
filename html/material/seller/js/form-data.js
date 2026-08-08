// Form Data - Dropdowns and data management for Seller Form

const FormDataUtils = {
    // Namibian regions and towns mapping
    townsByRegion: {
        'Zambezi': ['Katima Mulilo', 'Sesheke', 'Bukalo', 'Linyanti', 'Sibinda'],
        'Erongo': ['Swakopmund', 'Walvis Bay', 'Henties Bay', 'Omaruru', 'Karibib', 'Usakos'],
        'Hardap': ['Mariental', 'Aranos', 'Gibeon', 'Hoachanas', 'Kalkrand', 'Rehoboth'],
        'Karas': ['Keetmanshoop', 'Lüderitz', 'Oranjemund', 'Karasburg', 'Bethanie', 'Aus'],
        'Kavango East': ['Rundu', 'Divundu', 'Bagani', 'Mukwe', 'Sauyemwa'],
        'Kavango West': ['Nkurenkuru', 'Tondoro', 'Mpungu', 'Kapako'],
        'Khomas': ['Windhoek', 'Rehoboth', 'Okahandja', 'Dordabis'],
        'Kunene': ['Opuwo', 'Outjo', 'Khorixas', 'Kamanjab', 'Sesfontein'],
        'Ohangwena': ['Eenhana', 'Okongo', 'Engela', 'Endola', 'Omundaungilo'],
        'Omaheke': ['Gobabis', 'Witvlei', 'Leonardville', 'Steinhausen', 'Otjinene'],
        'Omusati': ['Outapi', 'Okahao', 'Oshikuku', 'Ruacana', 'Tsandi'],
        'Oshana': ['Oshakati', 'Ongwediva', 'Ondangwa', 'Okatana', 'Uukwiyu Uushona'],
        'Oshikoto': ['Omuthiya', 'Tsumeb', 'Oniipa', 'Ompundja', 'Onayena'],
        'Otjozondjupa': ['Otjiwarongo', 'Grootfontein', 'Okakarara', 'Otavi', 'Okahandja']
    },

    // Nationalities list
    nationalities: [
        'Namibian', 'South African', 'Angolan', 'Zambian', 'Zimbabwean', 'Botswanan',
        'British', 'German', 'Dutch', 'Portuguese', 'American', 'Canadian', 'Australian',
        'Indian', 'Chinese', 'Other'
    ],

    // Property zoning statuses for development house types
    developmentPropertyTypes: [
        'Free Standing House Unit',
        'General Residential House Unit',
        'Business/Commercial Property',
        'Farm Property',
        'Institutional Property'
    ],

    // House types for residential developments
    houseTypes: [
        '1 Bedroom', '2 Bedroom', '3 Bedroom', '4 Bedroom', '5+ Bedroom',
        'Studio', 'Apartment', 'Townhouse', 'Villa', 'Duplex'
    ],

    // Property detail types
    propertyTypes: [
        'Single Residential',
        'General Residential',
        'Farm',
        'Commercial/Business'
    ],

    // Land types
    landTypes: [
        'Vacant Land',
        'Existing Property'
    ],

    // Current form data storage
    currentData: {},

    // Initialize dropdowns and form data
    init() {
        this.populateNationalities();
        this.populateRegions();
        this.loadSavedData();
        this.setupFormDataBinding();
    },

    // Populate nationality dropdowns
    populateNationalities() {
        const nationalitySelects = document.querySelectorAll('#nationality, #spouseNationality');
        
        nationalitySelects.forEach(select => {
            // Clear existing options except first
            const placeholder = select.querySelector('option[value=""]');
            select.innerHTML = '';
            if (placeholder) select.appendChild(placeholder);
            
            // Add nationality options
            this.nationalities.forEach(nationality => {
                const option = document.createElement('option');
                option.value = nationality;
                option.textContent = nationality;
                select.appendChild(option);
            });
        });
    },

    // Populate region dropdowns
    populateRegions() {
        const regionSelects = document.querySelectorAll('#region, #nokRegion, #propertyRegion');
        
        regionSelects.forEach(select => {
            // Clear existing options except first
            const placeholder = select.querySelector('option[value=""]');
            select.innerHTML = '';
            if (placeholder) select.appendChild(placeholder);
            
            // Add region options
            Object.keys(this.townsByRegion).forEach(region => {
                const option = document.createElement('option');
                option.value = region;
                option.textContent = region;
                select.appendChild(option);
            });
        });
    },

    // Populate towns based on region
    populateTowns(regionValue, townSelectId) {
        const townSelect = document.getElementById(townSelectId);
        if (!townSelect || !regionValue) return;

        // Clear existing options
        townSelect.innerHTML = '<option value="">Select Town</option>';

        // Add town options for selected region
        const towns = this.townsByRegion[regionValue] || [];
        towns.forEach(town => {
            const option = document.createElement('option');
            option.value = town;
            option.textContent = town;
            townSelect.appendChild(option);
        });
    },

    // By-reference region/town population for dynamically-created selects
    // (populateRegions/populateTowns above only target fixed, pre-existing
    // element IDs and can't reach elements added later via JS templates).
    populateRegionsInto(selectEl) {
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">Select Region</option>';
        Object.keys(this.townsByRegion).forEach(region => {
            const option = document.createElement('option');
            option.value = region;
            option.textContent = region;
            selectEl.appendChild(option);
        });
    },

    populateTownsInto(regionValue, selectEl) {
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">Select Town</option>';
        const towns = this.townsByRegion[regionValue] || [];
        towns.forEach(town => {
            const option = document.createElement('option');
            option.value = town;
            option.textContent = town;
            selectEl.appendChild(option);
        });
    },

    // Setup form data binding for auto-save
    setupFormDataBinding() {
        const form = document.getElementById('sellerApplicationForm');
        if (!form) return;

        // Auto-save on input change
        form.addEventListener('change', (e) => {
            this.saveFormData();
        });

        form.addEventListener('input', (e) => {
            // Debounced save for text inputs
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                this.saveFormData();
            }, 1000);
        });
    },

    // Keep sensitive draft data scoped to this browser tab. Persistent
    // localStorage leaked identity/contact/property data to later users on a
    // shared device.
    saveFormData() {
        const form = document.getElementById('sellerApplicationForm');
        if (!form) return;

        const browserFormData = new FormData(form);
        const data = {};

        // Convert FormData to object
        for (let [key, value] of browserFormData.entries()) {
            if (data[key]) {
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }

        sessionStorage.setItem('sellerApplicationData', JSON.stringify(data));
        this.currentData = data;
    },

    // Load saved form data from this tab only
    loadSavedData() {
        try {
            localStorage.removeItem('sellerApplicationData');
            const savedData = sessionStorage.getItem('sellerApplicationData');
            if (savedData) {
                this.currentData = JSON.parse(savedData);
                this.populateFormWithData(this.currentData);
            }
        } catch (error) {
            console.error('Error loading saved form data:', error);
        }
    },

    // Populate form with saved data
    populateFormWithData(data) {
        Object.keys(data).forEach(key => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element) {
                if (element.type === 'radio' || element.type === 'checkbox') {
                    if (element.value === data[key] || data[key] === true) {
                        element.checked = true;
                    }
                } else if (element.tagName === 'SELECT' || element.type !== 'file') {
                    element.value = data[key];
                }
            }
        });

        // Trigger change events to update dependent dropdowns
        setTimeout(() => {
            const regionSelects = document.querySelectorAll('#region, #nokRegion, #propertyRegion');
            regionSelects.forEach(select => {
                if (select.value) {
                    select.dispatchEvent(new Event('change'));
                }
            });
        }, 100);
    },

    // Clear all form data
    clearFormData() {
        sessionStorage.removeItem('sellerApplicationData');
        localStorage.removeItem('sellerApplicationData');
        this.currentData = {};
        document.getElementById('sellerApplicationForm').reset();
    },

    // Get form data as object
    getFormData() {
        const form = document.getElementById('sellerApplicationForm');
        if (!form) return {};

        const browserFormData = new FormData(form);
        const data = {};

        // Convert FormData to object
        for (let [key, value] of browserFormData.entries()) {
            if (key.includes('[]')) {
                // Handle array fields
                const arrayKey = key.replace('[]', '');
                if (!data[arrayKey]) data[arrayKey] = [];
                data[arrayKey].push(value);
            } else if (data[key]) {
                // Handle multiple values for same key
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }

        // Add file information
        this.addFileInformation(data);

        return data;
    },

    // Add file information to form data
    addFileInformation(data) {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            if (input.files && input.files.length > 0) {
                const files = Array.from(input.files);
                data[input.name + '_files'] = files.map(file => ({
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    lastModified: file.lastModified
                }));
            }
        });
    },

    // Validate form data completeness
    validateDataCompleteness() {
        const data = this.getFormData();
        const missingFields = [];

        // Required fields validation
        const requiredFields = [
            'surname', 'firstName', 'dateOfBirth', 'idType', 'idNumber', 
            'nationality', 'gender', 'maritalStatus', 'streetName', 
            'region', 'town', 'email', 'mobileNumber', 'nokSurname', 
            'nokFirstName', 'nokContactNumber', 'nokEmail', 'nokStreetName',
            'nokRegion', 'nokTown', 'saleType', 'propertyDetailType',
            'landType', 'landSize', 'sellingPrice', 'propertyStreetName',
            'propertyRegion', 'propertyTown'
        ];

        requiredFields.forEach(field => {
            if (!data[field] || data[field] === '') {
                missingFields.push(field);
            }
        });

        return {
            isValid: missingFields.length === 0,
            missingFields: missingFields
        };
    },

    // Format currency display
    formatCurrency(value) {
        if (!value) return '';
        const number = typeof value === 'string' ? parseFloat(value.replace(/[^\d.]/g, '')) : value;
        if (isNaN(number)) return '';
        return new Intl.NumberFormat('en-NA', {
            style: 'currency',
            currency: 'NAD',
            minimumFractionDigits: 2
        }).format(number);
    },

    // Format phone number
    formatPhoneNumber(phone) {
        if (!phone) return '';
        // Remove all non-digit characters
        const cleaned = phone.replace(/\D/g, '');
        
        // Namibian phone number formats
        if (cleaned.startsWith('264')) {
            // International format
            return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 5)} ${cleaned.slice(5)}`;
        } else if (cleaned.startsWith('0')) {
            // Local format with 0
            return `${cleaned.slice(0, 3)} ${cleaned.slice(3)}`;
        } else if (cleaned.length >= 8) {
            // Local format without 0
            return `${cleaned.slice(0, 2)} ${cleaned.slice(2)}`;
        }
        
        return phone;
    },

    // Validate email format
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Validate Namibian ID number
    isValidNamibianId(idNumber) {
        // Basic Namibian ID validation (11 digits)
        return /^\d{11}$/.test(idNumber);
    },

    // Validate passport number
    isValidPassport(passportNumber) {
        // Basic passport validation (6-9 alphanumeric characters)
        return /^[A-Z0-9]{6,9}$/i.test(passportNumber);
    },

    // Calculate age from date of birth
    calculateAge(dateOfBirth) {
        if (!dateOfBirth) return 0;
        const today = new Date();
        const birthDate = new Date(dateOfBirth);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age;
    },

    // Get current date in YYYY-MM-DD format
    getCurrentDate() {
        return new Date().toISOString().split('T')[0];
    },

    // Export form data for submission
    exportForSubmission() {
        const data = this.getFormData();
        
        // Structure data for API submission
        return {
            personalDetails: {
                surname: data.surname,
                firstName: data.firstName,
                middleName: data.middleName,
                maidenName: data.maidenName,
                dateOfBirth: data.dateOfBirth,
                idType: data.idType,
                idNumber: data.idNumber,
                nationality: data.nationality,
                gender: data.gender
            },
            maritalStatus: {
                status: data.maritalStatus,
                spouseDetails: data.maritalStatus === 'Married' ? {
                    surname: data.spouseSurname,
                    firstName: data.spouseFirstName,
                    dateOfBirth: data.spouseDateOfBirth,
                    idType: data.spouseIdType,
                    idNumber: data.spouseIdNumber,
                    nationality: data.spouseNationality,
                    gender: data.spouseGender
                } : null
            },
            residentialAddress: {
                erfNo: data.erfNo,
                streetName: data.streetName,
                suburb: data.suburb,
                location: data.location,
                region: data.region,
                town: data.town,
                email: data.email,
                mobileNumber: data.mobileNumber,
                poBox: data.poBox
            },
            nextOfKin: {
                surname: data.nokSurname,
                firstName: data.nokFirstName,
                contactNumber: data.nokContactNumber,
                email: data.nokEmail,
                address: {
                    erfNo: data.nokErfNo,
                    streetName: data.nokStreetName,
                    suburb: data.nokSuburb,
                    location: data.nokLocation,
                    region: data.nokRegion,
                    town: data.nokTown
                }
            },
            saleType: {
                type: data.saleType,
                propertyDeveloper: data.saleType === 'Property Development' ? {
                    name: data.developerName,
                    propertyType: data.propertyType
                } : null
            },
            propertyDetails: {
                type: data.propertyDetailType,
                landType: data.landType,
                landSize: data.landSize,
                houseSize: data.houseSize,
                rooms: data.rooms,
                bathrooms: data.bathrooms,
                additionalFeatures: data.additionalFeatures,
                sellingPrice: data.sellingPrice,
                address: {
                    erfNo: data.propertyErfNo,
                    streetName: data.propertyStreetName,
                    suburb: data.propertySuburb,
                    location: data.propertyLocation,
                    region: data.propertyRegion,
                    town: data.propertyTown
                }
            },
            declaration: {
                certification: data.certificationDeclaration === 'on',
                authorization: data.authorizationDeclaration === 'on',
                indemnification: data.indemnificationDeclaration === 'on',
                commissionFees: data.commissionFeesDeclaration === 'on',
                propertyRights: data.propertyRightsDeclaration === 'on',
                signatureLocation: data.signatureLocation,
                signatureDate: data.signatureDate,
                signatureType: data.signatureType,
                otpNumber: data.otpNumber
            },
            submissionDate: new Date().toISOString(),
            applicationStatus: 'submitted'
        };
    }
};

// Initialize form data when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    FormDataUtils.init();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormDataUtils;
}
