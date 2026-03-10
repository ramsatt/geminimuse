-- ============================================================
-- GeminiMuse — MySQL Database Schema
-- Run this once on your shared hosting via phpMyAdmin or CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS geminimuse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE geminimuse;

-- ── Favorites ────────────────────────────────────────────────
-- Device-ID based (no login required)
CREATE TABLE IF NOT EXISTS favorites (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  device_id   VARCHAR(36)  NOT NULL,
  prompt_id   INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY  uq_fav       (device_id, prompt_id),
  INDEX       idx_device   (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Copy Events ───────────────────────────────────────────────
-- One row per copy action — used to compute counts
CREATE TABLE IF NOT EXISTS copy_events (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prompt_id   INT UNSIGNED NOT NULL,
  device_id   VARCHAR(36)  NOT NULL,
  language    VARCHAR(5)   NOT NULL DEFAULT 'en',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX       idx_prompt   (prompt_id),
  INDEX       idx_device   (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Prompt Stats (denormalized copy count cache) ──────────────
-- Updated on every copy event via INSERT ... ON DUPLICATE KEY
CREATE TABLE IF NOT EXISTS prompt_stats (
  prompt_id   INT UNSIGNED NOT NULL PRIMARY KEY,
  copy_count  INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
