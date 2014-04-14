<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigImportValidateEventSubscriberBase.
 */

namespace Drupal\Core\Config;

use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a base event listener implementation for config sync validation.
 */
abstract class ConfigImportValidateEventSubscriberBase implements EventSubscriberInterface {

  /**
   * Checks that the configuration synchronization is valid.
   *
   * @param ConfigImporterEvent $event
   *   The config import event.
   */
  abstract public function onConfigImporterValidate(ConfigImporterEvent $event);

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT_VALIDATE][] = array('onConfigImporterValidate', 20);
    return $events;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * @param string $string
   *   A string containing the English string to translate.
   * @param array $args
   *   An associative array of replacements to make after translation. Based
   *   on the first character of the key, the value is escaped and/or themed.
   *   See \Drupal\Component\Utility\String::format() for details.
   * @param array $options
   *   An associative array of additional options, with the following elements:
   *   - 'langcode': The language code to translate to a language other than
   *      what is used to display the page.
   *   - 'context': The context the source string belongs to.
   *
   * @return string
   *   The translated string.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface::translate()
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return \Drupal::translation()->translate($string, $args, $options);
  }
}
