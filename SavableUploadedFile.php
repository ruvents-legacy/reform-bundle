<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class SavableUploadedFile extends UploadedFile
{
    /**
     * @var SavedUploadedFile|null
     */
    private $savedFile = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($pathname, $originalName, $mimeType = null, $size = null, $error = null)
    {
        parent::__construct($pathname, $originalName, $mimeType, $size, $error);
    }

    public static function fromUploadedFile(UploadedFile $uploadedFile)
    {
        return new self(
            $uploadedFile->getRealPath(),
            $uploadedFile->getClientOriginalName(),
            $uploadedFile->getClientMimeType(),
            $uploadedFile->getClientSize(),
            $uploadedFile->getError()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return $this->isSaved() ? $this->savedFile->isValid() : parent::isValid();
    }

    /**
     * {@inheritdoc}
     */
    public function move($directory, $name = null)
    {
        return $this->isSaved() ? $this->savedFile->move($directory, $name) : parent::move($directory, $name);
    }

    /**
     * @return bool
     */
    public function isSaved()
    {
        return null !== $this->savedFile;
    }

    /**
     * @param string $pathname
     */
    public function save($pathname)
    {
        $file = parent::move(dirname($pathname), basename($pathname));

        $this->savedFile = SavedUploadedFile::create(
            $file->getPathname(),
            $this->getClientOriginalName(),
            $this->getClientMimeType(),
            $this->getClientSize()
        );
    }
}
