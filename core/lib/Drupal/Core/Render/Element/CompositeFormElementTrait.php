<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\CompositeFormElementTrait.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a trait for radios, checkboxes, and similar composite form elements.
 *
 * Any form element that is comprised of several distinct parts can use this
 * trait to add support for a composite title or description.
 */
trait CompositeFormElementTrait {

  /**
   * Adds form element theming to an element if its title or description is set.
   *
   * This is used as a pre render function for checkboxes and radios.
   */
  public static function preRenderCompositeFormElement($element) {
    // Set the element's title attribute to show #title as a tooltip, if needed.
    if (isset($element['#title']) && $element['#title_display'] == 'attribute') {
      $element['#attributes']['title'] = $element['#title'];
      if (!empty($element['#required'])) {
        // Append an indication that this field is required.
        $element['#attributes']['title'] .= ' (' . t('Required') . ')';
      }
    }

    if (isset($element['#title']) || isset($element['#description'])) {
      // @see #type 'fieldgroup'
      $element['#attributes']['id'] = $element['#id'] . '--wrapper';
      $element['#theme_wrappers'][] = 'fieldset';
      $element['#attributes']['class'][] = 'fieldgroup';
      $element['#attributes']['class'][] = 'form-composite';
    }
    return $element;
  }

}
