// Form Data - Static data for dropdowns and validation
const FormData = {
    // Namibian regions
    regions: [
        'Erongo', 'Hardap', 'Karas', 'Kavango East', 'Kavango West', 
        'Khomas', 'Kunene', 'Ohangwena', 'Omaheke', 'Omusati', 
        'Oshana', 'Oshikoto', 'Otjozondjupa', 'Zambezi'
    ],

    // Towns by region
    townsByRegion: {
        'Erongo': ['Walvis Bay', 'Swakopmund', 'Henties Bay', 'Omaruru', 'Karibib', 'Usakos'],
        'Hardap': ['Mariental', 'Rehoboth', 'Aranos', 'Gibeon', 'Hoachanas'],
        'Karas': ['Keetmanshoop', 'Karasburg', 'Luderitz', 'Oranjemund', 'Warmbad'],
        'Kavango East': ['Rundu', 'Divundu', 'Bagani', 'Mukwe'],
        'Kavango West': ['Nkurenkuru', 'Tondoro', 'Kapako'],
        'Khomas': ['Windhoek', 'Okahandja', 'Rehoboth'],
        'Kunene': ['Opuwo', 'Khorixas', 'Outjo', 'Kamanjab'],
        'Ohangwena': ['Eenhana', 'Ohangwena', 'Okongo'],
        'Omaheke': ['Gobabis', 'Witvlei', 'Leonardville'],
        'Omusati': ['Outapi', 'Okahao', 'Oshikuku', 'Ruacana'],
        'Oshana': ['Oshakati', 'Ondangwa', 'Ongwediva'],
        'Oshikoto': ['Omuthiya', 'Tsumeb', 'Oniipa'],
        'Otjozondjupa': ['Otjiwarongo', 'Grootfontein', 'Okakarara'],
        'Zambezi': ['Katima Mulilo', 'Linyanti', 'Sibbinda']
    },

    // World nationalities
    nationalities: [
        'Afghan', 'Albanian', 'Algerian', 'American', 'Andorran', 'Angolan', 'Antiguans', 'Argentinean',
        'Armenian', 'Australian', 'Austrian', 'Azerbaijani', 'Bahamian', 'Bahraini', 'Bangladeshi',
        'Barbadian', 'Belarusian', 'Belgian', 'Belizean', 'Beninese', 'Bhutanese', 'Bolivian',
        'Bosnian', 'Brazilian', 'British', 'Bruneian', 'Bulgarian', 'Burkinabe', 'Burmese', 'Burundian',
        'Cambodian', 'Cameroonian', 'Canadian', 'Cape Verdean', 'Central African', 'Chadian', 'Chilean',
        'Chinese', 'Colombian', 'Comoran', 'Congolese', 'Costa Rican', 'Croatian', 'Cuban', 'Cypriot',
        'Czech', 'Danish', 'Djibouti', 'Dominican', 'Dutch', 'East Timorese', 'Ecuadorean', 'Egyptian',
        'Emirian', 'Equatorial Guinean', 'Eritrean', 'Estonian', 'Ethiopian', 'Fijian', 'Filipino',
        'Finnish', 'French', 'Gabonese', 'Gambian', 'Georgian', 'German', 'Ghanaian', 'Greek',
        'Grenadian', 'Guatemalan', 'Guinea-Bissauan', 'Guinean', 'Guyanese', 'Haitian', 'Herzegovinian',
        'Honduran', 'Hungarian', 'I-Kiribati', 'Icelander', 'Indian', 'Indonesian', 'Iranian', 'Iraqi',
        'Irish', 'Israeli', 'Italian', 'Ivorian', 'Jamaican', 'Japanese', 'Jordanian', 'Kazakhstani',
        'Kenyan', 'Kittian and Nevisian', 'Kuwaiti', 'Kyrgyz', 'Laotian', 'Latvian', 'Lebanese',
        'Liberian', 'Libyan', 'Liechtensteiner', 'Lithuanian', 'Luxembourger', 'Macedonian', 'Malagasy',
        'Malawian', 'Malaysian', 'Maldivan', 'Malian', 'Maltese', 'Marshallese', 'Mauritanian',
        'Mauritian', 'Mexican', 'Micronesian', 'Moldovan', 'Monacan', 'Mongolian', 'Moroccan',
        'Mosotho', 'Motswana', 'Mozambican', 'Namibian', 'Nauruan', 'Nepalese', 'New Zealander',
        'Ni-Vanuatu', 'Nicaraguan', 'Nigerian', 'Nigerien', 'North Korean', 'Northern Irish', 'Norwegian',
        'Omani', 'Pakistani', 'Palauan', 'Panamanian', 'Papua New Guinean', 'Paraguayan', 'Peruvian',
        'Polish', 'Portuguese', 'Qatari', 'Romanian', 'Russian', 'Rwandan', 'Saint Lucian', 'Salvadoran',
        'Samoan', 'San Marinese', 'Sao Tomean', 'Saudi', 'Scottish', 'Senegalese', 'Serbian',
        'Seychellois', 'Sierra Leonean', 'Singaporean', 'Slovakian', 'Slovenian', 'Solomon Islander',
        'Somali', 'South African', 'South Korean', 'Spanish', 'Sri Lankan', 'Sudanese', 'Surinamer',
        'Swazi', 'Swedish', 'Swiss', 'Syrian', 'Taiwanese', 'Tajik', 'Tanzanian', 'Thai', 'Togolese',
        'Tongan', 'Trinidadian or Tobagonian', 'Tunisian', 'Turkish', 'Tuvaluan', 'Ugandan',
        'Ukrainian', 'Uruguayan', 'Uzbekistani', 'Venezuelan', 'Vietnamese', 'Welsh', 'Yemenite',
        'Zambian', 'Zimbabwean'
    ],

    // ID/Passport types
    idTypes: [
        { value: 'national_id', label: 'National ID' },
        { value: 'passport', label: 'Passport' },
        { value: 'drivers_license', label: "Driver's License" }
    ],

    // Employment types
    employmentTypes: [
        { value: 'full_time_permanent', label: 'Full-time Permanent Employee' },
        { value: 'part_time_permanent', label: 'Part-time Permanent Employee' },
        { value: 'contract_employee', label: 'Contract Employee' },
        { value: 'self_employed', label: 'Self-employed' },
        { value: 'business_owner', label: 'Business Owner' },
        { value: 'pensioner', label: 'Pensioner' },
        { value: 'unemployed', label: 'Unemployed' },
        { value: 'student', label: 'Student' }
    ],

    // Property types
    propertyTypes: [
        { value: 'house', label: 'House' },
        { value: 'apartment', label: 'Apartment' },
        { value: 'townhouse', label: 'Townhouse' },
        { value: 'flat', label: 'Flat' },
        { value: 'commercial', label: 'Commercial Property' },
        { value: 'land', label: 'Land/Plot' },
        { value: 'farm', label: 'Farm' },
        { value: 'industrial', label: 'Industrial Property' }
    ],

    // Relationships for next of kin
    relationships: [
        'Parent', 'Sibling', 'Spouse', 'Child', 'Grandparent', 'Grandchild',
        'Uncle/Aunt', 'Cousin', 'Friend', 'Guardian', 'Other'
    ],

    // Document types required
    documentTypes: {
        'id_passport': {
            label: 'ID/Passport Copy',
            required: true,
            acceptedTypes: ['.pdf', '.jpg', '.jpeg', '.png'],
            maxSize: 10 // MB
        },
        'proof_of_income': {
            label: 'Proof of Income',
            required: true,
            acceptedTypes: ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png'],
            maxSize: 10 // MB
        },
        'bank_statements': {
            label: 'Bank Statements (Last 3 months)',
            required: true,
            acceptedTypes: ['.pdf', '.jpg', '.jpeg', '.png'],
            maxSize: 10 // MB
        },
        'employment_letter': {
            label: 'Employment Letter/Contract',
            required: false,
            acceptedTypes: ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png'],
            maxSize: 10 // MB
        },
        'marriage_certificate': {
            label: 'Marriage Certificate (if married)',
            required: false,
            acceptedTypes: ['.pdf', '.jpg', '.jpeg', '.png'],
            maxSize: 10 // MB
        },
        'additional_documents': {
            label: 'Additional Documents',
            required: false,
            acceptedTypes: ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png'],
            maxSize: 10 // MB
        },
        'signatureFile': {
            label: 'Signature',
            required: true,
            acceptedTypes: ['.pdf', '.jpg', '.jpeg', '.png'],
            maxSize: 10 // MB
        }
    },

    // Form steps configuration
    steps: [
        {
            id: 1,
            title: 'Personal Details',
            icon: 'fas fa-user',
            description: 'Basic personal information'
        },
        {
            id: 2,
            title: 'Marital Status',
            icon: 'fas fa-heart',
            description: 'Marital status and spouse details'
        },
        {
            id: 3,
            title: 'Residential Address',
            icon: 'fas fa-home',
            description: 'Current address and contact information'
        },
        {
            id: 4,
            title: 'Next of Kin',
            icon: 'fas fa-users',
            description: 'Emergency contact information'
        },
        {
            id: 5,
            title: 'Employment Details',
            icon: 'fas fa-briefcase',
            description: 'Employment and income information'
        },
        {
            id: 6,
            title: 'Property Purchase',
            icon: 'fas fa-building',
            description: 'Property preferences and budget'
        },
        {
            id: 7,
            title: 'Document Upload',
            icon: 'fas fa-file-upload',
            description: 'Required documentation'
        },
        {
            id: 8,
            title: 'Declaration',
            icon: 'fas fa-signature',
            description: 'Acknowledgment and signature'
        }
    ]
};

