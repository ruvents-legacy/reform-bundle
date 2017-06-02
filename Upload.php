<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Upload
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var UploadedFile
     */
    private $file;

    /**
     * @var bool
     */
    private $saved = false;

    /**
     * @param string       $id
     * @param UploadedFile $file
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($id, UploadedFile $file)
    {
        if (!is_string($id)) {
            throw new \InvalidArgumentException(
                sprintf('Id must be string, %s given.', gettype($id))
            );
        }

        if (!$file->isFile()) {
            throw new \InvalidArgumentException(
                sprintf('File "%s" does not exist.', $file->getPathname())
            );
        }

        $this->id = $id;
        $this->file = $file;
        $this->saved = !is_uploaded_file($file->getPathname());
    }

    /**
     * @return string
     */
    public static function generateId()
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    /**
     * @param string $id
     *
     * @return null|Upload
     * @throws \InvalidArgumentException
     */
    public static function findById($id)
    {
        if (!is_string($id)) {
            throw new \InvalidArgumentException(
                sprintf('Id must be string, %s given.', gettype($id))
            );
        }

        $id = self::sanitizeId($id);

        if (!is_file($pathname = self::getTempDir().DIRECTORY_SEPARATOR.$id)) {
            return null;
        }

        $metadataPathname = $pathname.'.json';
        $metadata = [];

        if (is_file($metadataPathname)) {
            $metadata = json_decode(file_get_contents($metadataPathname), true);
        }

        $file = new SavedUploadedFile(
            $pathname,
            isset($metadata['client_original_name']) ? $metadata['client_original_name'] : $id,
            isset($metadata['client_mime_type']) ? $metadata['client_mime_type'] : null,
            isset($metadata['client_size']) ? $metadata['client_size'] : null
        );

        return new self($id, $file);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    private static function sanitizeId($id)
    {
        return preg_replace('/[^0-9a-zA-Z_-]+/', '', $id);
    }

    /**
     * @return string
     */
    private static function getTempDir()
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'ruvents_reform_upload';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->file;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return bool
     */
    public function isSaved()
    {
        return $this->saved;
    }

    public function save()
    {
        if ($this->saved) {
            return;
        }

        $file = $this->file->move(self::getTempDir(), $this->getId());

        $this->file = new SavedUploadedFile(
            $file->getPathname(),
            $this->file->getClientOriginalName(),
            $this->file->getClientMimeType(),
            $this->file->getClientSize()
        );

        $metadataPathname = $this->file->getPathname().'.json';
        $metadata = [
            'client_original_name' => $this->file->getClientOriginalName(),
            'client_mime_type' => $this->file->getClientMimeType(),
            'client_size' => $this->file->getClientSize(),
        ];

        file_put_contents($metadataPathname, json_encode($metadata, JSON_UNESCAPED_UNICODE));

        $this->saved = true;
    }
}
