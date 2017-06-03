<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SavedUploadedFile extends UploadedFile
{
    /**
     * {@inheritdoc}
     */
    public function __construct($path, $originalName, $mimeType = null, $size = null)
    {
        parent::__construct($path, $originalName, $mimeType, $size);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return $this->isFile();
    }

    /**
     * {@inheritdoc}
     */
    public function move($directory, $name = null)
    {
        return File::move($directory, $name);
    }

    public function remove()
    {
        if ($this->isFile()) {
            @unlink($this->getPathname());
        }
    }
}
