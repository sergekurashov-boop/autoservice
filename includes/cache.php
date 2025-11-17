<?php
// includes/cache.php
define('AUTOSERVICE_SECURE', true);
class Cache {
    private $cache_dir;
    private $ttl;
    
    public function __construct($cache_dir = 'cache/', $ttl = 3600) {
        $this->cache_dir = $cache_dir;
        $this->ttl = $ttl;
        $this->ensureCacheDir();
    }
    
    private function ensureCacheDir() {
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    public function get($key) {
        $file = $this->cache_dir . md5($key) . '.cache';
        
        if (file_exists($file) && (time() - filemtime($file)) < $this->ttl) {
            return unserialize(file_get_contents($file));
        }
        
        return false;
    }
    
    public function set($key, $data) {
        $file = $this->cache_dir . md5($key) . '.cache';
        return file_put_contents($file, serialize($data));
    }
    
    public function delete($key) {
        $file = $this->cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }
    
    public function clear() {
        $files = glob($this->cache_dir . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

// Глобальный кэш
$cache = new Cache();
?>