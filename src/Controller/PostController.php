<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\PapirusNote\AbstractPapirusController;
use App\PapirusNote\Exception\PostNotFound;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use LogicException;
use Symfony\Component\Yaml\Exception\ParseException;

class PostController extends AbstractPapirusController
{
    /**
     * 
     * @param mixed $slug 
     * @return Response 
     * @throws NotFoundExceptionInterface 
     * @throws ContainerExceptionInterface 
     * @throws ServiceNotFoundException 
     * @throws DirectoryNotFoundException 
     * @throws LogicException 
     * @throws ParseException 
     */
    public function index($slug): Response
    {
        try {
            return $this->render($this->getParameter('papirus.theme') . '/post.html.twig', [
                'papirusPost' => $this->papirusNote->getPapirusNotePost()->getPostBySlug($slug),
                'papirusPostSummary' => $this->papirusNote->getPapirusNotePost()->getPostSummaryData($slug),
            ]);
        } catch (PostNotFound $e) {
            return $this->redirectToRoute('papirus_page');
        }
    }
}
