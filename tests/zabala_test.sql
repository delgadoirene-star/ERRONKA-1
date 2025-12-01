-- Test Database Schema for Zabala Platform
-- This schema is identical to production but for testing purposes

CREATE DATABASE IF NOT EXISTS zabala_test;
USE zabala_test;

CREATE TABLE IF NOT EXISTS usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  izena VARCHAR(80) NOT NULL,
  abizena VARCHAR(120) NOT NULL,
  nan VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(160) NOT NULL UNIQUE,
  user VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','langilea') NOT NULL DEFAULT 'langilea',
  aktibo TINYINT(1) NOT NULL DEFAULT 1,
  jaiotegun DATE NULL,
  iban VARCHAR(34) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS langilea (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  departamendua VARCHAR(80) DEFAULT '',
  pozisio VARCHAR(80) DEFAULT '',
  data_kontratazio DATE NULL,
  soldata DECIMAL(10,2) DEFAULT 0,
  telefonoa VARCHAR(30) DEFAULT '',
  foto VARCHAR(200) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_langilea_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS produktua (
  id INT AUTO_INCREMENT PRIMARY KEY,
  izena VARCHAR(120) NOT NULL,
  deskripzioa TEXT,
  kategoria VARCHAR(80) DEFAULT '',
  prezioa DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 0,
  irudia VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS salmenta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  langile_id INT NOT NULL,
  produktu_id INT NOT NULL,
  kantitatea INT NOT NULL,
  prezioa_unitarioa DECIMAL(10,2) NOT NULL,
  prezioa_totala DECIMAL(10,2) GENERATED ALWAYS AS (kantitatea * prezioa_unitarioa) STORED,
  data_salmenta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  bezeroa_izena VARCHAR(160) DEFAULT '',
  bezeroa_nif VARCHAR(30) DEFAULT '',
  bezeroa_telefonoa VARCHAR(30) DEFAULT '',
  oharra TEXT,
  CONSTRAINT fk_salmenta_langilea FOREIGN KEY (langile_id) REFERENCES langilea(id) ON DELETE CASCADE,
  CONSTRAINT fk_salmenta_produktua FOREIGN KEY (produktu_id) REFERENCES produktua(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seguritatea_loga (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(40) NOT NULL,
  event_scope VARCHAR(80) DEFAULT '',
  usuario_id INT NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  detail TEXT NULL,
  INDEX idx_event_type (event_type),
  INDEX idx_usuario_id (usuario_id)
);

CREATE INDEX idx_usuario_email ON usuario(email);
CREATE INDEX idx_usuario_nan ON usuario(nan);
