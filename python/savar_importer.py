"""
Script de Python para extraer datos del sistema SAVAR usando Selenium
y cargarlos automáticamente al sistema HERMES EXPRESS
"""

import json
import time
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import mysql.connector
from mysql.connector import Error
import requests

# Configuración de la base de datos
DB_CONFIG = {
    'host': 'localhost',
    'database': 'hermes_express',
    'user': 'root',
    'password': ''
}

# Configuración de SAVAR (ajusta según tu sistema)
SAVAR_URL = "https://sistema-savar.com/login"  # URL de ejemplo
SAVAR_USERNAME = "tu_usuario"
SAVAR_PASSWORD = "tu_contraseña"

class SavarImporter:
    def __init__(self):
        """Inicializar el importador"""
        self.driver = None
        self.db_connection = None
        self.paquetes_extraidos = []
        
    def setup_driver(self):
        """Configurar el driver de Selenium"""
        chrome_options = Options()
        # Descomentar para ejecutar sin ventana
        # chrome_options.add_argument('--headless')
        chrome_options.add_argument('--disable-gpu')
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        
        self.driver = webdriver.Chrome(options=chrome_options)
        self.driver.maximize_window()
        print("✓ Driver de Selenium configurado")
        
    def connect_database(self):
        """Conectar a la base de datos MySQL"""
        try:
            self.db_connection = mysql.connector.connect(**DB_CONFIG)
            if self.db_connection.is_connected():
                print("✓ Conexión a base de datos establecida")
                return True
        except Error as e:
            print(f"✗ Error al conectar a la base de datos: {e}")
            return False
            
    def login_savar(self):
        """Iniciar sesión en el sistema SAVAR"""
        try:
            print("Iniciando sesión en SAVAR...")
            self.driver.get(SAVAR_URL)
            time.sleep(2)
            
            # Ajusta los selectores según el HTML real de SAVAR
            username_input = WebDriverWait(self.driver, 10).until(
                EC.presence_of_element_located((By.ID, "username"))  # Ajustar selector
            )
            password_input = self.driver.find_element(By.ID, "password")  # Ajustar selector
            
            username_input.send_keys(SAVAR_USERNAME)
            password_input.send_keys(SAVAR_PASSWORD)
            
            login_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")  # Ajustar
            login_button.click()
            
            time.sleep(3)
            print("✓ Sesión iniciada en SAVAR")
            return True
            
        except Exception as e:
            print(f"✗ Error al iniciar sesión: {e}")
            return False
            
    def extract_packages(self):
        """Extraer paquetes del sistema SAVAR"""
        try:
            print("Extrayendo paquetes...")
            
            # Navegar a la sección de paquetes (ajusta la URL)
            self.driver.get(SAVAR_URL + "/paquetes")
            time.sleep(2)
            
            # Esperar a que la tabla cargue
            WebDriverWait(self.driver, 10).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "table tbody tr"))
            )
            
            # Extraer filas de la tabla (ajusta los selectores)
            rows = self.driver.find_elements(By.CSS_SELECTOR, "table tbody tr")
            
            for row in rows:
                try:
                    # Ajusta estos selectores según la estructura HTML real
                    cells = row.find_elements(By.TAG_NAME, "td")
                    
                    paquete = {
                        'codigo_savar': cells[0].text.strip(),
                        'codigo_seguimiento': cells[1].text.strip(),
                        'destinatario_nombre': cells[2].text.strip(),
                        'destinatario_telefono': cells[3].text.strip(),
                        'direccion_completa': cells[4].text.strip(),
                        'ciudad': cells[5].text.strip() if len(cells) > 5 else '',
                        'provincia': cells[6].text.strip() if len(cells) > 6 else '',
                        'peso': self.parse_float(cells[7].text) if len(cells) > 7 else 0,
                        'estado': 'pendiente',
                        'fecha_extraccion': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                    }
                    
                    self.paquetes_extraidos.append(paquete)
                    
                except Exception as e:
                    print(f"Error al procesar fila: {e}")
                    continue
            
            print(f"✓ {len(self.paquetes_extraidos)} paquetes extraídos")
            return True
            
        except Exception as e:
            print(f"✗ Error al extraer paquetes: {e}")
            return False
            
    def geocode_address(self, address):
        """
        Geocodificar dirección usando Google Maps API
        Retorna (latitud, longitud) o (None, None)
        """
        try:
            # Necesitas una API Key de Google Maps
            api_key = "TU_GOOGLE_MAPS_API_KEY"
            url = f"https://maps.googleapis.com/maps/api/geocode/json"
            params = {
                'address': address,
                'key': api_key
            }
            
            response = requests.get(url, params=params)
            data = response.json()
            
            if data['status'] == 'OK':
                location = data['results'][0]['geometry']['location']
                return location['lat'], location['lng']
            else:
                return None, None
                
        except Exception as e:
            print(f"Error al geocodificar: {e}")
            return None, None
            
    def save_to_database(self):
        """Guardar paquetes en la base de datos"""
        if not self.db_connection or not self.db_connection.is_connected():
            print("✗ No hay conexión a la base de datos")
            return False
            
        cursor = self.db_connection.cursor()
        insertados = 0
        errores = 0
        
        try:
            # Primero, guardar datos JSON de importación
            importacion_data = json.dumps(self.paquetes_extraidos, ensure_ascii=False)
            sql_import = """INSERT INTO importaciones_savar 
                           (datos_json, total_registros, estado) 
                           VALUES (%s, %s, 'procesando')"""
            cursor.execute(sql_import, (importacion_data, len(self.paquetes_extraidos)))
            importacion_id = cursor.lastrowid
            
            # Insertar cada paquete
            sql = """INSERT INTO paquetes 
                    (codigo_seguimiento, codigo_savar, destinatario_nombre, 
                     destinatario_telefono, direccion_completa, ciudad, provincia, 
                     peso, direccion_latitud, direccion_longitud, estado) 
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE 
                    codigo_savar = VALUES(codigo_savar),
                    destinatario_nombre = VALUES(destinatario_nombre)"""
            
            for paquete in self.paquetes_extraidos:
                try:
                    # Geocodificar dirección
                    lat, lng = self.geocode_address(paquete['direccion_completa'])
                    
                    cursor.execute(sql, (
                        paquete['codigo_seguimiento'],
                        paquete['codigo_savar'],
                        paquete['destinatario_nombre'],
                        paquete['destinatario_telefono'],
                        paquete['direccion_completa'],
                        paquete['ciudad'],
                        paquete['provincia'],
                        paquete['peso'],
                        lat,
                        lng,
                        'pendiente'
                    ))
                    insertados += 1
                    
                    # Pausa para no saturar la API de geocoding
                    time.sleep(0.2)
                    
                except Exception as e:
                    print(f"Error al insertar paquete {paquete.get('codigo_seguimiento', 'N/A')}: {e}")
                    errores += 1
                    continue
            
            # Actualizar importación
            sql_update_import = """UPDATE importaciones_savar 
                                  SET registros_procesados = %s, 
                                      registros_fallidos = %s,
                                      estado = 'completado'
                                  WHERE id = %s"""
            cursor.execute(sql_update_import, (insertados, errores, importacion_id))
            
            self.db_connection.commit()
            print(f"✓ {insertados} paquetes guardados en la base de datos")
            print(f"✗ {errores} paquetes con errores")
            return True
            
        except Exception as e:
            self.db_connection.rollback()
            print(f"✗ Error al guardar en base de datos: {e}")
            return False
        finally:
            cursor.close()
            
    def parse_float(self, value):
        """Convertir string a float de forma segura"""
        try:
            return float(value.replace(',', '.'))
        except:
            return 0.0
            
    def close(self):
        """Cerrar conexiones"""
        if self.driver:
            self.driver.quit()
            print("✓ Driver cerrado")
            
        if self.db_connection and self.db_connection.is_connected():
            self.db_connection.close()
            print("✓ Conexión a base de datos cerrada")
            
    def run(self):
        """Ejecutar el proceso completo de importación"""
        print("=" * 60)
        print("HERMES EXPRESS - Importador de SAVAR")
        print("=" * 60)
        
        try:
            # 1. Configurar driver
            self.setup_driver()
            
            # 2. Conectar a base de datos
            if not self.connect_database():
                return False
            
            # 3. Iniciar sesión en SAVAR
            if not self.login_savar():
                return False
            
            # 4. Extraer paquetes
            if not self.extract_packages():
                return False
            
            # 5. Guardar en base de datos
            if not self.save_to_database():
                return False
            
            print("=" * 60)
            print("✓ Importación completada exitosamente")
            print("=" * 60)
            return True
            
        except Exception as e:
            print(f"✗ Error general: {e}")
            return False
            
        finally:
            self.close()


if __name__ == "__main__":
    importer = SavarImporter()
    importer.run()
