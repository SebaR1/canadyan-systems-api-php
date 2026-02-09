<?php
/**
 * Validador de Imágenes
 * Valida formato, tamaño y proporciones de imágenes
 */

class ImageValidator {
    
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Proporciones permitidas con tolerancia del 2%
    private $aspectRatios = [
        'cuadrado' => [
            'ratio' => 1.0,
            'min' => 0.98,
            'max' => 1.02,
            'label' => '1:1 (cuadrado)'
        ],
        'horizontal' => [
            'ratio' => 1.333,
            'min' => 1.31,
            'max' => 1.36,
            'label' => '4:3 (horizontal)'
        ],
        'vertical' => [
            'ratio' => 0.75,
            'min' => 0.73,
            'max' => 0.77,
            'label' => '3:4 (vertical)'
        ]
    ];
    
    private $minWidth = 500;
    private $minHeight = 500;
    private $maxWidth = 3000;
    private $maxHeight = 3000;
    
    /**
     * Validar imagen completa
     * 
     * @param array $file - Array del archivo ($_FILES['imagen'])
     * @param int $maxSize - Tamaño máximo en bytes
     * @return array - ['valid' => bool, 'error' => string, 'dimensions' => array]
     */
    public function validate($file, $maxSize = 5242880) {
        // Validar tipo MIME
        if (!in_array($file['type'], $this->allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF'
            ];
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'valid' => false,
                'error' => 'Extensión de archivo no permitida'
            ];
        }
        
        // Validar tamaño
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1048576, 1);
            return [
                'valid' => false,
                'error' => "El archivo excede el tamaño máximo de {$maxMB}MB"
            ];
        }
        
        // Obtener dimensiones
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return [
                'valid' => false,
                'error' => 'No se pudo leer la información de la imagen'
            ];
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Validar dimensiones mínimas
        if ($width < $this->minWidth || $height < $this->minHeight) {
            return [
                'valid' => false,
                'error' => "La imagen debe tener al menos {$this->minWidth}x{$this->minHeight}px. Tu imagen: {$width}x{$height}px"
            ];
        }
        
        // Validar dimensiones máximas
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            return [
                'valid' => false,
                'error' => "La imagen no debe exceder {$this->maxWidth}x{$this->maxHeight}px. Tu imagen: {$width}x{$height}px"
            ];
        }
        
        // Validar proporción (aspect ratio)
        $aspectRatioValidation = $this->validateAspectRatio($width, $height);
        if (!$aspectRatioValidation['valid']) {
            return $aspectRatioValidation;
        }
        
        // Todo OK
        return [
            'valid' => true,
            'dimensions' => [
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => $aspectRatioValidation['aspect_ratio'],
                'aspect_ratio_type' => $aspectRatioValidation['type']
            ]
        ];
    }
    
    /**
     * Validar proporción de la imagen
     * 
     * @param int $width - Ancho de la imagen
     * @param int $height - Alto de la imagen
     * @return array - ['valid' => bool, 'error' => string, 'aspect_ratio' => float, 'type' => string]
     */
    private function validateAspectRatio($width, $height) {
        $ratio = $width / $height;
        
        // Verificar si coincide con alguna proporción permitida
        foreach ($this->aspectRatios as $key => $allowed) {
            if ($ratio >= $allowed['min'] && $ratio <= $allowed['max']) {
                return [
                    'valid' => true,
                    'aspect_ratio' => $ratio,
                    'type' => $key,
                    'type_label' => $allowed['label']
                ];
            }
        }
        
        // No coincide con ninguna proporción permitida
        $formatos = [];
        foreach ($this->aspectRatios as $key => $allowed) {
            $formatos[] = $allowed['label'];
        }
        
        return [
            'valid' => false,
            'error' => sprintf(
                "La imagen debe tener proporción %s. Tu imagen: %dx%d (proporción %.2f:1). " .
                "Ejemplos válidos:\n• Cuadrado: 800x800, 1000x1000\n• Horizontal: 800x600, 1200x900\n• Vertical: 600x800, 900x1200",
                implode(', ', $formatos),
                $width,
                $height,
                $ratio
            )
        ];
    }
    
    /**
     * Verificar si una imagen es válida (sin detalles)
     * 
     * @param array $file - Array del archivo
     * @param int $maxSize - Tamaño máximo
     * @return bool
     */
    public function isValid($file, $maxSize = 5242880) {
        $result = $this->validate($file, $maxSize);
        return $result['valid'];
    }
    
    /**
     * Obtener información de dimensiones de una imagen
     * 
     * @param string $tmpPath - Ruta temporal de la imagen
     * @return array|null - Dimensiones o null si falla
     */
    public function getDimensions($tmpPath) {
        $imageInfo = @getimagesize($tmpPath);
        if (!$imageInfo) {
            return null;
        }
        
        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'mime' => $imageInfo['mime']
        ];
    }
}