<?php

namespace Ruvents\ReformBundle\Form\TypeExtension;

use Ruvents\ReformBundle\StatefulFileManager;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StatefulFileExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['stateful_file']) {
            return;
        }

        $builder
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $rootForm = $form->getRoot();
                $data = $event->getData();

                if (!$rootForm->getConfig()->getOption('stateful_file')) {
                    return;
                }

                /** @var StatefulFileManager $manager */
                $manager = $form
                    ->getRoot()
                    ->get('_tmp_dir')
                    ->getConfig()
                    ->getAttribute('stateful_file_manager');

                if ($data instanceof UploadedFile) {
                    $manager->removeFile($form);
                    $manager->registerNewUploadedFile($form, $data);

                    return;
                }

                $event->setData($manager->findFile($form));
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FileType::class;
    }
}
