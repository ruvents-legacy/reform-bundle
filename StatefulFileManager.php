<?php

namespace Ruvents\ReformBundle;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StatefulFileManager
{
    /**
     * @var string
     */
    private $dir;

    /**
     * @var array
     */
    private $uploadedFilesInfo = [];

    /**
     * @param string $dir
     */
    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * @param FormInterface $form
     * @param UploadedFile  $uploadedFile
     */
    public function registerNewUploadedFile(FormInterface $form, UploadedFile $uploadedFile)
    {
        $this->uploadedFilesInfo[] = [$form, $uploadedFile];
    }

    /**
     * @param FormInterface $form
     *
     * @return UploadedFile|null
     */
    public function findFile(FormInterface $form)
    {
        $pathname = $this->getPathname($form);

        if (!is_file($pathname)) {
            return null;
        }

        $metadata = $this->findMetadata($pathname);

        return new UploadedFile(
            $pathname,
            isset($metadata['client_original_name']) ? $metadata['client_original_name'] : basename($pathname),
            isset($metadata['client_mime_type']) ? $metadata['client_mime_type'] : null,
            isset($metadata['client_size']) ? $metadata['client_size'] : null,
            UPLOAD_ERR_OK,
            true
        );
    }

    /**
     * @param FormInterface $form
     */
    public function removeFile(FormInterface $form)
    {
        $pathname = $this->getPathname($form);
        $metadataPathname = $this->getMetadataPathname($pathname);

        if (is_file($pathname)) {
            @unlink($pathname);
        }

        if (is_file($metadataPathname)) {
            @unlink($metadataPathname);
        }
    }

    public function saveFiles()
    {
        foreach ($this->uploadedFilesInfo as $info) {
            /**
             * @var FormInterface $form
             * @var UploadedFile  $uploadedFile
             */
            list($form, $uploadedFile) = $info;

            if ($form->isValid()) {
                $pathname = $this->getPathname($form);
                $this->copyUploadedFile($uploadedFile, $pathname);
                $this->saveMetadata($uploadedFile, $pathname);
            }
        }
    }

    /**
     * @param FormInterface $form
     *
     * @return string
     */
    private function getPathname(FormInterface $form)
    {
        $pathname = $form->getName();

        while (!$form->isRoot()) {
            $form = $form->getParent();
            $pathname = $form->getName().'_'.$pathname;
        }

        return rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.$this->dir.DIRECTORY_SEPARATOR.$pathname;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string       $pathname
     *
     * @throws FileException
     */
    private function copyUploadedFile(UploadedFile $uploadedFile, $pathname)
    {
        if (!$uploadedFile->isValid()) {
            throw new FileException($uploadedFile->getErrorMessage());
        }

        if (!is_uploaded_file($uploadedFile->getPathname())) {
            throw new FileException(
                sprintf('"%s" is not an uploaded file.', $uploadedFile->$this->getPathname())
            );
        }

        $dir = dirname($pathname);

        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new FileException(sprintf('Unable to create the "%s" directory', $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory', $dir));
        }

        if (!@copy($uploadedFile->getPathname(), $pathname)) {
            throw new FileException(
                sprintf('Could not move the file "%s" to "%s" (%s)',
                    $uploadedFile->getPathname(),
                    $pathname,
                    null === error_get_last() ? 'no error' : strip_tags(error_get_last()['message'])
                )
            );
        }

        @chmod($pathname, 0666 & ~umask());
    }

    /**
     * @param string $pathname
     *
     * @return string
     */
    private function getMetadataPathname($pathname)
    {
        return $pathname.'.json';
    }

    /**
     * @param string $pathname
     *
     * @return array
     */
    private function findMetadata($pathname)
    {
        $metadataPathname = $this->getMetadataPathname($pathname);
        $metadata = [];

        if (is_file($metadataPathname)) {
            $metadata = json_decode(file_get_contents($metadataPathname), true) ?: [];
        }

        return $metadata;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string       $pathname
     */
    private function saveMetadata(UploadedFile $uploadedFile, $pathname)
    {
        $metadataPathname = $this->getMetadataPathname($pathname);
        $metadata = [
            'client_original_name' => $uploadedFile->getClientOriginalName(),
            'client_mime_type' => $uploadedFile->getClientMimeType(),
            'client_size' => $uploadedFile->getClientSize(),
        ];
        file_put_contents($metadataPathname, json_encode($metadata, JSON_UNESCAPED_UNICODE));
    }
}
