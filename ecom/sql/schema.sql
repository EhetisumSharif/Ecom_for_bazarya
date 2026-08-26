-- E-commerce Website with Public Catalog & B2B Login Schema

-- ----------------------------
-- 1. Categories Table (For filtering and organizing products)
-- ----------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Insert initial data
INSERT INTO categories (name) VALUES 
('Electronics'), 
('Furniture'), 
('Chemicals'), 
('Safety'),
('Tools');

-- ----------------------------
-- 2. Products Table (Core E-commerce data)
-- ----------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    description TEXT,
    features TEXT,
    image_url VARCHAR(255) DEFAULT 'https://placehold.co/150x150/e5e7eb/374151?text=No+Image',
    price DECIMAL(10, 2) NOT NULL, -- All prices in AED (Requirement G.1)
    stock INT NOT NULL DEFAULT 0,
    moq INT NOT NULL DEFAULT 1,      -- Minimum Order Quantity (Requirement F.1)
    is_visible BOOLEAN NOT NULL DEFAULT TRUE, -- Enable/disable visibility (Requirement F.1)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Insert mock products (with prices for B2B)
INSERT INTO products (name, sku, category_id, description, features, price, stock, moq) VALUES
('Industrial Grade Wrench Set', 'TOOL-8452', 5, 'Heavy-duty wrench set for industrial maintenance.', '15 pieces; Chrome Vanadium steel; Anti-slip grip.', 250.00, 150, 1),
('Server Rack Cabinet 42U', 'ELEC-9001', 1, 'Standard 42U height cabinet for network servers.', 'Ventilated doors; Locking mechanism; Depth: 1000mm.', 4500.00, 25, 1),
('Ergonomic Mesh Chair', 'FUR-5003', 2, 'High-back office chair with adjustable lumbar support.', 'Breathable mesh; 3D adjustable armrests; 5-year warranty.', 1250.50, 80, 5),
('Floor Cleaner Concentrate 20L', 'CHM-7010', 3, 'High-efficiency concentrate for large area cleaning.', 'Biodegradable; Low foam; Citrus scent.', 150.99, 500, 20);

-- ----------------------------
-- 3. B2B Users Table (Requirement B)
-- ----------------------------
CREATE TABLE b2b_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255),
    contact_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Encrypted passwords (Requirement H)
    phone VARCHAR(50),
    is_active BOOLEAN NOT NULL DEFAULT TRUE, -- Activate/Deactivate accounts (Requirement F.3)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
-- 4. Orders Table (Requirement D & F)
-- ----------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    b2b_user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Confirmed', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending', -- (Requirement F.2)
    delivery_address TEXT NOT NULL,
    contact_number VARCHAR(50) NOT NULL,
    company_name VARCHAR(255),
    total_amount DECIMAL(10, 2) NOT NULL, -- Total = Product Price Only (Requirement G.2)
    payment_method VARCHAR(50) DEFAULT 'COD',
    FOREIGN KEY (b2b_user_id) REFERENCES b2b_users(id)
);

-- ----------------------------
-- 5. Order Items Table (Details of what was ordered)
-- ----------------------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL, -- Price at time of order
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);