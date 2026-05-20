# Project Record: Nexus Fest 2026

---

# Chapter 1: Synopsis

## 1.1 Introduction
Nexus Fest 2026 is an immersive, high-performance web platform designed specifically for managing inter-departmental college fests integrating IT (BCA) and Commerce (BCom) competition tracks. Sporting a premium, cyber-tech inspired dark mode user interface, the system manages the entire event lifecycle—from user registration and unique ID allocation to team formation, QR-code based attendance tracking, leaderboard compilation, and digital certificate distribution.

## 1.2 Project Category
- **Category:** Web-Based Application
- **Domain:** Event Management and Coordination Systems
- **Architecture:** Client-Server Monolithic Architecture

## 1.3 Problem Statement
Traditional academic fest platforms suffer from severe database bottlenecks and security issues. During peak registration hours (600+ students registering simultaneously), shared hosting environments (such as Hostinger Premium) routinely hit connection limits, generating "Too many connections" errors. Additionally, standard QR pass implementations rely on "global" user passes, allowing students to check in to incorrect events or share codes. There is a critical need for a system with:
1. Short-lived database connections to survive resource-constrained hosts.
2. Event-specific QR ticket generation to prevent incorrect check-ins.
3. A mobile-first, responsive interface for track coordinators executing tasks on their feet.

## 1.4 Objectives
- **Objective 1:** Establish a secure registration flow that automatically allocates unique participant identification tags (in the format `NXS-XXXX`).
- **Objective 2:** Implement an event-specific QR code ticketing system (`EVENT_QR|user_id|event_id`) to validate student check-ins via local camera inputs.
- **Objective 3:** Build a high-performance DB Singleton pattern coupled with short caching layers to sustain peak traffic without service outages.

## 1.5 Proposed System
The proposed Nexus Fest 2026 system implements a custom PHP database wrapper using the Singleton Pattern to ensure exactly one PDO connection is used per script execution. It disables persistent connections and registers an explicit shutdown function to release MySQL connections immediately. To safeguard attendance validation, the platform rejects global QR passes and requires students to display event-specific tickets. A touch-optimized coordinator screen replaces standard desktop tables with compact cards, incorporating custom inline dropdowns for immediate status synchronization.

## 1.6 Methodology / Working
- **Data Acquisition:** Students input registration details (Name, Email, Phone, Course, Year, Roll Number). Coordinators record attendance and select participation outcomes. The system scans and decodes QR tickets through camera frames.
- **Processing & Logic:** The PHP backend validates input boundaries, hashes passwords using bcrypt (`PASSWORD_DEFAULT`), handles dynamic team creation with invite code links, limits event registrations by capacity, and caches notice board updates.
- **Rendering & State:** Custom styling provides a neon cyber-tech design with responsive CSS, card layouts, and bottom navigation. User state is secured in PHP sessions using HttpOnly and SameSite cookie options.

## 1.7 Hardware Requirements
- **Processor:** Dual-core 2.0 GHz or higher (client/server hosting minimum).
- **RAM:** Minimum 4 GB (8 GB Recommended for local testing).
- **Storage:** Minimum 100 MB of free storage space for hosting files.
- **Network:** Active internet connection (broadband/4G for administrative desks; stable mobile network for QR check-ins).

## 1.8 Software Requirements
- **Operating System:** Windows, Linux, or macOS.
- **Backend Language:** PHP 8.0 or higher.
- **Frontend Technologies:** HTML5, CSS3, Vanilla JavaScript, html5-qrcode library, and qrcodejs.
- **Web Server:** Apache (with `.htaccess` rewrite rules), Nginx, or PHP Built-in Server.
- **Database:** MySQL 5.7+ or MariaDB 10.3+.

## 1.9 Modules and Features of Modules
- **Student Module**
  - Unique ID Generation (`NXS-XXXX`).
  - Event discovery filtered by IT and Commerce Tracks.
  - Team creation & invite code management.
  - Event-specific QR ticket display.
  - Public leaderboard access.
  - Dynamic certificate downloads.
- **Coordinator Module**
  - Assigned events overview dashboard.
  - Camera-based QR ticket scanner.
  - Inline attendance & score update desk.
  - CSV format data exports.
- **Admin Module**
  - Command dashboard with live registration and event metrics.
  - User role management (Elevating students to coordinators).
  - Track creation and coordinator assignment.
  - Announcement and global alert broadcasts.

## 1.10 Module Description
- **Student Module:** Used by fest participants. Handles profile setup and assigns a unique student ID. Allows participants to view rules, join team events using invite codes, generate QR entry tickets, and download participation certificates after evaluation.
- **Coordinator Module:** Used by assigned event managers. Displays their assigned track duties. Contains an optical QR scanning interface that reads specific event tickets, confirms validity, and registers attendance. Includes options to log participant status (Winner, Runner, Participated) and export rosters.
- **Admin Module:** Used by event organizers. Controls global parameters. Administrators can modify events, change user classifications, seed data banks, and post announcements on the dashboard.

## 1.11 Database Tables
- `users`: Stores participant profile info (Name, Email, Phone, Course, Year, Roll Number, Hashed Password) and role permissions (`student`, `coordinator`, `admin`).
- `events`: Tracks competition profiles, category categories (IT/Commerce Track), schedule details, coordinate points of contact, and size boundaries.
- `teams`: Holds team structures, referencing leader ids, event ids, and unique alphanumeric invite codes.
- `team_members`: Maps users who joined a team via invite code.
- `registrations`: Joins users and events; updates attendance (`absent`, `present`), score points, and status (`registered`, `participated`, `winner`, `runner`).
- `announcements`: Logs public broadcasts (title, content, type) appearing on notice boards.

