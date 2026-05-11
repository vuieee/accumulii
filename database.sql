CREATE DATABASE IF NOT EXISTS accumulii;
USE accumulii;

DROP TABLE IF EXISTS showcases;
DROP TABLE IF EXISTS profile_comments;
DROP TABLE IF EXISTS repositories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(100) DEFAULT 'Developer',
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

CREATE TABLE showcases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_name VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    file_type VARCHAR(10) DEFAULT 'png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (username, email, password, role) VALUES 
('joshuareed', 'joshuareed@accumulii.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lead Full-Stack Engineer'),
('lancer', 'lancer@accumulii.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Backend Engineer'),
('john', 'john@accumulii.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Database Administrator'),
('joshuadan', 'joshuadan@accumulii.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Frontend Developer');

INSERT INTO repositories (user_id, repo_name, repo_description, language, stars, created_at) VALUES
(1, 'yasb', 'Yet Another Status Bar', 'Python', 412, '2024-01-15 09:12:00'),
(1, 'komorebi', 'A tiling window manager for Windows', 'Rust', 1850, '2024-02-10 14:30:00'),
(1, 'floorp-projects', 'Floorp Web Browser', 'JavaScript', 1205, '2024-03-05 11:00:00'),
(1, 'OpenArc', 'Open source Arc browser alternative', 'TypeScript', 890, '2024-04-20 10:05:00'),
(1, 'OptiScaler', 'DLSS/FSR upscaling wrapper', 'C++', 560, '2024-05-12 16:20:00'),
(1, 'd3d8to9', 'D3D8 to D3D9 converter', 'C++', 1420, '2024-06-18 08:45:00'),
(1, 'helium', 'Helium floating browser', 'Swift', 734, '2024-07-22 13:15:00'),
(1, 'reshade-shaders', 'A collection of post-processing shaders', 'HLSL', 3200, '2024-08-30 12:00:00');

INSERT INTO showcases (user_id, image_name, title, category, file_type) VALUES
(1, 'net-themes', 'Terminal Network Themes', 'Terminal', 'png'),
(1, 'bspwm', 'BSPWM Under 2GB RAM', 'Ricing', 'png'),
(1, 'hypr-switch', 'Hyprland Theme Switcher', 'Ricing', 'png'),
(1, 'hypr-gruv', 'Hyprland Yay Gruvy', 'Ricing', 'png'),
(1, 'gentoo-min', 'Cozy Gentoo Minimalist', 'Ricing', 'png'),
(1, 'gentoo-cat', 'Gentoo Cozy Setup', 'Ricing', 'png'),
(1, 'niri-glass', 'Niri Glassy Rice', 'Ricing', 'png'),
(1, 'yabai', 'Yabai Simple Rice', 'Ricing', 'png');