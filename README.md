# Roadside Assistance Administrative Platform

A comprehensive web application for managing roadside assistance operations, built with PHP, MySQL, and Bootstrap.

## Features

- **Customer Management**: Add, edit, and manage customer information
- **Service Request Management**: Create, assign, and track service requests
- **Technician Management**: Manage technician profiles and availability
- **Invoice Generation**: Create and manage invoices for completed services
- **Dashboard**: Real-time overview of operations and statistics
- **Responsive Design**: Works on desktop, tablet, and mobile devices

## Technology Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Bootstrap 5.1.3
- **Icons**: Font Awesome 6.0
- **Server**: Apache (XAMPP)

## Prerequisites

- XAMPP (or similar LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser with JavaScript enabled

## Installation & Setup

### 1. Clone/Download the Project
```bash
# Navigate to your web server directory
cd /var/www/html

# Clone the repository
git clone [repository-url] claude_admin2
```

### 2. Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Ensure both services are running (green status)

### 3. Database Setup
The application will automatically create the database and tables when you first access it. No manual database setup is required.

**Default Database Configuration:**
- Host: localhost
- Database: roadside_assistance
- Username: (set in `.env` — `DB_USER`)
- Password: (set in `.env` — `DB_PASS`)

### 4. Access the Application
1. Open your web browser
2. Navigate to: `http://localhost/claude_admin2/`
3. You'll be redirected to the login page

**Default Login Credentials:**
- Username: `admin`
- Password: generated at first launch — check the PHP error log (`/var/log/apache2/error.log`) and change it immediately

### 5. First-Time Setup
After logging in:
1. Add some customers via the Customers page
2. Add technicians via the Technicians page
3. Create service requests
4. Assign technicians to requests
5. Complete requests and generate invoices

## Project Structure

```
claude_admin2/
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styles
│   └── js/
│       └── app.js             # JavaScript functionality
├── api/
│   ├── assign_technician.php  # AJAX endpoint for technician assignment
│   └── update_status.php      # AJAX endpoint for status updates
├── config/
│   └── database.php           # Database configuration and setup
├── includes/
│   └── functions.php          # Shared utility functions
├── pages/
│   ├── customers.php          # Customer management
│   ├── dashboard.php          # Main dashboard
│   ├── invoices.php           # Invoice management
│   ├── login.php              # User authentication
│   ├── logout.php             # Logout handler
│   ├── service-requests.php   # Service request management
│   └── technicians.php        # Technician management
├── .github/
│   └── copilot-instructions.md # Development guidelines
├── index.php                  # Main application entry point
└── README.md                  # This file
```

## Database Schema

### Tables Created Automatically:

1. **users** - System users and authentication
2. **customers** - Customer information
3. **technicians** - Technician profiles and availability
4. **service_requests** - Service requests and their status
5. **invoices** - Generated invoices for completed services

## Usage Guide

### Dashboard
- Overview of system statistics
- Recent service requests
- Quick navigation to all modules

### Customer Management
- Add new customers with contact information
- Edit existing customer details
- View customer service history
- Create new service requests for customers

### Service Request Management
- Create new service requests
- Assign technicians to requests
- Update request status (pending → assigned → in progress → completed)
- Set priority levels (low, medium, high, urgent)
- Track estimated vs actual costs

### Technician Management
- Add technician profiles
- Set specializations and hourly rates
- Track availability status (available, busy, offline)
- View active job assignments

### Invoice Management
- Generate invoices from completed service requests
- Automatic tax calculations
- Track payment status
- Print-friendly invoice views

## Customization

### Adding New Service Types
Edit the service type options in `pages/service-requests.php`:
```php
<option value="new_service">New Service Type</option>
```

### Modifying Tax Rates
Default tax rate is set to 8.25%. Change it in `pages/invoices.php`:
```php
<input type="number" ... value="8.25" ...>
```

### Styling Customization
Modify `assets/css/style.css` to customize the appearance:
- Color schemes
- Layout adjustments
- Responsive behavior

## Security Features

- Session-based authentication
- SQL injection prevention with prepared statements
- Input sanitization and validation
- XSS protection with htmlspecialchars()
- CSRF protection for sensitive operations

## Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Ensure MySQL service is running in XAMPP
   - Check database credentials in `config/database.php`

2. **Page Not Loading**
   - Verify Apache service is running
   - Check that the URL is correct: `http://localhost/claude_admin2/`

3. **Login Issues**
   - Check the PHP error log for the generated admin password on first run
   - Clear browser cache and cookies

4. **Permission Errors**
   - Ensure XAMPP has proper write permissions
   - Run XAMPP as administrator if needed

### Error Logging
Enable PHP error reporting by adding to the top of `index.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Development

### Adding New Features
1. Create new page files in `/pages/` directory
2. Add navigation links in `index.php`
3. Update routing in the main switch statement
4. Add corresponding database tables if needed

### API Endpoints
AJAX endpoints are located in `/api/` directory:
- Follow existing patterns for new endpoints
- Always validate user authentication
- Return JSON responses with proper HTTP status codes

## Future Enhancements

Potential features for future development:
- Email notifications for customers and technicians
- GPS tracking integration
- Mobile app for technicians
- Advanced reporting and analytics
- Multi-location support
- Customer portal
- SMS notifications
- Payment gateway integration

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the code comments for implementation details
3. Test with the default data and credentials

## License

This project is developed as an MVP for demonstration purposes. Modify and use as needed for your specific requirements.