## 1.12 Advantages
- **High Performance:** Employs Singleton PDO configurations and session notice caches to prevent server limits from being exceeded.
- **Improved Security:** Protects entry gates using event-specific tickets instead of generic reusable QR passes.
- **Touch Usability:** Compact card layouts and custom inline selector boxes enable rapid on-field coordination.

## 1.13 Applications
- IT and Commerce college festivals.
- High-volume academic hackathons and competitions.
- Institutional registration portals.

## 1.14 Limitations
- **Camera Dependency:** Coordinators must authorize camera permissions for QR reading features.
- **Network Dependency:** No offline local sync is supported; database interactions require real-time connectivity.

## 1.15 Future Scope
- **Push Notification Integration:** Real-time reminders for event schedules and leaderboard changes.
- **Native Applications:** Android and iOS mobile wrappers to improve camera response speeds and add local caching capabilities.

## 1.16 Conclusion
Nexus Fest 2026 successfully delivers a visually striking, secure, and resource-efficient fest management environment. By incorporating Singleton database practices, strict input constraints, and event-focused check-in pathways, the system overcomes resource constraints in shared hosting environments and improves event coordination workflows.

---

# Chapter 2: Software Requirement Specification (SRS)

## 2.1 Introduction

### 2.1.1 Purpose
This SRS documents the functional scope, interface configurations, performance parameters, and architectural constraints of Nexus Fest 2026. It serves as a blueprint for developers, testers, and event supervisors.

### 2.1.2 Scope
This system covers student registration, role assignments, event browsing, team formations, QR verification checks, score registries, and leaderboard listings. It does *not* cover financial transactions or email notifications.

### 2.1.3 Definitions, Acronyms, and Abbreviations
- **NXS:** Nexus (The prefix used for unique participant codes).
- **PDO:** PHP Data Objects (Database access abstraction layer).
- **CSRF:** Cross-Site Request Forgery (Security token protection).
- **TTL:** Time-To-Live (Cache duration).
- **FPM:** FastCGI Process Manager.

### 2.1.4 Overview
This SRS defines the stack, functional capabilities, and design guidelines that drive the client and server interfaces of Nexus Fest 2026.

## 2.2 Language and Tools

### 2.2.1 Frontend
- **HTML5 & CSS3:** Structure and glowing, glassmorphic theme styling.
- **Vanilla JavaScript:** Event behaviors, QR decoding logic, and modal rendering.
- **Html5-Qrcode Library:** Integrates with local devices to scan QR codes.

### 2.2.2 Backend
- **PHP 8.x:** Processes requests, coordinates transactions, and enforces authentication limits.

### 2.2.3 Database
- **MySQL / MariaDB:** Stores system data with relational foreign key structures.

## 2.3 Overall Description

### 2.3.1 Product Functions
- User registration and automated ID generation.
- Solo and team event registration (with invite codes and locked team rosters).
- Camera-enabled QR code attendance verification.
- Roster exports (CSV).
- Certificate issue desk.
- Leaderboard rankings.

### 2.3.2 User Characteristics
- **Students:** Require an intuitive, mobile-friendly interface to manage registrations and tickets.
- **Coordinators:** Require touch-friendly mobile dashboards to verify attendance and update scores on-site.
- **Administrators:** Require a centralized dashboard to manage database tables and roles.

### 2.3.3 General Constraints
- Connection limits in shared hosting environments.
- Browser-dependent camera access permissions.

### 2.3.4 Assumptions and Dependencies
- Users are accessing the platform using modern, HTML5-compatible mobile or desktop browsers.
- A MySQL server instance is running and reachable by the PHP backend.

## 2.4 Specific Requirements

### 2.4.1 External Interface Requirements

#### 2.4.1.1 User Interfaces
- Cyber-themed dark mode interface with Orbitron and Exo 2 typography.
- Glassmorphism panels, scanline overlays, and neon button highlights.
- Mobile bottom navigation bars to replace desktop sidebars on smaller screens.

#### 2.4.1.2 Hardware Interfaces
- Front or rear camera access on mobile devices for the QR scanner.

#### 2.4.1.3 Software Interfaces
- Web browser client interacting with an Apache/Nginx server via standard HTTP/HTTPS protocols.
- PHP PDO communicating with the local MySQL server.

#### 2.4.1.4 Communication Interfaces
- Secure HTTP cookies with Lax SameSite and HttpOnly policies.
- Database access managed through a Singleton instance to prevent connection leaks.

### 2.4.2 Functional Requirements

#### 2.4.2.1 Module Description
- **Registration Flow:** Validates input details. On success, generates a unique ID (`NXS-XXXX`) and inserts the user with a bcrypt-hashed password.
- **Team Matchmaking:** Generates a unique invite code when a student creates a team for a team event. Other students can join using the code, and team rosters are locked once registration closes.
- **QR Validation Flow:** When a ticket is scanned, the coordinator page decodes `EVENT_QR|user_id|event_id`, verifies the event ID matches, and updates the registration attendance status to `present` via AJAX.

### 2.4.3 Performance Requirements

#### 2.4.3.1 Static Requirements
- Normalized database schema (3rd Normal Form).
- Cached announcements in the user session (2-minute TTL) to minimize database reads under high concurrent loads.

#### 2.4.3.2 Dynamic Requirements
- Page load times under 1.5 seconds.
- Database connection lifecycle terminated via an explicit PHP shutdown hook to free connection pools.

### 2.4.4 Design Constraints
- Standard Custom CSS styling (no external Tailwind layers unless requested).
- Cryptographically secure CSRF validation tokens required for POST requests.
- Passwords must be hashed using PHP's `password_hash` implementation.
