<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
    public function move($directory, $name = null)
    {
        if ($this->isSaved()) {
            $this->savedFile->remove();
        }

        return parent::move($directory, $name);
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
     *
     * @throws FileException
     */
    public function save($pathname)
    {
        $path = dirname($pathname);

        if (!is_dir($path)) {
            if (false === @mkdir($path, 0777, true) && !is_dir($path)) {
                throw new FileException(sprintf('Unable to create the "%s" directory', $path));
            }
        } elseif (!is_writable($path)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory', $path));
        }

        if (!is_uploaded_file($this->getPathname())) {
            throw new FileException(sprintf(
                'Could not move the file "%s" to "%s" (not a valid uploaded file)',
                $this->getPathname(), $pathname
            ));
        }

        if (!@copy($this->getPathname(), $pathname)) {
            $error = error_get_last();
            /** @noinspection PhpParamsInspection */
            throw new FileException(sprintf(
                'Could not move the file "%s" to "%s" (%s)',
                $this->getPathname(), $pathname, strip_tags($error['message'])
            ));
        }

        @chmod($pathname, 0666 & ~umask());

        $this->savedFile = SavedUploadedFile::create(
            $pathname,
            $this->getClientOriginalName(),
            $this->getClientMimeType(),
            $this->getClientSize()
        );
    }
}
