<?php

class Fitxategia {
    
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    private const MAX_FILE_SIZE = 5242880;
    
    public static function igoBalioztatuta(array $file, string $uploadDir = 'storage/uploads/fotos'): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'path' => null, 'error' => 'Fitxategi formatu okerra.'];
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'path' => null, 'error' => 'Fitxategia handiegia da (max 5MB).'];
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'path' => null, 'error' => 'Ez da fitxategirik igo.'];
            default:
                return ['success' => false, 'path' => null, 'error' => 'Errore ezezaguna.'];
        }
        
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'path' => null, 'error' => 'Fitxategia handiegia da (max 5MB).'];
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true)) {
            return ['success' => false, 'path' => null, 'error' => 'Fitxategi mota ez da onartzen (JPEG, PNG, GIF, WebP bakarrik).'];
        }
        
        $fullUploadDir = __DIR__ . '/../' . $uploadDir;
        if (!is_dir($fullUploadDir)) {
            if (!mkdir($fullUploadDir, 0755, true)) {
                error_log("Failed to create upload directory: {$fullUploadDir}");
                return ['success' => false, 'path' => null, 'error' => 'Ezin izan da karpeta sortu.'];
            }
        }
        
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'bin'
        };
        
        $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
        $destination = $fullUploadDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            error_log("Failed to move uploaded file to: {$destination}");
            return ['success' => false, 'path' => null, 'error' => 'Ezin izan da fitxategia gorde.'];
        }
        
        chmod($destination, 0644);
        
        $relativePath = '/' . $uploadDir . '/' . $filename;
        
        return ['success' => true, 'path' => $relativePath, 'error' => null];
    }
    
    public static function ezabatu(string $path): bool {
        if (empty($path)) return false;
        
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $fullPath = __DIR__ . '/../' . ltrim($path, '/');
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    public static function balioztaDimentsioak(string $tmpPath, int $maxWidth = 2000, int $maxHeight = 2000): bool {
        $imageInfo = getimagesize($tmpPath);
        if (!$imageInfo) return false;
        
        [$width, $height] = $imageInfo;
        return $width <= $maxWidth && $height <= $maxHeight;
    }
}
