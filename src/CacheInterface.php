<?php

namespace MattFerris\Cache;


interface CacheInterface
{
    public function has(string $id): bool;
    public function get(string $id);
    public function put(string $id, $contents, int $ttl = 3600);
}
