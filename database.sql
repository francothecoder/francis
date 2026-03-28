SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS community_topic_props;
DROP TABLE IF EXISTS community_topic_views;
DROP TABLE IF EXISTS community_comments;
DROP TABLE IF EXISTS community_topics;
DROP TABLE IF EXISTS support_replies;
DROP TABLE IF EXISTS support_requests;
DROP TABLE IF EXISTS downloads;
DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS payment_records;
DROP TABLE IF EXISTS subscription_requests;
DROP TABLE IF EXISTS user_memberships;
DROP TABLE IF EXISTS membership_plans;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student') NOT NULL DEFAULT 'student',
    university VARCHAR(190) NULL,
    bio TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS membership_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL DEFAULT 30,
    description TEXT NULL,
    features TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_memberships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    approved_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_memberships_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_memberships_plan FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_memberships_admin FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    reference_code VARCHAR(120) NULL,
    notes TEXT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_requests_plan FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_requests_admin FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NULL,
    request_id INT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(100) NOT NULL,
    reference_code VARCHAR(120) NULL,
    notes TEXT NULL,
    recorded_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_plan FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_request FOREIGN KEY (request_id) REFERENCES subscription_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    access_level ENUM('free','basic','pro','elite') NOT NULL DEFAULT 'free',
    file_path VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_projects_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    type VARCHAR(100) NOT NULL,
    content LONGTEXT NULL,
    file_path VARCHAR(255) NULL,
    access_level ENUM('free','basic','pro','elite') NOT NULL DEFAULT 'free',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_resources_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS downloads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_type ENUM('project','resource') NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_downloads_item (item_type, item_id),
    CONSTRAINT fk_downloads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_support_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    support_request_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_support_replies_ticket FOREIGN KEY (support_request_id) REFERENCES support_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_support_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_topics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    category VARCHAR(100) NOT NULL,
    content LONGTEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(190) NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_community_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    comment TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(190) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_community_comments_topic FOREIGN KEY (topic_id) REFERENCES community_topics(id) ON DELETE CASCADE,
    CONSTRAINT fk_community_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_community_comments_parent FOREIGN KEY (parent_id) REFERENCES community_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_topic_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    session_key VARCHAR(120) NOT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_topic_session (topic_id, session_key),
    INDEX idx_topic_views_topic (topic_id),
    CONSTRAINT fk_topic_views_topic FOREIGN KEY (topic_id) REFERENCES community_topics(id) ON DELETE CASCADE,
    CONSTRAINT fk_topic_views_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_topic_props (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_topic_user_prop (topic_id, user_id),
    INDEX idx_topic_props_topic (topic_id),
    CONSTRAINT fk_topic_props_topic FOREIGN KEY (topic_id) REFERENCES community_topics(id) ON DELETE CASCADE,
    CONSTRAINT fk_topic_props_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password, role, university, bio) VALUES
('Francis Kwesa Admin', 'admin@franciskwesa.com', '$2y$12$xutMBIZDDjkpU1Wg93mwOebAmLS1GzztFBoJqe6v4rFGg4pK/MacW', 'admin', 'Admin', 'System administrator account'),
('Sample Student', 'student@example.com', '$2y$12$WoHzoAvml0EcnQ1JnqFr8OAVqHVrxSadZp8B.JDF.yvMRc4l5Xx2u', 'student', 'University of Zambia', 'Sample seeded student account');

INSERT INTO membership_plans (name, price, duration_days, description, features) VALUES
('Basic', 50.00, 30, 'For students getting started.', 'Selected projects, basic resources, community access'),
('Pro', 100.00, 30, 'Best for active university students.', 'Full project library, premium resources, faster support, community access'),
('Elite', 200.00, 30, 'For students who need closer support.', 'Everything in Pro, one-on-one guidance, premium support');

INSERT INTO user_memberships (user_id, plan_id, starts_at, expires_at, status, approved_by) VALUES
(2, 2, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 1);

INSERT INTO projects (title, category, description, access_level, created_by) VALUES
('Student Management System', 'PHP', 'Complete school management style project with login and reports.', 'pro', 1),
('Courier Tracking System', 'PHP', 'Track parcels, delivery updates, and agent activity.', 'pro', 1),
('Library Management System', 'Web', 'Manage books, students, and lending workflows.', 'basic', 1),
('Simple Portfolio Website', 'Frontend', 'A beginner-friendly portfolio template for students.', 'free', 1);

INSERT INTO resources (title, type, content, access_level, created_by) VALUES
('How to Choose a Final Year Project', 'Guide', 'Start with a real problem, define features early, and keep your scope achievable.', 'free', 1),
('PHP Project Documentation Outline', 'Template', 'Chapter 1: Introduction\nChapter 2: Literature Review\nChapter 3: Methodology\nChapter 4: Implementation\nChapter 5: Testing and Conclusion', 'basic', 1),
('How to Prepare for Project Demo Day', 'Guide', 'Know your use case, practice the walkthrough, and prepare for common questions.', 'pro', 1);

INSERT INTO support_requests (user_id, subject, message, status) VALUES
(2, 'Need help choosing a final year project', 'I need ideas around a PHP web system that is realistic for university submission.', 'open');

INSERT INTO support_replies (support_request_id, user_id, message) VALUES
(1, 1, 'Start by choosing a problem you understand well. A student management or small inventory platform is usually realistic and strong.');

INSERT INTO community_topics (user_id, title, category, content) VALUES
(2, 'Best final year project ideas for PHP students?', 'Projects', 'I want a project idea that is practical, not too huge, and can impress my lecturers.'),
(1, 'Welcome to the Francis Kwesa student community', 'Announcements', 'Use this space to ask questions, share tips, and support each other.');

INSERT INTO community_comments (topic_id, user_id, parent_id, comment) VALUES
(1, 1, NULL, 'Start with something that solves a real issue around campus, school administration, or local business workflows.'),
(1, 2, 1, 'That helps. I am considering a hostel management system.'),
(2, 2, NULL, 'Glad to be here.');

INSERT INTO community_topic_views (topic_id, user_id, session_key) VALUES
(1, 2, 'seed-student-1'),
(1, 1, 'seed-admin-1'),
(2, 2, 'seed-student-2');

INSERT INTO community_topic_props (topic_id, user_id) VALUES
(1, 1),
(1, 2),
(2, 2);

INSERT INTO settings (key_name, value) VALUES
('site_phone', '+260963884318'),
('site_email', 'hello@franciskwesa.com'),
('site_whatsapp', '260963884318');
