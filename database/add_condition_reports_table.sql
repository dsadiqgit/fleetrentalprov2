-- Fleet Rental Pro - Add Condition Reports Table
-- Migration to support vehicle condition tracking at pickup and return

CREATE TABLE IF NOT EXISTS booking_condition_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  booking_id INT NOT NULL,
  report_type ENUM('pickup', 'return') NOT NULL,
  mileage INT NOT NULL,
  photo_front VARCHAR(500),
  photo_back VARCHAR(500),
  photo_left VARCHAR(500),
  photo_right VARCHAR(500),
  photo_rim1 VARCHAR(500),
  photo_rim2 VARCHAR(500),
  photo_rim3 VARCHAR(500),
  misc_photos TEXT, -- JSON array of up to 5 additional photos
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  UNIQUE KEY idx_booking_type (booking_id, report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
