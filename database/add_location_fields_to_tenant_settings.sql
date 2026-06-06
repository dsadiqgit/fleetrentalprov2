-- Migration: Add pickup_location and dropoff_location fields to tenant_settings table
-- Run this to update existing databases

ALTER TABLE tenant_settings 
ADD COLUMN IF NOT EXISTS pickup_location TEXT,
ADD COLUMN IF NOT EXISTS dropoff_location TEXT,
ADD COLUMN IF NOT EXISTS min_booking_notice INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS booking_notice_unit ENUM('hours', 'days') DEFAULT 'hours',
ADD COLUMN IF NOT EXISTS buffer_time_hours INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS max_booking_advance_days INT DEFAULT 30,
ADD COLUMN IF NOT EXISTS opening_time TIME DEFAULT '09:00:00',
ADD COLUMN IF NOT EXISTS closing_time TIME DEFAULT '18:00:00',
ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'GBP';
