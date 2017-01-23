<?php

namespace Ruvents\ReformBundle\Helper;

use Ruvents\ReformBundle\MockUploadedFile;
use Ruvents\ReformBundle\TmpFile;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHelper
{
    /**
     * @var FormInterface[][]
     */
    private $uploadFormsByRootFormHash = [];

    /**
     * @param FormInterface $uploadForm
     */
    public function registerUploadForm(FormInterface $uploadForm)
    {
        $rootForm = $uploadForm;

        while (!$rootForm->isRoot()) {
            $rootForm = $rootForm->getParent();
        }

        $hash = $this->getFormHash($rootForm);
        $this->uploadFormsByRootFormHash[$hash][] = $uploadForm;
    }

    /**
     * @param FormInterface $rootForm
     */
    public function processValidatedRootForm(FormInterface $rootForm)
    {
        $hash = $this->getFormHash($rootForm);

        if (empty($this->uploadFormsByRootFormHash[$hash])) {
            return;
        }

        foreach ($this->uploadFormsByRootFormHash[$hash] as $form) {
            $tmpFile = $form->getData();

            if ($form->isValid() && $tmpFile instanceof TmpFile) {
                if ($uploadedFile = $tmpFile->getUploadedFile()) {
                    $this->saveUploadedFile($uploadedFile, $tmpFile->getPathname());
                }
            }
        }
    }

    /**
     * @param string $pathname
     *
     * @return null|MockUploadedFile
     */
    public function createMockUploadedFile($pathname)
    {
        if (!is_file($pathname)) {
            return null;
        }

        $metaPathname = $pathname.'.json';
        $meta = is_file($pathname)
            ? json_decode(file_get_contents($metaPathname), true)
            : [];

        return new MockUploadedFile(
            $pathname,
            isset($meta['originalName']) ? $meta['originalName'] : basename($pathname),
            isset($meta['mimeType']) ? $meta['mimeType'] : null,
            isset($meta['size']) ? $meta['size'] : null
        );
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string       $pathname
     */
    public function saveUploadedFile(UploadedFile $uploadedFile, $pathname)
    {
        $path = dirname($pathname);
        $basename = basename($pathname);

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $metaPathname = $pathname.'.json';
        $meta = [
            'originalName' => $uploadedFile->getClientOriginalName(),
            'mimeType' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getClientSize(),
        ];

        file_put_contents($metaPathname, json_encode($meta));

        $uploadedFile->move($path, $basename);
    }

    /**
     * @param FormInterface $form
     *
     * @return string
     */
    private function getFormHash(FormInterface $form)
    {
        return spl_object_hash($form);
    }
}
