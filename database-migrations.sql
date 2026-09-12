-- Chay mot lan tren database da ton tai neu bang products thieu gallery.
USE trachuyen_db;

SET @has_gallery = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND COLUMN_NAME = 'gallery'
);
SET @sql = IF(@has_gallery = 0,
    'ALTER TABLE products ADD COLUMN gallery LONGTEXT NULL AFTER image_url',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
