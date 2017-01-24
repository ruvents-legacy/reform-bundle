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
    private $defaultTmpDir;

    /**
     * @var FormInterface[][]
     */
    private $formsByRootFormHash = [];

    /**
     * @param string $defaultTmpDir
     */
    public function __construct($defaultTmpDir)
    {
        $this->defaultTmpDir = $defaultTmpDir;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('file', $options['file_type'], $options['file_options'])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                $id = empty($data['id']) ? null : $data['id'];
                $file = isset($data['file']) && $data['file'] instanceof UploadedFile ? $data['file'] : null;
                $tmpDir = $form->getConfig()->getOption('tmp_dir');

                if (!$id && !$file) {
                    return;
                }

                if (!$file && !$data['file'] = $this->getMockUploadedFile($id, $tmpDir)) {
                    return;
                }

                if (!$id) {
                    $data['id'] = sha1(uniqid(get_class($this)));
                }

                $this->registerUploadForm($form);
                $form->setData(new Upload());
                $event->setData($data);
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
                'label' => false,
                'file_type' => FileType::class,
                'file_options' => [],
                'tmp_dir' => $this->defaultTmpDir,
            ])
            ->setAllowedTypes('file_type', 'string')
            ->setAllowedTypes('file_options', 'array')
            ->setAllowedTypes('tmp_dir', 'string');
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

            dump($upload);

            if ($form->isValid() && $upload instanceof Upload && $upload->getId() && $upload->getFile()) {
                $this->saveUploadedFile(
                    $upload->getFile(),
                    $upload->getId(),
                    $form->getConfig()->getOption('tmp_dir')
                );
            }
        }
    }

    /**
     * @param FormInterface $uploadForm
     */
    private function registerUploadForm(FormInterface $uploadForm)
    {
        $rootForm = $uploadForm;

        while (!$rootForm->isRoot()) {
            $rootForm = $rootForm->getParent();
        }

        $hash = $this->getFormHash($rootForm);
        $this->formsByRootFormHash[$hash][] = $uploadForm;
    }

    /**
     * @param string $id
     * @param string $path
     *
     * @return null|MockUploadedFile
     */
    private function getMockUploadedFile($id, $path)
    {
        $pathname = rtrim($path, '/').'/'.$id;

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
     * @param string       $id
     * @param string       $path
     */
    private function saveUploadedFile(UploadedFile $uploadedFile, $id, $path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $metaPathname = rtrim($path, '/').'/'.$id.'.json';
        $meta = [
            'originalName' => $uploadedFile->getClientOriginalName(),
            'mimeType' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getClientSize(),
        ];

        file_put_contents($metaPathname, json_encode($meta));

        $uploadedFile->move($path, $id);
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
