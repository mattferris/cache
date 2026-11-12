<?php

namespace MattFerris\Cache;

use Exception;


class CacheIdNotFoundException extends Exception
{
    protected $cacheId;


    /**
     * @param string $cacheId The ID of the cache entry
     */
    public function __construct(string $cacheId) {
        $this->cacheId = $cacheId;
        parent::__construct("no cache entry found for '{$id}'");
    }


    /**
     * @return string The ID of the cache entry
     */
    public function getCacheId(): string {
        return $this->cacheId;
    }
}

