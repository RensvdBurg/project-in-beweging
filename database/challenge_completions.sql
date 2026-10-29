-- Restored from commit 7b12dbd. The composite key enforces one XP award per period.
CREATE TABLE challenge_completions (
    user_id BIGINT UNSIGNED NOT NULL,
    challenge_id BIGINT UNSIGNED NOT NULL,
    challenge_interval BIGINT UNSIGNED NOT NULL,
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, challenge_id, challenge_interval),
    KEY idx_challenge_completions_challenge_id (challenge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
