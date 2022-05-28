<?php

namespace App\PapirusNote;

use App\PapirusNote\Exception\PageNotFound;
use App\PapirusNote\Exception\UnableToParseMdFileHeader;
use Symfony\Component\Finder\Finder;
use Cocur\Slugify\Slugify;
use LogicException;
use Parsedown;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;

class Page extends AbstractPapirusNote
{
    /**
     * @param mixed $pageSlug 
     * @return array 
     * @throws DirectoryNotFoundException 
     * @throws LogicException 
     * @throws ParseException 
     * @throws PostNotFound 
     */
    public function getPageBySlug($pageSlug): array
    {
        $cachedPage = $this->papirusNote
            ->getCache()
            ->getItem('page.' . $pageSlug);

        if (!$cachedPage->isHit()) {
            $pageList = $this->buildCache();

            $foundPage = array_search($pageSlug, array_column($pageList, 'slug'));
            if ($foundPage === false) {
                throw new PageNotFound;
            }
        }

        return $this->papirusNote
            ->getCache()
            ->getItem('page.' . $pageSlug)
            ->get();
    }

    /**
     * @return array 
     * @throws DirectoryNotFoundException 
     * @throws LogicException 
     * @throws ParseException 
     */
    public function buildCache(): array
    {
        $cachedPageList = $this->papirusNote
            ->getCache()
            ->getItem('pages');

        // Find files in folder
        $finder = new Finder();
        $finder->files()->in($this->papirusNote->getContentPath() . "page");

        $cachedPages = [];
        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();

            // Cache the post
            $md5File = md5_file($absoluteFilePath);

            $searchByFilepathIndex = array_search(
                $absoluteFilePath,
                array_column(($cachedPageList->get()['pages'] ?? []), 'filepath')
            );

            if (
                $searchByFilepathIndex !== false
                && $cachedPageList->get()['pages'][$searchByFilepathIndex]['md5sum'] == $md5File
            ) {
                continue;
            }

            try {
                $parsedMdFile = $this->parseMdFile($absoluteFilePath);
            } catch (UnableToParseMdFileHeader $e) {
                continue;
            }

            // Slugify title
            $pageSlug = (new Slugify())->slugify($parsedMdFile['headers']['title']);

            // Parse file body
            $htmlContent = (new Parsedown())->text($parsedMdFile['content']);

            $cachedPage = $this->papirusNote
                ->getCache()
                ->getItem('page.' . $pageSlug);

            $cachedPage->set([
                'headers' => $parsedMdFile['headers'],
                'body' => $htmlContent,
                'slug' => $pageSlug,
                'size' => $file->getSize(),
                'filepath' => $absoluteFilePath,
                'md5sum' => $md5File
            ]);

            // Save the cache
            $this->papirusNote
                ->getCache()
                ->save($cachedPage);

            // Set cached posts
            $cachedPages[] = [
                'date' => $parsedMdFile['headers']['date'],
                'title' => $parsedMdFile['headers']['title'],
                'slug' => $pageSlug,
                'size' => $file->getSize(),
                'filepath' => $absoluteFilePath,
                'md5sum' => $md5File
            ];
        }

        // Sort cached pages array by date
        usort($cachedPages, function($page1, $page2) {
            return $page2['date'] - $page1['date'];
        });

        // Save cached pages data
        $cachedPageList->set([
            'cachedTime' => time(),
            'pages' => $cachedPages
        ]);

        $this->papirusNote
            ->getCache()
            ->save($cachedPageList);

        // Return cached pages
        return $cachedPages;
    }
}
