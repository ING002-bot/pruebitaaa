<?php
// Configuración de la base de datos

// Credenciales de conexión a MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hermes_express');
define('DB_CHARSET', 'utf8mb4');

// Clase singleton para la conexión a la base de datos
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die("Error de conexión a la base de datos: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset(DB_CHARSET);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function fetchAll($result) {
        $rows = [];
        if ($result && is_object($result)) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        } elseif (is_array($result)) {
            // Si ya es un array, devolverlo
            return $result;
        }
        return $rows;
    }

    public function fetch($result) {
        if ($result && is_object($result)) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function fetchColumn($result, $column = 0) {
        if ($result && is_object($result)) {
            $row = $result->fetch_assoc();
            if ($row) {
                $values = array_values($row);
                return isset($values[$column]) ? $values[$column] : null;
            }
        }
        return null;
    }
}

// Crear instancia global de la conexión
$conexion = Database::getInstance()->getConnection();

// Función para ejecutar queries de forma segura
function ejecutarQuery($sql) {
    global $conexion;
    return $conexion->query($sql);
}

// Función para preparar statements
function prepararStatement($sql) {
    global $conexion;
    return $conexion->prepare($sql);
}

// Función para obtener última inserción
function obtenerUltimoId() {
    global $conexion;
    return $conexion->insert_id;
}

// Función para escapar strings
function escapar($string) {
    global $conexion;
    return $conexion->real_escape_string($string);
}

// Manejo de errores de base de datos
function manejarErrorDB($mensaje = 'Error en la base de datos') {
    global $conexion;
    error_log("Error DB: " . $conexion->error);
    return [
        'success' => false,
        'mensaje' => $mensaje,
        'error' => $conexion->error
    ];
}
?>
