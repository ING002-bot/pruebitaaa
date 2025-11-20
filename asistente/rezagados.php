<?php
require_once '../config/config.php';
requireRole(['asistente']);

header('Location: ../admin/rezagados.php');
exit;
