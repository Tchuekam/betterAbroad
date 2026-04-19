-- ============================================================
--  BetterAbroad — Schema additions
--  Run in phpMyAdmin → SQL tab (after existing schema.sql)
--  Or: mysql -u root -p betterabroad < schema_additions.sql
-- ============================================================

USE betterabroad;

-- TOS acceptance flag
ALTER TABLE users
  ADD COLUMN tos_accepted TINYINT(1) NOT NULL DEFAULT 0
  AFTER is_active;

-- ── CREDITS ──────────────────────────────────────────────────
-- One row per user, holds their current balance.
CREATE TABLE IF NOT EXISTS credits (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL UNIQUE,
  balance    INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ── CREDIT TRANSACTIONS ──────────────────────────────────────
-- Immutable ledger: every earn/spend/refund/bonus is a new row.
CREATE TABLE IF NOT EXISTS credit_transactions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT          NOT NULL,
  amount       INT          NOT NULL,            -- positive = earn, negative = spend
  type         ENUM('earn','spend','refund','bonus') NOT NULL,
  reason       VARCHAR(255) DEFAULT NULL,        -- e.g. 'signup_bonus', 'seminar_register'
  reference_id INT          DEFAULT NULL,        -- seminar_id, intro_id, etc.
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user       (user_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ── SEMINARS ─────────────────────────────────────────────────
-- A paid virtual recruitment session created by admin for a university.
CREATE TABLE IF NOT EXISTS seminars (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  university_id     INT          NOT NULL,
  title             VARCHAR(255) NOT NULL,
  description       TEXT         DEFAULT NULL,
  target_majors     VARCHAR(500) DEFAULT NULL,   -- comma-separated
  target_intake     VARCHAR(50)  DEFAULT NULL,   -- e.g. 'Fall 2026'
  max_participants  INT          NOT NULL DEFAULT 100,
  registered_count  INT          NOT NULL DEFAULT 0,
  meet_link         VARCHAR(500) DEFAULT NULL,
  scheduled_at      DATETIME     NOT NULL,
  price_fcfa        INT          NOT NULL,        -- amount university paid
  tier              ENUM('basic','standard','premium') NOT NULL,
  status            ENUM('scheduled','live','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_by        INT          NOT NULL,        -- admin user_id
  created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (university_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by)    REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_university  (university_id),
  INDEX idx_status      (status),
  INDEX idx_scheduled   (scheduled_at)
) ENGINE=InnoDB;

-- ── SEMINAR REGISTRATIONS ────────────────────────────────────
-- One row per student per seminar. UNIQUE prevents double registration.
CREATE TABLE IF NOT EXISTS seminar_registrations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  seminar_id    INT         NOT NULL,
  student_id    INT         NOT NULL,
  registered_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  attended      TINYINT(1)  NOT NULL DEFAULT 0,
  FOREIGN KEY (seminar_id) REFERENCES seminars(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
  UNIQUE KEY unique_reg (seminar_id, student_id),
  INDEX idx_seminar (seminar_id),
  INDEX idx_student (student_id)
) ENGINE=InnoDB;

-- Enrollment certificates (admin-issued)
CREATE TABLE IF NOT EXISTS enrollment_certificates (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT          NOT NULL,
  certificate_id VARCHAR(100) NOT NULL,
  file_path      VARCHAR(255) NOT NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_application (application_id),
  UNIQUE KEY uniq_certificate (certificate_id)
) ENGINE=InnoDB;
