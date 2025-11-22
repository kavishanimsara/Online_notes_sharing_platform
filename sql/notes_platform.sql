CREATE TABLE `activity_log` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) DEFAULT NULL,
 `action` varchar(100) NOT NULL,
 `details` text DEFAULT NULL,
 `ip_address` varchar(45) DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `idx_user_id` (`user_id`),
 KEY `idx_action` (`action`),
 KEY `idx_created_at` (`created_at`),
 CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci


CREATE TABLE `admins` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `admin_level` enum('super_admin','admin','moderator') DEFAULT 'admin',
 `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Admin permissions' CHECK (json_valid(`permissions`)),
 `created_by` int(11) DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `last_login` timestamp NULL DEFAULT NULL,
 `is_active` tinyint(1) DEFAULT 1,
 PRIMARY KEY (`id`),
 UNIQUE KEY `user_id` (`user_id`),
 KEY `created_by` (`created_by`),
 KEY `idx_level` (`admin_level`),
 KEY `idx_active` (`is_active`),
 CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
 CONSTRAINT `admins_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `admin_activity_log` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `admin_id` int(11) NOT NULL,
 `action` varchar(100) NOT NULL,
 `target_type` varchar(50) DEFAULT NULL,
 `target_id` int(11) DEFAULT NULL,
 `details` text DEFAULT NULL,
 `ip_address` varchar(45) DEFAULT NULL,
 `user_agent` text DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `idx_admin` (`admin_id`),
 KEY `idx_action` (`action`),
 KEY `idx_target` (`target_type`,`target_id`),
 KEY `idx_created` (`created_at`),
 CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `admin_verification` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `email` varchar(100) NOT NULL,
 `verification_code` varchar(6) NOT NULL,
 `full_name` varchar(100) DEFAULT NULL,
 `phone_number` varchar(20) DEFAULT NULL,
 `reason` text DEFAULT NULL,
 `status` enum('pending','approved','rejected') DEFAULT 'pending',
 `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `verified_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY `idx_email` (`email`),
 KEY `idx_code` (`verification_code`),
 KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci



