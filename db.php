<?php
function getDb(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'logistica';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $port = getenv('DB_PORT') ?: '3306';

    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<h1>Error de conexión</h1>';
        echo '<p>No se pudo conectar a MySQL. Configura las variables DB_HOST, DB_NAME, DB_USER y DB_PASS.</p>';
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</pre>';
        exit;
    }

    ensureTables($pdo);
    return $pdo;
}

function ensureTables(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        nickname VARCHAR(255) DEFAULT "",
        address TEXT,
        tax_id VARCHAR(50),
        credit_days INT DEFAULT 0,
        account_status ENUM("facturado","pagado","mixto") DEFAULT "facturado",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        nickname VARCHAR(255) DEFAULT "",
        address TEXT,
        tax_id VARCHAR(50),
        credit_days INT DEFAULT 0,
        account_status ENUM("facturado","pagado","mixto") DEFAULT "facturado",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS shipments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NULL,
        supplier_id INT NULL,
        reference VARCHAR(255) NOT NULL,
        mode ENUM("aereo","maritimo","terrestre","hc","servicio_local") DEFAULT "aereo",
        description TEXT,
        origin VARCHAR(255),
        destination VARCHAR(255),
        departure_date DATE NULL,
        arrival_date DATE NULL,
        revenue_amount DECIMAL(12,2) DEFAULT 0,
        revenue_currency ENUM("MXN","USD") DEFAULT "MXN",
        cost_amount DECIMAL(12,2) DEFAULT 0,
        cost_currency ENUM("MXN","USD") DEFAULT "MXN",
        fx_rate DECIMAL(10,4) DEFAULT 1.0000,
        status ENUM("borrador","activo","en_transito","entregado","cerrado") DEFAULT "borrador",
        invoice_status ENUM("sin_facturar","facturado","pagado") DEFAULT "sin_facturar",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_shipments_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
        CONSTRAINT fk_shipments_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shipment_id INT NULL,
        type ENUM("cobro","pago") NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency ENUM("MXN","USD") DEFAULT "MXN",
        fx_rate DECIMAL(10,4) DEFAULT 1.0000,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_payments_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shipment_id INT NULL,
        invoice_number VARCHAR(100) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency ENUM("MXN","USD") DEFAULT "MXN",
        status ENUM("borrador","emitida","pagada","vencida") DEFAULT "borrador",
        issue_date DATE NULL,
        due_date DATE NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_invoices_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function convertToMXN(float $amount, string $currency, float $fxRate = 1.0): float
{
    return $currency === 'USD' ? $amount * $fxRate : $amount;
}
