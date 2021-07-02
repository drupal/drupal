<?php

namespace Drupal\stable;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides trusted callbacks to the Stable theme.
 *
 * @package Drupal\stable
 */
class StableTrustedCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #process callback for stable_element_info_alter().
   */
  public static function processTextFormat(array &$element, FormStateInterface $formState, array &$form) {
    $element['format']['#attributes']['class'][] = 'filter-wrapper';
    $element['format']['guidelines']['#attributes']['class'][] = 'filter-guidelines';
    $element['format']['format']['#attributes']['class'][] = 'filter-list';
    $element['format']['help']['#attributes']['class'][] = 'filter-help';

    return $element;
  }

  /**
   * @inheritDoc
   */
  public static function trustedCallbacks() {
    return ['processTextFormat'];
  }

}
