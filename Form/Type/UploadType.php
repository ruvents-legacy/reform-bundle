<?php

namespace Ruvents\ReformBundle\Form\Type;

use Ruvents\ReformBundle\SavableUploadedFile;
use Ruvents\ReformBundle\SavedUploadedFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UploadType extends AbstractType
{
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
        $builder
            ->add($options['id_name'], $options['id_type'], $options['id_options'])
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
                'label' => false,
                'id_name' => '_id',
                'id_type' => Type\HiddenType::class,
                'id_options' => [
                    'mapped' => false,
                ],
                'file_name' => 'file',
                'file_type' => Type\FileType::class,
                'file_options' => [],
            ])
            ->setAllowedTypes('id_name', 'string')
            ->setAllowedTypes('id_type', 'string')
            ->setAllowedTypes('id_options', 'array')
            ->setAllowedTypes('file_name', 'string')
            ->setAllowedTypes('file_type', 'string')
            ->setAllowedTypes('file_options', 'array');
    }

    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        $idName = $form->getConfig()->getOption('id_name');
        $fileName = $form->getConfig()->getOption('file_name');

        $id = isset($data[$idName]) && $this->isIdValid($data[$idName]) ? $data[$idName] : null;
        $file = isset($data[$fileName]) && $data[$fileName] instanceof HttpUploadedFile ? $data[$fileName] : null;

        // if a new file was uploaded
        if (null !== $file) {
            // remove old saved uploaded file if exists
            if (null !== $id && null !== $oldFile = SavedUploadedFile::find($this->getPathname($id))) {
                $oldFile->remove();
            }

            $id = $this->generateId();
            $file = SavableUploadedFile::fromUploadedFile($file);

            $this->savableUploadedFiles[$this->getFormHash($form->getRoot())][$id] = $file;
        } // when id is correct, try to find a saved uploaded file
        elseif (null !== $id) {
            $file = SavedUploadedFile::find($this->getPathname($id));
        }

        $data[$idName] = $id;
        $data[$fileName] = $file;

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
