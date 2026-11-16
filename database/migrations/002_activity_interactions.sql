-- Additive migration: stores per-user likes and comments for activity posts.
CREATE TABLE IF NOT EXISTS activity_post_likes (
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, user_id),
    INDEX activity_post_likes_user (user_id),
    CONSTRAINT activity_post_likes_post_fk
        FOREIGN KEY (post_id) REFERENCES activity_posts (id) ON DELETE CASCADE,
    CONSTRAINT activity_post_likes_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_post_comments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    body VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX activity_post_comments_post_created (post_id, created_at, id),
    CONSTRAINT activity_post_comments_post_fk
        FOREIGN KEY (post_id) REFERENCES activity_posts (id) ON DELETE CASCADE,
    CONSTRAINT activity_post_comments_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
