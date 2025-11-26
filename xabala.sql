-- Xabala Enpresen Datu-basea
CREATE DATABASE IF NOT EXISTS xabala_db;
USE xabala_db;

CREATE TABLE IF NOT EXISTS usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  izena VARCHAR(100) NOT NULL,
  abizena VARCHAR(100) NOT NULL,
  nan VARCHAR(20) UNIQUE,
  email VARCHAR(190) UNIQUE NOT NULL,
  user VARCHAR(60) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','langilea') DEFAULT 'langilea',
  aktibo TINYINT(1) DEFAULT 1,
  jaiotegun DATE NULL,
  iban VARCHAR(34) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS langilea (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  departamendua VARCHAR(100),
  pozisio VARCHAR(100),
  data_kontratazio DATE,
  soldata DECIMAL(10,2) DEFAULT 0,
  telefonoa VARCHAR(30),
  foto VARCHAR(255),
  aktibo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS produktua (
  id INT AUTO_INCREMENT PRIMARY KEY,
  izena VARCHAR(150) NOT NULL,
  deskripzioa TEXT,
  kategoria VARCHAR(100),
  prezioa DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 0,
  aktibo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS salmenta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  langile_id INT NOT NULL,
  produktu_id INT NOT NULL,
  kantitatea INT NOT NULL,
  prezioa_unitarioa DECIMAL(10,2) NOT NULL,
  prezioa_totala DECIMAL(10,2) NOT NULL,
  data_salmenta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  bezeroa_izena VARCHAR(150),
  bezeroa_nif VARCHAR(30),
  bezeroa_telefonoa VARCHAR(30),
  oharra TEXT,
  FOREIGN KEY (langile_id) REFERENCES langilea(id) ON DELETE CASCADE,
  FOREIGN KEY (produktu_id) REFERENCES produktua(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seguritatea_loga (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  evento VARCHAR(60) NOT NULL,
  detaleak VARCHAR(255),
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_seguritatea_usuario (usuario_id),
  FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE SET NULL
);

CREATE INDEX idx_usuario_email ON usuario(email);
CREATE INDEX idx_usuario_nan ON usuario(nan);
CREATE INDEX idx_langilea_usuario ON langilea(usuario_id);
CREATE INDEX idx_salmenta_langile ON salmenta(langile_id);
CREATE INDEX idx_salmenta_produktu ON salmenta(produktu_id);
CREATE INDEX idx_salmenta_data ON salmenta(data_salmenta);

CREATE USER IF NOT EXISTS 'xabala_user'@'%' IDENTIFIED BY 'xabala_pass';
GRANT ALL PRIVILEGES ON xabala_db.* TO 'xabala_user'@'%';
FLUSH PRIVILEGES;
