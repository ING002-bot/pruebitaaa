<?php
/**
 * SimpleXLSX - Lector de archivos XLSX simple y ligero
 * No requiere Composer ni extensiones adicionales
 * Versión: 1.0 (simplificada)
 */

class SimpleXLSX {
    private $zip;
    private $sharedStrings = [];
    private $sheets = [];
    
    public function __construct($filename) {
        if (!file_exists($filename)) {
            throw new Exception('Archivo no encontrado: ' . $filename);
        }
        
        if (!class_exists('ZipArchive')) {
            throw new Exception('La extensión ZIP no está habilitada en PHP');
        }
        
        $this->zip = new ZipArchive();
        if ($this->zip->open($filename) !== true) {
            throw new Exception('No se pudo abrir el archivo XLSX');
        }
        
        $this->loadSharedStrings();
        $this->loadWorksheet();
    }
    
    private function loadSharedStrings() {
        $xml = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return; // No hay strings compartidos
        }
        
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        
        $nodes = $doc->getElementsByTagName('t');
        foreach ($nodes as $node) {
            $this->sharedStrings[] = $node->nodeValue;
        }
    }
    
    private function loadWorksheet() {
        // Cargar la primera hoja
        $xml = $this->zip->getFromName('xl/worksheets/sheet1.xml');
        if ($xml === false) {
            throw new Exception('No se pudo leer la hoja de cálculo');
        }
        
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        
        $rows = $doc->getElementsByTagName('row');
        $data = [];
        
        foreach ($rows as $row) {
            $rowNum = $row->getAttribute('r');
            $cells = $row->getElementsByTagName('c');
            $rowData = [];
            
            foreach ($cells as $cell) {
                $cellRef = $cell->getAttribute('r');
                $cellType = $cell->getAttribute('t');
                $valueNode = $cell->getElementsByTagName('v')->item(0);
                
                $value = '';
                if ($valueNode) {
                    $value = $valueNode->nodeValue;
                    
                    // Si es tipo 's' (shared string), buscar el valor real
                    if ($cellType === 's' && isset($this->sharedStrings[$value])) {
                        $value = $this->sharedStrings[$value];
                    }
                }
                
                // Extraer la columna de la referencia (A, B, C, etc.)
                preg_match('/([A-Z]+)/', $cellRef, $matches);
                $col = $matches[1];
                
                $rowData[$col] = $value;
            }
            
            $data[$rowNum] = $rowData;
        }
        
        $this->sheets[] = $data;
    }
    
    public function rows() {
        return $this->sheets[0] ?? [];
    }
    
    public function getCell($row, $col) {
        return $this->sheets[0][$row][$col] ?? '';
    }
    
    public function __destruct() {
        if ($this->zip) {
            $this->zip->close();
        }
    }
}
