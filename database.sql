SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS lenco_webhook_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS tutor_ratings;
DROP TABLE IF EXISTS session_messages;
DROP TABLE IF EXISTS payout_requests;
DROP TABLE IF EXISTS tutor_wallet_transactions;
DROP TABLE IF EXISTS payment_transactions;
DROP TABLE IF EXISTS study_sessions;
DROP TABLE IF EXISTS help_offers;
DROP TABLE IF EXISTS help_requests;
DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS user_subscriptions;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS xp_transactions;
DROP TABLE IF EXISTS user_rewards;
DROP TABLE IF EXISTS tutor_profiles;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student','tutor') NOT NULL DEFAULT 'student',
    university VARCHAR(190) NULL,
    bio TEXT NULL,
    avatar_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutor_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    headline VARCHAR(190) NOT NULL,
    bio TEXT NOT NULL,
    subjects VARCHAR(255) NOT NULL,
    starting_price DECIMAL(10,2) NOT NULL DEFAULT 25.00,
    min_offer_price DECIMAL(10,2) NOT NULL DEFAULT 15.00,
    rating_average DECIMAL(3,2) NOT NULL DEFAULT 0,
    total_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    total_sessions INT UNSIGNED NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tutor_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_rewards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    current_xp INT UNSIGNED NOT NULL DEFAULT 0,
    current_level INT UNSIGNED NOT NULL DEFAULT 1,
    reward_credits INT UNSIGNED NOT NULL DEFAULT 0,
    streak_days INT UNSIGNED NOT NULL DEFAULT 0,
    last_activity_date DATE NULL,
    CONSTRAINT fk_user_rewards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE xp_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    reason_key VARCHAR(100) NOT NULL,
    points INT NOT NULL,
    reference_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_xp_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscription_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    monthly_price DECIMAL(10,2) NOT NULL,
    help_discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    monthly_help_credits INT UNSIGNED NOT NULL DEFAULT 0,
    feature_summary VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    payment_transaction_id INT UNSIGNED NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE resources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    resource_type VARCHAR(100) NOT NULL,
    excerpt VARCHAR(255) NULL,
    content LONGTEXT NOT NULL,
    access_level ENUM('free','starter','plus','pro') NOT NULL DEFAULT 'free',
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(190) NULL,
    external_url VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resources_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE help_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    selected_tutor_id INT UNSIGNED NULL,
    accepted_offer_id INT UNSIGNED NULL,
    subject VARCHAR(100) NOT NULL,
    title VARCHAR(190) NOT NULL,
    details TEXT NOT NULL,
    urgency ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
    suggested_budget DECIMAL(10,2) NOT NULL DEFAULT 0,
    reward_credits_to_use INT UNSIGNED NOT NULL DEFAULT 0,
    attachment_path VARCHAR(255) NULL,
    status ENUM('open','quoted','accepted','awaiting_payment','payment_pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_help_requests_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_help_requests_tutor FOREIGN KEY (selected_tutor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE help_offers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    tutor_id INT UNSIGNED NOT NULL,
    offered_amount DECIMAL(10,2) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('submitted','accepted','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_help_offers_request FOREIGN KEY (request_id) REFERENCES help_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_help_offers_tutor FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE study_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL UNIQUE,
    student_id INT UNSIGNED NOT NULL,
    tutor_id INT UNSIGNED NOT NULL,
    agreed_amount DECIMAL(10,2) NOT NULL,
    final_amount DECIMAL(10,2) NOT NULL,
    platform_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    tutor_earnings DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('in_progress','completed','cancelled') NOT NULL DEFAULT 'in_progress',
    started_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    CONSTRAINT fk_study_sessions_request FOREIGN KEY (request_id) REFERENCES help_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_study_sessions_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_study_sessions_tutor FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE session_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    message TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(190) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_session_messages_session FOREIGN KEY (session_id) REFERENCES study_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_session_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NULL,
    session_id INT UNSIGNED NULL,
    plan_id INT UNSIGNED NULL,
    student_id INT UNSIGNED NOT NULL,
    tutor_id INT UNSIGNED NULL,
    payment_type ENUM('help_request','subscription') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    base_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    reward_credit_value DECIMAL(10,2) NOT NULL DEFAULT 0,
    platform_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    tutor_earnings DECIMAL(10,2) NOT NULL DEFAULT 0,
    provider VARCHAR(30) NULL,
    phone_number VARCHAR(25) NULL,
    gateway VARCHAR(30) NOT NULL DEFAULT 'lenco',
    gateway_reference VARCHAR(100) NOT NULL UNIQUE,
    lenco_reference VARCHAR(100) NULL,
    gateway_status VARCHAR(40) NULL,
    gateway_payload LONGTEXT NULL,
    status ENUM('pending','held','released','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    notes VARCHAR(255) NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_transactions_request FOREIGN KEY (request_id) REFERENCES help_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_payment_transactions_session FOREIGN KEY (session_id) REFERENCES study_sessions(id) ON DELETE SET NULL,
    CONSTRAINT fk_payment_transactions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL,
    CONSTRAINT fk_payment_transactions_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_transactions_tutor FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutor_wallet_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT UNSIGNED NOT NULL,
    session_id INT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_type ENUM('credit','debit') NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tutor_wallet_transactions_tutor FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tutor_wallet_transactions_session FOREIGN KEY (session_id) REFERENCES study_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payout_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('requested','paid','rejected') NOT NULL DEFAULT 'requested',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    CONSTRAINT fk_payout_requests_tutor FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutor_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL UNIQUE,
    tutor_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tutor_ratings_session FOREIGN KEY (session_id) REFERENCES study_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_tutor_ratings_tutor FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tutor_ratings_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    target_path VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lenco_webhook_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NULL,
    gateway_reference VARCHAR(100) NULL,
    payload LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('platform_commission_percent', '20'),
('platform_name', 'Academic Support Hub'),
('lenco_mode', 'sandbox'),
('lenco_public_key', ''),
('lenco_secret_key', ''),
('lenco_callback_url', ''),
('smtp_enabled', '0'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_encryption', 'tls'),
('smtp_from_email', ''),
('smtp_from_name', 'Academic Support Hub'),
('smtp_reply_to_email', ''),
('smtp_reply_to_name', 'Academic Support Hub'),
('email_notifications_enabled', '1');

INSERT INTO users (name, email, password, role, university, bio) VALUES
('Admin User', 'admin@academicsupporthub.com', '$2y$12$gz2cseFerWijZXl1IhiiQO3dgPyPuHhg2r9xBb/tVgIY/8LAAeZP6', 'admin', 'Platform Admin', 'Main administrator'),
('Sample Student', 'student@example.com', '$2y$12$b33aZ./ZbuhZzfkayvslEe8ei//wquKBucaO0Xnkxzh18YLHS6K0q', 'student', 'University of Zambia', 'Active student account'),
('Tutor Grace', 'tutor@example.com', '$2y$12$2hUM5zHrgOh2.aI7TO.pveStq5tCJtakwjL04nV/lMHtjJVz3kg8C', 'tutor', 'Copperbelt University', 'Experienced tutor in ICT, databases, and systems analysis');

INSERT INTO tutor_profiles (user_id, headline, bio, subjects, starting_price, min_offer_price, rating_average, total_reviews, total_sessions, is_verified) VALUES
(3, 'ICT, Databases and Systems Analysis Tutor', 'Helps students understand project design, system analysis, ERD work, and practical ICT tasks.', 'ICT, Databases, Systems Analysis, Documentation', 35.00, 25.00, 4.80, 12, 18, 1);

INSERT INTO user_rewards (user_id, current_xp, current_level, reward_credits, streak_days, last_activity_date) VALUES
(1, 0, 1, 0, 1, CURDATE()),
(2, 45, 2, 3, 4, CURDATE()),
(3, 65, 2, 2, 3, CURDATE());

INSERT INTO subscription_plans (name, monthly_price, help_discount_percent, monthly_help_credits, feature_summary, description) VALUES
('Starter Plan', 49.00, 5.00, 1, 'Great for getting started with guided support.', 'Access starter resources, 1 monthly help credit, and discounted academic help sessions.'),
('Study Plus', 99.00, 10.00, 3, 'Ideal for active students who need more support.', 'More credits, deeper resource access, and stronger help discounts.'),
('Academic Pro', 149.00, 15.00, 5, 'Premium plan for students who want priority support.', 'Best value for frequent guided sessions, premium resources, and stronger learning rewards.');

INSERT INTO user_subscriptions (user_id, plan_id, starts_at, ends_at, status) VALUES
(2, 2, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active');

INSERT INTO resources (title, resource_type, excerpt, content, access_level, attachment_name, external_url, created_by) VALUES
('How to structure a strong final-year system proposal', 'Guide', 'Break down chapters, scope, and deliverables for a stronger academic proposal.', 'Start with the problem statement, define measurable objectives, map users clearly, and keep your scope realistic for your course calendar.', 'free', NULL, NULL, 1),
('Documentation outline for PHP academic systems', 'Template', 'A quick chapter-by-chapter outline for student documentation work.', 'Chapter 1: Introduction
Chapter 2: Literature Review
Chapter 3: Methodology
Chapter 4: System Design
Chapter 5: Testing, Findings and Conclusion', 'starter', NULL, NULL, 1),
('Database normalization revision note', 'Revision Note', 'A compact note for 1NF, 2NF and 3NF revision.', 'A quick breakdown of 1NF, 2NF, and 3NF with simple examples for academic systems.', 'plus', NULL, NULL, 1);

INSERT INTO help_requests (student_id, selected_tutor_id, subject, title, details, urgency, suggested_budget, reward_credits_to_use, status) VALUES
(2, NULL, 'ICT', 'Need help breaking down a school management ERD', 'I need help understanding users, entities, and relationships for a simple school system ERD before submission.', 'normal', 30.00, 1, 'quoted');

INSERT INTO help_offers (request_id, tutor_id, offered_amount, message, status) VALUES
(1, 3, 35.00, 'I can guide you through the entities, attributes, and relationships step by step and help you prepare a cleaner ERD.', 'submitted');

INSERT INTO notifications (user_id, title, message, target_path) VALUES
(2, 'Welcome back', 'Your Study Plus plan is active and your reward credits are ready to use.', 'student/dashboard.php'),
(3, 'Tutor profile verified', 'Your profile is visible to students and you can now receive study requests.', 'tutor/dashboard.php');
