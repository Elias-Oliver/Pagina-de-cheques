CREATE TABLE IF NOT EXISTS cheques (
    numeroCheque INT AUTO_INCREMENT NOT NULL PRIMARY KEY,                -- Número de cheque (valor entero)
    proveedor VARCHAR(100) NOT NULL,         -- Proveedor (cadena de texto)
    Monto DECIMAL(10, 2) NOT NULL,           -- Monto (decimal con 2 decimales)
    MontoLetras VARCHAR(255) NOT NULL,       -- Monto en letras (cadena de texto)
    observaciones TEXT,                       -- Observaciones (texto largo)
    fechaRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Fecha y hora de registro (se establece automáticamente)
);
