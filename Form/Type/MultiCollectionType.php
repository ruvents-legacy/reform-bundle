<?php

namespace Ruvents\ReformBundle\Form\Type;

use Ruvents\ReformBundle\Form\EventSubscriber\MultiCollectionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiCollectionType extends AbstractType
{
    const MAP_CHILD = '_map';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['allow_add']) {
            $prototypes = [];

            foreach ($options['entries'] as $entry) {
                $name = str_replace('%entry%', $entry['name'], $options['prototype_name']);

                $prototypeOptions = array_replace([
                    'required' => $options['required'],
                ], $entry['options']);

                if (null !== $entry['prototype_data']) {
                    $prototypeOptions['data'] = $entry['prototype_data'];
                }

                $prototypes[$entry['name']] = [
                    'data' => $builder
                        ->create($name, $entry['type'], $prototypeOptions)
                        ->getForm(),
                    'entry' => $builder
                        ->create($name, HiddenType::class, ['data' => $entry['name']])
                        ->getForm(),
                ];
            }

            $builder
                ->setAttribute('prototypes', $prototypes)
                ->add(self::MAP_CHILD, FormType::class, [
                    'label' => false,
                    'mapped' => false,
                    'allow_extra_fields' => true,
                ]);
        }

        $builder->addEventSubscriber(new MultiCollectionSubscriber(
            $options['entries'],
            $options['entry_mapper'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty']
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_add'] = $options['allow_add'];
        $view->vars['allow_delete'] = $options['allow_delete'];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($form->getConfig()->getAttribute('prototypes', []) as $name => $prototypes) {
            /** @var FormInterface[] $prototypes */
            $view->vars['prototypes'][$name] = [
                'data' => $dataView = $prototypes['data']
                    ->setParent($form)
                    ->createView($view),
                'entry' => $prototypes['entry']
                    ->setParent($form->get(self::MAP_CHILD))
                    ->createView($view[self::MAP_CHILD]),
            ];

            if ($dataView->vars['multipart']) {
                $view->vars['multipart'] = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        /** @noinspection PhpUnusedParameterInspection */
        $resolver
            ->setRequired([
                'entries',
                'entry_mapper',
            ])
            ->setDefaults([
                'allow_add' => false,
                'allow_delete' => false,
                'delete_empty' => false,
                'prototype_name' => '__name__%entry%__',
            ])
            ->setAllowedTypes('entries', 'array')
            ->setAllowedTypes('entry_mapper', 'callable')
            ->setNormalizer('entries', function (Options $options, $entries) {
                $newEntries = [];

                foreach ($entries as $name => $entry) {
                    $resolver = new OptionsResolver();
                    $this->configureEntryResolver($resolver);

                    $entry['name'] = $name;
                    $resolvedEntry = $resolver->resolve($entry);

                    /** @noinspection PhpIllegalArrayKeyTypeInspection */
                    $newEntries[$resolvedEntry['name']] = $resolvedEntry;
                }

                return $newEntries;
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'multi_collection';
    }

    public function configureEntryResolver(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([
                'name',
                'type',
            ])
            ->setDefaults([
                'options' => [],
                'prototype' => true,
                'prototype_data' => null,
            ])
            ->setAllowedTypes('name', ['int', 'string'])
            ->setAllowedTypes('type', 'string')
            ->setAllowedTypes('options', 'array')
            ->setAllowedTypes('prototype', 'bool')
            ->setNormalizer('options', function (Options $options, $entryOptions) {
                $entryOptions['block_name'] = $options['name'].'_entry';

                return $entryOptions;
            });
    }
}
