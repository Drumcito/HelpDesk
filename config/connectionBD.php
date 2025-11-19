<?php

class Database
{
    private static ?Database $instance = null;
    private \PDO $connection;

    /**
     * Constructor privado: configura la conexión.
     */
    private function __construct()
    {
        $host = 'localhost';
        $db   = 'eqf_helpdesk';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

        try {
            $this->connection = new \PDO(
                $dsn,
                $user,
                $pass,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (\PDOException $e) {
            // Aquí puedes personalizar el manejo de error
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    /**
     * Punto de acceso único al PDO (Facade).
     */
    public static function getConnection(): \PDO
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connection;
    }
}
