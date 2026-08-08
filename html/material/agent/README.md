# Agent Registration Form - Portal Integration

## Overview

The Agent Registration Form has been successfully integrated into the Nuru Real Estate Portal system. This is a comprehensive 5-step form that allows real estate agents to register and submit their application through the portal's secure interface.

## Integration Features

- **Portal Authentication**: Fully integrated with portal's user authentication system
- **Permission-based Access**: Controlled access using portal's role-based permission system
- **Security Measures**: CSRF protection, file upload validation, and rate limiting
- **Multi-step Form**: 5-step process with data validation and persistence
- **File Upload System**: Secure document upload with file type and size validation
- **Bootstrap UI**: Consistent styling with portal's Bootstrap theme
- **Responsive Design**: Mobile-friendly interface
- **Database Integration**: Uses portal's existing database connection and security measures

## Form Structure

### Step 1: Personal Details
- Name information (Surname, First Name, Middle Name, Maiden Name)
- Date of birth validation
- ID type and number (National ID or Passport)
- Nationality selection
- Gender selection

### Step 2: Residential Address
- Full address information with Namibian town/region auto-population
- Email and mobile number validation
- PO Box information

### Step 3: Next of Kin
- Next of kin personal information
- Contact details with validation
- Address information with auto-fill features

### Step 4: Employment Details
- Company information
- Job title and employment number
- Monthly income breakdown (Gross, Deductions, Net Pay)
- Employment address with town/region mapping

### Step 5: Document Upload
- ID Document (Required)
- Proof of Residence (Required)
- Agency's FFC (Required)
- Agent's NEAB Card (Required)
- Agent's FFC (Required)

## File Structure

```
portal/agent/
├── index.php                    # Main form interface with portal integration
├── ajax_handler.php            # AJAX request handler
├── database_schema.sql         # Database schema for agent tables
├── README.md                   # This documentation file
├── css/
│   └── agent-form.css          # Form-specific styling
├── js/
│   └── agent-form.js           # Form JavaScript functionality
├── includes/
│   └── form_handler.php        # Form data handling class
└── uploads/                    # Uploaded documents directory
```

## Database Tables

### `agent_form_sessions`
- Manages form session state and CSRF tokens
- Stores temporary form data during completion process
- Links to portal's user authentication system

### `agent_applications` 
- Stores completed agent applications
- Contains all form data in normalized structure
- Includes workflow status (pending, reviewed, approved, rejected)
- Links to portal's user system for tracking

### `agent_uploaded_files`
- Manages uploaded document files
- Links files to sessions and applications
- Stores file metadata and security information

## Installation Instructions

1. **Database Setup**: Run the database schema SQL file to create required tables:
   ```sql
   SOURCE portal/agent/database_schema.sql;
   ```

2. **Permissions Setup**: Ensure the following permissions exist in your portal:
   - `manage_agents`: Full access to agent functionality
   - `view_agents`: View-only access to agent applications
   - `approve_agents`: Approve/reject agent applications

3. **Directory Permissions**: Ensure the uploads directory is writable:
   ```bash
   chmod 755 portal/agent/uploads/
   ```

4. **Portal Integration**: The form automatically integrates with portal's:
   - Authentication system (`PortalAuth` class)
   - Database connection
   - Security features (CSRF, session management)
   - User permission system

## Security Features

- **Authentication Required**: Users must be logged into portal
- **Permission-based Access**: Only users with `manage_agents` permission can access
- **CSRF Protection**: All form submissions protected with CSRF tokens
- **File Upload Validation**: 
  - File type restrictions (PDF, DOC, DOCX, Images)
  - File size limits (10MB maximum)
  - MIME type verification
  - Unique filename generation
- **Rate Limiting**: Prevents abuse with request rate limiting
- **Input Sanitization**: All user inputs sanitized and validated
- **SQL Injection Protection**: Prepared statements used throughout

## Usage

1. **Access**: Navigate to `portal/agent/` in the portal
2. **Authentication**: User must be logged in with appropriate permissions
3. **Form Completion**: Complete all 5 steps with required information
4. **File Upload**: Upload all 5 required document types
5. **Submission**: Review and submit completed application
6. **Tracking**: Application receives unique ID for tracking

## API Endpoints

The following AJAX endpoints are available:

- `POST /agent/ajax_handler.php?action=init` - Initialize form session
- `POST /agent/ajax_handler.php?action=save_step` - Save step data
- `POST /agent/ajax_handler.php?action=update_step` - Update current step
- `POST /agent/ajax_handler.php?action=get_region` - Get region for town
- `POST /agent/ajax_handler.php?action=upload_file` - Upload document file
- `POST /agent/ajax_handler.php?action=submit_form` - Submit completed application

## Validation Rules

### Personal Details
- Surname and first name are required
- Date of birth must indicate age 18-120
- National ID must be 11 digits
- Passport must be 6-9 alphanumeric characters

### Contact Information
- Valid email format required
- Phone numbers must be 7-15 characters with allowed formatting
- Town selection required for address

### Employment Details
- Company name and job title required
- Income fields must be valid currency amounts
- Gross income must be greater than or equal to net pay plus deductions

### File Uploads
- All 5 document types required
- Maximum file size: 10MB
- Allowed formats: PDF, DOC, DOCX, JPG, PNG, GIF, TIFF, BMP

## Error Handling

- Form validates each step before allowing progression
- File uploads include progress indicators and error messages
- AJAX errors are handled gracefully with user-friendly messages
- Server-side validation provides detailed error feedback

## Portal Integration Points

1. **Authentication**: Uses `PortalAuth` class for user verification
2. **Database**: Integrates with portal's database connection
3. **Permissions**: Respects portal's role-based permission system
4. **Styling**: Uses portal's Bootstrap theme and CSS variables
5. **Navigation**: Includes portal breadcrumb navigation
6. **Activity Logging**: Logs form submissions to portal activity log

## Maintenance

- **Session Cleanup**: Form sessions expire after 2 hours
- **File Management**: Uploaded files are stored in organized directory structure
- **Database Optimization**: Indexes provided for efficient querying
- **Error Logging**: All errors logged to PHP error log for debugging

## Future Enhancements

Potential areas for future development:
- Application review workflow interface
- Email notifications for application status changes
- Bulk approval/rejection capabilities
- Advanced reporting and analytics
- Integration with external agent licensing systems
- PDF generation for completed applications

## Support

For technical support or integration issues:
1. Check error logs for detailed error information
2. Verify database table creation and permissions
3. Ensure proper portal authentication configuration
4. Validate file system permissions for uploads directory

## Version Information

- **Version**: 1.0.0
- **Integration Date**: 2025-08-22
- **Portal Compatibility**: Nuru Real Estate Portal v1.0+
- **PHP Requirements**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.2+
