<?php

namespace MattFerris\Cache;

use DateTime;
use MattFerris\Cache\CacheInterface;


/**
 * Use the filesystem to cache rendered and compiled templates
 */
class FileCache implements CacheInterface
{
    protected $path;
    protected $split = 0;
    protected $keymap = [];
    protected $ttl = 3600;


    /**
     * @param string $path Path to the cache folder
     * @param int $split The number of levels of subfolders to split the keys across
     */
    public function __construct(string $path, int $split = 0, int $ttl = 3600) {
        $this->path = $path;
        $this->split = $split;
        $this->ttl = $ttl;
    }


    protected function key(string $id): string {
        $key = sha1($id);
        $prefix = '';
        for ($i=0; $i<$this->split; $i+=2) {
            $prefix .= substr($key, $i, 2).DIRECTORY_SEPARATOR;
        }
        return $prefix.$key;
    }


    protected function mkpath(string $file) {
        $suffix = substr(dirname($file), strlen($this->path)+1);
        $parts = explode(DIRECTORY_SEPARATOR, $suffix);
        $path = $this->path;
        foreach ($parts as $p) {
            $path .= DIRECTORY_SEPARATOR.$p;
            if (!file_exists($path)) mkdir($path);
        }
    }


    /**
     * Determine if an date is in the past
     *
     * @param DateTime $date The datetime to check
     * @return bool True if the datetime is in the past, otherwise false
     */
    protected function isExpired(DateTime $date): bool {
        $now = new DateTime();
        $diff = $date->diff($now);
        if ($diff->invert === 0) return true;
        return false;
    }


    /**
     * Check if the cache has a fresh entry for the given ID. This method will
     * return false if the cache has an entry for the ID, but the entry is
     * stale (has expired).
     *
     * @param string $id The entry ID to check
     * @return bool True if there is a fresh entry, otherwise false
     */
    public function has(string $id): bool {
        $file = $this->path.DIRECTORY_SEPARATOR.$this->key($id);
        if (!file_exists($file)) return false;

        $fh = fopen($file, 'r');
        flock($fh, LOCK_SH);
        $date = fread($fh, 25);
        flock($fh, LOCK_UN);
        fclose($fh);

        if ($this->isExpired(new DateTime($date))) return false;

        return true;
    }


    /**
     * Get a cache entry
     *
     * @param string $id The entry ID to get
     * @return string The cache contents
     */
    public function get(string $id) {
        if (!$this->has($id)) {
            //throw new CacheIdNotFoundException($id);
            return null;
        }
     
        $file = $this->path.DIRECTORY_SEPARATOR.$this->key($id);

        $fh = fopen($file, 'r');
        flock($fh, LOCK_SH);
        $date = fread($fh, 25);

        if ($this->isExpired(new DateTime($date))) {
             flock($fh, LOCK_UN);
             fclose($fh);
             throw new CacheExpiredException($id);
        }

        $encoding = fread($fh, 1);
        $csum = fread($fh, 32);

        $contents = '';
        while (!feof($fh)) {
            $contents .= fread($fh, 4096);
        }

        flock($fh, LOCK_UN);
        fclose($fh);

        if (md5($contents) !== $csum) {
            throw new CacheValidationException($id);
        }

        switch ($encoding) {
            case 's':
                $contents = unserialize($contents);
                break;
            case 'p':
                break;
        }

        return $contents;
    }


    /**
     * Cache content under a given ID, optionally setting a TTL for the content
     *
     * @param string $id The cache ID
     * @param mixed $contents The content to cache
     * @param int $ttl The number of seconds until the entry expires
     */
    public function put(string $id, $contents, int $ttl = 0) {
        $file = $this->path.DIRECTORY_SEPARATOR.$this->key($id);
        $encoding = 'p';

        if ($ttl === 0) $ttl = $this->ttl;

        if (!is_string($contents)) {
            $contents = serialize($contents);
            $encoding = 's';
        }

        $date = (new DateTime("+$ttl seconds"))->format(DATE_ATOM);
        $csum = sprintf('%s', md5($contents));

        if ($this->split > 0 && !file_exists(dirname($file))) $this->mkpath($file);

        file_put_contents("{$file}.tmp", $date.$encoding.$csum.$contents);
        rename("{$file}.tmp", $file);
    }


    public function evict(string $id) {
        $file = $this->path.DIRECTORY_SEPARATOR.$this->key($id);
        if (file_exists($file)) unlink($file);
    }
}

