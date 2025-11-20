<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            color: white;
        }
        .error-icon {
            font-size: 120px;
            margin-bottom: 30px;
        }
        h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .btn-home {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i class="bi bi-shield-exclamation error-icon"></i>
        <h1>Acceso Denegado</h1>
        <p class="lead">No tienes permisos para acceder a esta sección</p>
        <a href="../auth/login.php" class="btn btn-light btn-lg btn-home">
            <i class="bi bi-house"></i> Volver al Inicio
        </a>
    </div>
</body>
</html>
