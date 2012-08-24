<?php
/**
 * @file
 * Definition of Drupal\locale\LocaleConfigSubscriber.
 */

namespace Drupal\locale;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigEvent;
use Drupal\Core\Config\StorageDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Locale Config helper
 *
 * $config is always a DrupalConfig object.
 */
class LocaleConfigSubscriber implements EventSubscriberInterface {
  /**
   * Override configuration values with localized data.
   *
   * @param Drupal\Core\Config\ConfigEvent $event
   *   The Event to process.
   */
  public function configLoad(ConfigEvent $event) {
    $config = $event->getConfig();
    $language = language(LANGUAGE_TYPE_INTERFACE);
    $locale_name = $this->getLocaleConfigName($config->getName(), $language);
    if ($override = $config->getStorage()->read($locale_name)) {
      $config->setOverride($override);
    }
  }

  /**
   * Get configuration name for this language.
   *
   * It will be the same name with a prefix depending on language code:
   * locale.config.LANGCODE.NAME
   */
  public function getLocaleConfigName($name, $language) {
    return 'locale.config.' . $language->langcode . '.' . $name;
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events['config.load'][] = array('configLoad', 20);
    return $events;
  }
}
