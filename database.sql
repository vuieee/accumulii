CREATE DATABASE IF NOT EXISTS accumulii;
USE accumulii;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Developer',
    bio TEXT,
    university VARCHAR(100) DEFAULT 'University of San Carlos - Talamban Campus',
    course VARCHAR(100) DEFAULT 'Bachelor of Science in Information Technology',
    year INT DEFAULT 1,
    avatar VARCHAR(255) DEFAULT 'default.png',
    theme VARCHAR(50) DEFAULT 'dark',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE repositories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    repo_name VARCHAR(100) NOT NULL,
    repo_description TEXT,
    language VARCHAR(50),
    stars INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE profile_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Passwords are set to 'password'
INSERT INTO users (username, email, password, role) VALUES 
('joshuareed', 'joshuareed@accumulii.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lead Dev, Frontend'),
('lancer', 'lancer@accumulii.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Backend'),
('john', 'john@accumulii.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Backend'),
('joshuadan', 'joshuadan@accumulii.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Assistant UI/UX');

-- Repository seed data
INSERT INTO repositories (user_id, repo_name, repo_description, language, stars, created_at) VALUES
(1, 'accumulii',         'Terminal-style developer profile and repo showcase platform', 'PHP',        14, '2024-11-03 09:12:00'),
(1, 'portforge',         'Static portfolio generator with CLI config and live preview',  'JavaScript', 8,  '2024-08-17 14:30:00'),
(1, 'snaproute',         'Lightweight PHP router with zero dependencies',                'PHP',        5,  '2024-06-01 11:00:00'),
(2, 'querycraft',        'SQL query builder with fluent interface for MySQL/PostgreSQL',  'PHP',        11, '2024-09-22 10:05:00'),
(2, 'logpulse',          'Real-time log monitoring dashboard with WebSocket support',    'JavaScript', 7,  '2024-07-14 16:20:00'),
(3, 'dbsync',            'Database migration tool with rollback and diff support',       'PHP',        9,  '2024-10-10 08:45:00'),
(3, 'cachewave',         'Redis-based caching layer with tag-based invalidation',        'PHP',        4,  '2024-05-28 13:15:00'),
(4, 'gridflow',          'CSS grid layout builder with drag-and-drop interface',         'JavaScript', 6,  '2024-12-01 12:00:00'),
(4, 'themepilot',        'Theme switcher utility for multi-theme web applications',      'CSS',        3,  '2024-04-09 09:30:00');