CREATE TABLE `categories` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(100) NOT NULL,
 `slug` varchar(100) NOT NULL,
 `description` text DEFAULT NULL,
 `icon` varchar(50) DEFAULT 'bi-folder',
 `parent_id` int(11) DEFAULT NULL,
 `display_order` int(11) DEFAULT 0,
 `is_active` tinyint(1) DEFAULT 1,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved' COMMENT 'Category approval status',
 `created_by` int(11) DEFAULT NULL COMMENT 'User who created category',
 `approved_by` int(11) DEFAULT NULL COMMENT 'Admin who approved/rejected',
 `approved_at` timestamp NULL DEFAULT NULL COMMENT 'When category was approved/rejected',
 `rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection',
 PRIMARY KEY (`id`),
 UNIQUE KEY `slug` (`slug`),
 KEY `idx_slug` (`slug`),
 KEY `idx_parent` (`parent_id`),
 KEY `idx_active` (`is_active`),
 CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	
CREATE TABLE `comments` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `note_id` int(11) NOT NULL,
 `user_id` int(11) NOT NULL,
 `parent_id` int(11) DEFAULT NULL,
 `content` text NOT NULL,
 `is_reported` tinyint(1) DEFAULT 0,
 `is_approved` tinyint(1) DEFAULT 1,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `idx_note_id` (`note_id`),
 KEY `idx_user_id` (`user_id`),
 KEY `idx_parent_id` (`parent_id`),
 CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
 CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
 CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `downloads` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `note_id` int(11) NOT NULL,
 `user_id` int(11) DEFAULT NULL,
 `ip_address` varchar(45) DEFAULT NULL,
 `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `idx_note_id` (`note_id`),
 KEY `idx_user_id` (`user_id`),
 KEY `idx_downloaded_at` (`downloaded_at`),
 CONSTRAINT `downloads_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
 CONSTRAINT `downloads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `email_templates` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `template_name` varchar(100) NOT NULL,
 `subject` varchar(255) NOT NULL,
 `body` text NOT NULL,
 `variables` text DEFAULT NULL COMMENT 'JSON array of available variables',
 `is_active` tinyint(1) DEFAULT 1,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `template_name` (`template_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	
CREATE TABLE `favorites` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `note_id` int(11) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `unique_favorite` (`user_id`,`note_id`),
 KEY `idx_user_id` (`user_id`),
 KEY `idx_note_id` (`note_id`),
 CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
 CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	
CREATE TABLE `notes` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `title` varchar(255) NOT NULL,
 `description` text DEFAULT NULL,
 `tags` varchar(255) DEFAULT NULL,
 `file_name` varchar(255) NOT NULL,
 `file_path` varchar(255) NOT NULL,
 `file_size` int(11) NOT NULL,
 `downloads` int(11) DEFAULT 0,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `views` int(11) DEFAULT 0,
 `likes_count` int(11) DEFAULT 0,
 `category_id` int(11) DEFAULT NULL,
 `subcategory_id` int(11) DEFAULT NULL,
 `file_type` varchar(50) DEFAULT NULL,
 `is_approved` tinyint(1) DEFAULT 1,
 `approved_by` int(11) DEFAULT NULL,
 `approved_at` timestamp NULL DEFAULT NULL,
 `rejection_reason` text DEFAULT NULL,
 `is_featured` tinyint(1) DEFAULT 0,
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 `status` enum('pending','approved','rejected') DEFAULT 'pending',
 PRIMARY KEY (`id`),
 KEY `user_id` (`user_id`),
 KEY `approved_by` (`approved_by`),
 KEY `idx_category` (`category_id`),
 KEY `idx_approved` (`is_approved`),
 KEY `idx_featured` (`is_featured`),
 KEY `subcategory_id` (`subcategory_id`),
 CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
 CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
 CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`subcategory_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
 CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
 CONSTRAINT `notes_ibfk_5` FOREIGN KEY (`subcategory_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `note_likes` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `note_id` int(11) NOT NULL,
 `user_id` int(11) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `unique_like` (`note_id`,`user_id`),
 KEY `idx_note_id` (`note_id`),
 KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `note_tags` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `note_id` int(11) NOT NULL,
 `tag_id` int(11) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `unique_note_tag` (`note_id`,`tag_id`),
 KEY `idx_note_id` (`note_id`),
 KEY `idx_tag_id` (`tag_id`),
 CONSTRAINT `note_tags_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
 CONSTRAINT `note_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `notifications` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `message` varchar(255) NOT NULL,
 `is_read` tinyint(1) DEFAULT 0,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `platform_settings` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `setting_key` varchar(100) NOT NULL,
 `setting_value` text DEFAULT NULL,
 `setting_type` varchar(50) DEFAULT 'text',
 `description` text DEFAULT NULL,
 `updated_by` int(11) DEFAULT NULL,
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `setting_key` (`setting_key`),
 KEY `updated_by` (`updated_by`),
 CONSTRAINT `platform_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `reports` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `reporter_id` int(11) NOT NULL,
 `reported_type` enum('note','comment','user') NOT NULL,
 `reported_id` int(11) NOT NULL,
 `reason` text NOT NULL,
 `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
 `admin_notes` text DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `resolved_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY `reporter_id` (`reporter_id`),
 KEY `idx_status` (`status`),
 KEY `idx_reported_type` (`reported_type`),
 CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `settings` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `setting_key` varchar(100) NOT NULL,
 `setting_value` text DEFAULT NULL,
 `setting_type` varchar(50) DEFAULT 'string',
 `description` text DEFAULT NULL,
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `setting_key` (`setting_key`),
 KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `site_statistics` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `stat_date` date NOT NULL,
 `total_users` int(11) DEFAULT 0,
 `new_users` int(11) DEFAULT 0,
 `total_notes` int(11) DEFAULT 0,
 `new_notes` int(11) DEFAULT 0,
 `total_downloads` int(11) DEFAULT 0,
 `total_views` int(11) DEFAULT 0,
 `total_comments` int(11) DEFAULT 0,
 `total_favorites` int(11) DEFAULT 0,
 `active_users` int(11) DEFAULT 0,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `stat_date` (`stat_date`),
 KEY `idx_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `tags` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(50) NOT NULL,
 `slug` varchar(50) NOT NULL,
 `usage_count` int(11) DEFAULT 0,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `name` (`name`),
 UNIQUE KEY `slug` (`slug`),
 KEY `idx_slug` (`slug`),
 KEY `idx_usage_count` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `users` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `username` varchar(50) NOT NULL,
 `email` varchar(100) NOT NULL,
 `password` varchar(255) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `last_login` timestamp NULL DEFAULT NULL,
 `login_attempts` int(11) DEFAULT 0,
 `locked_until` timestamp NULL DEFAULT NULL,
 `last_ip` varchar(45) DEFAULT NULL,
 `total_uploads` int(11) DEFAULT 0,
 `total_downloads` int(11) DEFAULT 0,
 `full_name` varchar(100) DEFAULT NULL,
 `age` int(11) DEFAULT NULL,
 `date_of_birth` date DEFAULT NULL,
 `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
 `phone_number` varchar(20) DEFAULT NULL,
 `institution_type` enum('school','college','university','other') DEFAULT NULL,
 `institution_name` varchar(200) DEFAULT NULL,
 `grade_level` varchar(50) DEFAULT NULL,
 `bio` text DEFAULT NULL,
 `profile_picture` varchar(255) DEFAULT 'default-avatar.png',
 `country` varchar(100) DEFAULT NULL,
 `city` varchar(100) DEFAULT NULL,
 `address` text DEFAULT NULL,
 `role` enum('user','admin') DEFAULT 'user',
 `is_active` tinyint(1) DEFAULT 1,
 `email_verified` tinyint(1) DEFAULT 0,
 `verification_code` varchar(6) DEFAULT NULL,
 `verification_expires` timestamp NULL DEFAULT NULL,
 `privacy_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'User privacy preferences' CHECK (json_valid(`privacy_settings`)),
 `last_activity` timestamp NULL DEFAULT NULL,
 `banned_at` timestamp NULL DEFAULT NULL COMMENT 'When user was banned',
 `banned_by` int(11) DEFAULT NULL COMMENT 'Admin ID who banned the user',
 `ban_reason` text DEFAULT NULL COMMENT 'Reason for banning',
 `phone` varchar(20) DEFAULT NULL COMMENT 'User phone number',
 PRIMARY KEY (`id`),
 UNIQUE KEY `username` (`username`),
 UNIQUE KEY `email` (`email`),
 KEY `idx_role` (`role`),
 KEY `idx_institution` (`institution_name`),
 KEY `idx_email_verified` (`email_verified`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `user_privacy` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `show_profile` tinyint(1) DEFAULT 1,
 `show_email` tinyint(1) DEFAULT 0,
 `show_phone` tinyint(1) DEFAULT 0,
 `show_institution` tinyint(1) DEFAULT 1,
 `show_location` tinyint(1) DEFAULT 0,
 `show_age` tinyint(1) DEFAULT 0,
 `show_activity` tinyint(1) DEFAULT 1,
 `allow_messages` tinyint(1) DEFAULT 1,
 `show_uploads` tinyint(1) DEFAULT 1,
 `show_downloads` tinyint(1) DEFAULT 0,
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `user_id` (`user_id`),
 CONSTRAINT `user_privacy_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `user_sessions` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `session_id` varchar(255) NOT NULL,
 `ip_address` varchar(45) DEFAULT NULL,
 `user_agent` text DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `unique_session` (`session_id`),
 KEY `idx_user_id` (`user_id`),
 KEY `idx_session_id` (`session_id`),
 CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
