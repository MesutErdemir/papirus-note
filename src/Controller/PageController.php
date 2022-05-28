<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\PapirusNote\AbstractPapirusController;
use App\PapirusNote\Exception\PageNotFound;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use LogicException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;

class PageController extends AbstractPapirusController
{
    /**
     * 
     * @param mixed $slug 
     * @return Response 
     * @throws ServiceNotFoundException 
     * @throws NotFoundExceptionInterface 
     * @throws ContainerExceptionInterface 
     * @throws LogicException 
     * @throws DirectoryNotFoundException 
     * @throws ParseException 
     */
    public function index($slug): Response
    {
        if (empty($slug)) {
            return $this->render($this->getParameter('papirus.theme') . '/home.html.twig');
        }

        try {
            return $this->render($this->getParameter('papirus.theme') . '/page.html.twig', [
                'papirusPage' => $this->papirusNote->getPapirusNotePage()->getPageBySlug($slug)
            ]);
        } catch (PageNotFound $e) {
            return $this->redirectToRoute('papirus_page');
        }
    }
}