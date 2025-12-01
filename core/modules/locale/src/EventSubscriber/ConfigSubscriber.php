<?php

namespace Drupal\locale\EventSubscriber;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Rebuilds the container when locale config is changed.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  public function __construct(#[Autowire(service: 'kernel')] private DrupalKernel $kernel) {
  }

  /**
   * Causes the container to be rebuilt on the next request if necessary.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $saved_config = $event->getConfig();
    if (!$saved_config->isNew() && $saved_config->getName() == 'locale.settings' && $event->isChanged('translate_english')) {
      // Trigger a container rebuild on the next request by invalidating it.
      $this->kernel->invalidateContainer();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    return $events;
  }

}
