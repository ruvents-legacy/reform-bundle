<?php

namespace Ruvents\ReformBundle\Form\TypeExtension;

use Ruvents\ReformBundle\StatefulFileManager;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormTypeStatefulFileExtension extends AbstractTypeExtension
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
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                if (!$form->isRoot() || !$form->getConfig()->getOption('compound')) {
                    return;
                }

                $data['_tmp_dir'] = isset($data['_tmp_dir']) ? $data['_tmp_dir'] : '';

                if (0 === preg_match('/^[0-9a-f]{32}$/', $data['_tmp_dir'])) {
                    $data['_tmp_dir'] = md5(uniqid(get_class($this)));
                }

                $event->setData($data);

                $filesDirForm = $form
                    ->getConfig()
                    ->getFormFactory()
                    ->createNamedBuilder('_tmp_dir', HiddenType::class, null, [
                        'mapped' => false,
                        'auto_initialize' => false,
                    ])
                    ->setAttribute('stateful_file_manager', new StatefulFileManager($data['_tmp_dir']))
                    ->getForm();

                $form->add($filesDirForm);
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();

                if ($form->isRoot() && !$form->isValid()) {
                    $form->get('_tmp_dir')
                        ->getConfig()
                        ->getAttribute('stateful_file_manager')
                        ->saveFiles();
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'stateful_file' => true,
            ])
            ->setAllowedTypes('stateful_file', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}
