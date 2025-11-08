-- Migration: add total_items to subjects and subject_id to board_passer_subjects
-- Safe ALTER statements (MySQL 8+ supports ADD COLUMN IF NOT EXISTS)

ALTER TABLE subjects
  ADD COLUMN IF NOT EXISTS total_items INT NOT NULL DEFAULT 50 AFTER subject_name;

-- Add subject_id column if missing. We avoid adding FK here to keep the SQL file simple; the provided PHP runner will add FK safely.
ALTER TABLE board_passer_subjects
  ADD COLUMN IF NOT EXISTS subject_id INT NULL AFTER board_passer_id;

-- Optionally link a subject to a specific exam type (nullable)
ALTER TABLE subjects
  ADD COLUMN IF NOT EXISTS exam_type_id INT NULL AFTER total_items;

-- Note: If you prefer to run the migration manually in MySQL/MariaDB shell, execute this file:
-- mysql -u root -p project_db < migrations/2025-10-22-add-subjects-columns.sql
