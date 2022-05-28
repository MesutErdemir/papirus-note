<?php

namespace App\PapirusNote;

use App\PapirusNote;
use App\PapirusNote\Exception\UnableToParseMdFileHeader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractPapirusNote
{
    protected PapirusNote $papirusNote;

    /**
     * @param PapirusNote $papirusNote 
     * @return void 
     */
    function __construct(PapirusNote $papirusNote)
    {
        $this->papirusNote = $papirusNote;
    }

    /**
     * @param string $absoluteFilePath 
     * @return array 
     * @throws ParseException 
     * @throws UnableToParseMdFileHeader 
     */
    protected function parseMdFile(string $absoluteFilePath)
    {
        // Read file content
        $fileContent = file_get_contents($absoluteFilePath);

        // Parse file header
        preg_match("/\-\-\-(.*)\-\-\-/Uism", $fileContent, $fileContentHeader);
        $fileContentHeaderArr = Yaml::parse($fileContentHeader[1]);

        // Check for file header title and date attributes (required)
        if (!isset($fileContentHeaderArr['title']) || !isset($fileContentHeaderArr['date'])) {
            throw new UnableToParseMdFileHeader;
        }

        // Remove file header
        $fileContentBody = preg_replace("/\-\-\-(.*)\-\-\-/Uism", "", $fileContent, 1);

        return [
            'headers' => $fileContentHeaderArr,
            'content' => $fileContentBody,
        ];
    }
}
