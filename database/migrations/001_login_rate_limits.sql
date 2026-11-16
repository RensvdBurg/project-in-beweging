-- Additive migration: safe to reapply; does not alter existing users or content.
CREATE TABLE IF NOT EXISTS login_rate_limits (
    bucket_key CHAR(64) NOT NULL,
    window_id BIGINT UNSIGNED NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (bucket_key, window_id),
    INDEX login_rate_limits_window (window_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
