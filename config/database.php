<?php
/**
 * database.php — eSPD Database Configuration
 * 
 * Provides database connection to MySQL (Sistem Pelatihan / eSPD shared database).
 * All data (kegiatan, SPD, audit logs, pengajar, users) resides in the same MySQL instance.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// ---------- .env loader ----------
function load_env(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key   = trim($key);
        $value = trim($value, " \t\"'");
        if ($key !== '') putenv("$key=$value");
    }
}

// Load .env
$envPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
];
foreach ($envPaths as $p) {
    if (file_exists($p)) { load_env($p); break; }
}

// ---------- Main DB singleton (MySQL) ----------
function get_db(): PDO {
    return get_mysql();
}

/**
 * Initialize database schema from schema.sql if tables are missing.
 */
function init_schema(PDO $pdo): void {
    $check = $pdo->query("SHOW TABLES LIKE 'kegiatan'");
    if ($check->fetch()) return; // Already initialized

    $schemaFile = __DIR__ . '/schema_mysql.sql';
    if (!file_exists($schemaFile)) return;

    // Execute script
    $sql = file_get_contents($schemaFile);
    // Note: PDO exec might not handle multiple statements cleanly if PDO::MYSQL_ATTR_MULTI_STATEMENTS is not set, 
    // but typically it works if simple. If it fails, we will need to split it.
    // For safety, let's split by statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) $pdo->exec($stmt);
    }
}

// ---------- MySQL singleton (Sistem Pelatihan, read-only) ----------
function get_mysql(): ?PDO {
    static $pdo = null;
    static $attempted = false;
    
    if ($attempted) return $pdo;
    $attempted = true;

    $host   = getenv('MYSQL_HOST') ?: '127.0.0.1';
    $port   = getenv('MYSQL_PORT') ?: '3306';
    $dbname = getenv('MYSQL_DB')   ?: '';
    $user   = getenv('MYSQL_USER') ?: '';
    $pass   = getenv('MYSQL_PASS') ?: '';

    if ($dbname === '') return null;

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;connect_timeout=3";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 3,
        ]);
    } catch (\Throwable $e) {
        $pdo = null; // MySQL not available; degrade gracefully
    }

    return $pdo;
}

// ---------- Query helpers ----------
function db_query(string $sql, array $params = []): array {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_execute(string $sql, array $params = []): int {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function db_last_id(): string {
    return get_db()->lastInsertId();
}

// ---------- MySQL helpers (read-only, for pengajar data) ----------
function db_mysql_query(string $sql, array $params = []): array {
    $pdo = get_mysql();
    if (!$pdo) return [];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
