<?php

function ensureActivityPostsTable(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS activity_posts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(120) NOT NULL,
            description VARCHAR(2000) NOT NULL,
            image_filename VARCHAR(64) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX activity_posts_user_created (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}
