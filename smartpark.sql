-- SmartPark Complete Database
-- Import this in phpMyAdmin

CREATE DATABASE IF NOT EXISTS smartpark;
USE smartpark;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Parking slots table
CREATE TABLE IF NOT EXISTS parking_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    slot_number VARCHAR(10) NOT NULL,
    vehicle_type ENUM('bike','car','truck') NOT NULL,
    status ENUM('available','occupied') DEFAULT 'available'
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    vehicle_type ENUM('bike','car','truck') NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME,
    duration_hours DECIMAL(5,2),
    amount DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('pending','paid') DEFAULT 'pending',
    status ENUM('active','completed','cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (slot_id) REFERENCES parking_slots(slot_id)
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'UPI',
    transaction_id VARCHAR(100),
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Insert default admin
INSERT IGNORE INTO users (name, email, phone, password, role)
VALUES ('Admin', 'admin@smartpark.com', '9999999999', MD5('admin123'), 'admin');

-- Insert parking slots
INSERT IGNORE INTO parking_slots (slot_number, vehicle_type) VALUES
('B-01','bike'),('B-02','bike'),('B-03','bike'),('B-04','bike'),('B-05','bike'),
('B-06','bike'),('B-07','bike'),('B-08','bike'),('B-09','bike'),('B-10','bike'),
('C-01','car'),('C-02','car'),('C-03','car'),('C-04','car'),('C-05','car'),
('C-06','car'),('C-07','car'),('C-08','car'),('C-09','car'),('C-10','car'),
('T-01','truck'),('T-02','truck'),('T-03','truck'),('T-04','truck'),('T-05','truck');
