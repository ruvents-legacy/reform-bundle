<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TmpFile extends File
{
    /**
     * @var UploadedFile
     */
    private $uploadedFile;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        parent::__construct($path, false);
    }

    /**
     * @return UploadedFile
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    /**
     * @param UploadedFile $uploadedFile
     *
     * @return $this
     */
    public function setUploadedFile(UploadedFile $uploadedFile)
    {
        $this->uploadedFile = $uploadedFile;

        return $this;
    }
}
