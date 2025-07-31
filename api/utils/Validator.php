<?php
/**
 * Clase Validator
 * Validaciones centralizadas para la API
 * Canadian Sistemas API
 */

class Validator {
    
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
        $this->errors = [];
    }
    
    /**
     * Validar campo requerido
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field][] = $message ?? "El campo {$field} es requerido";
        }
        return $this;
    }
    
    /**
     * Validar email
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = $message ?? "El campo {$field} debe ser un email válido";
            }
        }
        return $this;
    }
    
    /**
     * Validar longitud mínima
     */
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (strlen($this->data[$field]) < $length) {
                $this->errors[$field][] = $message ?? "El campo {$field} debe tener al menos {$length} caracteres";
            }
        }
        return $this;
    }
    
    /**
     * Validar longitud máxima
     */
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (strlen($this->data[$field]) > $length) {
                $this->errors[$field][] = $message ?? "El campo {$field} no puede tener más de {$length} caracteres";
            }
        }
        return $this;
    }
    
    /**
     * Validar que sea numérico
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field][] = $message ?? "El campo {$field} debe ser numérico";
            }
        }
        return $this;
    }
    
    /**
     * Validar que sea entero
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
                $this->errors[$field][] = $message ?? "El campo {$field} debe ser un número entero";
            }
        }
        return $this;
    }
    
    /**
     * Validar CUIT argentino
     */
    public function cuit($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!$this->validateCuit($this->data[$field])) {
                $this->errors[$field][] = $message ?? "El CUIT no tiene un formato válido";
            }
        }
        return $this;
    }
    
    /**
     * Validar teléfono argentino
     */
    public function phone($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            // Patrón básico para teléfonos argentinos
            $pattern = '/^(\+54)?[\s\-]?(\d{2,4})[\s\-]?(\d{4})[\s\-]?(\d{4})$/';
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field][] = $message ?? "El número de teléfono no es válido";
            }
        }
        return $this;
    }
    
    /**
     * Validar que esté en una lista de valores
     */
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!in_array($this->data[$field], $values)) {
                $values_str = implode(', ', $values);
                $this->errors[$field][] = $message ?? "El campo {$field} debe ser uno de: {$values_str}";
            }
        }
        return $this;
    }
    
    /**
     * Validar formato de fecha
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field][] = $message ?? "El campo {$field} debe tener formato de fecha válido ({$format})";
            }
        }
        return $this;
    }
    
    /**
     * Validar expresión regular personalizada
     */
    public function regex($field, $pattern, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field][] = $message ?? "El campo {$field} no cumple con el formato requerido";
            }
        }
        return $this;
    }
    
    /**
     * Validar que dos campos coincidan
     */
    public function match($field, $match_field, $message = null) {
        if (isset($this->data[$field]) && isset($this->data[$match_field])) {
            if ($this->data[$field] !== $this->data[$match_field]) {
                $this->errors[$field][] = $message ?? "El campo {$field} debe coincidir con {$match_field}";
            }
        }
        return $this;
    }
    
    /**
     * Validar rango numérico
     */
    public function between($field, $min, $max, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $value = (float)$this->data[$field];
            if ($value < $min || $value > $max) {
                $this->errors[$field][] = $message ?? "El campo {$field} debe estar entre {$min} y {$max}";
            }
        }
        return $this;
    }
    
    /**
     * Validar que sea una URL válida
     */
    public function url($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->errors[$field][] = $message ?? "El campo {$field} debe ser una URL válida";
            }
        }
        return $this;
    }
    
    /**
     * Validar archivo subido
     */
    public function file($field, $allowed_types = [], $max_size = 2048000, $message = null) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES[$field];
            
            // Verificar errores de subida
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[$field][] = $message ?? "Error al subir el archivo";
                return $this;
            }
            
            // Verificar tamaño
            if ($file['size'] > $max_size) {
                $max_mb = round($max_size / 1024 / 1024, 2);
                $this->errors[$field][] = "El archivo no puede ser mayor a {$max_mb}MB";
            }
            
            // Verificar tipo de archivo
            if (!empty($allowed_types)) {
                $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($file_type, $allowed_types)) {
                    $types_str = implode(', ', $allowed_types);
                    $this->errors[$field][] = "El archivo debe ser de tipo: {$types_str}";
                }
            }
        }
        return $this;
    }
    
    /**
     * Verificar si hay errores
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Obtener todos los errores
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Obtener errores de un campo específico
     */
    public function getFieldErrors($field) {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Agregar error personalizado
     */
    public function addError($field, $message) {
        $this->errors[$field][] = $message;
        return $this;
    }
    
    /**
     * Limpiar errores
     */
    public function clearErrors() {
        $this->errors = [];
        return $this;
    }
    
    /**
     * Validar CUIT argentino
     */
    private function validateCuit($cuit) {
        // Remover guiones y espacios
        $cuit = preg_replace('/[^0-9]/', '', $cuit);
        
        // Verificar que tenga 11 dígitos
        if (strlen($cuit) != 11) {
            return false;
        }
        
        // Algoritmo de validación de CUIT argentino
        $multiplicadores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        
        for ($i = 0; $i < 10; $i++) {
            $suma += intval($cuit[$i]) * $multiplicadores[$i];
        }
        
        $resto = $suma % 11;
        $digito_verificador = $resto < 2 ? $resto : 11 - $resto;
        
        return intval($cuit[10]) == $digito_verificador;
    }
    
    /**
     * Método estático para validación rápida
     */
    public static function make($data) {
        return new self($data);
    }
}
?>