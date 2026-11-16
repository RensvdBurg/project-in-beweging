-- Add a public, optional profile description without changing existing users.
SET @about_me_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'about_me'
);
SET @about_me_migration = IF(
    @about_me_column_exists = 0,
    CONCAT(
        'ALTER TABLE users ADD COLUMN about_me VARCHAR(500) NOT NULL DEFAULT ',
        CHAR(39),
        CHAR(39),
        ' AFTER full_name'
    ),
    'SELECT 1'
);
PREPARE about_me_statement FROM @about_me_migration;
EXECUTE about_me_statement;
DEALLOCATE PREPARE about_me_statement;