// Currency formatting for Namibian Dollar
const formatCurrency = (amount) => {
    const numericAmount = Number(amount);
    const formattedAmount = new Intl.NumberFormat('en-NA', {
        minimumFractionDigits: 2
    }).format(Number.isFinite(numericAmount) ? numericAmount : 0);

    // Chromium renders NAD with the ambiguous "$" symbol in some locales.
    // Use the explicit Namibian-dollar prefix used throughout the application.
    return `N$${formattedAmount}`;
};

// Phone number formatting for Namibian numbers
const formatPhoneNumber = (phone) => {
    // Remove non-numeric characters
    const cleaned = phone.replace(/\D/g, '');
    
    // Namibian mobile format: +264 XX XXX XXXX
    if (cleaned.length === 11 && cleaned.startsWith('264')) {
        return `+264 ${cleaned.substr(3, 2)} ${cleaned.substr(5, 3)} ${cleaned.substr(8)}`;
    }
    
    // Namibian local format: 0XX XXX XXXX
    if (cleaned.length === 10 && cleaned.startsWith('0')) {
        return `${cleaned.substr(0, 3)} ${cleaned.substr(3, 3)} ${cleaned.substr(6)}`;
    }
    
    return phone; // Return as is if format doesn't match
};

// Date utilities
const DateUtils = {
    // Calculate age from date of birth
    calculateAge: (dateOfBirth) => {
        const today = new Date();
        const birthDate = new Date(dateOfBirth);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age;
    },
    
    // Format date for display
    formatDate: (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-NA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },
    
    // Get current date in YYYY-MM-DD format
    getCurrentDate: () => {
        return new Date().toISOString().split('T')[0];
    },
    
    // Get minimum date for 18+ age requirement
    getMinBirthDate: () => {
        const date = new Date();
        date.setFullYear(date.getFullYear() - 18);
        return date.toISOString().split('T')[0];
    }
};

