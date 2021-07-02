<?php

namespace Drupal\language;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

class LanguageTrustedCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #process callback for language_element_info_alter()
   *
   * Expands the language_configuration form element.
   */
  public static function languageSelectProcess(array &$element, FormStateInterface $form_state, array &$form) {
    // Don't set the options if another module (translation for example) already
    // set the options.
    if (!isset($element['#options'])) {
      $element['#options'] = [];
      foreach (\Drupal::languageManager()->getLanguages($element['#languages']) as $langcode => $language) {
        $element['#options'][$langcode] = $language->isLocked() ? t('- @name -', ['@name' => $language->getName()]) : $language->getName();
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['languageSelectProcess'];
  }

}
