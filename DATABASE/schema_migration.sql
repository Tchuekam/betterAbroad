-- ============================================================
--  BetterAbroad - Documents table migration
--  Run once in phpMyAdmin -> SQL tab
--  Adds UNIQUE(user_id, doc_type) so re-uploading a doc
--  replaces the old record instead of duplicating it.
-- ============================================================

USE betterabroad;

ALTER TABLE documents
  ADD UNIQUE KEY unique_user_doc (user_id, doc_type);

-- If the ALTER fails with "Duplicate entry", clean duplicate rows first:
--
-- DELETE d1 FROM documents d1
-- INNER JOIN documents d2
--   ON d1.user_id = d2.user_id
--  AND d1.doc_type = d2.doc_type
--  AND d1.id > d2.id;
--
-- Then re-run the ALTER TABLE above.
