# Buyer Portal - Nuru Real Estate

## Overview

The Buyer Portal is a comprehensive, multi-step application form that allows prospective property buyers to submit their applications to Nuru Real Estate. This portal has been converted from React to vanilla JavaScript/HTML and integrated with a PHP backend API.

## Features

### Multi-Step Form
- **8 comprehensive steps** covering all buyer information requirements
- **Progress indicator** with visual feedback
- **Step validation** ensuring data quality before proceeding
- **Auto-save functionality** preserving user progress
- **Mobile-responsive design** using Bootstrap 5

### Form Steps

1. **Personal Details** - Basic personal information and identification
2. **Marital Status** - Marital status with conditional spouse details
3. **Residential Address** - Current address and contact information
4. **Next of Kin** - Emergency contact and their details
5. **Employment Details** - Employment and income information
6. **Property Purchase** - Property preferences and budget
7. **Document Upload** - Required documentation with file validation
8. **Declaration** - Legal acknowledgments and digital signature

### Key Features

#### Form Validation
- Real-time field validation
- Step-by-step validation preventing progression with errors
- Custom validation for Namibian formats (phone numbers, ID numbers)
- File upload validation (size, type restrictions)
- Age verification (18+ requirement)

#### User Experience
- **Auto-completion** for dependent dropdowns (region → town)
- **Currency formatting** for financial fields
- **Phone number formatting** for Namibian standards
- **Drag-and-drop file uploads** with preview
- **Conditional field display** based on user selections
- **Progress saving** in localStorage

#### Security
- CSRF protection for all form submissions
- Secure file upload handling
- Input sanitization and validation
- Session management for security tokens

## Technical Architecture

### Frontend Structure
```
portal/buyer/
├── index.php              # Main form page with PHP session handling
├── css/
│   └── buyer-form.css     # Custom styling for the buyer form
├── js/
│   ├── form-data.js       # Static data (regions, nationalities, etc.)
│   ├── form-validation.js # Comprehensive validation logic
│   ├── form-steps.js      # Step navigation and form structure
│   └── buyer-form.js      # Main form handling and API integration
└── assets/                # Static assets (if needed)
```

### Backend Integration
```
api/
├── controllers/
│   └── BuyerController.php # Handles buyer application API endpoints
└── index.php              # Main API router with CSRF endpoint
```

### Database Schema
The buyer applications are stored in the `buyer_applications` table with comprehensive fields covering:
- Personal information
- Contact details
- Employment information
- Property preferences
- Application status and metadata

## API Endpoints

### Buyer Applications
- `POST /api/applications/buyers` - Submit new buyer application
- `GET /api/applications/buyers` - List buyer applications (admin)
- `GET /api/applications/buyers/{id}` - Get specific application (admin)
- `PUT /api/applications/buyers/{id}` - Update application status (admin)
- `DELETE /api/applications/buyers/{id}` - Delete application (admin)

### Utility Endpoints
- `GET /api/csrf-token` - Get CSRF token for secure form submission

## Form Validation Rules

### Personal Details
- First name and last name are required
- ID/Passport number validation based on type selection
- Date of birth must indicate age 18 or older
- Nationality and gender are required selections

### Contact Information
- Email addresses must be valid format
- Phone numbers must follow Namibian format (+264 XX XXX XXXX)
- Residential address requires region and town

### Employment
- Monthly income is required and must be positive
- Employment details vary based on employment type
- Unemployed applicants have reduced requirements

### Property Purchase
- Property value and down payment must be positive
- Down payment cannot exceed property value
- At least one preferred area must be specified

### Documents
- ID/Passport copy (required)
- Proof of income (required)
- Bank statements (required)
- Marriage certificate (required if married)
- Additional documents (optional)

## File Upload Specifications

### Accepted File Types
- **Documents**: PDF, DOC, DOCX
- **Images**: JPG, JPEG, PNG

### File Size Limits
- **Standard documents**: 5MB maximum
- **Bank statements**: 10MB maximum (can be multiple files)
- **Additional documents**: 10MB maximum

### Security Features
- File type validation on both client and server
- Virus scanning integration ready
- Secure file naming and storage
- Access control for uploaded files

## Responsive Design

The buyer portal is fully responsive and optimized for:
- **Desktop**: Full-width layout with optimal spacing
- **Tablet**: Adapted grid system with touch-friendly controls
- **Mobile**: Single-column layout with optimized input fields

### Mobile-Specific Features
- Touch-friendly file upload areas
- Optimized form navigation
- Readable typography and proper contrast
- Gesture-friendly interactions

## Browser Compatibility

- **Modern browsers**: Chrome 80+, Firefox 75+, Safari 13+, Edge 80+
- **JavaScript**: ES6 features with fallbacks
- **CSS**: Modern flexbox and grid with vendor prefixes
- **Mobile browsers**: iOS Safari 13+, Chrome Mobile 80+

## Security Considerations

### Data Protection
- All form data is encrypted in transit (HTTPS required)
- Sensitive information is sanitized before storage
- File uploads are scanned and validated
- Session tokens prevent CSRF attacks

### Privacy
- Form data is auto-saved locally but cleared on submission
- No sensitive data is stored in browser history
- Document uploads are immediately secured
- User data is handled per GDPR guidelines

## Development Setup

### Prerequisites
- PHP 7.4+ with required extensions
- MySQL/MariaDB database
- Web server (Apache/Nginx)
- Modern browser for testing

### Installation
1. Copy buyer portal files to web server
2. Ensure proper file permissions for uploads directory
3. Configure database connection in API
4. Set up HTTPS certificate
5. Test form submission flow

### Configuration
- Update `js/buyer-form.js` with correct API base URL
- Configure file upload limits in PHP settings
- Set appropriate session timeout values
- Configure email notifications for form submissions

## Deployment Checklist

- [ ] PHP configuration optimized for file uploads
- [ ] Database tables created and accessible
- [ ] Upload directories with proper permissions
- [ ] HTTPS certificate installed and configured
- [ ] CSRF protection enabled and tested
- [ ] Form validation tested on all browsers
- [ ] File upload limits configured
- [ ] Email notifications configured
- [ ] Error logging enabled
- [ ] Performance monitoring in place

## Maintenance

### Regular Tasks
- Monitor form submission success rates
- Review and update validation rules
- Check file upload directory disk usage
- Update nationality and location data as needed
- Review security logs for suspicious activity

### Updates
- Test form functionality after any system updates
- Validate browser compatibility with new releases
- Update dependencies and security patches
- Review and update privacy policies as needed

## Support Information

### Common Issues
- **File upload failures**: Check file size and type restrictions
- **Validation errors**: Ensure all required fields are completed
- **Progress loss**: Check browser localStorage availability
- **Mobile issues**: Verify responsive design breakpoints

### Troubleshooting
- Clear browser cache and localStorage for form issues
- Check browser console for JavaScript errors
- Verify network connectivity for API calls
- Confirm CSRF token generation and validation

---

**Created by MiniMax Agent** - Advanced AI Assistant for Complex Development Tasks

This buyer portal represents a complete conversion from React to vanilla JavaScript while maintaining all functionality and adding enhanced features for security, validation, and user experience.
