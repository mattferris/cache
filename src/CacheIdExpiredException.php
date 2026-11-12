<?php

namespace MattFerris\Cache;

use Exception;


class CacheIdExpiredException extends Exception
{
    protected $cacheId;


    /**
     * @param string $cacheId The ID of the cache entry
     */
    public function __construct(string $cacheId) {
        $this->cacheId = $cacheId;
        parent::__construct("stale cache for '{$cacheId}'");
    }


    /**
     * Return the cache ID
     *
     * @param string The ID of the cache entry
     */
    public function getCacheId(): string {
        return $this->cacheId;
    }
}

