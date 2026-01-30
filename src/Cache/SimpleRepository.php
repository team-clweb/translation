<?php

namespace Waavi\Translation\Cache;

use Illuminate\Contracts\Cache\Store;

class SimpleRepository implements CacheRepositoryInterface
{
    /**
     * The cache store implementation.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The cache prefix for all translation keys.
     *
     * @var string
     */
    protected $cachePrefix;

    /**
     * The key used to store the registry of all translation cache keys.
     *
     * @var string
     */
    protected $registryKey;

    /**
     * Create a new cache repository instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  string  $cachePrefix
     * @return void
     */
    public function __construct(Store $store, $cachePrefix)
    {
        $this->store = $store;
        $this->cachePrefix = $cachePrefix;
        $this->registryKey = $cachePrefix . '_registry';
    }

    /**
     * Generate a unique cache key for the given locale, group, and namespace.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return string
     */
    protected function generateKey($locale, $group, $namespace)
    {
        return $this->cachePrefix . '_' . md5("{$locale}-{$group}-{$namespace}");
    }

    /**
     * Get all registered cache keys from the registry.
     *
     * @return array
     */
    protected function getRegistry(): array
    {
        return $this->store->get($this->registryKey) ?? [];
    }

    /**
     * Save the registry to the cache.
     *
     * @param  array  $registry
     * @return void
     */
    protected function saveRegistry(array $registry): void
    {
        $this->store->forever($this->registryKey, $registry);
    }

    /**
     * Add a key to the registry.
     *
     * @param  string  $key
     * @return void
     */
    protected function addToRegistry(string $key): void
    {
        $registry = $this->getRegistry();
        if (!in_array($key, $registry)) {
            $registry[] = $key;
            $this->saveRegistry($registry);
        }
    }

    /**
     * Remove a key from the registry.
     *
     * @param  string  $key
     * @return void
     */
    protected function removeFromRegistry(string $key): void
    {
        $registry = $this->getRegistry();
        $registry = array_values(array_filter($registry, fn($k) => $k !== $key));
        $this->saveRegistry($registry);
    }

    /**
     * Checks if an entry with the given key exists in the cache.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return boolean
     */
    public function has($locale, $group, $namespace)
    {
        return !is_null($this->get($locale, $group, $namespace));
    }

    /**
     * Get an item from the cache.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return mixed
     */
    public function get($locale, $group, $namespace)
    {
        $key = $this->generateKey($locale, $group, $namespace);
        return $this->store->get($key);
    }

    /**
     * Put an item into the cache store.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @param  mixed   $content
     * @param  integer $minutes
     * @return void
     */
    public function put($locale, $group, $namespace, $content, $minutes)
    {
        $key = $this->generateKey($locale, $group, $namespace);
        $this->addToRegistry($key);
        // Laravel 10: put() expects seconds, not minutes
        $this->store->put($key, $content, $minutes * 60);
    }

    /**
     * Flush a specific cache entry.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return void
     */
    public function flush($locale, $group, $namespace)
    {
        $key = $this->generateKey($locale, $group, $namespace);
        $this->store->forget($key);
        $this->removeFromRegistry($key);
    }

    /**
     * Flush all translation cache entries (without affecting other cache).
     *
     * @return void
     */
    public function flushAll()
    {
        $registry = $this->getRegistry();
        foreach ($registry as $key) {
            $this->store->forget($key);
        }
        $this->store->forget($this->registryKey);
    }
}
