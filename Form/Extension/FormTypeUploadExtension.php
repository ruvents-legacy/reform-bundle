<?php

namespace Ruvents\ReformBundle\Form\Extension;

use Ruvents\ReformBundle\Form\Type\UploadType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FormTypeUploadExtension extends AbstractTypeExtension
{
    /**
     * @var UploadType
     */
    private $uploadType;

    /**
     * @param UploadType $uploadType
     */
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

            if ($form->isRoot()) {
                $this->uploadType->processValidatedRootForm($form);
            }
        }, -1);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}
