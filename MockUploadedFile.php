<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Is used for uploads saved between requests in the temporary location
 */
class MockUploadedFile extends UploadedFile
{
    /**
     * Omitted the $error argument, because this object is always created for a successfully uploaded file
     *
     * @param string      $path         The full temporary path to the file
     * @param string      $originalName The original file name
     * @param string|null $mimeType     The type of the file as provided by PHP; null defaults to
     *                                  application/octet-stream
     * @param int|null    $size         The file size
     *
     * @throws FileNotFoundException
     */
    public function __construct($path, $originalName, $mimeType = null, $size = null)
    {
        if (!is_file($path)) {
            throw new FileNotFoundException($path);
        }

        parent::__construct($path, $originalName, $mimeType, $size);
    }

    /**
     * Always returns true because this object is always created for a successfully uploaded file
     */
    public function isValid()
    {
        return true;
    }

    /**
     * Uses the original implementation from the Symfony\Component\HttpFoundation\File\File class
     */
    public function move($directory, $name = null)
    {
        return File::move($directory, $name);
    }
}
