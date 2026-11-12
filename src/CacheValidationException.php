<?php

namespace MattFerris\Cache;

use Exception;


class CacheValidationException extends Exception
{
    protected $cacheId;


    /**
     * @param string $cacheId The ID of the cache entry
     */
    public function __construct(string $cacheId) {
        $this->cacheId = $cacheId;
        parent::__construct("unable to validate cached data for '{$cacheId}'");
    }


    /**
     * @return string The ID of the cache entry
     */
    public function getCacheId(): string {
        return $this->cacheId;
    }
}

