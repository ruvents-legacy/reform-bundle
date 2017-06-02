<?php

namespace Ruvents\ReformBundle\Form\TypeExtension;

use Ruvents\ReformBundle\Form\Type\UploadType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UploadFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var UploadType
     */
    private $uploadType;

    public function __construct(UploadType $uploadType)
    {
        $this->uploadType = $uploadType;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();

            if ($form->isRoot() && $form->isValid()) {
                $this->uploadType->saveNewUploads($form);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}
