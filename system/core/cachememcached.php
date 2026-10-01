<?php
class cmsCacheMemcached {

    /**
     * @var Memcached
     */
    private $memcached;
    /**
     * @var string
     */
    private $site_namespace = '';
    /**
     * @var cmsConfigs
     */
    private $config;

    public function isDependencySatisfied() {
        return extension_loaded('memcached') && class_exists('Memcached');
    }

    public function __construct(cmsConfigs $config) {

        $this->config = $config;

        $this->site_namespace = 'instantcms.' . sprintf('%u', crc32($config->host));
    }

    public function set($key, $value, $ttl) {
        return $this->memcached->set($this->getKey($key), serialize($value), $ttl);
    }

    public function has($key) {
        return true;
    }

    public function get($key) {

        $value = $this->memcached->get($this->getKey($key));
        if (!$value) {
            return false;
        }

        return unserialize($value);
    }

    public function clean($ns = false) {

        if ($ns) {

            return $this->memcached->increment($this->getNamespaceKey($ns));

        } else {

            return $this->memcached->flush();
        }
    }

    public function start() {

        $this->memcached = new Memcached();

        if (!$this->memcached->addServer($this->config->cache_host, $this->config->cache_port)) {

            throw new Exception('Memcached connect error');
        }

        return true;
    }

    public function stop() {

        $this->memcached->quit();

        return true;
    }

    public function testConnection() {

        $this->memcached->set('ping', 'pong', 10);

        return $this->memcached->get('ping') === 'pong' ? 1 : 0;
    }

    public function getStats() {
        return $this->memcached->getStats();
    }

    private function getKey($_key) {

        $last_dot_pos = strrpos($_key, '.');

        if ($last_dot_pos === false) {

            $key = $_key;
            $ns  = '';

        } else {

            $key = substr($_key, $last_dot_pos + 1);
            $ns  = substr($_key, 0, $last_dot_pos);
        }

        $ns_value = $this->getNamespaceValue($ns);

        return "{$this->site_namespace}.{$ns_value}.{$ns}.{$key}";
    }

    private function getNamespaceValue($ns) {

        $namespace_key = $this->getNamespaceKey($ns);

        $ns_value = $this->memcached->get($namespace_key);

        if ($ns_value === false) {

            $ns_value = time();

            $this->memcached->set($namespace_key, $ns_value, 86400);
        }

        return $ns_value;
    }

    private function getNamespaceKey($ns) {
        return $this->site_namespace . '.namespace:' . $ns;
    }

}
