<?php

namespace Ruvents\ReformBundle\Form\Type;

use Ruvents\ReformBundle\StatefulFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatefulFileType extends AbstractType implements DataMapperInterface
{
    /**
     * @var StatefulFile[][]
     */
    private $newFiles = [];

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('file', $options['file_type'], $options['file_options'])
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();

                if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
                    $data['id'] = StatefulFile::generateId();
                    $event->setData($data);
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
                'data_class' => StatefulFile::class,
                'empty_data' => null,
                'error_bubbling' => false,
                'file_type' => FileType::class,
                'file_options' => [
                    'label' => false,
                ],
            ])
            ->setAllowedTypes('file_type', 'string')
            ->setAllowedTypes('file_options', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        if (null === $data) {
            return;
        }

        if (!$data instanceof StatefulFile) {
            throw new UnexpectedTypeException($data, sprintf('null or instance of %s', StatefulFile::class));
        }

        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */

        $forms['id']->setData($data->getId());
        $forms['file']->setData($data->getFile());
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$upload)
    {
        if (null !== $upload && !$upload instanceof StatefulFile) {
            throw new UnexpectedTypeException($upload, sprintf('null or instance of %s', StatefulFile::class));
        }

        $forms = iterator_to_array($forms);
        $upload = null;

        /** @var FormInterface[] $forms */

        if ($forms['id']->isEmpty()) {
            return;
        }

        $id = $forms['id']->getData();

        if ($forms['file']->isEmpty()) {
            $upload = StatefulFile::findById($id);
        } else {
            $upload = new StatefulFile($id, $forms['file']->getData());
            $this->newFiles[$this->getFormHash($forms['file']->getRoot())][] = $upload;
        }
    }

    public function saveNewFiles(FormInterface $rootForm)
    {
        $hash = $this->getFormHash($rootForm);

        if (!isset($this->newFiles[$hash])) {
            return;
        }

        foreach ($this->newFiles[$hash] as $upload) {
            $upload->save();
        }
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
