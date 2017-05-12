<?php

namespace Ruvents\ReformBundle\Form\Type;

use Ruvents\ReformBundle\MockUploadedFile;
use Ruvents\ReformBundle\Upload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UploadType extends AbstractType
{
    /**
     * @var string
     */
    private $defaultPath;

    /**
     * @var FormInterface[][]
     */
    private $formsByRootFormHash = [];

    /**
     * @param string $defaultPath
     */
    public function __construct($defaultPath)
    {
        $this->defaultPath = $defaultPath;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['file_options']['required'] = $options['required'];

        $builder
            ->add('name', $options['name_type'], $options['name_options'])
            ->add('file', $options['file_type'], $options['file_options'])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                $name = trim($data['name']);

                if ('' === $name || 0 < preg_match('#[/\\\]+#', $name)) {
                    $name = null;
                }

                $file = $data['file'];

                if (!$file instanceof UploadedFile || !$file->isValid()) {
                    $file = null;
                }

                if (null === $file) {
                    if (null === $name) {
                        return;
                    }

                    $path = $form->getConfig()->getOption('path');
                    $file = $this->getMockUploadedFile($name, $path);

                    if (null === $file) {
                        return;
                    }
                } else {
                    $name = null;
                }

                // TODO: remove old MockUploadedFile

                if (null === $name) {
                    $ext = $file->guessExtension();
                    $name = sha1(uniqid(get_class($this), true)).($ext ? '.'.$ext : '');
                }

                $data = is_array($data) ? $data : [];
                $data['name'] = $name;
                $data['file'] = $file;

                $event->setData($data);

                if (null === $form->getData()) {
                    $dataClass = $form->getConfig()->getOption('data_class');
                    $form->setData(new $dataClass);
                }

                $this->registerUploadForm($form);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => Upload::class,
                'empty_data' => null,
                'error_bubbling' => false,
                'file_options' => [],
                'file_type' => FileType::class,
                'label' => false,
                'name_type' => HiddenType::class,
                'name_options' => [],
                'path' => $this->defaultPath,
            ])
            ->setAllowedTypes('file_type', 'string')
            ->setAllowedTypes('file_options', 'array')
            ->setAllowedTypes('name_type', 'string')
            ->setAllowedTypes('name_options', 'array')
            ->setAllowedTypes('path', 'string');
    }

    /**
     * @param FormInterface $rootForm
     */
    public function processValidatedRootForm(FormInterface $rootForm)
    {
        $hash = $this->getFormHash($rootForm);

        if (empty($this->formsByRootFormHash[$hash])) {
            return;
        }

        foreach ($this->formsByRootFormHash[$hash] as $form) {
            $upload = $form->getData();

            if ($form->isValid() && $upload instanceof Upload && $upload->getName() && $upload->getFile()) {
                $mock = $this->saveUploadedFile(
                    $upload->getFile(),
                    $upload->getName(),
                    $form->getConfig()->getOption('path')
                );

                $upload->setFile($mock);
            }
        }
    }

    /**
     * @param FormInterface $uploadForm
     */
    public function registerUploadForm(FormInterface $uploadForm)
    {
        $rootForm = $uploadForm;

        while (!$rootForm->isRoot()) {
            $rootForm = $rootForm->getParent();
        }

        $rootHash = $this->getFormHash($rootForm);
        $uploadHash = $this->getFormHash($uploadForm);
        $this->formsByRootFormHash[$rootHash][$uploadHash] = $uploadForm;
    }

    /**
     * @param string $name
     * @param string $path
     *
     * @return null|MockUploadedFile
     */
    private function getMockUploadedFile($name, $path)
    {
        $pathname = rtrim($path, '/').'/'.$name;

        if (!is_file($pathname)) {
            return null;
        }

        $metaPathname = $pathname.'.json';
        $meta = is_file($metaPathname)
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
     * @param string       $name
     * @param string       $path
     *
     * @return MockUploadedFile
     */
    private function saveUploadedFile(UploadedFile $uploadedFile, $name, $path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $metaPathname = rtrim($path, '/').'/'.$name.'.json';
        $meta = [
            'originalName' => $uploadedFile->getClientOriginalName(),
            'mimeType' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getClientSize(),
        ];

        file_put_contents($metaPathname, json_encode($meta));

        $file = $uploadedFile->move($path, $name);

        return new MockUploadedFile(
            $file->getPathname(),
            $uploadedFile->getClientOriginalName(),
            $uploadedFile->getClientMimeType(),
            $uploadedFile->getClientSize()
        );
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
