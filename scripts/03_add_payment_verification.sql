-- Add verification fields to payments table
ALTER TABLE payments ADD COLUMN verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending';
ALTER TABLE payments ADD COLUMN verified_by INT NULL;
ALTER TABLE payments ADD COLUMN verified_at DATETIME NULL;
ALTER TABLE payments ADD COLUMN verification_notes TEXT NULL;
ALTER TABLE payments ADD CONSTRAINT fk_payments_verified_by FOREIGN KEY (verified_by) REFERENCES users(id);

-- Create uploads directory structure
-- Note: This would need to be created on the server filesystem
-- mkdir -p uploads/payment_receipts
-- chmod 755 uploads/payment_receipts
