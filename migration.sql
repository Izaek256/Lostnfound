-- Migration Script: Add is_admin Field to Existing Users Table
-- Run this SQL if you have an existing database without the is_admin field

-- Check if is_admin column exists
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'lostfound_db' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'is_admin';

-- If the above query returns no results, run this:
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER password;

-- Verify the column was added
DESCRIBE users;

-- Optional: Grant admin rights to an existing user
-- Replace 'your_username' with the actual username
UPDATE users SET is_admin = 1 WHERE username = 'your_username';

-- Verify admin rights were granted
SELECT id, username, email, is_admin, created_at FROM users WHERE is_admin = 1;
