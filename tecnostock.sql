-- =====================================================
-- Base de datos TecnoStock
-- Ejecutar en phpMyAdmin (pestaña "SQL") o importar el archivo
-- =====================================================

DROP DATABASE IF EXISTS tecnostock;
CREATE DATABASE tecnostock CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE tecnostock;

-- -----------------------------------------------------
-- Tabla usuario (quien inicia sesión en el sistema)
-- estado: 1 = activo, 0 = inactivo
-- -----------------------------------------------------
CREATE TABLE usuario (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL,
    correo    VARCHAR(100) NOT NULL UNIQUE,
    clave     VARCHAR(255) NOT NULL,          -- se guarda encriptada, nunca en texto plano
    estado    TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla categoria (agrupa los productos)
-- -----------------------------------------------------
CREATE TABLE categoria (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    estado      TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla producto
-- codigo UNIQUE  -> regla: el código no puede repetirse
-- CHECK          -> regla: precio y stock no pueden ser negativos
-- FOREIGN KEY    -> cada producto pertenece a una categoría
-- -----------------------------------------------------
CREATE TABLE producto (
    idProducto  INT AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(30)  NOT NULL UNIQUE,
    nombre      VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    precio      DECIMAL(10,2) NOT NULL,
    stockActual INT NOT NULL DEFAULT 0,
    stockMinimo INT NOT NULL DEFAULT 0,
    estado      TINYINT NOT NULL DEFAULT 1,   -- 1 = activo, 0 = desactivado
    idCategoria INT NOT NULL,
    CONSTRAINT chkPrecio      CHECK (precio >= 0),
    CONSTRAINT chkStockActual CHECK (stockActual >= 0),
    CONSTRAINT chkStockMinimo CHECK (stockMinimo >= 0),
    CONSTRAINT fkProductoCategoria FOREIGN KEY (idCategoria)
        REFERENCES categoria(idCategoria)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla movimiento (historial de entradas y salidas)
-- Registra fecha, tipo, cantidad, producto y usuario responsable
-- -----------------------------------------------------
CREATE TABLE movimiento (
    idMovimiento INT AUTO_INCREMENT PRIMARY KEY,
    fechaHora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo         ENUM('entrada','salida') NOT NULL,
    cantidad     INT NOT NULL,
    observacion  VARCHAR(255),
    idProducto   INT NOT NULL,
    idUsuario    INT NOT NULL,
    CONSTRAINT chkCantidad CHECK (cantidad > 0),
    CONSTRAINT fkMovimientoProducto FOREIGN KEY (idProducto)
        REFERENCES producto(idProducto),
    CONSTRAINT fkMovimientoUsuario FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario)
) ENGINE=InnoDB;

-- =====================================================
-- DATOS DE PRUEBA
-- =====================================================

-- Usuario de prueba: correo admin@tecnostock.cl / clave admin123
INSERT INTO usuario (nombre, correo, clave) VALUES
('Encargado de Inventario', 'admin@tecnostock.cl',
 '$2y$10$NgBFmW/DfDFJlINiH4FrguU2K4QaewK8uQnZZslRO7HtNxaZIg38O');

INSERT INTO categoria (nombre, descripcion) VALUES
('Audio',      'Audífonos, parlantes y micrófonos'),
('Cables',     'Cables y cargadores'),
('Periféricos','Mouse, teclados y accesorios de escritorio');

INSERT INTO producto (codigo, nombre, descripcion, precio, stockActual, stockMinimo, idCategoria) VALUES
('AUD-001', 'Audífonos Bluetooth',   'Audífonos inalámbricos con estuche', 19990, 25, 5,  1),
('CAB-001', 'Cable USB-C 1m',        'Cable de carga rápida',               4990, 3,  10, 2),   -- stock bajo a propósito
('PER-001', 'Mouse inalámbrico',     'Mouse óptico 1600 DPI',               8990, 12, 4,  3);

-- Movimientos de ejemplo
INSERT INTO movimiento (tipo, cantidad, observacion, idProducto, idUsuario) VALUES
('entrada', 25, 'Stock inicial', 1, 1),
('entrada', 13, 'Stock inicial', 2, 1),
('salida',  10, 'Venta',         2, 1),
('entrada', 12, 'Stock inicial', 3, 1);
