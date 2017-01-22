<?php

namespace Ruvents\ReformBundle\Form\Type;

use Ruvents\ReformBundle\Helper\UploadHelper;
use Ruvents\ReformBundle\TmpFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UploadType extends AbstractType
{
    /**
     * @var UploadHelper
     */
    private $uploadHelper;

    /**
     * @var string
     */
    private $defaultTmpDir;

    /**
     * @param UploadHelper $uploadHelper
     * @param string       $defaultTmpDir
     */
    public function __construct(UploadHelper $uploadHelper, $defaultTmpDir)
    {
        $this->uploadHelper = $uploadHelper;
        $this->defaultTmpDir = $defaultTmpDir;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['file_options']['property_path'] = 'uploadedFile';

        $builder
            ->add('id', HiddenType::class, ['mapped' => false])
            ->add('file', $options['file_type'], $options['file_options'])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $idGenerator = $form->getConfig()->getOption('id_generator');
                $tmpDir = $form->getConfig()->getOption('tmp_dir');

                $id = isset($data['id']) && preg_match('/^\w+$/', $data['id'])
                    ? $data['id']
                    : $idGenerator();

                $tmpPath = rtrim($tmpDir, '/').'/'.$id;

                $file = isset($data['file']) && $data['file'] instanceof UploadedFile
                    ? $data['file']
                    : $this->uploadHelper->createUploadedFile($tmpPath);

                if ($file === null) {
                    $event->setData(null);

                    return;
                }

                $event->setData([
                    'id' => $id,
                    'file' => $file,
                ]);

                $form->setData(new TmpFile($tmpPath));
                $this->uploadHelper->registerUploadForm($form);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => TmpFile::class,
                'empty_data' => null,
                'label' => false,
                'file_type' => FileType::class,
                'file_options' => [],
                'tmp_dir' => $this->defaultTmpDir,
                'id_generator' => function () {
                    return sha1(uniqid(get_class($this)));
                },
            ])
            ->setAllowedTypes('file_type', 'string')
            ->setAllowedTypes('file_options', 'array')
            ->setAllowedTypes('tmp_dir', 'string')
            ->setAllowedTypes('id_generator', 'callable');
    }
}
