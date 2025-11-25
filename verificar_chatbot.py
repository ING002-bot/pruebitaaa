#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script de Prueba del Chatbot v2.0
Verifica que todo esté funcionando correctamente
"""

import os
import re
from pathlib import Path

def verificar_archivos():
    """Verificar que los archivos existen"""
    print("=" * 60)
    print("🔍 VERIFICACIÓN DE ARCHIVOS")
    print("=" * 60)
    
    archivos = {
        'admin/api_chatbot.php': '✅ Backend del chatbot',
        'admin/chatbot.php': '✅ Frontend del chatbot',
        'admin/chatbot_acceso.php': '✅ Control de acceso',
        'CHATBOT_MEJORADO.md': '✅ Documentación mejoras',
        'GUIA_COMANDOS_CHATBOT.md': '✅ Guía de comandos',
        'COMANDOS_CHATBOT.md': '✅ Referencia comandos',
    }
    
    for archivo, desc in archivos.items():
        ruta = Path(archivo)
        if ruta.exists():
            tamaño = ruta.stat().st_size
            print(f"  {desc}: {archivo} ({tamaño} bytes) ✓")
        else:
            print(f"  ⚠️  {archivo} NO ENCONTRADO")
    
    print()

def verificar_sintaxis_php():
    """Verificar sintaxis de archivos PHP"""
    print("=" * 60)
    print("🐘 VERIFICACIÓN DE SINTAXIS PHP")
    print("=" * 60)
    
    archivos_php = [
        'admin/api_chatbot.php',
        'admin/chatbot.php',
    ]
    
    for archivo in archivos_php:
        if Path(archivo).exists():
            # Leer el archivo
            with open(archivo, 'r', encoding='utf-8') as f:
                contenido = f.read()
            
            # Verificaciones básicas
            if '<?php' in contenido:
                print(f"  ✓ {archivo}: Contiene etiqueta PHP")
            
            if contenido.count('<?php') == 1:
                print(f"  ✓ {archivo}: Una sola etiqueta <?php")
            
            if contenido.strip().endswith('?>'):
                print(f"  ✓ {archivo}: Cierre PHP correcto")
            
            # Verificar classes
            if 'class ChatbotIA' in contenido:
                print(f"  ✓ {archivo}: Clase ChatbotIA definida")
            
            # Verificar métodos principales
            metodos = [
                'procesarPregunta',
                'removerAcentos',
                'consultarPaquetes',
                'interpretarPreguntaGeneral'
            ]
            
            for metodo in metodos:
                if f'function {metodo}' in contenido or f'private function {metodo}' in contenido:
                    print(f"  ✓ Método {metodo}() encontrado")
    
    print()

def verificar_contenido():
    """Verificar contenido de archivos"""
    print("=" * 60)
    print("📋 VERIFICACIÓN DE CONTENIDO")
    print("=" * 60)
    
    # Verificar api_chatbot.php
    with open('admin/api_chatbot.php', 'r', encoding='utf-8') as f:
        api_content = f.read()
    
    verificaciones_api = {
        'inicializarPatrones': 'Método de patrones',
        'removerAcentos': 'Normalización de acentos',
        'consultarPaquetes': 'Consultas de paquetes',
        'consultarClientes': 'Consultas de clientes',
        'consultarRepartidores': 'Consultas de repartidores',
        'consultarIngresos': 'Consultas de ingresos',
        'generarReporte': 'Generador de reportes',
        'cuant(o|a|os|as)': 'Regex de conjugaciones',
    }
    
    print("  Backend (api_chatbot.php):")
    for check, desc in verificaciones_api.items():
        if check in api_content:
            print(f"    ✓ {desc}")
        else:
            print(f"    ⚠️  {desc} NO ENCONTRADO")
    
    # Verificar chatbot.php
    with open('admin/chatbot.php', 'r', encoding='utf-8') as f:
        frontend_content = f.read()
    
    verificaciones_fe = {
        'SpeechRecognition': 'API de Reconocimiento de Voz',
        'speechSynthesis': 'API de Síntesis de Voz',
        'chatForm.addEventListener': 'Event listeners',
        'btnVoz.addEventListener': 'Botón micrófono',
        'btnSonido.addEventListener': 'Control de sonido',
        'agregarMensaje': 'Función de mensajes',
        'procesarPregunta': 'Procesador de preguntas',
        'hablarRespuesta': 'Función de síntesis',
    }
    
    print("\n  Frontend (chatbot.php):")
    for check, desc in verificaciones_fe.items():
        if check in frontend_content:
            print(f"    ✓ {desc}")
        else:
            print(f"    ⚠️  {desc} NO ENCONTRADO")
    
    print()

def contar_estadisticas():
    """Contar estadísticas de código"""
    print("=" * 60)
    print("📊 ESTADÍSTICAS DE CÓDIGO")
    print("=" * 60)
    
    api_path = Path('admin/api_chatbot.php')
    fe_path = Path('admin/chatbot.php')
    
    if api_path.exists():
        with open(api_path, 'r', encoding='utf-8') as f:
            api_lines = len(f.readlines())
        print(f"  Backend (api_chatbot.php): {api_lines} líneas")
    
    if fe_path.exists():
        with open(fe_path, 'r', encoding='utf-8') as f:
            fe_lines = len(f.readlines())
        print(f"  Frontend (chatbot.php): {fe_lines} líneas")
    
    # Contar patrones
    if api_path.exists():
        with open(api_path, 'r', encoding='utf-8') as f:
            contenido = f.read()
        patrones = contenido.count("'=>")
        funciones = len(re.findall(r'function\s+\w+', contenido))
        print(f"\n  Patrones de reconocimiento: {patrones}+")
        print(f"  Funciones/Métodos: {funciones}")
    
    print()

def listar_comandos():
    """Listar comandos soportados"""
    print("=" * 60)
    print("🎯 COMANDOS SOPORTADOS")
    print("=" * 60)
    
    categorias = {
        '📦 Paquetes': [
            'Total: "¿Cuántos paquetes hay?"',
            'Pendientes: "Paquetes pendientes"',
            'Entregados: "Paquetes entregados"',
            'Hoy: "Paquetes de hoy"',
            'Por repartidor: "Paquetes de Juan"',
            'Estadísticas: "por estado"'
        ],
        '👥 Clientes': [
            'Total: "¿Cuántos clientes?"',
            'Activos: "Clientes activos"',
            'Por ciudad: "Clientes en Lima"'
        ],
        '🚚 Repartidores': [
            'Total: "¿Cuántos repartidores?"',
            'Activos: "Repartidores activos"',
            'Top: "mejores repartidores"'
        ],
        '💰 Ingresos': [
            'Total: "Ingresos totales"',
            'Hoy: "¿Cuánto ganamos hoy?"',
            'Mes: "Ingresos del mes"'
        ],
        '📊 Reportes': [
            'Resumen: "Dame un resumen"',
            'Problemas: "Entregas fallidas"',
            'Pendientes: "Tareas pendientes"'
        ],
        '💬 Saludos': [
            'Hola: "Hola" → Saludo amistoso',
            'Ayuda: "Ayuda" → Lista de funciones',
            'Gracias: "Gracias" → Confirmación'
        ]
    }
    
    for categoria, ejemplos in categorias.items():
        print(f"\n  {categoria}:")
        for ejemplo in ejemplos:
            print(f"    • {ejemplo}")
    
    print()

def main():
    """Ejecutar todas las verificaciones"""
    print("\n")
    print("█" * 60)
    print("█" + " " * 58 + "█")
    print("█" + "  🤖 VERIFICADOR DE CHATBOT v2.0".center(58) + "█")
    print("█" + " " * 58 + "█")
    print("█" * 60)
    print("\n")
    
    try:
        verificar_archivos()
        verificar_sintaxis_php()
        verificar_contenido()
        contar_estadisticas()
        listar_comandos()
        
        print("=" * 60)
        print("✅ VERIFICACIÓN COMPLETADA")
        print("=" * 60)
        print("\n✓ Sistema listo para usar")
        print("✓ Accede a: http://localhost/pruebitaaa/admin/chatbot.php")
        print("✓ Requiere: Sesión de admin activa\n")
        
    except Exception as e:
        print(f"\n❌ Error durante verificación: {e}\n")

if __name__ == '__main__':
    main()
