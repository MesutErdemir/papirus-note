<?php

namespace App\PapirusNote;

use App\Service\PapirusNote;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractPapirusController extends AbstractController
{
    protected $papirusNote;

    /**
     * @param PapirusNote $papirusNote 
     * @return void 
     */
    function __construct(PapirusNote $papirusNote)
    {
        $this->papirusNote = $papirusNote;
    }
}
