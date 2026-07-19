-- ============================================================
-- University Club & Event Management System
-- Database Schema v1.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS unihubt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unihubt;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Table: departments
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    student_id VARCHAR(30) UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','club_admin','super_admin') DEFAULT 'student',
    department_id INT UNSIGNED,
    batch VARCHAR(20),
    phone VARCHAR(20),
    profile_picture VARCHAR(255) DEFAULT NULL,
    status ENUM('active','blocked','pending') DEFAULT 'active',
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Table: teacher_supervisors
-- ============================================================
CREATE TABLE IF NOT EXISTS teacher_supervisors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(20),
    department VARCHAR(150),
    designation VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Table: clubs
-- ============================================================
CREATE TABLE IF NOT EXISTS clubs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    short_description TEXT,
    full_description LONGTEXT,
    mission TEXT,
    vision TEXT,
    activities TEXT,
    logo VARCHAR(255) DEFAULT NULL,
    banner VARCHAR(255) DEFAULT NULL,
    category VARCHAR(80) DEFAULT 'General',
    supervisor_id INT UNSIGNED,
    admin_id INT UNSIGNED,
    president_name VARCHAR(100),
    vice_president_name VARCHAR(100),
    total_members INT UNSIGNED DEFAULT 0,
    status ENUM('active','suspended','pending','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supervisor_id) REFERENCES teacher_supervisors(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Table: club_members
-- ============================================================
CREATE TABLE IF NOT EXISTS club_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('member','vice_president','president','club_admin') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_club_member (club_id, user_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table: club_join_requests
-- ============================================================
CREATE TABLE IF NOT EXISTS club_join_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME DEFAULT NULL,
    reviewed_by INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Table: club_creation_requests
-- ============================================================
CREATE TABLE IF NOT EXISTS club_creation_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requested_by INT UNSIGNED NOT NULL,
    club_name VARCHAR(150) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    banner VARCHAR(255) DEFAULT NULL,
    description TEXT,
    objectives TEXT,
    activities TEXT,
    supervisor_name VARCHAR(150),
    supervisor_email VARCHAR(150),
    reason TEXT,
    contact_info VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by INT UNSIGNED DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Table: events
-- ============================================================
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(210) NOT NULL UNIQUE,
    description LONGTEXT,
    category VARCHAR(80) DEFAULT 'General',
    venue VARCHAR(255),
    banner VARCHAR(255) DEFAULT NULL,
    start_date DATETIME,
    end_date DATETIME,
    registration_deadline DATETIME,
    max_participants INT UNSIGNED DEFAULT 0,
    current_participants INT UNSIGNED DEFAULT 0,
    status ENUM('pending','approved','ongoing','completed','cancelled') DEFAULT 'pending',
    registration_type ENUM('auto','manual') DEFAULT 'auto',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table: event_images
-- ============================================================
CREATE TABLE IF NOT EXISTS event_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table: event_registrations
-- ============================================================
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('pending','confirmed','attended','cancelled') DEFAULT 'confirmed',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attended_at DATETIME DEFAULT NULL,
    UNIQUE KEY unique_event_reg (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    related_id INT UNSIGNED DEFAULT NULL,
    related_type VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table: activity_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Table: settings
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Departments
INSERT INTO departments (name, code) VALUES
('Computer Science & Engineering', 'CSE'),
('Electrical & Electronic Engineering', 'EEE'),
('Business Administration', 'BBA'),
('English', 'ENG'),
('Mathematics', 'MATH'),
('Physics', 'PHY'),
('Civil Engineering', 'CE'),
('Architecture', 'ARCH'),
('Law', 'LAW'),
('Pharmacy', 'PHR');

-- Teacher Supervisors
INSERT INTO teacher_supervisors (name, email, phone, department, designation) VALUES
('Dr. Rahim Uddin', 'rahim@university.edu', '01700000001', 'CSE', 'Professor'),
('Dr. Fatema Begum', 'fatema@university.edu', '01700000002', 'EEE', 'Associate Professor'),
('Mr. Kamal Hossain', 'kamal@university.edu', '01700000003', 'BBA', 'Senior Lecturer'),
('Dr. Nasreen Akter', 'nasreen@university.edu', '01700000004', 'ENG', 'Professor'),
('Mr. Sabbir Ahmed', 'sabbir@university.edu', '01700000005', 'CSE', 'Assistant Professor');

-- Super Admin (password: Admin@1234)
INSERT INTO users (full_name, student_id, email, password_hash, role, status) VALUES
('Super Administrator', 'ADMIN001', 'admin@university.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'super_admin', 'active');

-- Sample Club Admins (password: Admin@1234)
INSERT INTO users (full_name, student_id, email, password_hash, role, department_id, batch, phone, status) VALUES
('Arif Rahman', 'CSE2001', 'arif@university.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'club_admin', 1, '2020', '01711111111', 'active'),
('Sadia Islam', 'EEE2002', 'sadia@university.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'club_admin', 2, '2020', '01711111112', 'active'),
('Mehedi Hasan', 'BBA2003', 'mehedi@university.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'club_admin', 3, '2020', '01711111113', 'active');

-- Sample Students (password: Admin@1234)
INSERT INTO users (full_name, student_id, email, password_hash, role, department_id, batch, phone, status) VALUES
('Rafi Khan', 'CSE2101', 'rafi@student.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'student', 1, '2021', '01722222221', 'active'),
('Nusrat Jahan', 'EEE2102', 'nusrat@student.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'student', 2, '2021', '01722222222', 'active'),
('Tanvir Ahmed', 'CSE2103', 'tanvir@student.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'student', 1, '2021', '01722222223', 'active'),
('Mitu Akter', 'BBA2104', 'mitu@student.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'student', 3, '2021', '01722222224', 'active'),
('Sabbir Alam', 'ENG2105', 'sabbir.s@student.edu', '$2y$10$upr2mEqjjqwRuh63vjZOrecD7hBPJikqgWOZe9gOhPyXiRmJgnZ9.', 'student', 4, '2021', '01722222225', 'active');

-- Sample Clubs
INSERT INTO clubs (name, slug, short_description, full_description, mission, vision, activities, category, supervisor_id, admin_id, president_name, vice_president_name, total_members, status) VALUES
('Programming Club', 'programming-club', 'A club for passionate coders and developers.', 'The Programming Club is the hub for all tech enthusiasts at UniHub University. We host hackathons, coding workshops, and competitive programming sessions throughout the year.', 'To foster a culture of innovation and technical excellence among students.', 'To produce world-class software engineers and entrepreneurs from our university.', 'Weekly coding sessions, Hackathons, Tech talks, Competitive programming, Project showcases', 'Technology', 1, 2, 'Arif Rahman', 'Tanvir Ahmed', 45, 'active'),
('Cultural Club', 'cultural-club', 'Celebrating arts, music, dance, and cultural heritage.', 'The Cultural Club is the beating heart of university life. We organize events celebrating diverse cultures, music performances, drama productions, and art exhibitions.', 'To preserve and promote cultural heritage while fostering creativity.', 'A vibrant cultural community that celebrates diversity.', 'Music concerts, Drama performances, Art exhibitions, Cultural festivals, Dance workshops', 'Arts & Culture', 4, 3, 'Sadia Islam', 'Nusrat Jahan', 62, 'active'),
('Debate Club', 'debate-club', 'Sharpen your arguments and public speaking skills.', 'The Debate Club trains students in critical thinking, argumentation, and public speaking. We participate in inter-university debate competitions and Model UN events.', 'To develop confident communicators and critical thinkers.', 'A university renowned for producing effective leaders and communicators.', 'Weekly debates, Model UN, Public speaking workshops, Inter-university competitions', 'Academic', 4, 4, 'Mehedi Hasan', 'Mitu Akter', 38, 'active'),
('Photography Club', 'photography-club', 'Capture the world through your lens.', 'The Photography Club brings together students passionate about visual storytelling. From portrait to landscape, street to wildlife photography, we cover it all.', 'To nurture visual artists and storytellers.', 'A creative hub producing talented photographers and visual content creators.', 'Photo walks, Editing workshops, Photo exhibitions, Photography competitions', 'Arts & Culture', 2, NULL, 'Rafi Khan', 'Sabbir Alam', 29, 'active'),
('Sports Club', 'sports-club', 'Promoting fitness, teamwork and sportsmanship.', 'The Sports Club organizes and coordinates all inter-department and inter-university sports events. We promote physical fitness, teamwork, and healthy competition.', 'To build a healthy, active, and competitive sporting culture.', 'A university known for athletic excellence and sportsperson spirit.', 'Cricket, Football, Basketball, Badminton, Table Tennis, Annual Sports Day', 'Sports', 3, NULL, 'Tanvir Ahmed', 'Rafi Khan', 78, 'active'),
('Business Club', 'business-club', 'Entrepreneurship, finance, and business innovation.', 'The Business Club is the premier platform for aspiring entrepreneurs and business leaders. We connect students with industry professionals and provide real-world business exposure.', 'To cultivate the next generation of business leaders.', 'A startup-friendly ecosystem that drives economic growth.', 'Business plan competitions, Industry visits, Entrepreneurship bootcamp, Guest lectures, Case study competitions', 'Business', 3, NULL, 'Mitu Akter', 'Mehedi Hasan', 53, 'active');

-- Sample Club Members
INSERT INTO club_members (club_id, user_id, role) VALUES
(1, 2, 'club_admin'), (1, 6, 'member'), (1, 8, 'member'),
(2, 3, 'club_admin'), (2, 7, 'member'), (2, 9, 'member'),
(3, 4, 'club_admin'), (3, 9, 'member'), (3, 6, 'member');

-- Sample Events
INSERT INTO events (club_id, title, slug, description, category, venue, start_date, end_date, registration_deadline, max_participants, current_participants, status, created_by) VALUES
(1, 'Annual Hackathon 2026', 'annual-hackathon-2026', 'A 24-hour non-stop coding competition where teams solve real-world problems using technology. Exciting prizes await the winners!', 'Competition', 'CSE Department Lab', '2026-07-20 09:00:00', '2026-07-21 09:00:00', '2026-07-18 23:59:00', 100, 42, 'approved', 2),
(2, 'Cultural Night 2026', 'cultural-night-2026', 'A grand cultural evening featuring music, dance, drama, and art. Celebrating the diversity and creativity of our university community.', 'Cultural', 'University Auditorium', '2026-07-25 18:00:00', '2026-07-25 22:00:00', '2026-07-23 23:59:00', 300, 185, 'approved', 3),
(3, 'Inter-University Debate Championship', 'inter-university-debate-2026', 'Teams from 10 universities compete in this prestigious debate championship. Topics covering social, political, and environmental issues.', 'Competition', 'Conference Hall A', '2026-08-05 10:00:00', '2026-08-05 17:00:00', '2026-08-02 23:59:00', 60, 24, 'approved', 4),
(1, 'Python Workshop for Beginners', 'python-workshop-beginners', 'A hands-on workshop introducing Python programming to beginners. No prior coding experience required. Learn variables, loops, functions and build your first app!', 'Workshop', 'CSE Lab 201', '2026-07-10 14:00:00', '2026-07-10 18:00:00', '2026-07-09 23:59:00', 40, 38, 'approved', 2),
(5, 'Annual Sports Day 2026', 'annual-sports-day-2026', 'The biggest sporting event of the year! Compete in cricket, football, basketball, and many more sports. Medals and trophies for winners.', 'Sports', 'University Sports Ground', '2026-08-15 08:00:00', '2026-08-15 18:00:00', '2026-08-13 23:59:00', 200, 67, 'approved', 2),
(6, 'Startup Pitch Competition', 'startup-pitch-competition-2026', 'Present your innovative startup ideas to a panel of investors and industry experts. Best ideas win seed funding and mentorship opportunities!', 'Competition', 'Business Faculty Seminar Hall', '2026-07-30 10:00:00', '2026-07-30 16:00:00', '2026-07-28 23:59:00', 50, 33, 'approved', 4);

-- Sample Notifications
INSERT INTO notifications (user_id, type, title, message, is_read) VALUES
(6, 'club_approved', 'Club Request Approved', 'Your request to join Programming Club has been approved!', 0),
(7, 'event_registered', 'Event Registration Confirmed', 'You have successfully registered for Cultural Night 2026.', 0),
(8, 'event_reminder', 'Upcoming Event Reminder', 'Annual Hackathon 2026 starts in 2 days. Get ready!', 0),
(2, 'join_request', 'New Join Request', 'A new student has requested to join Programming Club.', 0);

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('university_name', 'UniHub University'),
('university_short', 'UHU'),
('contact_email', 'clubs@university.edu'),
('contact_phone', '+880-1700-000000'),
('site_tagline', 'Connect. Collaborate. Grow.'),
('max_clubs_per_student', '5'),
('auto_approve_events', '0');
