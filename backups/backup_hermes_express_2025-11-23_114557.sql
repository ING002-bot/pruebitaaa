-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hermes_express
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `caja_chica`
--

DROP TABLE IF EXISTS `caja_chica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caja_chica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('asignacion','gasto','devolucion') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` text NOT NULL,
  `descripcion` text DEFAULT NULL,
  `foto_comprobante` varchar(255) DEFAULT NULL,
  `asignado_por` int(11) DEFAULT NULL COMMENT 'ID del admin que asigna',
  `asignado_a` int(11) DEFAULT NULL COMMENT 'ID del asistente que recibe',
  `registrado_por` int(11) NOT NULL COMMENT 'ID del usuario que registra',
  `asignacion_padre_id` int(11) DEFAULT NULL COMMENT 'ID de la asignaci??n original para gastos',
  `fecha_operacion` datetime NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asignado_por` (`asignado_por`),
  KEY `registrado_por` (`registrado_por`),
  KEY `asignacion_padre_id` (`asignacion_padre_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_asignado_a` (`asignado_a`),
  KEY `idx_fecha` (`fecha_operacion`),
  CONSTRAINT `caja_chica_ibfk_1` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `caja_chica_ibfk_2` FOREIGN KEY (`asignado_a`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `caja_chica_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caja_chica_ibfk_4` FOREIGN KEY (`asignacion_padre_id`) REFERENCES `caja_chica` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja_chica`
--

