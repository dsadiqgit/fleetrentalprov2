-- ============================================
-- CUSTOMER VERIFICATIONS TABLE
-- Stores Didit identity verification data
-- ============================================
CREATE TABLE IF NOT EXISTS customer_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  customer_email VARCHAR(255) NOT NULL,
  session_id VARCHAR(255) NOT NULL UNIQUE,
  verification_status ENUM('pending', 'approved', 'declined', 'in_review') DEFAULT 'pending',
  first_name VARCHAR(255),
  last_name VARCHAR(255),
  date_of_issue DATE,
  expiration_date DATE,
  verified_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  INDEX idx_tenant_id (tenant_id),
  INDEX idx_customer_email (customer_email),
  INDEX idx_session_id (session_id),
  INDEX idx_status (verification_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
