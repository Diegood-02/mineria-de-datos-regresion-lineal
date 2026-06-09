CREATE DATABASE IF NOT EXISTS mineria_datos;
USE mineria_datos;

CREATE TABLE IF NOT EXISTS inversion_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mes VARCHAR(20) NOT NULL,
    inversion DECIMAL(10, 2) NOT NULL,
    ventas DECIMAL(10, 2) NOT NULL
);

INSERT INTO inversion_ventas (mes, inversion, ventas) VALUES
    ('Enero',   10, 50.00),
    ('Febrero', 20, 80.00),
    ('Marzo',   30, 100.00);

SELECT * FROM inversion_ventas;
