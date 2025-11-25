<?php
/**
 * API del Chatbot Inteligente v2.0 - CORREGIDA
 * Procesa preguntas naturales y devuelve información de BD
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['tipo' => 'error', 'respuesta' => 'No autorizado', 'icono' => '🚫']);
    exit;
}

$action = $_POST['action'] ?? '';
$input = $_POST['input'] ?? '';

class ChatbotIA {
    private $db;
    private $patrones = [];
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
            if (!$this->db) {
                throw new Exception("Conexión a BD no disponible");
            }
        } catch (Exception $e) {
            $this->db = null;
        }
        $this->inicializarPatrones();
    }
    
    private function inicializarPatrones() {
        $this->patrones = [
            'paquetes' => [
                'total' => 'cuant(o|a|os|as)?\s+paquetes|total\s+paquetes|cantidad\s+paquetes|paquetes\s+totales|cuantos\s+hay|^total$|hay\s+paquetes',
                'pendientes' => 'paquetes?\s+pendientes|sin\s+entregar|falta\s+entregar|no\s+entregados?|por\s+entregar|rezagados?|en\s+espera',
                'entregados' => 'paquetes?\s+entregados?|completados?|entregas?\s+exitosas?|cumplidos?|finalizados?',
                'hoy' => 'paquetes?\s+(hoy|de\s+hoy|registrados?\s+hoy|llegaron?|recibidos?\s+hoy)',
            ],
            'clientes' => [
                'total' => 'cuant(o|a|os|as)?\s+clientes|total\s+clientes|cantidad\s+clientes|clientes?\s+totales',
                'activos' => 'clientes?\s+activos?|clientes?\s+registrados?|clientes?\s+vigentes?|clientes?\s+nuevos?',
            ],
            'repartidores' => [
                'total' => 'cuant(o|a|os|as)?\s+repartidores|total\s+repartidores|cantidad\s+repartidores|cuantos\s+conductores',
                'activos' => 'repartidores?\s+activos?|repartidores?\s+disponibles?|conductores?\s+activos?|en\s+servicio',
            ],
            'ingresos' => [
                'total' => 'ingresos?\s+totales?|total\s+ingresos?|ganancias?\s+totales?|cuanto\s+ganamos?|ganancia\s+total|facturacion\s+total',
                'hoy' => 'ingresos?\s+(hoy|de\s+hoy)|ganancias?\s+(hoy|de\s+hoy)|cuanto\s+ganamos?\s+hoy|dinero\s+de\s+hoy',
                'mes' => 'ingresos?\s+(del\s+mes|mensuales?|este\s+mes)|ganancias?\s+(del\s+mes|mensuales?)',
            ],
            'reportes' => [
                'resumen' => 'resumen|reporte\s+general|estado\s+general|cómo\s+estamos?|vista\s+general|dashboard|overview',
                'problemas' => 'problemas?|entregas?\s+fallidas?|entregas?\s+con\s+problemas?|errores?|incidentes?|rechazos?',
                'pendientes' => 'tareas?\s+pendientes?|qué\s+falta|pendientes|por\s+hacer|alertas?',
            ]
        ];
    }
    
    public function procesarPregunta($pregunta) {
        $pregunta = strtolower(trim($pregunta));
        $pregunta_clean = $this->removerAcentos($pregunta);
        
        // 1. Buscar coincidencia de patrones
        foreach ($this->patrones as $categoria => $tipos) {
            foreach ($tipos as $tipo => $regex) {
                if (preg_match('/' . $regex . '/i', $pregunta_clean)) {
                    return $this->ejecutarConsulta($categoria, $tipo, $pregunta);
                }
            }
        }
        
        // 2. Respuestas rápidas
        return $this->respuestasRapidas($pregunta);
    }
    
    private function removerAcentos($texto) {
        $acentos = ['á', 'é', 'í', 'ó', 'ú', 'ä', 'ë', 'ï', 'ö', 'ü', 'ñ'];
        $sin_acentos = ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n'];
        return str_replace($acentos, $sin_acentos, $texto);
    }
    
    private function respuestasRapidas($pregunta) {
        $comunes = [
            '/(hola|hi|hey)/' => ['👋', 'Hola, ¿en qué puedo ayudarte?'],
            '/(gracias|thanks)/' => ['😊', '¡De nada! Cualquier cosa aquí estoy'],
            '/(ayuda|help)/' => ['📖', '📦 Paquetes • 👥 Clientes • 🚚 Repartidores • 💰 Ingresos • 📊 Reportes'],
            '/(cómo estás|how are you)/' => ['🤖', 'Funcionando perfecto, gracias!'],
            '/(si|yes|ok)/' => ['✅', 'Entendido!'],
            '/(no|nope)/' => ['❌', 'De acuerdo'],
        ];
        
        foreach ($comunes as $patron => $respuesta) {
            if (preg_match($patron, $pregunta)) {
                return [
                    'tipo' => 'exito',
                    'respuesta' => $respuesta[1],
                    'icono' => $respuesta[0]
                ];
            }
        }
        
        return [
            'tipo' => 'error',
            'respuesta' => '❓ No entendí eso. Puedo ayudarte con:\n\n📦 **Paquetes** - "¿Cuántos hay?" "Pendientes"\n👥 **Clientes** - "Total" "Activos"\n🚚 **Repartidores** - "¿Cuántos?" "Activos"\n💰 **Ingresos** - "Totales" "Hoy" "Mes"\n📊 **Reportes** - "Resumen"',
            'icono' => '🤔'
        ];
    }
    
    private function ejecutarConsulta($categoria, $tipo, $pregunta) {
        if (!$this->db) {
            return ['tipo' => 'error', 'respuesta' => '❌ Error de conexión BD', 'icono' => '❌'];
        }
        
        switch ($categoria) {
            case 'paquetes':
                return $this->consultarPaquetes($tipo);
            case 'clientes':
                return $this->consultarClientes($tipo);
            case 'repartidores':
                return $this->consultarRepartidores($tipo);
            case 'ingresos':
                return $this->consultarIngresos($tipo);
            case 'reportes':
                return $this->generarReporte($tipo);
            default:
                return ['tipo' => 'error', 'respuesta' => 'Consulta no soportada', 'icono' => '❓'];
        }
    }
    
    private function consultarPaquetes($tipo) {
        switch ($tipo) {
            case 'total':
                $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM paquetes");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "📦 **Total paquetes:** " . $row['cnt'], 'icono' => '📦'];
                
            case 'pendientes':
                $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM paquetes WHERE estado != 'entregado'");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "⏳ **Pendientes:** " . $row['cnt'], 'icono' => '⏳'];
                
            case 'entregados':
                $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM paquetes WHERE estado = 'entregado'");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "✅ **Entregados:** " . $row['cnt'], 'icono' => '✅'];
                
            case 'hoy':
                $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM paquetes WHERE DATE(fecha_registro) = CURDATE()");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "📅 **Hoy:** " . $row['cnt'], 'icono' => '📅'];
        }
        return ['tipo' => 'error', 'respuesta' => 'No pude procesar', 'icono' => '❌'];
    }
    
    private function consultarClientes($tipo) {
        switch ($tipo) {
            case 'total':
                $stmt = $this->db->query("SELECT COUNT(DISTINCT destinatario_nombre) as cnt FROM paquetes");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "👥 **Total clientes:** " . $row['cnt'], 'icono' => '👥'];
                
            case 'activos':
                $stmt = $this->db->query("SELECT COUNT(DISTINCT destinatario_nombre) as cnt FROM paquetes WHERE DATE(fecha_registro) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "💚 **Activos (30d):** " . $row['cnt'], 'icono' => '💚'];
        }
        return ['tipo' => 'error', 'respuesta' => 'No pude procesar', 'icono' => '❌'];
    }
    
    private function consultarRepartidores($tipo) {
        switch ($tipo) {
            case 'total':
                $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM usuarios WHERE rol = 'repartidor'");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "🚚 **Total:** " . $row['cnt'], 'icono' => '🚚'];
                
            case 'activos':
                $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "🟢 **Activos:** " . $row['cnt'], 'icono' => '🟢'];
        }
        return ['tipo' => 'error', 'respuesta' => 'No pude procesar', 'icono' => '❌'];
    }
    
    private function consultarIngresos($tipo) {
        switch ($tipo) {
            case 'total':
                $stmt = $this->db->query("SELECT COALESCE(SUM(monto), 0) as cnt FROM pagos WHERE estado = 'completado'");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "💰 **Total:** S/. " . number_format($row['cnt'], 2), 'icono' => '💰'];
                
            case 'hoy':
                $stmt = $this->db->query("SELECT COALESCE(SUM(monto), 0) as cnt FROM pagos WHERE estado = 'completado' AND DATE(fecha_pago) = CURDATE()");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "📈 **Hoy:** S/. " . number_format($row['cnt'], 2), 'icono' => '📈'];
                
            case 'mes':
                $stmt = $this->db->query("SELECT COALESCE(SUM(monto), 0) as cnt FROM pagos WHERE estado = 'completado' AND MONTH(fecha_pago) = MONTH(CURDATE()) AND YEAR(fecha_pago) = YEAR(CURDATE())");
                if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ ' . $this->db->error, 'icono' => '❌'];
                $row = $stmt->fetch_assoc();
                $stmt->close();
                return ['tipo' => 'exito', 'respuesta' => "📊 **Mes:** S/. " . number_format($row['cnt'], 2), 'icono' => '📊'];
        }
        return ['tipo' => 'error', 'respuesta' => 'No pude procesar', 'icono' => '❌'];
    }
    
    private function generarReporte($tipo) {
        if ($tipo === 'resumen') {
            $p_stmt = $this->db->query("SELECT COUNT(*) as cnt FROM paquetes");
            if (!$p_stmt) return ['tipo' => 'error', 'respuesta' => '❌ Error consulta paquetes', 'icono' => '❌'];
            $paquetes = $p_stmt->fetch_assoc()['cnt'];
            $p_stmt->close();
            
            $e_stmt = $this->db->query("SELECT COUNT(*) as cnt FROM paquetes WHERE estado = 'entregado'");
            if (!$e_stmt) return ['tipo' => 'error', 'respuesta' => '❌ Error consulta entregados', 'icono' => '❌'];
            $entregados = $e_stmt->fetch_assoc()['cnt'];
            $e_stmt->close();
            
            $r_stmt = $this->db->query("SELECT COUNT(*) as cnt FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'");
            if (!$r_stmt) return ['tipo' => 'error', 'respuesta' => '❌ Error consulta repartidores', 'icono' => '❌'];
            $repartidores = $r_stmt->fetch_assoc()['cnt'];
            $r_stmt->close();
            
            $i_stmt = $this->db->query("SELECT COALESCE(SUM(monto), 0) as cnt FROM pagos WHERE estado = 'completado'");
            if (!$i_stmt) return ['tipo' => 'error', 'respuesta' => '❌ Error consulta ingresos', 'icono' => '❌'];
            $ingresos = $i_stmt->fetch_assoc()['cnt'];
            $i_stmt->close();
            
            $pendientes = $paquetes - $entregados;
            $pct = ($paquetes > 0) ? round(($entregados / $paquetes) * 100) : 0;
            
            $resp = "📊 **RESUMEN EJECUTIVO**\n\n";
            $resp .= "📦 Paquetes: **$paquetes**\n";
            $resp .= "✅ Entregados: **$entregados** ($pct%)\n";
            $resp .= "⏳ Pendientes: **$pendientes**\n";
            $resp .= "🚚 Repartidores: **$repartidores**\n";
            $resp .= "💰 Ingresos: **S/. " . number_format($ingresos, 2) . "**";
            
            return ['tipo' => 'exito', 'respuesta' => $resp, 'icono' => '📊'];
        }
        
        if ($tipo === 'problemas') {
            $stmt = $this->db->query("
                SELECT COUNT(*) as total,
                       SUM(CASE WHEN estado = 'problema' THEN 1 ELSE 0 END) as problemas,
                       SUM(CASE WHEN estado = 'devuelto' THEN 1 ELSE 0 END) as devueltos
                FROM paquetes
                WHERE estado IN ('problema', 'devuelto')
            ");
            
            if (!$stmt) return ['tipo' => 'error', 'respuesta' => '❌ Error en consulta', 'icono' => '❌'];
            $row = $stmt->fetch_assoc();
            $stmt->close();
            
            $total = $row['total'] ?? 0;
            $problemas = $row['problemas'] ?? 0;
            $devueltos = $row['devueltos'] ?? 0;
            
            if ($total == 0) {
                return ['tipo' => 'exito', 'respuesta' => "✅ **¡Excelente!** No hay entregas con problemas", 'icono' => '✅'];
            }
            
            $resp = "⚠️ **ENTREGAS CON PROBLEMAS**\n\n";
            $resp .= "🚫 **Total afectadas:** $total\n";
            if ($problemas > 0) $resp .= "❌ **Con problemas:** $problemas\n";
            if ($devueltos > 0) $resp .= "↩️ **Devueltas:** $devueltos";
            
            return ['tipo' => 'advertencia', 'respuesta' => $resp, 'icono' => '⚠️'];
        }
        
        return ['tipo' => 'error', 'respuesta' => 'No pude procesar', 'icono' => '❌'];
    }
}

// Procesar petición
if ($action === 'chat' && !empty($input)) {
    try {
        $chatbot = new ChatbotIA();
        $respuesta = $chatbot->procesarPregunta($input);
        echo json_encode($respuesta);
    } catch (Exception $e) {
        echo json_encode([
            'tipo' => 'error',
            'respuesta' => '❌ ' . $e->getMessage(),
            'icono' => '❌'
        ]);
    }
} else {
    echo json_encode([
        'tipo' => 'error',
        'respuesta' => 'Solicitud inválida',
        'icono' => '❌'
    ]);
}
?>
