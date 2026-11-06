-- MiniForge Task Manager schema (SQLite).
-- Idempotent: safe to run several times.
--
-- Constraints mirror the application validation rules:
--   title       required, 1..255 characters
--   description optional, at most 2000 characters
--   status      TODO | IN_PROGRESS | DONE

CREATE TABLE IF NOT EXISTS tasks (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    title       TEXT    NOT NULL CHECK (length(trim(title)) BETWEEN 1 AND 255),
    description TEXT    NULL     CHECK (description IS NULL OR length(description) <= 2000),
    status      TEXT    NOT NULL DEFAULT 'TODO' CHECK (status IN ('TODO', 'IN_PROGRESS', 'DONE'))
);
