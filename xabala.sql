-- Xabala Enpresen Datu-basea

CREATE DATABASE IF NOT EXISTS xabala_db;
USE xabala_db;

-- Erabiltzaileak (Admin eta Langileak)
CREATE TABLE usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    izena VARCHAR(100) NOT NULL,
    abizena VARCHAR(100) NOT NULL,
    nan VARCHAR(9) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    user VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'langilea') DEFAULT 'langilea',
    aktibo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Langileak
CREATE TABLE langilea (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL UNIQUE,
    departamendua VARCHAR(100),
    pozisio VARCHAR(100),
    data_kontratazio DATE,
    soldata DECIMAL(10,2),
    telefonoa VARCHAR(15),
    aktibo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

-- Produktuak
CREATE TABLE produktua (
    id INT PRIMARY KEY AUTO_INCREMENT,
    izena VARCHAR(150) NOT NULL,
    deskripzioa TEXT,
    kategoria VARCHAR(100),
    prezioa DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    aktibo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Salmenteak
CREATE TABLE salmenta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    langile_id INT NOT NULL,
    produktu_id INT NOT NULL,
    kantitatea INT NOT NULL,
    prezioa_unitarioa DECIMAL(10,2),
    prezioa_totala DECIMAL(10,2),
    data_salmenta DATETIME DEFAULT CURRENT_TIMESTAMP,
    bezeroa_izena VARCHAR(150),
    bezeroa_nif VARCHAR(15),
    bezeroa_telefonoa VARCHAR(15),
    oharra TEXT,
    aktibo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (langile_id) REFERENCES langilea(id),
    FOREIGN KEY (produktu_id) REFERENCES produktua(id)
);

-- Segurtasun-loga
CREATE TABLE seguritatea_loga (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    evento VARCHAR(100),
    detaleak TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE SET NULL
);

-- Indizeak (performance)
CREATE INDEX idx_usuario_email ON usuario(email);
CREATE INDEX idx_usuario_nan ON usuario(nan);
CREATE INDEX idx_langilea_usuario ON langilea(usuario_id);
CREATE INDEX idx_salmenta_langile ON salmenta(langile_id);
CREATE INDEX idx_salmenta_produktu ON salmenta(produktu_id);
CREATE INDEX idx_salmenta_data ON salmenta(data_salmenta);
CREATE INDEX idx_seguritatea_usuario ON seguritatea_loga(usuario_id);

-- Create user and grant permissions
CREATE USER IF NOT EXISTS 'xabala_user'@'%' IDENTIFIED BY 'xabala_pass';
GRANT ALL PRIVILEGES ON *.* TO 'xabala_user'@'%';
FLUSH PRIVILEGES;

