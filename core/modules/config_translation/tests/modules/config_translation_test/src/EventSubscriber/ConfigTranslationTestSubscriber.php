<?php

namespace Drupal\config_translation_test\EventSubscriber;

use Drupal\config_translation\Event\ConfigMapperPopulateEvent;
use Drupal\config_translation\Event\ConfigTranslationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds configuration names to configuration mapper on POPULATE_MAPPER event.
 */
class ConfigTranslationTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigTranslationEvents::POPULATE_MAPPER => [
        ['addConfigNames'],
      ],
    ];
  }

  /**
   * Reacts to the populating of a configuration mapper.
   *
   * @param \Drupal\config_translation\Event\ConfigMapperPopulateEvent $event
   *   The configuration mapper event.
   */
  public function addConfigNames(ConfigMapperPopulateEvent $event) {
    $mapper = $event->getMapper();
    if ($mapper->getBaseRouteName() === 'system.site_information_settings' && $mapper->getLangcode() === 'en') {
      $mapper->addConfigName('config_translation_test.content');
    }
  }

}
