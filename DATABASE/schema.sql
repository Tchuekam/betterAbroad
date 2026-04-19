-- ============================================================
--  BetterAbroad — Complete MySQL Schema
--  Run this in phpMyAdmin → SQL tab, or via mysql CLI:
--  mysql -u root -p betterabroad < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS betterabroad
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE betterabroad;

-- ── USERS (core auth table) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(255) UNIQUE NOT NULL,
  password     VARCHAR(255)        NOT NULL,
  role         ENUM('student','university','admin') NOT NULL DEFAULT 'student',
  is_active    TINYINT(1)          NOT NULL DEFAULT 1,
  tos_accepted TINYINT(1)          NOT NULL DEFAULT 0,
  last_login   DATETIME            DEFAULT NULL,
  created_at   TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role  (role)
) ENGINE=InnoDB;

-- ── STUDENT PROFILES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS student_profiles (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  user_id             INT          UNIQUE NOT NULL,
  full_name           VARCHAR(255) DEFAULT NULL,
  phone               VARCHAR(20)  DEFAULT NULL,
  dob                 DATE         DEFAULT NULL,
  nationality         VARCHAR(100) DEFAULT NULL,
  gpa                 DECIMAL(3,2) DEFAULT NULL,
  major               VARCHAR(255) DEFAULT NULL,
  budget              VARCHAR(100) DEFAULT NULL,
  description         TEXT         DEFAULT NULL,
  completion_pct      TINYINT      NOT NULL DEFAULT 20,
  verified            ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  verified_at         DATETIME     DEFAULT NULL,
  verified_by         INT          DEFAULT NULL,
  verification_note   TEXT         DEFAULT NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_verified (verified),
  INDEX idx_gpa      (gpa)
) ENGINE=InnoDB;

-- ── UNIVERSITY PROFILES ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS university_profiles (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  user_id             INT          UNIQUE NOT NULL,
  uni_name            VARCHAR(255) DEFAULT NULL,
  country             VARCHAR(100) DEFAULT NULL,
  website             VARCHAR(255) DEFAULT NULL,
  programs            TEXT         DEFAULT NULL,
  intake_periods      VARCHAR(255) DEFAULT NULL,
  description         TEXT         DEFAULT NULL,
  verified            ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  verified_at         DATETIME     DEFAULT NULL,
  verified_by         INT          DEFAULT NULL,
  verification_note   TEXT         DEFAULT NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_verified (verified),
  INDEX idx_country  (country)
) ENGINE=InnoDB;

-- ── DOCUMENTS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS documents (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT          NOT NULL,
  doc_type     VARCHAR(100) NOT NULL,   -- 'transcript','passport','logo','accreditation'
  file_name    VARCHAR(255) NOT NULL,
  file_path    VARCHAR(500) NOT NULL,
  file_size    INT          DEFAULT NULL,
  mime_type    VARCHAR(100) DEFAULT NULL,
  status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  uploaded_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_doc (user_id, doc_type),
  INDEX idx_user_id  (user_id),
  INDEX idx_doc_type (doc_type)
) ENGINE=InnoDB;

-- ── MESSAGES ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  from_user_id INT          NOT NULL,
  to_user_id   INT          NOT NULL,
  body         TEXT         NOT NULL,
  is_read      TINYINT(1)   NOT NULL DEFAULT 0,
  read_at      DATETIME     DEFAULT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (to_user_id)   REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_from   (from_user_id),
  INDEX idx_to     (to_user_id),
  INDEX idx_thread (from_user_id, to_user_id)
) ENGINE=InnoDB;

-- ── APPLICATIONS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS applications (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  student_id      INT          NOT NULL,
  university_id   INT          NOT NULL,
  status          ENUM('new','review','interview','offer','rejected','withdrawn')
                               NOT NULL DEFAULT 'new',
  personal_stmt   TEXT         DEFAULT NULL,
  admin_note      TEXT         DEFAULT NULL,
  applied_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id)    REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (university_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_application (student_id, university_id),
  INDEX idx_student    (student_id),
  INDEX idx_university (university_id),
  INDEX idx_status     (status)
) ENGINE=InnoDB;

-- ── SAVED / BOOKMARKS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS saved_profiles (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  saved_user_id INT NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)       REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (saved_user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_save (user_id, saved_user_id)
) ENGINE=InnoDB;

-- ── NOTIFICATIONS ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT          NOT NULL,
  type         VARCHAR(100) NOT NULL,  -- 'verified','rejected','new_message','application_update'
  title        VARCHAR(255) NOT NULL,
  body         TEXT         DEFAULT NULL,
  is_read      TINYINT(1)   NOT NULL DEFAULT 0,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB;

-- ── ENROLLMENT CERTIFICATES ────────────────────────────────────────────
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

-- ── ADMIN ACTIVITY LOG ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_log (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  admin_id     INT          NOT NULL,
  action       VARCHAR(100) NOT NULL,
  target_type  VARCHAR(50)  DEFAULT NULL,
  target_id    INT          DEFAULT NULL,
  details      TEXT         DEFAULT NULL,
  ip_address   VARCHAR(45)  DEFAULT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_admin  (admin_id),
  INDEX idx_action (action)
) ENGINE=InnoDB;

-- ── SEED: Default admin account ──────────────────────────────
-- Password: admin123 (bcrypt hash)
INSERT IGNORE INTO users (email, password, role)
VALUES (
  'admin@betterabroad.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin'
);

-- ============================================================
--  FOLDER STRUCTURE for file uploads (create these in htdocs):
--  /betterabroad/uploads/
--    /students/{user_id}/transcript.pdf
--    /students/{user_id}/passport.pdf
--    /universities/{user_id}/logo.png
--    /universities/{user_id}/accreditation.pdf
-- ============================================================
