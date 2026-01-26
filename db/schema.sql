-- Generated schema for MySQL (utf8mb4, InnoDB)
-- Run with: mysql -u user -p database_name < db/schema.sql

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `countries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(10) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `country_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `cities_country_idx` (`country_id`),
  CONSTRAINT `cities_country_fk` FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` DATETIME NULL,
  `password` VARCHAR(255) NOT NULL,
  `remember_token` VARCHAR(100) NULL,
  `country_id` INT UNSIGNED NULL,
  `city_id` INT UNSIGNED NULL,
  `phone` VARCHAR(50) NULL,
  `account_type` VARCHAR(50) NULL,
  `profile_image_id` BIGINT UNSIGNED NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_country_idx` (`country_id`),
  KEY `users_city_idx` (`city_id`),
  KEY `users_phone_idx` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `display_name` VARCHAR(255) NULL,
  `permissions` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_role` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `user_role_role_idx` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_name` VARCHAR(255) NULL,
  `path` VARCHAR(1024) NULL,
  `type` VARCHAR(50) NULL,
  `status` VARCHAR(50) NULL DEFAULT 'processing',
  `thumbnail_url` VARCHAR(1024) NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `related_resource` VARCHAR(100) NULL,
  `related_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `media_user_idx` (`user_id`),
  KEY `media_related_idx` (`related_resource`,`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('normal','unique','caishha','auction') NOT NULL DEFAULT 'normal',
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `category_id` INT UNSIGNED NULL,
  `brand_id` INT UNSIGNED NULL,
  `model_id` INT UNSIGNED NULL,
  `city_id` INT UNSIGNED NULL,
  `country_id` INT UNSIGNED NULL,
  `year` SMALLINT UNSIGNED NULL,
  `price_cash` DECIMAL(12,2) NULL,
  `banner_image_id` BIGINT UNSIGNED NULL,
  `banner_color` VARCHAR(30) NULL,
  `is_verified_ad` TINYINT(1) NOT NULL DEFAULT 0,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('draft','pending','published','expired','removed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ads_user_idx` (`user_id`),
  KEY `ads_type_idx` (`type`),
  KEY `ads_status_idx` (`status`),
  KEY `ads_brand_model_idx` (`brand_id`,`model_id`),
  KEY `ads_price_idx` (`price_cash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ad_media` (
  `ad_id` BIGINT UNSIGNED NOT NULL,
  `media_id` BIGINT UNSIGNED NOT NULL,
  `position` INT UNSIGNED NULL,
  `is_banner` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ad_id`,`media_id`),
  KEY `ad_media_media_idx` (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `brands_name_en_idx` (`name_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `models` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `brand_id` BIGINT UNSIGNED NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255) NULL,
  `year_from` SMALLINT UNSIGNED NULL,
  `year_to` SMALLINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `models_brand_idx` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `packages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  `duration_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `features` JSON NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_packages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `package_id` BIGINT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_packages_user_idx` (`user_id`),
  KEY `user_packages_package_idx` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `offers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `comment` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `offers_ad_idx` (`ad_id`),
  KEY `offers_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `caishha_offers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `comment` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `caishha_ad_idx` (`ad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `findit_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requester_id` BIGINT UNSIGNED NOT NULL,
  `brand_id` INT UNSIGNED NULL,
  `model_id` INT UNSIGNED NULL,
  `min_price` DECIMAL(12,2) NULL,
  `max_price` DECIMAL(12,2) NULL,
  `min_year` SMALLINT UNSIGNED NULL,
  `max_year` SMALLINT UNSIGNED NULL,
  `city_id` INT UNSIGNED NULL,
  `country_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `findit_requester_idx` (`requester_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `target` VARCHAR(50) NULL,
  `target_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NULL,
  `data` JSON NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_hidden` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_idx` (`user_id`),
  KEY `notifications_target_idx` (`target`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NULL,
  `body` TEXT NULL,
  `stars` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `seller_id` BIGINT UNSIGNED NULL,
  `ad_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_user_idx` (`user_id`),
  KEY `reviews_seller_idx` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NULL,
  `reason` TEXT NULL,
  `reported_by_user_id` BIGINT UNSIGNED NOT NULL,
  `target_type` VARCHAR(50) NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `reports_reporter_idx` (`reported_by_user_id`),
  KEY `reports_target_idx` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `auctions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` BIGINT UNSIGNED NOT NULL,
  `start_price` DECIMAL(12,2) NULL,
  `last_price` DECIMAL(12,2) NULL,
  `start_time` DATETIME NULL,
  `end_time` DATETIME NULL,
  `winner_user_id` BIGINT UNSIGNED NULL,
  `auto_close` TINYINT(1) NOT NULL DEFAULT 0,
  `is_last_price_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `auctions_ad_idx` (`ad_id`),
  KEY `auctions_end_time_idx` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bids` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `auction_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `bids_auction_idx` (`auction_id`),
  KEY `bids_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `favorites` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `ad_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`user_id`,`ad_id`),
  KEY `favorites_ad_idx` (`ad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Session, cache and jobs tables (Laravel-style)
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_idx` (`user_id`),
  KEY `sessions_last_activity_idx` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`email`),
  KEY `password_reset_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT UNSIGNED NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_idx` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT UNSIGNED NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_idx` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_idx` (`queue`),
  KEY `jobs_reserved_idx` (`reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT UNSIGNED NOT NULL,
  `pending_jobs` INT UNSIGNED NOT NULL,
  `failed_jobs` INT UNSIGNED NOT NULL,
  `failed_job_ids` LONGTEXT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT UNSIGNED NULL,
  `created_at` INT UNSIGNED NOT NULL,
  `finished_at` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `job_batches_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Add foreign keys that reference tables created above
ALTER TABLE `users`
  ADD CONSTRAINT `users_profile_image_fk` FOREIGN KEY (`profile_image_id`) REFERENCES `media`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_country_fk` FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_city_fk` FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `user_role`
  ADD CONSTRAINT `user_role_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_role_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `media`
  ADD CONSTRAINT `media_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `models`
  ADD CONSTRAINT `models_brand_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ads`
  ADD CONSTRAINT `ads_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ads_banner_media_fk` FOREIGN KEY (`banner_image_id`) REFERENCES `media`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `ads_brand_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `ads_model_fk` FOREIGN KEY (`model_id`) REFERENCES `models`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `ads_city_fk` FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `ads_country_fk` FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `ad_media`
  ADD CONSTRAINT `ad_media_ad_fk` FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ad_media_media_fk` FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_packages`
  ADD CONSTRAINT `user_packages_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_packages_package_fk` FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `offers`
  ADD CONSTRAINT `offers_ad_fk` FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `offers_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `caishha_offers`
  ADD CONSTRAINT `caishha_ad_fk` FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `caishha_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `findit_requests`
  ADD CONSTRAINT `findit_requester_fk` FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_seller_fk` FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_ad_fk` FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `reports`
  ADD CONSTRAINT `reports_reporter_fk` FOREIGN KEY (`reported_by_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `auctions`
  ADD CONSTRAINT `auctions_ad_fk` FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `auctions_winner_fk` FOREIGN KEY (`winner_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `bids`
  ADD CONSTRAINT `bids_auction_fk` FOREIGN KEY (`auction_id`) REFERENCES `auctions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bids_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `favorites_ad_fk` FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
