<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\KernelDestructionSubscriber.
 */

namespace Drupal\Core\EventSubscriber;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Destructs services that are initiated and tagged with "needs_destruction".
 *
 * @see \Drupal\Core\DestructableInterface
 */
class KernelDestructionSubscriber implements EventSubscriberInterface, ContainerAwareInterface {

  use ContainerAwareTrait;
  /**
   * Holds an array of service ID's that will require destruction.
   *
   * @var array
   */
  protected $services = array();

  /**
   * Registers a service for destruction.
   *
   * Calls to this method are set up in
   * RegisterServicesForDestructionPass::process().
   *
   * @param string $id
   *   Name of the service.
   */
  public function registerService($id) {
    $this->services[] = $id;
  }

  /**
   * Invoked by the terminate kernel event.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The event object.
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    foreach ($this->services as $id) {
      // Check if the service was initialized during this request, destruction
      // is not necessary if the service was not used.
      if ($this->container->initialized($id)) {
        $service = $this->container->get($id);
        $service->destruct();
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = array('onKernelTerminate', 100);
    return $events;
  }
}
