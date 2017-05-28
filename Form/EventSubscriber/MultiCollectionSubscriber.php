<?php

namespace Ruvents\ReformBundle\Form\EventSubscriber;

use Ruvents\ReformBundle\Form\Type\MultiCollectionType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class MultiCollectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $entries;

    /**
     * @var callable
     */
    private $entryMapper;

    /**
     * Whether children could be added to the group.
     *
     * @var bool
     */
    private $allowAdd;

    /**
     * Whether children could be removed from the group.
     *
     * @var bool
     */
    private $allowDelete;

    /**
     * @var bool
     */
    private $deleteEmpty;

    public function __construct(
        array $entries,
        callable $entryMapper,
        $allowAdd = false,
        $allowDelete = false,
        $deleteEmpty = false
    ) {
        $this->entries = $entries;
        $this->entryMapper = $entryMapper;
        $this->allowAdd = $allowAdd;
        $this->allowDelete = $allowDelete;
        $this->deleteEmpty = $deleteEmpty;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            // (MergeCollectionListener, MergeDoctrineCollectionListener)
            FormEvents::SUBMIT => ['onSubmit', 50],
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (null === $data) {
            $data = [];
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            if (MultiCollectionType::MAP_CHILD === $name) {
                continue;
            }

            $form->remove($name);
        }

        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
            $entry = $this->entries[($this->entryMapper)($value)];

            $form->add($name, $entry['type'], array_replace([
                'property_path' => '['.$name.']',
            ], $entry['options']));
        }
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            $data = [];
        }

        // Remove all empty rows
        if ($this->allowDelete) {
            foreach ($form as $name => $child) {
                /** @var FormInterface $child */
                if (!isset($data[$name])) {
                    $form->remove($name);
                }
            }
        }

        // Add all additional rows
        if ($this->allowAdd) {
            $map = isset($data[MultiCollectionType::MAP_CHILD]) ? $data[MultiCollectionType::MAP_CHILD] : [];

            foreach ($data as $name => $value) {
                if (!$form->has($name) && isset($map[$name])) {
                    $entry = $this->entries[$map[$name]];

                    $form
                        ->add($name, $entry['type'], array_replace([
                            'property_path' => '['.$name.']',
                        ], $entry['options']))
                        ->get(MultiCollectionType::MAP_CHILD)
                        ->add($name, HiddenType::class, ['data' => $entry['name']]);
                }
            }
        }
    }

    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        // At this point, $data is an array or an array-like object that already contains the
        // new entries, which were added by the data mapper. The data mapper ignores existing
        // entries, so we need to manually unset removed entries in the collection.

        if (null === $data) {
            $data = [];
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        if ($this->deleteEmpty) {
            $previousData = $event->getForm()->getData();
            foreach ($form as $name => $child) {
                $isNew = !isset($previousData[$name]);

                // $isNew can only be true if allowAdd is true, so we don't
                // need to check allowAdd again
                if ($child->isEmpty() && ($isNew || $this->allowDelete)) {
                    unset($data[$name]);
                    $form->remove($name);
                }
            }
        }

        // The data mapper only adds, but does not remove items, so do this here
        if ($this->allowDelete) {
            $toDelete = [];

            foreach ($data as $name => $child) {
                if (!$form->has($name)) {
                    $toDelete[] = $name;
                }
            }

            foreach ($toDelete as $name) {
                unset($data[$name]);
            }
        }

        $event->setData($data);
    }
}

