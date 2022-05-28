<?php

namespace App\PapirusNote;

use App\PapirusNote\Exception\PostNotFound;
use App\PapirusNote\Exception\UnableToParseMdFileHeader;
use Symfony\Component\Finder\Finder;
use Cocur\Slugify\Slugify;
use LogicException;
use Parsedown;
use ArrayIterator;
use LimitIterator;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;

class Post extends AbstractPapirusNote
{
    /**
     * @param int $page 
     * @param int $perPage 
     * @return array 
     * @throws DirectoryNotFoundException 
     * @throws LogicException 
     * @throws ParseException 
     */
    public function getPostList($page = 1, $perPage = 10): array
    {
        $cachedPostList = $this->papirusNote
            ->getCache()
            ->getItem('posts');

        $postList = [];
        if (!$cachedPostList->isHit()) {
            $postList = $this->buildCache();
        }
        else {
            $postList = $cachedPostList->get()['posts'];
        }
        
        $postsIterator = new ArrayIterator($postList);
        $postsArr = [];
        foreach (new LimitIterator($postsIterator, ($page - 1) * $perPage, $perPage) as $post) {
            $postsArr[] = $this->getPostBySlug($post['slug']);
        }

        return [
            'posts' => $postsArr,
            'totalPages' => ceil(count($postList) / $perPage),
            'currentPage' => $page
        ];
    }

    /**
     * 
     * @param string $postSlug 
     * @return array 
     * @throws DirectoryNotFoundException 
     * @throws LogicException 
     * @throws ParseException 
     * @throws PostNotFound 
     */
    public function getPostSummaryData(string $postSlug): array
    {
        $cachedPostList = $this->papirusNote
            ->getCache()
            ->getItem('posts');

        $postList = [];
        if (!$cachedPostList->isHit()) {
            $postList = $this->buildCache();
        }
        else {
            $postList = $cachedPostList->get()['posts'];
        }

        $foundPostIndex = array_search($postSlug, array_column($postList, 'slug'));
        if ($foundPostIndex === false) {
            throw new PostNotFound;
        }

        return $postList[$foundPostIndex];
    }

    /**
     * @param mixed $postSlug 
     * @return array 
     * @throws DirectoryNotFoundException 
     * @throws LogicException 
     * @throws ParseException 
     * @throws PostNotFound 
     */
    public function getPostBySlug($postSlug): array
    {
        $cachedPost = $this->papirusNote
            ->getCache()
            ->getItem('post.' . $postSlug);

        if (!$cachedPost->isHit()) {
            $postList = $this->buildCache();

            $foundPostIndex = array_search($postSlug, array_column($postList, 'slug'));
            if ($foundPostIndex === false) {
                throw new PostNotFound;
            }
        }

        return $this->papirusNote
            ->getCache()
            ->getItem('post.' . $postSlug)
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
        $cachedPostList = $this->papirusNote
            ->getCache()
            ->getItem('posts');
        
        // Find files in folder
        $finder = new Finder();
        $finder->files()->in($this->papirusNote->getContentPath() . "post");

        $cachedPosts = [];
        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();

            // Cache the post
            $md5File = md5_file($absoluteFilePath);

            $searchByFilepathIndex = array_search(
                $absoluteFilePath,
                array_column(($cachedPostList->get()['posts'] ?? []), 'filepath')
            );

            if (
                $searchByFilepathIndex !== false
                && $cachedPostList->get()['posts'][$searchByFilepathIndex]['md5sum'] == $md5File
            ) {
                continue;
            }

            try {
                $parsedMdFile = $this->parseMdFile($absoluteFilePath);
            } catch (UnableToParseMdFileHeader $e) {
                continue;
            }

            // Slugify title
            $postSlug = (new Slugify())->slugify($parsedMdFile['headers']['title']);

            // Parse file body
            $htmlContent = (new Parsedown())->text($parsedMdFile['content']);

            $cachedPost = $this->papirusNote
                ->getCache()
                ->getItem('post.' . $postSlug);

            $cachedPost->set([
                'headers' => $parsedMdFile['headers'],
                'body' => $htmlContent,
                'slug' => $postSlug,
                'size' => $file->getSize(),
                'filepath' => $absoluteFilePath,
                'md5sum' => $md5File
            ]);

            // Save the cache
            $this->papirusNote
                ->getCache()
                ->save($cachedPost);

            // Set cached posts
            $cachedPosts[] = [
                'date' => $parsedMdFile['headers']['date'],
                'title' => $parsedMdFile['headers']['title'],
                'slug' => $postSlug,
                'size' => $file->getSize(),
                'filepath' => $absoluteFilePath,
                'md5sum' => $md5File
            ];
        }

        // Sort cached posts array by date
        usort($cachedPosts, function($post1, $post2) {
            return $post2['date'] - $post1['date'];
        });

        // Get Previous and Next Posts Info
        foreach (array_keys($cachedPosts) as $postIndex) {
            $cachedPosts[$postIndex]['extra_data']['previous_post'] = $cachedPosts[$postIndex+1] ?? null;
            $cachedPosts[$postIndex]['extra_data']['next_post'] = $cachedPosts[$postIndex-1] ?? null;
        }

        // Save cached posts data
        $cachedPostList->set([
            'cachedTime' => time(),
            'posts' => $cachedPosts
        ]);

        $this->papirusNote
            ->getCache()
            ->save($cachedPostList);

        // Return cached posts
        return $cachedPosts;
    }
}
