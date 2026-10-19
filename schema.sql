-- ============================================================
-- THE SOCIALS — Database Schema
-- Community Membership Platform
-- Version: 1.0.0
-- ============================================================

-- Create the database



-- ============================================================
-- TABLE: users
-- Core identity table for all registered members.
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(120)    NOT NULL,
  `email`         VARCHAR(255)    NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: clubs
-- Each club has a unique join_code that members use to enter.
-- theme_color defaults to the brand's Deep Hunter Green.
-- ============================================================
CREATE TABLE IF NOT EXISTS `clubs` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `club_name`     VARCHAR(150)    NOT NULL,
  `join_code`     VARCHAR(20)     NOT NULL,
  `theme_color`   VARCHAR(7)      NOT NULL DEFAULT '#32471C',
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clubs_join_code` (`join_code`),
  INDEX      `idx_clubs_name`     (`club_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: memberships (Junction / Pivot Table)
-- Many-to-Many bridge between users and clubs.
-- role ENUM enforces only valid privilege levels.
-- Composite unique key prevents duplicate memberships.
-- ============================================================
CREATE TABLE IF NOT EXISTS `memberships` (
  `id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`   INT UNSIGNED    NOT NULL,
  `club_id`   INT UNSIGNED    NOT NULL,
  `role`      ENUM('admin', 'moderator', 'member') NOT NULL DEFAULT 'member',
  `joined_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Prevent a user from joining the same club twice
  UNIQUE KEY `uq_membership` (`user_id`, `club_id`),

  -- Foreign key constraints with cascading deletes
  CONSTRAINT `fk_memberships_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_memberships_club`
    FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SEED DATA: A default club for testing
-- ============================================================
INSERT INTO `clubs` (`club_name`, `join_code`, `theme_color`)
VALUES ('The Socials HQ', 'SOCIALS-2026', '#32471C');


-- ============================================================
-- TABLE: messages
-- Handles general club chat and direct messages (DMs)
-- ============================================================
CREATE TABLE IF NOT EXISTS `messages` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `sender_id`    INT UNSIGNED    NOT NULL,
  `club_id`      INT UNSIGNED    DEFAULT NULL,
  `recipient_id` INT UNSIGNED    DEFAULT NULL,
  `message_text` TEXT            NOT NULL,
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: posts
-- Media Feed containing photo and video uploads
-- ============================================================
CREATE TABLE IF NOT EXISTS `posts` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    NOT NULL,
  `club_id`     INT UNSIGNED    NOT NULL,
  `media_path`  VARCHAR(255)    NOT NULL,
  `media_type`  ENUM('photo', 'video') NOT NULL,
  `caption`     TEXT            DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: voice_channel_users
-- Keeps track of active users inside a voice group call
-- ============================================================
CREATE TABLE IF NOT EXISTS `voice_channel_users` (
  `user_id`    INT UNSIGNED    NOT NULL,
  `club_id`    INT UNSIGNED    NOT NULL,
  `is_muted`   TINYINT(1)      NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

