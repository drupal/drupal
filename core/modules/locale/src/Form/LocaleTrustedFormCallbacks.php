<?php

namespace Drupal\locale\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements TrustedFormCallbacks for locale module.
 *
 * @package Drupal\locale\Form
 */
class LocaleTrustedFormCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #after_build callback for TranslationStatusForm::buildForm.
   */
  public static function translationLanguageTable(array &$element, FormStateInterface $formState) {
    // Remove checkboxes of languages without updates.
    if ($element['#not_found']) {
      foreach ($element['#not_found'] as $langcode) {
        $element[$langcode] = [];
      }
    }
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return ['translationLanguageTable'];
  }

}
