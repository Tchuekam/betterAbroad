-- ============================================================
--  BetterAbroad — Documents table migration
--  Run ONCE in phpMyAdmin → SQL tab
--  Adds UNIQUE(user_id, doc_type) so re-uploading a doc
--  replaces the old record instead of duplicating it.
-- ============================================================

USE betterabroad;

-- Add the unique constraint (safe — will error if already exists,
-- just skip if you see "Duplicate key name" warning)
ALTER TABLE documents
  ADD UNIQUE KEY unique_user_doc (user_id, doc_type);

-- ============================================================
--  That's it. No data loss — existing rows are kept.
--  If you get "Duplicate entry" it means a user already has
--  two rows for the same doc_type. Run this first to clean:
--
--  DELETE d1 FROM documents d1
--  INNER JOIN documents d2
--    ON d1.user_id = d2.user_id
--   AND d1.doc_type = d2.doc_type
--   AND d1.id > d2.id;
--
--  Then re-run the ALTER TABLE above.
-- ============================================================