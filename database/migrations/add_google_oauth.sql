-- Add Google OAuth support to users table
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(100) NULL AFTER driving_license_url,
MODIFY COLUMN password VARCHAR(255) NULL,
ADD INDEX idx_google_id (google_id);
