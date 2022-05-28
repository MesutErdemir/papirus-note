<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\PapirusNote\AbstractPapirusController;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use LogicException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

class PostsController extends AbstractPapirusController
{
    /**
     * 
     * @param mixed $page 
     * @return Response 
     * @throws DirectoryNotFoundException 
     * @throws LogicException 
     * @throws ParseException 
     * @throws ServiceNotFoundException 
     * @throws NotFoundExceptionInterface 
     * @throws ContainerExceptionInterface 
     */
    public function index($page): Response
    {
        // Group Yearly
        $papirusPostsByYearly = [];
        $papirusPosts = $this->papirusNote->getPapirusNotePost()->getPostList($page);
        foreach ($papirusPosts['posts'] as $post) {
            $postYear = date('Y', $post['headers']['date']);
            
            $papirusPostsByYearly[$postYear][] = $post;
        }

        $papirusPosts['posts'] = $papirusPostsByYearly;

        return $this->render($this->getParameter('papirus.theme') . '/post-list.html.twig', [
            'papirusPosts' => $papirusPosts
        ]);
    }
}
