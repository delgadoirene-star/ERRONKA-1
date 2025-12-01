-- Zabala Enpresen Datu-basea
CREATE DATABASE IF NOT EXISTS zabala_db;
USE zabala_db;

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

-- E-commerce tables for online store (Etsy-like functionality)

-- Bezero: Customer table for e-commerce
CREATE TABLE IF NOT EXISTS bezero (
  id INT AUTO_INCREMENT PRIMARY KEY,
  izena VARCHAR(80) NOT NULL,
  abizena VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  telefonoa VARCHAR(30) DEFAULT '',
  helbidea TEXT,
  hiria VARCHAR(100) DEFAULT '',
  posta_kodea VARCHAR(10) DEFAULT '',
  probintzia VARCHAR(80) DEFAULT '',
  aktibo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Saskia: Shopping cart table
CREATE TABLE IF NOT EXISTS saskia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bezero_id INT NULL,
  saio_id VARCHAR(64) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bezero_id (bezero_id),
  INDEX idx_saio_id (saio_id),
  CONSTRAINT fk_saskia_bezero FOREIGN KEY (bezero_id) REFERENCES bezero(id) ON DELETE SET NULL
);

-- Saskia_item: Shopping cart items
CREATE TABLE IF NOT EXISTS saskia_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  saskia_id INT NOT NULL,
  produktu_id INT NOT NULL,
  kantitatea INT NOT NULL DEFAULT 1,
  prezioa_unitarioa DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_saskia_item_saskia FOREIGN KEY (saskia_id) REFERENCES saskia(id) ON DELETE CASCADE,
  CONSTRAINT fk_saskia_item_produktua FOREIGN KEY (produktu_id) REFERENCES produktua(id) ON DELETE CASCADE
);

-- Eskaera: Orders table
CREATE TABLE IF NOT EXISTS eskaera (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bezero_id INT NULL,
  izena VARCHAR(80) NOT NULL,
  abizena VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  telefonoa VARCHAR(30) DEFAULT '',
  helbidea TEXT NOT NULL,
  hiria VARCHAR(100) NOT NULL,
  posta_kodea VARCHAR(10) NOT NULL,
  probintzia VARCHAR(80) NOT NULL,
  guztira DECIMAL(10,2) NOT NULL,
  egoera ENUM('pendiente', 'prozesatzen', 'bidalita', 'entregatuta', 'ezeztatuta') NOT NULL DEFAULT 'pendiente',
  ordainketa_metodoa VARCHAR(50) DEFAULT 'tarjeta',
  oharra TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bezero_id (bezero_id),
  INDEX idx_egoera (egoera),
  CONSTRAINT fk_eskaera_bezero FOREIGN KEY (bezero_id) REFERENCES bezero(id) ON DELETE SET NULL
);

-- Eskaera_item: Order items
CREATE TABLE IF NOT EXISTS eskaera_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  eskaera_id INT NOT NULL,
  produktu_id INT NOT NULL,
  produktu_izena VARCHAR(120) NOT NULL,
  kantitatea INT NOT NULL,
  prezioa_unitarioa DECIMAL(10,2) NOT NULL,
  prezioa_totala DECIMAL(10,2) GENERATED ALWAYS AS (kantitatea * prezioa_unitarioa) STORED,
  CONSTRAINT fk_eskaera_item_eskaera FOREIGN KEY (eskaera_id) REFERENCES eskaera(id) ON DELETE CASCADE,
  CONSTRAINT fk_eskaera_item_produktua FOREIGN KEY (produktu_id) REFERENCES produktua(id) ON DELETE RESTRICT
);

CREATE INDEX idx_bezero_email ON bezero(email);
CREATE INDEX idx_produktua_kategoria ON produktua(kategoria);

-- Sample artisan products for e-commerce store
INSERT INTO produktua (izena, deskripzioa, kategoria, prezioa, stock, stock_minimo, irudia) VALUES
('Kamiseta Artisaua - Euskal Diseinua', 'Eskuz margotutako kamiseta euskal diseinu tradizionalarekin. Algodoi organikoz egina.', 'Kamiseta', 29.95, 50, 10, ''),
('Kamiseta Artisaua - Lore Motiboak', 'Lore eta landare motiboekin dekoratutako kamiseta, tinta ekologikoekin inprimatuta.', 'Kamiseta', 34.95, 35, 10, ''),
('Kamiseta Artisaua - Mendi Paisaia', 'Euskal mendien paisaia margotuta duen kamiseta originala.', 'Kamiseta', 32.00, 45, 10, ''),
('Tote Bag - Eskuz Josia', 'Eskuz josia den tote bag-a, lino naturalaz egina. Poltsiko barnekoa barne.', 'Poltsa', 24.50, 60, 15, ''),
('Tote Bag - Euskal Estanpak', 'Euskal arte tradizionalarekin estanpatutako poltsa. Jasangarria eta iraunkorra.', 'Poltsa', 22.00, 75, 15, ''),
('Tote Bag - Koloretan', 'Kolore biziekin margotutako tote bag artisaua.', 'Poltsa', 19.95, 40, 10, ''),
('Bufanda Artisaua - Lana Naturala', 'Eskuz ehundutako bufanda lana naturalaz. Diseinu bakarra.', 'Osagarriak', 45.00, 20, 5, ''),
('Txapela Artisaua', 'Txapela tradizionala diseinu modernoarekin. Euskal artisautzaren produktua.', 'Osagarriak', 38.00, 25, 5, ''),
('Eskularruak Artisauak', 'Larru naturala eta lana konbinatzen dituzten eskularru artisauak.', 'Osagarriak', 42.00, 15, 5, ''),
('Taza Zeramikoa - Eskuz Egina', 'Zeramika eskuz egindako taza, diseinu bakarrarekin.', 'Etxea', 18.50, 80, 20, ''),
('Plater Set Zeramikoa', '4 plateren multzoa, eskuz margotua euskal motiboekin.', 'Etxea', 65.00, 12, 3, ''),
('Edalontzi Artisaua', 'Beiraz egindako edalontzi artisaua, grabatu bakarrarekin.', 'Etxea', 15.00, 50, 10, ''),
('Kandela Artisaua - Usain Naturala', 'Argizari naturalaz egindako kandela, lore usainekin.', 'Etxea', 12.00, 100, 25, ''),
('Puzzle Egurrezkoa', 'Egurrez egindako puzzle artisaua, euskal paisaiekin.', 'Jokoak', 28.00, 30, 8, ''),
('Mahai Jokoa - Euskal Herria', 'Mahai joko originala Euskal Herriari buruz, eskuz egina.', 'Jokoak', 55.00, 15, 5, ''),
('Bizikleta Zorroa', 'Larru eta ehunez egindako bizikleta zorroa, eskuz josia.', 'Osagarriak', 48.00, 20, 5, '');
