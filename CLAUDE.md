# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Trade Logger** is a PHP trading journal application built with vanilla PHP, MySQL, and Bootstrap 5. The application helps traders log and analyze their trading activities with comprehensive strategy management and analytics.

## Architecture

### Technology Stack
- **Backend**: PHP 7.4+ with PDO MySQL
- **Frontend**: Bootstrap 5, Chart.js, vanilla JavaScript
- **Database**: MySQL with prepared statements
- **Authentication**: Session-based with CSRF protection

### Core Structure
```
/opt/lampp/htdocs/trade-logger/
├── config/              # Database and app configuration
├── models/              # Data models (Trade, Strategy, Admin)  
├── views/               # MVC view templates organized by feature
├── assets/              # CSS, JS, and vendor files
├── api/                 # AJAX endpoints (export-csv.php, export-pdf.php, etc.)
├── includes/            # Helper functions and utilities
├── uploads/             # User file uploads (trades/, strategies/)
└── legacy/              # Reference code - DO NOT MODIFY
```

### MVC Architecture
- **Models**: `models/Trade.php`, `models/Strategy.php`, `models/Admin.php`
- **Views**: Organized in `views/` by feature (auth/, trades/, strategies/, admin/)
- **Controllers**: Embedded in main PHP files (dashboard.php, login.php, etc.)

## Database Architecture

### Core Tables
- **users**: User accounts with authentication and limits
- **strategies**: Trading strategies with conditions and metadata  
- **trades**: Individual trade records with P&L tracking
- **strategy_conditions**: Entry/exit/invalidation conditions

### Key Features
- User strategy limits (configurable per user)
- Account size tracking for position sizing
- Trade outcome tracking (Win/Loss/Break-even)
- File upload support for trade screenshots

## Development Commands

### Local Development Environment (XAMPP)
```bash
# Start XAMPP services
sudo /opt/lampp/lampp start

# Access application
# http://localhost/trade-logger

# Database access via phpMyAdmin  
# http://localhost/phpmyadmin
```

### Database Management
```bash
# Import schema (if needed)
mysql -u root -p trade_logger < db/schema.sql

# Database connection configured in config/database.php
# Uses environment variables: DB_HOST, DB_NAME, DB_USER, DB_PASS
```

## Key Classes and Models

### Trade Model (`models/Trade.php`)
- **Key Methods**: `create()`, `update()`, `delete()`, `getByUserId()`, `getTradeStats()`
- **Features**: Full validation, transaction support, filtering and pagination
- **Analytics**: Monthly stats, instrument performance, win rate calculations

### Strategy Model (`models/Strategy.php`) 
- **Key Methods**: Strategy CRUD operations, condition management
- **Features**: User strategy limits, file upload handling
- **Relationships**: One-to-many with trades and conditions

### Database Class (`config/database.php`)
- **Features**: PDO MySQL with prepared statements, transaction support
- **Methods**: `query()`, `fetch()`, `fetchAll()`, `execute()`, `beginTransaction()`

## JavaScript Architecture

### Core Files
- `assets/js/app.js`: Global utilities, CSRF handling, toast notifications
- `assets/js/trades.js`: Trade form handling and validation  
- `assets/js/strategies.js`: Strategy management functionality
- `assets/js/charts.js`: Chart.js analytics and visualizations

### Global Object
```javascript
window.TradeLogger = {
    baseUrl: window.location.origin + '/trade-logger',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    utils: { /* utility functions */ }
}
```

## Security Features

### Authentication & Authorization
- Session-based authentication with configurable lifetime
- CSRF token protection on all forms  
- `requireLogin()` and `requireAdmin()` helper functions
- Input sanitization via `sanitize()` helper

### File Upload Security
- Restricted file types (ALLOWED_IMAGE_TYPES)
- Size limits (UPLOAD_MAX_SIZE = 4MB)
- Secure upload path handling

## Configuration

### Environment Configuration (`config/config.php`)
- Database connection parameters via environment variables
- File upload settings and limits
- Session configuration and security settings
- Date/time format constants

### Key Constants
```php
BASE_URL = 'http://localhost/trade-logger'
UPLOAD_PATH = __DIR__ . '/../uploads/'
SESSION_LIFETIME = 86400
DEFAULT_STRATEGY_LIMIT = 3
```

## Common Development Patterns

### Form Processing
1. CSRF token validation
2. Input sanitization via `sanitize()`
3. Model validation and processing
4. Flash message feedback
5. Redirect on success

### Database Operations
1. Use prepared statements via Database class
2. Wrap multi-step operations in transactions
3. Validate data in model classes before database operations
4. Use consistent error handling and logging

### File Uploads
1. Validate file type and size in helper functions
2. Generate secure filenames  
3. Store relative paths in database
4. Handle file cleanup on record deletion

## Analytics and Reporting

### Trade Statistics
- Win rate calculations with completed trades only
- Monthly trade performance tracking
- Instrument-specific analytics
- Risk-reward ratio (RRR) analysis

### Chart.js Integration
- Monthly performance charts in `assets/js/charts.js`
- Responsive chart configuration
- Real-time data updates via AJAX

## Testing and Quality

### Manual Testing Approach
- Test all CRUD operations through web interface
- Verify authentication and authorization flows
- Test file upload functionality with various file types
- Validate data integrity across related models

### Error Handling
- Model-level validation with descriptive error messages
- Database transaction rollbacks on failures
- User-friendly flash messages for feedback
- Error logging for debugging

## Legacy Code

The `legacy/` directory contains reference code from a WordPress plugin project and should **NOT** be modified. It serves as a reference for architectural patterns and implementation examples only.

## Development Notes

- All custom functions use consistent naming (camelCase for JS, snake_case for PHP)
- Database queries use prepared statements exclusively
- File uploads are validated and secured
- Session management follows PHP best practices
- Bootstrap 5 classes used throughout for consistent styling