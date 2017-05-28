<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SavedUploadedFile extends UploadedFile
{
    /**
     * {@inheritdoc}
     */
    public function __construct($pathname, $originalName, $mimeType = null, $size = null)
    {
        parent::__construct($pathname, $originalName, $mimeType, $size);
    }

    /**
     * @param string $pathname
     *
     * @return null|self
     */
    public static function find($pathname)
    {
        if (!is_file($pathname)) {
            return null;
        }

        $metaPathname = self::metadataPathname($pathname);
        $metaData = is_file($metaPathname) ? (json_decode(file_get_contents($metaPathname), true) ?: []) : [];

        return new self(
            $pathname,
            isset($metaData['originalName']) ? $metaData['originalName'] : basename($pathname),
            isset($metaData['mimeType']) ? $metaData['mimeType'] : null,
            isset($metaData['size']) ? $metaData['size'] : null
        );
    }

    /**
     * @param string      $pathname
     * @param string      $originalName
     * @param string|null $mimeType
     * @param int|null    $size
     *
     * @return SavedUploadedFile
     * @throws \InvalidArgumentException
     */
    public static function create($pathname, $originalName, $mimeType = null, $size = null)
    {
        $metaPathname = self::metadataPathname($pathname);

        $metaData = [
            'originalName' => $originalName,
            'mimeType' => $mimeType,
            'size' => $size,
        ];

        file_put_contents($metaPathname, json_encode($metaData, JSON_UNESCAPED_UNICODE));

        return new self($pathname, $originalName, $mimeType, $size);
    }

    /**
     * @param string $pathname
     *
     * @return string
     */
    private static function metadataPathname($pathname)
    {
        return $pathname.'.json';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function move($directory, $name = null)
    {
        $this->removeMetadata();

        return File::move($directory, $name);
    }

    public function remove()
    {
        $this->removeMetadata();
        $this->isFile() && @unlink($this->getPathname());
    }

    private function removeMetadata()
    {
        is_file($metadataPathname = self::metadataPathname($this->getPathname())) && @unlink($metadataPathname);
    }
}
