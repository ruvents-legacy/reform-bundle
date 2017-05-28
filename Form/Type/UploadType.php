<?php

namespace Ruvents\ReformBundle\Form\Type;

use Ruvents\ReformBundle\SavableUploadedFile;
use Ruvents\ReformBundle\SavedUploadedFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UploadType extends AbstractType
{
    const ID_CHILD = '_id';

    /**
     * @var string
     */
    private $path;

    /**
     * @var SavableUploadedFile[][]
     */
    private $savableUploadedFiles = [];

    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['file_options']['required'] = $options['required'];

        $builder
            ->add(self::ID_CHILD, HiddenType::class, ['mapped' => false])
            ->add($options['file_name'], $options['file_type'], $options['file_options'])
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'error_bubbling' => false,
                'file_name' => 'file',
                'file_type' => FileType::class,
                'file_options' => [],
                'label' => false,
            ])
            ->setAllowedTypes('file_name', 'string')
            ->setAllowedTypes('file_type', 'string')
            ->setAllowedTypes('file_options', 'array');
    }

    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        $fileName = $form->getConfig()->getOption('file_name');

        $id = isset($data[self::ID_CHILD]) && $this->isIdValid($data[self::ID_CHILD])
            ? $data[self::ID_CHILD]
            : null;
        $uploadedFile = isset($data[$fileName]) && $data[$fileName] instanceof HttpUploadedFile
            ? $data[$fileName]
            : null;

        // if a new file was uploaded
        if (null !== $uploadedFile) {
            if (null !== $id && null !== $old = SavedUploadedFile::find($this->getPathname($id))) {
                $old->remove();
            }

            $id = $this->generateId();
            $uploadedFile = SavableUploadedFile::fromUploadedFile($uploadedFile);

            $this->savableUploadedFiles[$this->getFormHash($form->getRoot())][$id] = $uploadedFile;
        } // when id is correct, try to find a saved uploaded file
        elseif (null !== $id) {
            $uploadedFile = SavedUploadedFile::find($this->getPathname($id));
        }

        $data[self::ID_CHILD] = $id;
        $data[$fileName] = $uploadedFile;

        $event->setData($data);
    }

    public function saveUploadedFiles(FormInterface $rootForm)
    {
        $hash = $this->getFormHash($rootForm);

        if (empty($this->savableUploadedFiles[$hash])) {
            return;
        }

        foreach ($this->savableUploadedFiles[$hash] as $id => $savableUploadedFile) {
            $savableUploadedFile->save($this->getPathname($id));
        }
    }

    /**
     * @param mixed $id
     *
     * @return bool
     */
    private function isIdValid($id)
    {
        return is_string($id) && preg_match('/^[0-9a-zA-Z_-]+$/', $id) > 0;
    }

    /**
     * @return string
     */
    private function generateId()
    {
        return rtrim(strtr(base64_encode(random_bytes(30)), '+/', '-_'), '=');
    }

    /**
     * @param string $id
     *
     * @return string
     */
    private function getPathname($id)
    {
        return rtrim($this->path, '/').'/'.$id;
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
