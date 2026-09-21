<?php
/**
 * SMM Panel - Database Connection and Helper
 * Core PHP PDO Prepared Statements
 */

require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_PORT,
                DB_NAME
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Production-safe error handling - never leak credentials
                error_log("Database Connection Error: " . $e->getMessage());
                http_response_code(500);
                die("<div style='font-family:system-ui;padding:40px;max-width:500px;margin:50px auto;border-radius:12px;background:#fff;border:1px solid #fecdd3;color:#9f1239;'>
                    <h2 style='margin-top:0;'>Database Connection Required</h2>
                    <p>Unable to connect to the MySQL database. Please verify your database configuration in <code>config.php</code> or <code>.env</code>.</p>
                </div>");
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): bool {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public static function lastInsertId(): string {
        return self::getConnection()->lastInsertId();
    }

    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool {
        return self::getConnection()->rollBack();
    }
}
