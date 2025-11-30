#!/bin/bash

# Monitor de Activación FlexSender - HERMES EXPRESS
# Este script verifica automáticamente cuando FlexSender se activa

echo "🔄 MONITOR FLEXSENDER - HERMES EXPRESS"
echo "====================================="
echo "⏰ Inicio: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')"
echo ""

$maxIntentos = 20
$intervalo = 300  # 5 minutos

for ($i = 1; $i -le $maxIntentos; $i++) {
    Write-Host "🔍 Verificación $i/$maxIntentos - $(Get-Date -Format 'HH:mm:ss')"
    
    # Ejecutar prueba PHP
    $resultado = php test_directo_flexsender.php 2>$null | Select-String "✅ MENSAJE ENVIADO EXITOSAMENTE"
    
    if ($resultado) {
        Write-Host ""
        Write-Host "🎉 ¡FLEXSENDER ACTIVADO!" -ForegroundColor Green
        Write-Host "✅ API funcionando correctamente" -ForegroundColor Green
        Write-Host "🚀 HERMES EXPRESS enviando WhatsApp reales" -ForegroundColor Green
        Write-Host ""
        Write-Host "📱 Probando sistema completo..."
        php test_sistema_completo.php
        break
    } else {
        Write-Host "⏳ Aún procesando pago... próximo intento en 5 min" -ForegroundColor Yellow
        if ($i -lt $maxIntentos) {
            Write-Host "💤 Esperando hasta $(Get-Date -Date (Get-Date).AddSeconds($intervalo) -Format 'HH:mm:ss')"
            Start-Sleep -Seconds $intervalo
        }
    }
    Write-Host ""
}

if ($i -gt $maxIntentos) {
    Write-Host "⚠️ Tiempo de espera agotado" -ForegroundColor Red
    Write-Host "💡 El pago puede tardar más de lo esperado" -ForegroundColor Yellow
    Write-Host "🔗 Verifica tu panel: https://panel.flexbis.com" -ForegroundColor Blue
}

Write-Host ""
Write-Host "⏰ Fin: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')"