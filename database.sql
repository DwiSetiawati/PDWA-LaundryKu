-- ============================================================
--  DATABASE: laundry_db
--  Sistem Laundry Online
-- ============================================================

CREATE DATABASE IF NOT EXISTS laundry_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laundry_db;

-- ------------------------------------------------------------
-- Tabel: admins
-- ------------------------------------------------------------
CREATE TABLE admins (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin: username=admin, password=admin123
INSERT INTO admins (username, password, full_name) VALUES
('admin', '$2y$10$kDMH0BFbeB.MlFmLJ2DSqOQjRLBYAYieIZnVewftS2tb0tuA02mTq', 'Administrator');

-- ------------------------------------------------------------
-- Tabel: services (jenis layanan)
-- ------------------------------------------------------------
CREATE TABLE services (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    price_per_kg DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL DEFAULT 3,
    description  VARCHAR(255),
    is_active    TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO services (name, price_per_kg, duration_days, description) VALUES
('Reguler',    5000,  3, 'Layanan cuci standar, selesai 3 hari'),
('Kilat',      8000,  1, 'Layanan express, selesai 1 hari'),
('Cuci Kering',12000, 2, 'Dry cleaning untuk pakaian khusus');

-- ------------------------------------------------------------
-- Tabel: customers
-- ------------------------------------------------------------
CREATE TABLE customers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(20)  NOT NULL,
    address    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Tabel: orders (pesanan utama)
-- ------------------------------------------------------------
CREATE TABLE orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number  VARCHAR(30)  NOT NULL UNIQUE,
    customer_id     INT NOT NULL,
    service_id      INT NOT NULL,
    weight_kg       DECIMAL(5,2) NOT NULL,
    total_price     DECIMAL(10,2) NOT NULL,
    status          ENUM('Queued','Washing','Ironing','Ready','Completed') DEFAULT 'Queued',
    estimated_done  DATETIME,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (service_id)  REFERENCES services(id)
);

-- ------------------------------------------------------------
-- Tabel: payments
-- ------------------------------------------------------------
CREATE TABLE payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL UNIQUE,
    payment_status  ENUM('Unpaid','Paid') DEFAULT 'Unpaid',
    payment_method  ENUM('Cash','Transfer','QRIS') DEFAULT 'Cash',
    amount_paid     DECIMAL(10,2) DEFAULT 0,
    paid_at         TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- ------------------------------------------------------------
-- Tabel: transaction_history (arsip completed)
-- ------------------------------------------------------------
CREATE TABLE transaction_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number  VARCHAR(30)  NOT NULL,
    customer_name   VARCHAR(100) NOT NULL,
    customer_phone  VARCHAR(20)  NOT NULL,
    service_name    VARCHAR(100) NOT NULL,
    weight_kg       DECIMAL(5,2) NOT NULL,
    total_price     DECIMAL(10,2) NOT NULL,
    payment_method  VARCHAR(50),
    completed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SELESAI — Jalankan file ini di phpMyAdmin > SQL tab
-- ============================================================
