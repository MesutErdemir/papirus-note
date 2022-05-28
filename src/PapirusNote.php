<?php

namespace App;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class PapirusNote
{
    protected object $cache;
    protected string $contentPath = __DIR__ . "/../content/";
    protected string $cachePath = __DIR__ . "/../var/cache/";

    function __construct()
    {
        $this->cache = new FilesystemAdapter(
            'papirus-note',
            600,
            $this->cachePath
        );
        
        $this->cache->prune();
    }

    /**
     * @return object 
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return string 
     */
    public function getContentPath()
    {
        return $this->contentPath;
    }
}
