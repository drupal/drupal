<?php

/**
 * @file
 * Contains \Drupal\language\EventSubscriber\ConfigSubscriber.
 */

namespace Drupal\language\EventSubscriber;

use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\language\ConfigurableLanguageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the container if default language has changed.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The default language.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Language\LanguageDefault $language_default
   *   The default language.
   */
  public function __construct(LanguageManagerInterface $language_manager, LanguageDefault $language_default) {
    $this->languageManager = $language_manager;
    $this->languageDefault = $language_default;
  }

  /**
   * Causes the container to be rebuilt on the next request.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() == 'system.site' && $event->isChanged('default_langcode')) {
      $language = $this->languageManager->getLanguage($saved_config->get('default_langcode'));
      // During an import the language might not exist yet.
      if ($language) {
        $this->languageDefault->set($language);
        $this->languageManager->reset();
        language_negotiation_url_prefixes_update();
      }
      // Trigger a container rebuild on the next request by invalidating it.
      ConfigurableLanguageManager::rebuildServices();
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 0);
    return $events;
  }

}
