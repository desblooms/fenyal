-- Restaurant Menu App Database Schema

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS u345095192_menudb;
USE u345095192_menudb;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admins (username, password, name) VALUES 
('admin', '$2y$10$X2mfQX9Q9KK3yLy9YX7qYeXnL0wYjzG7MJxg5q.mxYVY1K1UtRdDK', 'Administrator') 
ON DUPLICATE KEY UPDATE id = id;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Menu items table
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    is_veg BOOLEAN DEFAULT FALSE,
    is_special BOOLEAN DEFAULT FALSE,
    is_available BOOLEAN DEFAULT TRUE,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Add-on categories table
CREATE TABLE IF NOT EXISTS addon_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    min_selections INT DEFAULT 0,
    max_selections INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add-ons table
CREATE TABLE IF NOT EXISTS addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES addon_categories(id) ON DELETE CASCADE
);

-- Menu item add-on category mapping
CREATE TABLE IF NOT EXISTS menu_item_addon_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL,
    addon_category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (addon_category_id) REFERENCES addon_categories(id) ON DELETE CASCADE,
    UNIQUE KEY (menu_item_id, addon_category_id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT,
    phone VARCHAR(20) NOT NULL,
    customer_name VARCHAR(100),
    order_type ENUM('dine-in', 'takeaway', 'delivery') NOT NULL,
    table_number VARCHAR(10),
    delivery_address TEXT,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'online', 'card') NOT NULL DEFAULT 'cash',
    checkout_method ENUM('whatsapp', 'normal') NOT NULL,
    status ENUM('pending', 'preparing', 'ready', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT,
    menu_item_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
);

-- Order item add-ons table
CREATE TABLE IF NOT EXISTS order_item_addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    addon_id INT,
    addon_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id) REFERENCES addons(id) ON DELETE SET NULL
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('active_theme', 'theme-1'),
('restaurant_name', 'Flavors Restaurant'),
('restaurant_phone', '+91 9876543210'),
('restaurant_email', 'contact@flavorsrestaurant.com'),
('restaurant_address', '123 Food Street, Malappuram, Kerala, IN'),
('restaurant_whatsapp', '+919876543210'),
('is_open', 'true'),
('delivery_fee', '40'),
('min_order_value', '100'),
('tax_percentage', '5')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert sample data for categories
INSERT INTO categories (name, description, position, is_active) VALUES
('Starters', 'Appetizers and small bites to start your meal', 1, TRUE),
('Main Course', 'Hearty meals to satisfy your hunger', 2, TRUE),
('Desserts', 'Sweet treats to end your meal', 3, TRUE),
('Beverages', 'Drinks to quench your thirst', 4, TRUE);

-- Insert sample data for menu items
INSERT INTO menu_items (category_id, name, description, price, is_veg, is_special, is_available, position) VALUES
(1, 'Veg Spring Rolls', 'Crispy rolls filled with vegetables', 120.00, TRUE, FALSE, TRUE, 1),
(1, 'Chicken Wings', 'Spicy chicken wings served with dip', 180.00, FALSE, TRUE, TRUE, 2),
(2, 'Paneer Butter Masala', 'Cottage cheese in rich tomato gravy', 220.00, TRUE, FALSE, TRUE, 1),
(2, 'Chicken Biryani', 'Fragrant rice cooked with spiced chicken', 250.00, FALSE, TRUE, TRUE, 2),
(3, 'Gulab Jamun', 'Sweet milk solids, deep-fried and soaked in sugar syrup', 80.00, TRUE, FALSE, TRUE, 1),
(3, 'Chocolate Brownie', 'Warm chocolate brownie with vanilla ice cream', 120.00, TRUE, TRUE, TRUE, 2),
(4, 'Fresh Lime Soda', 'Refreshing lime soda, sweet or salted', 60.00, TRUE, FALSE, TRUE, 1),
(4, 'Masala Chai', 'Indian spiced tea', 40.00, TRUE, FALSE, TRUE, 2);

-- Insert sample addon categories
INSERT INTO addon_categories (name, is_required, min_selections, max_selections) VALUES
('Spice Level', TRUE, 1, 1),
('Extra Toppings', FALSE, 0, 3),
('Sides', FALSE, 0, 2);

-- Insert sample addons
INSERT INTO addons (category_id, name, price) VALUES
(1, 'Mild', 0.00),
(1, 'Medium', 0.00),
(1, 'Spicy', 0.00),
(2, 'Extra Cheese', 40.00),
(2, 'Mushrooms', 30.00),
(2, 'Jalapenos', 20.00),
(3, 'French Fries', 70.00),
(3, 'Garlic Bread', 60.00);

-- Map addons to menu items
INSERT INTO menu_item_addon_categories (menu_item_id, addon_category_id) VALUES
(3, 1), -- Paneer Butter Masala with Spice Level
(3, 2), -- Paneer Butter Masala with Extra Toppings
(4, 1), -- Chicken Biryani with Spice Level
(4, 3); -- Chicken Biryani with Sides