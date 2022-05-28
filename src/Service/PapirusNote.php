<?php

namespace App\Service;

use App\PapirusNote as AppPapirusNote;
use App\PapirusNote\Page;
use App\PapirusNote\Post;

class PapirusNote
{
    protected $papirusNoteObject;

    public function __construct()
    {
        $this->papirusNoteObject = new AppPapirusNote;
    }

    /**
     * @return Post 
     */
    public function getPapirusNotePost(): Post
    {
        return (new Post($this->papirusNoteObject));
    }

    /**
     * @return Page 
     */
    public function getPapirusNotePage(): Page
    {
        return (new Page($this->papirusNoteObject));
    }
}
