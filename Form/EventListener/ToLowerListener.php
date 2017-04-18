<?php

namespace Ruvents\ReformBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ToLowerListener implements EventSubscriberInterface
{
    /**
     * @var string|callable
     */
    private $function;

    /**
     * @param string|callable $function
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($function = 'mb_strtolower')
    {
        if (!is_callable($function)) {
            throw new \InvalidArgumentException('Function must be callable.');
        }

        $this->function = $function;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (!is_string($data)) {
            return;
        }

        $event->setData(call_user_func($this->function, $data));
    }
}
