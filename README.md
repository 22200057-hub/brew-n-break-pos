# Brew n' Break POS System

A Point-of-Sale system for **Brew n' Break** — a cafe and billiard lounge. Built with PHP (frontend), React.js (UI components), and Python Flask (backend API).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | PHP, HTML, CSS, JavaScript |
| UI Components | React 18 (via CDN), Babel Standalone |
| Backend API | Python 3 + Flask |
| Database | MySQL (via XAMPP) |
| Charts | Chart.js |

---

## Features

- **Dashboard** — Live stats (orders, revenue, bookings, table hours), billiard table status, revenue chart, top-selling products, Airbnb booking calendar
- **Billiard Management** — Add/edit/delete sessions, live countdown timers, table status tracking
- **Cafe Orders / Menu** — Product catalog, order management
- **Transaction Management** — Unified view of cafe orders and billiard sessions with paid/unpaid badges
- **Bookings** — Airbnb and room reservations
- **Reports** — Auto-generated daily reports with print support
- **Notifications** — Real-time alerts for sessions expiring within 5 minutes
- **User Management** — Admin and Staff roles with separate dashboards
- **Settings** — Profile and account settings
- **Responsive Design** — Works on desktop, tablet, and mobile

---

## Default Login

| Username | Password | Role |
|---|---|---|
| `admin` | `admin` | Administrator |

---

## Requirements

### XAMPP
- Apache (port 80)
- MySQL (port 3306)
- PHP 8.x

### Python (Flask Backend)
- Python 3.8+
- pip packages listed in `requirements.txt`

---

## Setup Instructions

### 1. Set up XAMPP

1. Install [XAMPP](https://www.apachefriends.org/)
2. Start **Apache** and **MySQL** in XAMPP Control Panel
3. Place the `brew-n-break-pos` folder inside `C:\xampp\htdocs\`

### 2. Import the Database

1. Open [phpMyAdmin](http://localhost/phpmyadmin)
2. Create a new database named `brew_n_break`
3. Click **Import**, select the `brew_n_break.sql` file, and click **Go**

### 3. Install Python Dependencies

Open a terminal (Command Prompt or PowerShell) and run:

```bash
cd C:\xampp\htdocs\brew-n-break-pos
pip install -r requirements.txt
```

The `requirements.txt` includes:
```
flask
flask-cors
mysql-connector-python
bcrypt
```

### 4. Start the Flask Backend

In the same terminal, run:

```bash
python app.py
```

You should see:
```
 * Running on http://127.0.0.1:5000
```

Keep this terminal open while using the system.

### 5. Access the System

Open your browser and go to:

```
http://localhost/brew-n-break-pos/
```

Log in with:
- **Username:** `admin`
- **Password:** `admin`

---

## How the Python Backend Works

The Flask app (`app.py`) runs on **port 5000** and provides a REST API that the React components call via `fetch()`:

| Endpoint | Method | Description |
|---|---|---|
| `/api/login` | POST | Authenticate user |
| `/api/billiard-status` | GET | Live billiard table status |
| `/api/dashboard-stats` | GET | Today's orders, revenue, hours, bookings |
| `/api/sessions` | GET | All billiard sessions |
| `/api/transactions` | GET | All cafe + billiard transactions |
| `/api/notifications` | GET | Expiring session alerts |
| `/api/session-action` | POST | Add / edit / delete billiard sessions |
| `/api/transaction-action` | POST | Checkout / edit / delete transactions |

React components on the dashboard, staff page, billiard, and transaction pages all fetch live data from these Flask endpoints.

---

## Project Structure

```
brew-n-break-pos/
├── app.py                  # Python Flask backend (IT119 integration)
├── requirements.txt        # Python dependencies
├── auth.php                # PHP session authentication
├── dashboard.php           # Admin dashboard (React components)
├── staff.php               # Staff dashboard (React components)
├── billiard.php            # Billiard session management
├── transactions.php        # Transaction management
├── bookings.php            # Room/Airbnb bookings
├── menu.php                # Cafe menu & orders
├── reports.php             # Report generation
├── notifications.php       # Notification center
├── users.php               # User management (admin only)
├── settings.php            # Account settings
├── print_report.php        # Printable report view
└── logout.php              # Session logout
```

---

## Notes

- The Flask backend must be running (`python app.py`) for live data features to work.
- PHP sessions handle authentication — the login page uses PHP directly.
- React components are loaded via CDN (no build step needed).
- CORS is configured to allow requests from `localhost` only.
