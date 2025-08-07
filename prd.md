# PHP Trading Journal App - Final Source Dev Prompt

The APP provides a framework for creating a defined trading strategy.

The main purpose of the APP is to provide structure and discipline to the trader's decision-making process, helping to stay focused on the rules of the system and avoid making impulsive trades.

It could also be set as a trading journal APP.

## Project Overview:
Build a standalone **Trading Journal Web App** using:
- **Vanilla PHP (no frameworks)**
- **MySQL** for database
- **Bootstrap 5** for styling
- **jQuery** (only where dynamic JS is needed)

The system must support:
- User registration & login
- Strategy template creation (max 3/user)
- Trade journaling (based on user’s strategy)
- Analytics dashboard with charts
- Admin (manual access override)

---

## ✅ Authentication
Implement basic session-based login system.

**Users Table:**
- `id`, `username`, `email`, `password_hash`, `strategy_limit` (default 3)

---

## 📘 Strategy Template Module

Each user can create up to **3 strategy templates**, unless granted more by admin.

### `strategies` table:
```sql
id | user_id | name | description | instrument | timeframes | sessions | chart_image_path | created_at
````

### `strategy_conditions` table:

```sql
id | strategy_id | type (entry/exit/invalidation) | description
```

Checklist conditions should be dynamically added with jQuery (e.g., "+ Add Condition").

---

## 📝 Trade Log Module

Each user can log trades linked to one of their templates.

### `trades` table:

```sql
id | user_id | strategy_id | date | instrument | session | entry_time | exit_time | entry_price | sl | tp | rrr | outcome | notes | created_at
```

**Trade Entry Form Fields:**

* Date
* Strategy (dropdown from user’s list)
* Instrument (dropdown)
* Session (Asia, London, NY)
* Entry Time
* Exit Time
* Entry Price
* Stop Loss
* Take Profit
* RRR
* Outcome (Win, Loss, Break-even)
* Notes

---

## 📊 Dashboard Module

Display analytics per user.

**Filters:**

* Date Range
* Strategy
* Instrument
* Session

**Metrics to show:**

* Win rate
* Average RRR
* Strategy-specific stats
* Monthly P/L (basic)
* Visuals:

  * Bar chart: Monthly trade count
  * Pie chart: Win/Loss/Break-even
  * Line chart: Equity curve (optional later)

Use Chart.js or Google Charts.

---

## 🧾 Admin Access (Optional Phase 2)

**Manual MySQL toggle or admin.php page**:

* View all users
* Update a user’s `strategy_limit` to allow more than 3 templates

---

## 🧠 Tech Summary

| Layer      | Tech                        |
| ---------- | --------------------------- |
| Frontend   | Bootstrap 5, jQuery         |
| Backend    | Vanilla PHP (PDO or MySQLi) |
| DB         | MySQL                       |
| Charts     | Chart.js                    |
| Upload Dir | `/uploads/`                 |

---

## ✅ MVP Workflow

1. Auth (Login, Register)
2. Strategy CRUD (with checklist)
3. Trade Journal Log Form
4. Dashboard with filters & charts
5. Admin override (if needed)

---

## 💡 UX Notes

* Use Bootstrap cards/tabs for strategy views
* Show user their current template count
* Trade list should be sortable
* Use Bootstrap modals for add/edit forms where needed
* Mobile-friendly

---

## Dev Instructions

* Keep file structure clean (`includes/`, `views/`, etc.)
* Use prepared statements
* Validate all form data (server-side + client-side)
* Secure file uploads


# Trading Journal App Skeleton

This is the base folder structure for the standalone PHP + MySQL trading journal app.

## Structure

- `includes/` → PHP logic and reusable modules
- `views/` → HTML/PHP view files
- `assets/css/` → Stylesheets (Bootstrap overrides, custom CSS)
- `assets/js/` → JavaScript (jQuery, Chart.js)
- `uploads/` → Uploaded chart images
- `db/` → Database connection scripts or SQL dumps
- `charts/` → Scripts for generating chart data for dashboard
