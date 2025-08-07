# Trading Journal App - Development Plan

## Project Overview
A standalone PHP trading journal application that provides structured trade logging, strategy management, and analytics for traders. Built with vanilla PHP, MySQL, Bootstrap 5 (Phoenix theme), and Chart.js.

## Core Requirements Summary
- **Authentication**: Registration with email verification, login, password reset
- **Strategy Management**: Max 3 strategies per user (admin override possible)
- **Trade Logging**: Journal trades linked to strategies with screenshots
- **Analytics**: Dashboard with charts, filters, and export functionality
- **Admin Panel**: User management and strategy limit adjustments

## Technical Stack
| Component | Technology |
|-----------|------------|
| Backend | Vanilla PHP with PDO |
| Database | MySQL |
| Frontend | Phoenix Bootstrap 5 Theme |
| JavaScript | jQuery + Chart.js |
| Date Format | YYYY/MM/DD |
| File Upload | 4MB limit, images only |
| Password | bcrypt with password_hash() |

## Database Schema

### users
```sql
id | username | email | password_hash | email_verified | verification_token | 
reset_token | strategy_limit (default 3) | is_admin | account_size | created_at
```

### strategies
```sql
id | user_id | name | description | instrument | timeframes (JSON) | 
sessions (JSON) | chart_image_path | created_at | updated_at
```

### strategy_conditions
```sql
id | strategy_id | type (entry/exit/invalidation) | description | created_at
```

### trades
```sql
id | user_id | strategy_id | date | instrument | session | direction (long/short) |
entry_time | exit_time | entry_price | sl | tp | rrr | outcome | 
status (open/closed/cancelled) | screenshot_path | notes | created_at | updated_at
```

### email_queue
```sql
id | to_email | subject | body | status | attempts | created_at | sent_at
```

## Project Structure
```
/trade-logger/
├── /config/
│   ├── database.php        # PDO connection class
│   ├── config.php          # App configuration
│   └── constants.php       # Global constants
├── /includes/
│   ├── auth.php           # Authentication functions
│   ├── validation.php     # Input validation helpers
│   ├── csrf.php          # CSRF protection
│   ├── email.php         # Email functions
│   ├── upload.php        # File upload handler
│   └── helpers.php       # Utility functions
├── /models/
│   ├── User.php
│   ├── Strategy.php
│   ├── Trade.php
│   └── Admin.php
├── /views/
│   ├── /auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── verify-email.php
│   │   └── reset-password.php
│   ├── /strategies/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── view.php
│   ├── /trades/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── view.php
│   ├── /dashboard/
│   │   ├── index.php
│   │   ├── analytics.php
│   │   └── exports.php
│   ├── /admin/
│   │   ├── users.php
│   │   └── settings.php
│   └── /layouts/
│       ├── header.php
│       ├── footer.php
│       └── nav.php
├── /assets/
│   ├── /css/
│   │   ├── phoenix/       # Phoenix theme files
│   │   └── custom.css
│   ├── /js/
│   │   ├── app.js
│   │   ├── charts.js
│   │   └── trade-validation.js
│   └── /vendor/
│       ├── jquery.min.js
│       └── chart.min.js
├── /uploads/
│   ├── /strategies/
│   └── /trades/
├── /db/
│   ├── schema.sql
│   └── seed.sql
├── /api/
│   ├── get-chart-data.php
│   ├── export-csv.php
│   └── export-pdf.php
├── index.php              # Landing/redirect
├── login.php
├── register.php
├── dashboard.php
└── logout.php
```

## Development Phases

### Phase 1: Foundation Setup (Day 1-2)
- [ ] Create database schema and tables
- [ ] Set up PDO connection class with error handling
- [ ] Create project folder structure
- [ ] Integrate Phoenix Bootstrap theme
- [ ] Build configuration system
- [ ] Implement basic routing/navigation
- [ ] Create layout templates (header, footer, nav)

### Phase 2: Authentication System (Day 3-4)
- [ ] User registration with validation
- [ ] Email verification system
- [ ] Login functionality with bcrypt
- [ ] Password reset feature
- [ ] Session management
- [ ] CSRF protection implementation
- [ ] Admin flag and access control

### Phase 3: Strategy Module (Day 5-6)
- [ ] Strategy CRUD operations
- [ ] Multi-select for timeframes/sessions (stored as JSON)
- [ ] Dynamic condition management with jQuery
- [ ] Chart image upload for strategies
- [ ] 3-strategy limit enforcement
- [ ] Strategy listing and detail views

