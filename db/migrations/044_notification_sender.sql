-- Preserve the originating user on in-app notifications.
SET @sender_user_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notifications'
      AND COLUMN_NAME = 'sender_user_id'
);
SET @sender_user_sql := IF(
    @sender_user_exists = 0,
    'ALTER TABLE notifications ADD COLUMN sender_user_id INT NULL AFTER user_id, ADD INDEX idx_notifications_sender_user (sender_user_id), ADD CONSTRAINT fk_notifications_sender_user FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE sender_user_stmt FROM @sender_user_sql;
EXECUTE sender_user_stmt;
DEALLOCATE PREPARE sender_user_stmt;
