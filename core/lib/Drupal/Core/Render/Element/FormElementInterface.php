<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for form element plugins.
 *
 * Form element plugins are a subset of render elements, specifically
 * representing HTML elements that take input as part of a form. Form element
 * plugins are discovered via the same mechanism as regular render element
 * plugins. See \Drupal\Core\Render\Element\ElementInterface for general
 * information about render element plugins.
 *
 * @see \Drupal\Core\Render\ElementInfoManager
 * @see \Drupal\Core\Render\Element\FormElement
 * @see \Drupal\Core\Render\Annotation\FormElement
 * @see plugin_api
 *
 * @ingroup theme_render
 */
interface FormElementInterface extends ElementInterface {

  /**
   * Determines how user input is mapped to an element's #value property.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param mixed $input
   *   The incoming input to populate the form element. If this is FALSE,
   *   the element's default value should be returned.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return mixed
   *   The value to assign to the element.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state);

}
