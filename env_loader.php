<?php
/**
 * Environment Configuration Loader
 * 
 * This file loads environment variables from the .env file
 * and makes them available via $_ENV superglobal.
 */

// Load .env file if it exists
function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        // Log warning but don't crash the application
        error_log("Warning: .env file not found at $path. Using default configuration.");
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes from value
            $value = trim($value, '"\'');
            
            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    return true;
}

// Helper function to get environment variable
function env($key, $default = null) {
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    
    return $default;
}

// Load environment variables
loadEnv();
?>
