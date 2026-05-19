-- FusionVerse Database Full Structure — PORTABLE EDITION
-- Use this file to import the database into your server.

CREATE DATABASE IF NOT EXISTS `fusionverse_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fusionverse_db`;

-- 1. Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `course` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','coordinator', 'admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table structure for table `events`
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `eligibility_stream` varchar(50) DEFAULT 'ALL',
  `description` text,
  `rules` text,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `coordinator_name` varchar(255) DEFAULT NULL,
  `coordinator_phone` varchar(20) DEFAULT NULL,
  `coordinator_id` varchar(20) DEFAULT NULL,
  `max_participants` int(11) DEFAULT '0',
  `current_participants` int(11) DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `is_team_event` tinyint(1) DEFAULT '0',
  `min_team_size` int(11) DEFAULT '2',
  `max_team_size` int(11) DEFAULT '4',
  `status` enum('active','full','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table structure for table `teams`
CREATE TABLE IF NOT EXISTS `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `leader_user_id` varchar(20) NOT NULL,
  `invite_code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `event_id` (`event_id`),
  KEY `leader_user_id` (`leader_user_id`),
  CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teams_ibfk_2` FOREIGN KEY (`leader_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table structure for table `team_members`
CREATE TABLE IF NOT EXISTS `team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_id_user_id` (`team_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table structure for table `registrations`
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `event_id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `status` enum('registered','winner','runner','participated') DEFAULT 'registered',
  `attendance` enum('absent','present') DEFAULT 'absent',
  `score` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_event_id` (`user_id`,`event_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table structure for table `announcements`
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` enum('alert','update','result') DEFAULT 'update',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Seed Data: Default Admin User (Password: admin123)
INSERT IGNORE INTO `users` (`user_id`, `name`, `email`, `phone`, `course`, `password`, `role`) VALUES
('ADMIN001', 'Super Admin', 'admin@fusionverse.com', '1234567890', 'Management', '$2y$10$eEHOyBwVqB2L4H7sWXX4yOWmDIfT3uFjE6E3qf4P2H4H1R3gT2fG6', 'admin');

-- 8. Seed Data: Default Events
INSERT IGNORE INTO `events` (`id`, `name`, `category`, `eligibility_stream`, `description`, `rules`, `date`, `time`, `venue`, `max_participants`, `is_team_event`, `min_team_size`, `max_team_size`) VALUES
(1, 'Code Rush', 'IT Track', 'IT', 'Fast-paced coding competition where logic meets speed.', 'Individual. No internet allowed.', '2026-04-10', '10:00:00', 'Lab 1', 50, 0, 1, 1),
(2, 'Biz Quiz', 'Commerce Track', 'Commerce', 'Test your business knowledge and strategy across rounds.', 'Team of 2. Buzzer format.', '2026-04-10', '11:30:00', 'Main Auditorium', 100, 1, 2, 2),
(3, 'Fusion Hack', 'IT Track', 'ALL', 'Build the future in this intensive 12-hour build-a-thon.', 'Team of 3-4. Innovative solutions only.', '2026-04-11', '09:00:00', 'Arena North', 30, 1, 3, 4);

