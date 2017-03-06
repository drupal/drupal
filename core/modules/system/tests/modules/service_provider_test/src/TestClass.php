<?php

namespace Drupal\service_provider_test;

use Drupal\Core\State\StateInterface;
use Drupal\Core\DestructableInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TestClass implements EventSubscriberInterface, DestructableInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * A simple kernel listener method.
   */
  public function onKernelRequestTest(GetResponseEvent $event) {
    drupal_set_message(t('The service_provider_test event subscriber fired!'));
  }

  /**
   * Flags the response in case a rebuild indicator is used.
   */
  public function onKernelResponseTest(FilterResponseEvent $event) {
    if ($this->container->hasParameter('container_rebuild_indicator')) {
      $event->getResponse()->headers->set('container_rebuild_indicator', $this->container->getParameter('container_rebuild_indicator'));
    }
    if ($this->container->hasParameter('container_rebuild_test_parameter')) {
      $event->getResponse()->headers->set('container_rebuild_test_parameter', $this->container->getParameter('container_rebuild_test_parameter'));
    }
  }

  /**
   * Registers methods as kernel listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequestTest'];
    $events[KernelEvents::RESPONSE][] = ['onKernelResponseTest'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    $this->state->set('service_provider_test.destructed', TRUE);
  }

}
