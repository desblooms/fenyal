-- Initial table creation 
CREATE TABLE categories ( 
  id INT PRIMARY KEY AUTO_INCREMENT, 
  name_en VARCHAR(100) NOT NULL, 
  name_ar VARCHAR(100) NOT NULL, 
  image VARCHAR(255), 
  sort_order INT DEFAULT 0, 
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
); 