### Phase 4: Trade Logging (Day 7-8)
- [ ] Trade entry form with validations
- [ ] Direction-based SL validation (long: SL < entry, short: SL > entry)
- [ ] Manual RRR input
- [ ] Trade screenshot upload
- [ ] Status management (Win/Loss/BE/Open/Cancelled)
- [ ] Trade edit functionality
- [ ] Trade listing with sorting/filtering

### Phase 5: Analytics Dashboard (Day 9-10)
- [ ] Filter system (date range, strategy, instrument, session)
- [ ] Win rate calculation
- [ ] Average RRR calculation
- [ ] Monthly P/L percentage calculation
- [ ] Account size input for equity curve
- [ ] Chart.js integration:
  - [ ] Monthly trade count (bar chart)
  - [ ] Win/Loss/BE distribution (pie chart)
  - [ ] Equity curve (line chart)
- [ ] Real-time chart updates with filters

### Phase 6: Export Features (Day 11)
- [ ] CSV export for filtered trade data
- [ ] PDF report generation with charts
- [ ] Downloadable strategy summaries

### Phase 7: Admin Panel (Day 12)
- [ ] User list with search
- [ ] Strategy limit adjustment per user
- [ ] User deletion functionality
- [ ] Basic usage statistics
- [ ] System health checks

### Phase 8: Testing & Polish (Day 13-14)
- [ ] Cross-browser testing
- [ ] Mobile responsiveness
- [ ] Performance optimization
- [ ] Security audit
- [ ] Error handling improvements
- [ ] User documentation

## Key Features Implementation Details

### Email Verification
- Generate unique token on registration
- Send verification email with link
- Block login until verified
- Token expiry after 24 hours

### Password Reset
- Request reset via email
- Generate secure token
- Email reset link
- Token valid for 1 hour
- Update password with new hash

### Trade Validation Rules
- Entry price required
- SL required and validated based on direction
- TP optional but validated if provided
- RRR manual input (not calculated)
- Date cannot be future
- Times must be valid format

### File Upload Security
- Max 4MB file size
- Allowed types: jpg, jpeg, png, gif
- Rename files with unique hash
- Store outside web root if possible
- Validate MIME type server-side

### Chart Data Processing
- Aggregate trades by month
- Calculate cumulative P/L
- Apply filters before aggregation
- Cache results for performance
- Return JSON for Chart.js

### DRY Principles Application
- Reusable validation functions
- Database abstraction layer
- Template components
- JavaScript modules
- CSS utility classes
- Configuration constants

## Security Considerations
- Prepared statements for all queries
- Input sanitization and validation
- CSRF tokens on all forms
- Session regeneration on login
- Secure password hashing
- File upload restrictions
- XSS protection in outputs
- SQL injection prevention

## Performance Optimizations
- Database indexing on foreign keys
- Lazy loading for images
- Chart data caching
- Pagination for trade lists
- Minified CSS/JS in production
- Optimized queries with JOINs

## Testing Checklist
- [ ] User registration flow
- [ ] Email verification process
- [ ] Login/logout functionality
- [ ] Password reset flow
- [ ] Strategy CRUD operations
- [ ] Trade logging with all statuses
- [ ] Trade validation rules
- [ ] Dashboard filters
- [ ] Chart rendering
- [ ] Export functionality
- [ ] Admin panel access
- [ ] Mobile responsiveness
- [ ] Cross-browser compatibility

## Deployment Notes
- PHP 7.4+ required
- MySQL 5.7+ required
- mod_rewrite for clean URLs
- SSL certificate recommended
- Cron job for email queue
- Backup strategy for uploads
- Environment variables for credentials

## Future Enhancements (Post-MVP)
- API for mobile app
- Advanced analytics
- Social features
- Automated trade import
- Backtesting tools
- Multi-language support
- Dark mode
- WebSocket for real-time updates

## Success Metrics
- User can register and verify email
- User can create up to 3 strategies
- User can log trades with screenshots
- Dashboard shows accurate analytics
- Charts update with filters
- Exports work correctly
- Admin can manage users
- Mobile experience is smooth

---

This plan will be updated as development progresses. Each completed phase will be marked and any blockers or changes will be documented.