LOCK TABLES `caja_chica` WRITE;
/*!40000 ALTER TABLE `caja_chica` DISABLE KEYS */;
INSERT INTO `caja_chica` VALUES (1,'asignacion',500.00,'compras','compra todo',NULL,1,2,1,NULL,'2025-11-20 12:29:00','2025-11-20 17:30:12'),(2,'gasto',100.00,'cargador','cargador blanco','comprobante_1763659867_691f505b230ae.jpg',NULL,2,2,1,'2025-11-20 12:30:00','2025-11-20 17:31:07');
/*!40000 ALTER TABLE `caja_chica` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entregas`
--

DROP TABLE IF EXISTS `entregas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entregas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paquete_id` int(11) NOT NULL,
  `ruta_id` int(11) DEFAULT NULL,
  `repartidor_id` int(11) NOT NULL,
  `fecha_entrega` timestamp NOT NULL DEFAULT current_timestamp(),
  `receptor_nombre` varchar(150) DEFAULT NULL,
  `receptor_dni` varchar(20) DEFAULT NULL,
  `receptor_firma` varchar(255) DEFAULT NULL,
  `foto_entrega` varchar(255) DEFAULT NULL,
  `foto_adicional_1` varchar(255) DEFAULT NULL,
  `foto_adicional_2` varchar(255) DEFAULT NULL,
  `latitud_entrega` decimal(10,8) DEFAULT NULL,
  `longitud_entrega` decimal(11,8) DEFAULT NULL,
  `tipo_entrega` enum('exitosa','parcial','rechazada','no_encontrado') NOT NULL,
  `observaciones` text DEFAULT NULL,
  `tiempo_entrega` int(11) DEFAULT NULL COMMENT 'Tiempo en minutos',
  PRIMARY KEY (`id`),
  KEY `ruta_id` (`ruta_id`),
  KEY `repartidor_id` (`repartidor_id`),
  KEY `idx_paquete` (`paquete_id`),
  KEY `idx_fecha` (`fecha_entrega`),
  CONSTRAINT `entregas_ibfk_1` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entregas_ibfk_2` FOREIGN KEY (`ruta_id`) REFERENCES `rutas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entregas_ibfk_3` FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entregas`
--

LOCK TABLES `entregas` WRITE;
/*!40000 ALTER TABLE `entregas` DISABLE KEYS */;
INSERT INTO `entregas` VALUES (1,1,NULL,3,'2025-11-20 15:30:28','omarcito','111111',NULL,'entrega_1_1763652628.png',NULL,NULL,-6.64325972,-79.79339206,'rechazada','no recibido',NULL);
/*!40000 ALTER TABLE `entregas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gastos`
--

DROP TABLE IF EXISTS `gastos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gastos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` enum('combustible','mantenimiento','salarios','administracion','otros') NOT NULL,
  `concepto` varchar(200) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_gasto` date NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_fecha` (`fecha_gasto`),
  KEY `idx_categoria` (`categoria`),
  CONSTRAINT `gastos_ibfk_1` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gastos`
--

LOCK TABLES `gastos` WRITE;
/*!40000 ALTER TABLE `gastos` DISABLE KEYS */;
INSERT INTO `gastos` VALUES (1,'combustible','aaa',111.00,'2025-11-23',1,'Comprobante: 211 | Archivo: gasto_1763914054_692331468dc68.png','2025-11-23 16:07:34');
/*!40000 ALTER TABLE `gastos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `importaciones_savar`
--

DROP TABLE IF EXISTS `importaciones_savar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `importaciones_savar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datos_json` longtext NOT NULL,
  `total_registros` int(11) DEFAULT NULL,
  `registros_procesados` int(11) DEFAULT 0,
  `registros_fallidos` int(11) DEFAULT 0,
  `estado` enum('pendiente','procesando','completado','error') DEFAULT 'pendiente',
  `fecha_importacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `procesado_por` int(11) DEFAULT NULL,
  `errores` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procesado_por` (`procesado_por`),
  KEY `idx_fecha` (`fecha_importacion`),
  CONSTRAINT `importaciones_savar_ibfk_1` FOREIGN KEY (`procesado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `importaciones_savar`
--

LOCK TABLES `importaciones_savar` WRITE;
/*!40000 ALTER TABLE `importaciones_savar` DISABLE KEYS */;
/*!40000 ALTER TABLE `importaciones_savar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingresos`
--

DROP TABLE IF EXISTS `ingresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingresos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('envio','servicio_adicional','recargo','otros') NOT NULL,
  `concepto` varchar(200) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `paquete_id` int(11) DEFAULT NULL,
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp(),
  `registrado_por` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paquete_id` (`paquete_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_fecha` (`fecha_ingreso`),
  KEY `idx_tipo` (`tipo`),
  CONSTRAINT `ingresos_ibfk_1` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ingresos_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingresos`
--

LOCK TABLES `ingresos` WRITE;
/*!40000 ALTER TABLE `ingresos` DISABLE KEYS */;
/*!40000 ALTER TABLE `ingresos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_sistema`
--

DROP TABLE IF EXISTS `logs_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `detalles` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha_accion`),
  CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_sistema`
--

LOCK TABLES `logs_sistema` WRITE;
/*!40000 ALTER TABLE `logs_sistema` DISABLE KEYS */;
INSERT INTO `logs_sistema` VALUES (1,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:13:21'),(2,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:14:29'),(3,NULL,'Intento de inicio de sesión fallido - Email: asistente@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:20:44'),(4,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:20:52'),(5,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:21:13'),(6,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:30:43'),(7,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:31:58'),(8,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:34:21'),(9,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:48:37'),(10,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:48:54'),(11,NULL,'Intento de inicio de sesión fallido - Email: asistente@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:49:28'),(12,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:49:42'),(13,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:49:56'),(14,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:50:30'),(15,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 14:59:28'),(16,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:04:51'),(17,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:06:07'),(18,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:09:32'),(19,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:15:26'),(20,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:15:54'),(21,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:16:20'),(22,1,'Creación de paquete','paquetes',1,'Código: 123456','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:17:57'),(23,1,'actualizar','paquetes',1,'Paquete actualizado: 123456',NULL,NULL,'2025-11-20 15:21:13'),(24,NULL,'Intento de inicio de sesión fallido - Email: carlos.r@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:27:54'),(25,NULL,'Intento de inicio de sesión fallido - Email: carlos.r@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:28:04'),(26,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:28:20'),(27,3,'Registro de entrega','entregas',1,'Tipo: rechazada','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:30:28'),(28,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:32:30'),(29,NULL,'Intento de inicio de sesión fallido - Email: admin@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:34:30'),(30,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:34:41'),(31,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:35:13'),(32,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 15:41:32'),(33,1,'actualizar','paquetes',1,'Paquete actualizado: 123456',NULL,NULL,'2025-11-20 16:15:17'),(34,1,'actualizar','paquetes',1,'Paquete actualizado: 123456',NULL,NULL,'2025-11-20 16:16:39'),(35,NULL,'Intento de inicio de sesión fallido - Email: carlos.r@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 16:35:33'),(36,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 16:35:44'),(37,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 16:49:04'),(38,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:00:06'),(39,4,'Inicio de sesión exitoso','usuarios',4,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:00:27'),(40,4,'Inicio de sesión exitoso','usuarios',4,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:01:52'),(41,NULL,'Intento de inicio de sesión fallido - Email: carlos.r@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:02:16'),(42,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:02:24'),(43,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:08:19'),(44,3,'Inicio de sesión exitoso','usuarios',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:12:26'),(45,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:19:09'),(46,1,'Actualizar tarifa','zonas_tarifas',31,'Estado: Desactivado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:19:40'),(47,1,'Actualizar tarifa','zonas_tarifas',31,'Estado: Activado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:19:52'),(48,1,'Asignación caja chica','caja_chica',1,'S/ 500 para Mar??a Gonz??lez','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:30:12'),(49,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:30:35'),(50,2,'2','gasto_caja_chica',0,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:31:07'),(51,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:31:49'),(52,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:33:51'),(53,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:34:25'),(54,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:35:25'),(55,1,'Inicio de sesión exitoso','usuarios',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:37:55'),(56,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:39:01'),(57,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2025-11-20 17:39:36'),(58,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:40:54'),(59,2,'Intento de inicio de sesión fallido - Email: asistente@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:41:54'),(60,2,'Inicio de sesión exitoso','usuarios',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 17:42:01'),(61,1,'Inicio de sesión exitoso','0',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 15:53:20'),(62,1,'Pago registrado: paquetes - S/. 1,000.00','0',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 15:59:58'),(63,1,'Gasto registrado: aaa - S/. 111.00','0',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 16:07:34'),(64,2,'Inicio de sesión exitoso','0',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 16:10:30'),(65,NULL,'Intento de inicio de sesión fallido - Email: carlos.r@hermesexpress.com',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 16:11:27'),(66,3,'Inicio de sesión exitoso','0',3,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 16:11:36'),(67,1,'Inicio de sesión exitoso','0',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 16:13:28'),(68,2,'Inicio de sesión exitoso','0',2,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 16:15:59'),(69,1,'Inicio de sesión exitoso','0',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-23 16:41:33');
/*!40000 ALTER TABLE `logs_sistema` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('info','alerta','urgente','sistema') DEFAULT 'info',
  `titulo` varchar(200) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_leida` (`usuario_id`,`leida`),
  KEY `idx_fecha` (`fecha_creacion`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
INSERT INTO `notificaciones` VALUES (1,3,'info','Nuevo paquete asignado','Se te ha asignado el paquete 123456',1,'2025-11-20 15:17:57'),(2,1,'info','Bienvenido al Sistema','Sistema de notificaciones activado correctamente',0,'2025-11-20 16:52:58'),(3,1,'alerta','Paquete Rezagado','El paquete #TRK123456 ha sido marcado como rezagado',0,'2025-11-20 16:52:58'),(4,1,'info','Nueva Entrega','Repartidor Carlos completó la entrega del paquete #TRK789012',0,'2025-11-20 16:52:58'),(5,2,'info','Sistema Actualizado','El sistema ha sido actualizado con nuevas funcionalidades',0,'2025-11-20 16:52:58'),(6,2,'alerta','Paquetes Pendientes','Hay 5 paquetes pendientes de asignación',0,'2025-11-20 16:52:58'),(7,3,'info','Nuevo Paquete Asignado','Se te ha asignado el paquete #TRK456789 para entrega hoy',1,'2025-11-20 16:52:58'),(8,3,'alerta','Recordatorio','Tienes 3 paquetes pendientes de entrega para hoy',1,'2025-11-20 16:52:58'),(9,3,'info','Pago Registrado','Se ha registrado tu pago de S/ 250.00 correspondiente a Noviembre 2025',1,'2025-11-20 16:52:58'),(10,4,'info','Nuevo Paquete Asignado','Se te ha asignado el paquete #TRK456789 para entrega hoy',0,'2025-11-20 16:52:58'),(11,4,'alerta','Recordatorio','Tienes 3 paquetes pendientes de entrega para hoy',0,'2025-11-20 16:52:58'),(12,4,'info','Pago Registrado','Se ha registrado tu pago de S/ 250.00 correspondiente a Noviembre 2025',0,'2025-11-20 16:52:58'),(13,2,'info','Nueva Asignación de Caja Chica','Se te ha asignado S/ 500.00 para: compras',0,'2025-11-20 17:30:12');
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repartidor_id` int(11) NOT NULL,
  `concepto` varchar(200) DEFAULT NULL,
  `periodo` varchar(100) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fin` date DEFAULT NULL,
  `total_paquetes` int(11) DEFAULT 0,
  `monto_por_paquete` decimal(10,2) DEFAULT NULL,
  `bonificaciones` decimal(10,2) DEFAULT 0.00,
  `deducciones` decimal(10,2) DEFAULT 0.00,
  `total_pagar` decimal(10,2) DEFAULT NULL,
  `estado` enum('pendiente','pagado','cancelado') DEFAULT 'pendiente',
  `fecha_pago` timestamp NULL DEFAULT NULL,
  `metodo_pago` enum('efectivo','transferencia','cheque') DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `generado_por` int(11) DEFAULT NULL,
  `fecha_generacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `generado_por` (`generado_por`),
  KEY `idx_repartidor` (`repartidor_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_registrado_por` (`registrado_por`),
  CONSTRAINT `fk_pagos_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`generado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (1,3,NULL,NULL,1000.00,'2025-11-01','2025-11-30',0,0.00,0.00,0.00,1000.00,'pagado','2025-11-23 05:00:00','',NULL,'Concepto: paquetes | Periodo: sep',1,'2025-11-23 15:59:58');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paquetes`
--

DROP TABLE IF EXISTS `paquetes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paquetes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_seguimiento` varchar(100) NOT NULL,
  `codigo_savar` varchar(100) DEFAULT NULL,
  `destinatario_nombre` varchar(150) NOT NULL,
  `destinatario_telefono` varchar(20) DEFAULT NULL,
  `destinatario_email` varchar(150) DEFAULT NULL,
  `direccion_completa` text NOT NULL,
  `direccion_latitud` decimal(10,8) DEFAULT NULL,
  `direccion_longitud` decimal(11,8) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `zona_tarifa_id` int(11) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL,
  `peso` decimal(8,2) DEFAULT NULL,
  `dimensiones` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `valor_declarado` decimal(10,2) DEFAULT NULL,
  `costo_envio` decimal(10,2) DEFAULT NULL,
  `estado` enum('pendiente','en_ruta','entregado','rezagado','devuelto','cancelado') DEFAULT 'pendiente',
  `prioridad` enum('normal','urgente','express') DEFAULT 'normal',
  `repartidor_id` int(11) DEFAULT NULL,
  `fecha_recepcion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_asignacion` timestamp NULL DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT NULL,
  `intentos_entrega` int(11) DEFAULT 0,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_seguimiento` (`codigo_seguimiento`),
  KEY `idx_codigo` (`codigo_seguimiento`),
  KEY `idx_estado` (`estado`),
  KEY `idx_repartidor` (`repartidor_id`),
  KEY `idx_fecha_entrega` (`fecha_entrega`),
  KEY `zona_tarifa_id` (`zona_tarifa_id`),
  CONSTRAINT `paquetes_ibfk_1` FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `paquetes_ibfk_2` FOREIGN KEY (`zona_tarifa_id`) REFERENCES `zonas_tarifas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `paquetes_ibfk_3` FOREIGN KEY (`zona_tarifa_id`) REFERENCES `zonas_tarifas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paquetes`
--

LOCK TABLES `paquetes` WRITE;
/*!40000 ALTER TABLE `paquetes` DISABLE KEYS */;
INSERT INTO `paquetes` VALUES (1,'123456','2026','OMAR','+51 912112380','','av PERU',NULL,NULL,'FERREÑAFE',NULL,'FERREÑAFE',NULL,1.01,NULL,'',0.09,3.50,'entregado','normal',3,'2025-11-20 15:17:57','2025-11-20 15:17:57','2025-11-20 15:30:28',1,'RAPIDO');
/*!40000 ALTER TABLE `paquetes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paquetes_rezagados`
--

DROP TABLE IF EXISTS `paquetes_rezagados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paquetes_rezagados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paquete_id` int(11) NOT NULL,
  `motivo` enum('direccion_incorrecta','destinatario_ausente','rechazo','zona_peligrosa','otros') NOT NULL,
  `descripcion_motivo` text DEFAULT NULL,
  `fecha_rezago` timestamp NOT NULL DEFAULT current_timestamp(),
  `intentos_realizados` int(11) DEFAULT 1,
  `proximo_intento` date DEFAULT NULL,
  `solucionado` tinyint(1) DEFAULT 0,
  `fecha_solucion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_paquete` (`paquete_id`),
  KEY `idx_solucionado` (`solucionado`),
  CONSTRAINT `paquetes_rezagados_ibfk_1` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paquetes_rezagados`
--

LOCK TABLES `paquetes_rezagados` WRITE;
/*!40000 ALTER TABLE `paquetes_rezagados` DISABLE KEYS */;
INSERT INTO `paquetes_rezagados` VALUES (1,1,'rechazo','no recibido','2025-11-20 15:30:28',1,NULL,0,NULL);
/*!40000 ALTER TABLE `paquetes_rezagados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ruta_paquetes`
--

DROP TABLE IF EXISTS `ruta_paquetes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ruta_paquetes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ruta_id` int(11) NOT NULL,
  `paquete_id` int(11) NOT NULL,
  `orden_entrega` int(11) DEFAULT NULL,
  `estado` enum('pendiente','entregado','fallido') DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `idx_ruta` (`ruta_id`),
  KEY `idx_paquete` (`paquete_id`),
  CONSTRAINT `ruta_paquetes_ibfk_1` FOREIGN KEY (`ruta_id`) REFERENCES `rutas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ruta_paquetes_ibfk_2` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruta_paquetes`
--

LOCK TABLES `ruta_paquetes` WRITE;
/*!40000 ALTER TABLE `ruta_paquetes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ruta_paquetes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rutas`
--

DROP TABLE IF EXISTS `rutas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rutas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `zona` varchar(50) DEFAULT NULL,
  `ubicaciones` text DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `repartidor_id` int(11) DEFAULT NULL,
  `fecha_ruta` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `estado` enum('planificada','en_progreso','completada','cancelada') DEFAULT 'planificada',
  `total_paquetes` int(11) DEFAULT 0,
  `paquetes_entregados` int(11) DEFAULT 0,
  `distancia_total` decimal(10,2) DEFAULT NULL,
  `tiempo_estimado` int(11) DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `creado_por` (`creado_por`),
  KEY `idx_fecha` (`fecha_ruta`),
  KEY `idx_repartidor` (`repartidor_id`),
  CONSTRAINT `rutas_ibfk_1` FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rutas_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rutas`
--

LOCK TABLES `rutas` WRITE;
/*!40000 ALTER TABLE `rutas` DISABLE KEYS */;
INSERT INTO `rutas` VALUES (1,'URBANO','URBANO','Chiclayo, Leonardo Ortiz, La Victoria, Santa Victoria','Cobertura completa zona urbana',NULL,'2025-11-20',NULL,NULL,'planificada',0,0,NULL,NULL,1,'2025-11-20 16:06:23'),(2,'PUEBLOS','PUEBLOS','Lambayeque, Mochumi, Túcume, Íllimo, Nueva Arica, Jayanca, Púcara, Mórrope, Motupe, Olmos, Salas','Cobertura completa de pueblos',NULL,'2025-11-20',NULL,NULL,'planificada',0,0,NULL,NULL,1,'2025-11-20 16:06:23'),(3,'PLAYAS','PLAYAS','San José, Santa Rosa, Pimentel, Reque, Monsefú, Eten, Puerto Eten','Cobertura completa zona de playas',NULL,'2025-11-20',NULL,NULL,'planificada',0,0,NULL,NULL,1,'2025-11-20 16:06:23'),(4,'COOPERATIVAS','COOPERATIVAS','Pomalca, Tumán, Pátapo, Pucalá, Saltur, Chongoyape','Cobertura completa de cooperativas',NULL,'2025-11-20',NULL,NULL,'planificada',0,0,NULL,NULL,1,'2025-11-20 16:06:23'),(5,'EXCOOPERATIVAS','EXCOOPERATIVAS','Ucupe, Mocupe, Zaña, Cayaltí, Oyotún, Lagunas','Cobertura completa de ex-cooperativas',NULL,'2025-11-20',NULL,NULL,'planificada',0,0,NULL,NULL,1,'2025-11-20 16:06:23'),(6,'FERREÑAFE','FERREÑAFE','Ferreñafe, Picsi, Pítipo, Motupillo, Pueblo Nuevo','Cobertura completa de Ferreñafe',NULL,'2025-11-20',NULL,NULL,'planificada',0,0,NULL,NULL,1,'2025-11-20 16:06:23');
/*!40000 ALTER TABLE `rutas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `saldo_caja_chica`
--

DROP TABLE IF EXISTS `saldo_caja_chica`;
/*!50001 DROP VIEW IF EXISTS `saldo_caja_chica`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `saldo_caja_chica` AS SELECT
 1 AS `asistente_id`,
  1 AS `nombre`,
  1 AS `apellido`,
  1 AS `total_asignado`,
  1 AS `total_gastado`,
  1 AS `total_devuelto`,
  1 AS `saldo_actual` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ubicaciones_tiempo_real`
--

DROP TABLE IF EXISTS `ubicaciones_tiempo_real`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ubicaciones_tiempo_real` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repartidor_id` int(11) NOT NULL,
  `ruta_id` int(11) DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `precision_metros` decimal(8,2) DEFAULT NULL,
  `velocidad` decimal(6,2) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_repartidor_timestamp` (`repartidor_id`,`timestamp`),
  KEY `idx_ruta` (`ruta_id`),
  CONSTRAINT `ubicaciones_tiempo_real_ibfk_1` FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ubicaciones_tiempo_real_ibfk_2` FOREIGN KEY (`ruta_id`) REFERENCES `rutas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicaciones_tiempo_real`
--

LOCK TABLES `ubicaciones_tiempo_real` WRITE;
/*!40000 ALTER TABLE `ubicaciones_tiempo_real` DISABLE KEYS */;
INSERT INTO `ubicaciones_tiempo_real` VALUES (1,3,NULL,-6.64325272,-79.79340431,0.00,0.00,'2025-11-20 14:32:16'),(2,3,NULL,-6.64325272,-79.79340431,0.00,0.00,'2025-11-20 14:32:29'),(3,3,NULL,-6.64325691,-79.79339581,0.00,0.00,'2025-11-20 14:32:37'),(4,3,NULL,-6.64325972,-79.79339206,0.00,0.00,'2025-11-20 15:08:06');
/*!40000 ALTER TABLE `ubicaciones_tiempo_real` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','asistente','repartidor') NOT NULL,
  `foto_perfil` varchar(255) DEFAULT 'default-avatar.svg',
  `estado` enum('activo','inactivo','suspendido') DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_rol` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Admin','Sistema','admin@hermesexpress.com',NULL,'$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py','admin','default-avatar.svg','activo','2025-11-20 14:12:46','2025-11-23 16:41:33'),(2,'Mar??a','Gonz??lez','asistente@hermesexpress.com','555-0101','$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py','asistente','default-avatar.svg','activo','2025-11-20 14:12:46','2025-11-23 16:15:59'),(3,'Carlos','Rodriguez','carlos.r@hermesexpress.com','555-0102','$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py','repartidor','perfil_3_1763914390.jpg','activo','2025-11-20 14:12:46','2025-11-23 16:11:36'),(4,'Juan','Perez','juan.p@hermesexpress.com','555-0103','$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py','repartidor','perfil_4_1763658075.jpg','activo','2025-11-20 14:12:46','2025-11-20 17:01:52');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zonas_tarifas`
--

DROP TABLE IF EXISTS `zonas_tarifas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zonas_tarifas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(50) NOT NULL COMMENT 'URBANO, PUEBLOS, PLAYAS, COOPERATIVAS, EXCOPERATIVAS, FERREÑAFE',
  `nombre_zona` varchar(100) NOT NULL,
  `tipo_envio` varchar(50) DEFAULT 'Paquete',
  `tarifa_repartidor` decimal(10,2) NOT NULL COMMENT 'Monto que recibe el repartidor por entrega',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_zona` (`categoria`,`nombre_zona`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zonas_tarifas`
--

LOCK TABLES `zonas_tarifas` WRITE;
/*!40000 ALTER TABLE `zonas_tarifas` DISABLE KEYS */;
INSERT INTO `zonas_tarifas` VALUES (1,'URBANO','Chiclayo','Paquete',1.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(2,'URBANO','Leonardo Ortiz','Paquete',1.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(3,'URBANO','La Victoria','Paquete',1.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(4,'URBANO','Santa Victoria','Paquete',1.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(5,'PUEBLOS','Lambayeque','Paquete',3.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(6,'PUEBLOS','Mochumi','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(7,'PUEBLOS','Tucume','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(8,'PUEBLOS','Illimo','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(9,'PUEBLOS','Nueva Arica','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(10,'PUEBLOS','Jayanca','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(11,'PUEBLOS','Pacora','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(12,'PUEBLOS','Morrope','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(13,'PUEBLOS','Motupe','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(14,'PUEBLOS','Olmos','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(15,'PUEBLOS','Salas','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(16,'PLAYAS','San Jose','Paquete',3.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(17,'PLAYAS','Santa Rosa','Paquete',3.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(18,'PLAYAS','Pimentel','Paquete',3.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(19,'PLAYAS','Reque','Paquete',3.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(20,'PLAYAS','Monsefu','Paquete',3.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(21,'PLAYAS','Eten','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(22,'PLAYAS','Puerto Eten','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(23,'COOPERATIVAS','Pomalca','Paquete',3.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(24,'COOPERATIVAS','Tuman','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(25,'COOPERATIVAS','Patapo','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(26,'COOPERATIVAS','Pucala','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(27,'COOPERATIVAS','Sartur','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(28,'COOPERATIVAS','Chongoyape','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(29,'EXCOPERATIVAS','Ucupe','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(30,'EXCOPERATIVAS','Mocupe','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(31,'EXCOPERATIVAS','Zaña','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:19:52'),(32,'EXCOPERATIVAS','Cayalti','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(33,'EXCOPERATIVAS','Oyotun','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(34,'EXCOPERATIVAS','Lagunas','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:16:11'),(35,'FERREÑAFE','Ferreñafe','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:15:02'),(36,'FERREÑAFE','Picsi','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:15:02'),(37,'FERREÑAFE','Pitipo','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:15:02'),(38,'FERREÑAFE','Motupillo','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:15:02'),(39,'FERREÑAFE','Pueblo Nuevo','Paquete',5.00,1,'2025-11-20 17:15:02','2025-11-20 17:15:02'),(40,'EXCOPERATIVAS','Za??a','Paquete',5.00,1,'2025-11-20 17:16:11','2025-11-20 17:16:11');
/*!40000 ALTER TABLE `zonas_tarifas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `saldo_caja_chica`
--

/*!50001 DROP VIEW IF EXISTS `saldo_caja_chica`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `saldo_caja_chica` AS select `cc`.`asignado_a` AS `asistente_id`,`u`.`nombre` AS `nombre`,`u`.`apellido` AS `apellido`,coalesce(sum(case when `cc`.`tipo` = 'asignacion' then `cc`.`monto` else 0 end),0) AS `total_asignado`,coalesce(sum(case when `cc`.`tipo` = 'gasto' then `cc`.`monto` else 0 end),0) AS `total_gastado`,coalesce(sum(case when `cc`.`tipo` = 'devolucion' then `cc`.`monto` else 0 end),0) AS `total_devuelto`,coalesce(sum(case when `cc`.`tipo` = 'asignacion' then `cc`.`monto` else 0 end),0) - coalesce(sum(case when `cc`.`tipo` = 'gasto' then `cc`.`monto` else 0 end),0) - coalesce(sum(case when `cc`.`tipo` = 'devolucion' then `cc`.`monto` else 0 end),0) AS `saldo_actual` from (`caja_chica` `cc` join `usuarios` `u` on(`cc`.`asignado_a` = `u`.`id`)) group by `cc`.`asignado_a`,`u`.`nombre`,`u`.`apellido` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-23 11:45:57
