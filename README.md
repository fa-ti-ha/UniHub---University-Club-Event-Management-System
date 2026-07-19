# UniHub — University Club & Event Management System

UniHub is a web-based University Club and Event Management System built with PHP, MySQL, HTML, CSS, and JavaScript. The system helps students discover university clubs, request club membership, register for events, receive notifications, and manage their profiles. It also provides separate dashboards for Student, Club Admin, and Super Admin roles.

---

## Project Overview

University clubs usually manage members, events, approvals, and announcements manually. UniHub solves this by providing a centralized platform where:

- Students can browse clubs and events.
- Students can request to join clubs.
- Students can register for approved events.
- Club admins can manage their club, members, join requests, and events.
- Super admins can manage users, clubs, events, club creation requests, and event approvals.
- Users receive notifications for important activities.

---

## Features

### Public Website

- Home page with university club and event overview
- Browse active clubs
- View club details
- Browse approved events
- View event details
- Login and registration pages

### Student Features

- Student registration
- Secure login and logout
- Student dashboard
- View joined clubs
- View registered events
- Request to join a club
- Request to create a new club
- Register for events
- View notifications
- Update profile and password

### Club Admin Features

- Club admin dashboard
- View club statistics
- Edit club information
- Manage club members
- Approve or reject club join requests
- Create events
- View club events
- View notifications
- Update profile

### Super Admin Features

- Super admin dashboard statistics
- Manage users
- Manage clubs
- Manage events
- Approve or reject club creation requests
- Approve or reject event requests
- View notifications
- Update profile

### Notification Features

- Notification badge count
- View all notifications
- Mark notifications as read
- Notifications for club approvals, event registration, join requests, and reminders

---

## User Roles

The system has three main roles:

| Role | Description |
|---|---|
| Student | Can join clubs, register for events, request club creation, and view notifications |
| Club Admin | Can manage assigned club, members, join requests, and events |
| Super Admin | Can manage users, clubs, events, and approval requests |

---

## Technology Stack

| Area | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Local Server | XAMPP |
| Version Control | Git and GitHub |
| Project Management | Jira Scrum |

---

## Folder Structure

```text
UniHub-UNH/
│
├── api/
│   ├── auth.php
│   ├── clubs.php
│   ├── events.php
│   ├── notifications.php
│   └── users.php
│
├── assets/
│   ├── css/
│   │   ├── auth.css
│   │   ├── components.css
│   │   ├── dashboard.css
│   │   ├── main.css
│   │   └── style.css
│   │
│   ├── js/
│   │   ├── admin.js
│   │   ├── auth.js
│   │   └── main.js
│   │
│   └── images/
│       ├── default-avatar.png
│       ├── default-avatar.svg
│       ├── default-banner.png
│       ├── default-banner.svg
│       ├── default-club.png
│       ├── default-club.svg
│       ├── default-event.png
│       └── default-event.svg
│
├── config/
│   └── db.php
│
├── dashboard/
│   ├── student/
│   │   ├── index.php
│   │   ├── my-clubs.php
│   │   ├── my-events.php
│   │   ├── notifications.php
│   │   └── profile.php
│   │
│   ├── club-admin/
│   │   ├── index.php
│   │   ├── edit-club.php
│   │   ├── members.php
│   │   ├── requests.php
│   │   ├── create-event.php
│   │   ├── events.php
│   │   ├── notifications.php
│   │   └── profile.php
│   │
│   └── super-admin/
│       ├── index.php
│       ├── users.php
│       ├── clubs.php
│       ├── events.php
│       ├── club-requests.php
│       ├── event-requests.php
│       ├── notifications.php
│       └── profile.php
│
├── database/
│   ├── schema.sql
│   └── seed.sql
│
├── includes/
│   ├── auth.php
│   ├── dashboard-shell.php
│   ├── footer.php
│   ├── functions.php
│   └── header.php
│
├── pages/
│   ├── club-detail.php
│   ├── clubs.php
│   ├── create-club.php
│   ├── event-detail.php
│   ├── events.php
│   ├── login.php
│   └── register.php
│
├── uploads/
│   ├── clubs/
│   │   ├── banners/
│   │   └── logos/
│   ├── events/
│   └── profiles/
│
├── Rules/
│   ├── rules.txt
│   └── branch_protect_rule.txt
│
├── .gitignore
├── .htaccess
├── CONTRIBUTING.md
├── README.md
└── index.php