// Populate dropdown options
const populateDropdowns = () => {
    // Populate nationality dropdown
    const nationalitySelect = document.getElementById('nationality');
    if (nationalitySelect) {
        FormData.nationalities.forEach(nationality => {
            const option = document.createElement('option');
            option.value = nationality;
            option.textContent = nationality;
            nationalitySelect.appendChild(option);
        });
    }

    // Populate other dropdowns as needed
    const populateSelect = (selectId, options, valueKey = null, labelKey = null) => {
        const select = document.getElementById(selectId);
        if (select) {
            options.forEach(option => {
                const optionElement = document.createElement('option');
                if (typeof option === 'string') {
                    optionElement.value = option;
                    optionElement.textContent = option;
                } else {
                    optionElement.value = valueKey ? option[valueKey] : option.value;
                    optionElement.textContent = labelKey ? option[labelKey] : option.label;
                }
                select.appendChild(optionElement);
            });
        }
    };

    // Populate employment types
    populateSelect('employmentType', FormData.employmentTypes);
    
    // Populate property types
    populateSelect('propertyType', FormData.propertyTypes);
    
    // Populate relationships
    populateSelect('nokRelationship', FormData.relationships);
    
    // Populate regions
    populateSelect('region', FormData.regions);
    populateSelect('nokRegion', FormData.regions);
    populateSelect('preferred-region-1', FormData.regions);
};

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FormData, formatCurrency, formatPhoneNumber, DateUtils };
}
