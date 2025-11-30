<?php
/**
 * Funciones para manejo dinámico de tarifas
 */

/**
 * Obtener tarifa por distrito
 * @param string $distrito Nombre del distrito
 * @return array Array con información de tarifa o null si no existe
 */
function obtenerTarifaPorDistrito($distrito) {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT id, categoria, nombre_zona, costo_cliente, tarifa_repartidor 
        FROM zonas_tarifas 
        WHERE UPPER(TRIM(nombre_zona)) = UPPER(TRIM(?)) AND activo = 1
        LIMIT 1
    ");
    
    if (!$stmt) return null;
    
    $stmt->bind_param("s", $distrito);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

/**
 * Obtener todas las tarifas por categoría
 * @return array Array de tarifas agrupadas por categoría
 */
function obtenerTarifasPorCategoria() {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("
        SELECT categoria, nombre_zona, costo_cliente, tarifa_repartidor 
        FROM zonas_tarifas 
        WHERE activo = 1 
        ORDER BY categoria, costo_cliente ASC, nombre_zona
    ");
    
    $tarifas = [];
    while ($row = $stmt->fetch_assoc()) {
        $tarifas[$row['categoria']][] = $row;
    }
    
    return $tarifas;
}

/**
 * Calcular costo de envío con factores de prioridad
 * @param string $distrito Distrito de destino
 * @param string $prioridad normal|urgente|express
 * @return float Costo final del envío
 */
function calcularCostoEnvio($distrito, $prioridad = 'normal') {
    $tarifa = obtenerTarifaPorDistrito($distrito);
    
    if (!$tarifa) {
        // Si no encuentra el distrito, usar tarifa urbana por defecto
        return TARIFA_URBANO_MIN;
    }
    
    $costo_base = $tarifa['costo_cliente'];
    
    // Aplicar factores según prioridad
    switch ($prioridad) {
        case 'urgente':
            return $costo_base * TARIFA_URGENTE_FACTOR;
        case 'express':
            return $costo_base * TARIFA_EXPRESS_FACTOR;
        default:
            return $costo_base;
    }
}

/**
 * Obtener ganancia del repartidor por entrega
 * @param string $distrito Distrito de entrega
 * @return float Ganancia del repartidor
 */
function obtenerGananciaRepartidor($distrito) {
    $tarifa = obtenerTarifaPorDistrito($distrito);
    
    if (!$tarifa) {
        // Ganancia mínima si no encuentra tarifa
        return 2.50;
    }
    
    return $tarifa['tarifa_repartidor'];
}

/**
 * Validar si un distrito tiene tarifa configurada
 * @param string $distrito Nombre del distrito
 * @return bool True si existe tarifa, false si no
 */
function distritoTieneTarifa($distrito) {
    return obtenerTarifaPorDistrito($distrito) !== null;
}

/**
 * Obtener estadísticas de tarifas
 * @return array Estadísticas generales
 */
function obtenerEstadisticasTarifas() {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("
        SELECT 
            categoria,
            COUNT(*) as total_zonas,
            MIN(costo_cliente) as precio_min,
            MAX(costo_cliente) as precio_max,
            AVG(costo_cliente) as precio_promedio,
            AVG(tarifa_repartidor) as ganancia_promedio
        FROM zonas_tarifas 
        WHERE activo = 1 
        GROUP BY categoria 
        ORDER BY precio_promedio ASC
    ");
    
    return Database::getInstance()->fetchAll($stmt);
}

/**
 * Calcular ganancia real por paquete
 * @param string $distrito Nombre del distrito
 * @param string $prioridad Prioridad del envío (normal, urgente, express)
 * @return array|null Detalles de ganancia o null si no existe tarifa
 */
function calcularGananciaReal($distrito, $prioridad = 'normal') {
    $tarifa = obtenerTarifaPorDistrito($distrito);
    
    if (!$tarifa) {
        return null;
    }
    
    $costo_cliente = calcularCostoEnvio($distrito, $prioridad);
    $costo_repartidor = $tarifa['tarifa_repartidor'];
    
    // Aplicar factor de prioridad al costo del repartidor también
    if ($prioridad === 'urgente') {
        $costo_repartidor *= 1.5; // 50% más al repartidor por urgente
    } elseif ($prioridad === 'express') {
        $costo_repartidor *= 2.0; // 100% más al repartidor por express
    }
    
    return [
        'costo_cliente' => $costo_cliente,
        'costo_repartidor' => $costo_repartidor,
        'ganancia_bruta' => $costo_cliente - $costo_repartidor,
        'margen_porcentaje' => round((($costo_cliente - $costo_repartidor) / $costo_cliente) * 100, 1),
        'zona' => $tarifa['nombre_zona'],
        'categoria' => $tarifa['categoria']
    ];
}

/**
 * Obtener zonas más rentables
 * @param int $limit Número de zonas a obtener
 * @return array Array de zonas ordenadas por ganancia
 */
function obtenerZonasMasRentables($limit = 10) {
    $db = Database::getInstance()->getConnection();
    
    $query = "SELECT 
        nombre_zona,
        categoria,
        costo_cliente,
        tarifa_repartidor,
        (costo_cliente - tarifa_repartidor) as ganancia,
        ROUND(((costo_cliente - tarifa_repartidor) / costo_cliente) * 100, 1) as margen_porcentaje
        FROM zonas_tarifas 
        WHERE activo = 1 
        ORDER BY ganancia DESC
        LIMIT ?"; 
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    
    return Database::getInstance()->fetchAll($stmt->get_result());
}

/**
 * Buscar distritos por texto (para autocompletado)
 * @param string $busqueda Texto a buscar
 * @return array Array de distritos coincidentes
 */
function buscarDistritos($busqueda) {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT nombre_zona, categoria, costo_cliente 
        FROM zonas_tarifas 
        WHERE nombre_zona LIKE ? AND activo = 1
        ORDER BY nombre_zona ASC
        LIMIT 10
    ");
    
    $busqueda_like = "%$busqueda%";
    $stmt->bind_param("s", $busqueda_like);
    $stmt->execute();
    
    return Database::getInstance()->fetchAll($stmt->get_result());
}
?>