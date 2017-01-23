<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Is used for successful uploads saved between requests to the tmpDir
 */
class MockUploadedFile extends UploadedFile
{
    /**
     * Omitted the $error argument, because this object is always created
     * for a successfully uploaded TmpFile
     *
     * @see UploadedFile::move()
     *
     * @param string      $path         The full temporary path to the file
     * @param string      $originalName The original file name
     * @param string|null $mimeType     The type of the file as provided by PHP; null defaults to
     *                                  application/octet-stream
     * @param int|null    $size         The file size
     */
    public function __construct($path, $originalName, $mimeType = null, $size = null)
    {
        parent::__construct($path, $originalName, $mimeType, $size);
    }

    /**
     * Returns always true for the MockUploadedFile
     */
    public function isValid()
    {
        return true;
    }

    /**
     * Use the original move from the File class
     */
    public function move($directory, $name = null)
    {
        return File::move($directory, $name);
    }
}
