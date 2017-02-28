<?php

namespace Drupal\serialization\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Config event subscriber to rebuild the container when BC config is saved.
 */
class BcConfigSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal Kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * BcConfigSubscriber constructor.
   *
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The Drupal Kernel.
   */
  public function __construct(DrupalKernelInterface $kernel) {
    $this->kernel = $kernel;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = 'onConfigSave';
    return $events;
  }

  /**
   * Invalidates the service container if serialization BC config gets updated.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();

    if ($saved_config->getName() === 'serialization.settings') {
      if ($event->isChanged('bc_primitives_as_strings')) {
        $this->kernel->invalidateContainer();
      }
    }
  }

}
