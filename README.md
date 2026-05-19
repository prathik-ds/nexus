# FusionVerse | Full-Stack College Fest Platform

FusionVerse is a high-performance web platform built specifically for a fusion of **IT (BCA)** and **Commerce (BCom)** fests. It features a futuristic, sci-fi-inspired UI with a robust PHP/MySQL backend.

## 🚀 Key Features

### 1. 🏠 Immersive Home Page
- **Hero Section**: Large glowing "FusionVerse" title with "Where Commerce Meets Code" tagline.
- **Countdown Timer**: Real-time ticker counting down to the fest date.
- **Dynamic Highlights**: Glassmorphism cards showcasing core tracks.

### 2. 🔐 User Authentication System
- **Registration**: Captures Name, Email, Phone, and Course.
- **Unique ID Generation**: Automatically assigns a `SYN-XXXX` ID for each student.
- **Secure Login**: Verifies credentials and manages secure PHP sessions.

### 3. 📊 User Dashboard
- **Profile Card**: Displays unique ID and student details.
- **My Registrations**: Table tracking registered events, status, and scores.
- **Notice Board**: Real-time alerts and announcements posted by admins.

### 4. 🎯 Event Management
- **Categorized Events**: Separates IT Track (Code Rush, Hackathon) and Commerce Track (Quizzes, Marketing).
- **Registration Logic**: Prevents duplicate registrations and enforces participant limits.
- **Automated Slots**: Updates available slots in real-time.

### 5. 🧑💼 Admin Control Panel
- **Live Stats**: View total users, registrations, and active event counts.
- **Score Management**: Admin can enter marks and update statuses (Winner, Runner, etc.).
- **Announcement Tool**: Post updates directly to all user notice boards.

### 6. 🏆 Leaderboard
- **Hall of Fame**: Publicly ranked list of top performers across all events.
- **Winner Badges**: Distinctive neon tags for winners and runners-up.

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3 (Custom Neon/Sci-Fi Theme), Vanilla JavaScript.
- **Design**: Orbitron & Exo 2 fonts, Glassmorphism, CSS Scanline animations.
- **Backend**: PHP (Session Management, Transactional Queries).
- **Database**: MySQL (Optimized schema with relational integrity).

---

## 📂 Project Structure

- `index.php` - Home Page
- `login.php` / `register.php` - Authentication
- `dashboard.php` - Personal User Portal
- `events.php` / `register_event.php` - Event Discovery & Sign-up
- `leaderboard.php` - Public Scores
- `admin.php` - Administration Command Center
- `config/db.php` - Database Connection
- `includes/` - Reusable UI Modules (Header, Footer)
- `assets/` - Custom CSS and JS logic

---

## 🏗️ Installation (Local)

1. **Database**: Import the `server/schema.sql` file into your MySQL database (or run the SQL from that file).
2. **Configuration**: Update `config/db.php` with your local database credentials if different from defaults.
3. **Server**: Host the files on a PHP server (XAMPP, WAMP, or `php -S localhost:8000`).

---

### ✨ Extra Aesthetics
- **Mouse Pulse**: A neon glow follows the user's cursor for an immersive feel.
- **Scanlines**: Subtle CRT-style scanlines overlay the entire site.
- **Transitions**: Smooth scroll-reveal animations for all glass cards.
