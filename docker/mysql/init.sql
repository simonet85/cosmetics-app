-- Runs once, on first start of an empty MySQL volume.
CREATE DATABASE IF NOT EXISTS cosmetics_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON cosmetics_test.* TO 'cosmetics'@'%';
