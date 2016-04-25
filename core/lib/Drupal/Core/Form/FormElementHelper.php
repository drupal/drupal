<?php

namespace Drupal\Core\Form;

use Drupal\Core\Render\Element;

/**
 * Provides common functionality for form elements.
 */
class FormElementHelper {

  /**
   * Retrieves a form element.
   *
   * @param string $name
   *   The name of the form element. If the #parents property of your form
   *   element is ['foo', 'bar', 'baz'] then the name is 'foo][bar][baz'.
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The form element.
   */
  public static function getElementByName($name, array $form) {
    foreach (Element::children($form) as $key) {
      if (implode('][', $form[$key]['#parents']) === $name) {
        return $form[$key];
      }
      elseif ($element = static::getElementByName($name, $form[$key])) {
        return $element;
      }
    }
    return [];
  }

  /**
   * Returns the title for the element.
   *
   * If the element has no title, this will recurse through all children of the
   * element until a title is found.
   *
   * @param array $element
   *   An associative array containing the properties of the form element.
   *
   * @return string
   *   The title of the element, or an empty string if none is found.
   */
  public static function getElementTitle(array $element) {
    $title = '';
    if (isset($element['#title'])) {
      $title = $element['#title'];
    }
    else {
      foreach (Element::children($element) as $key) {
        if ($title = static::getElementTitle($element[$key])) {
          break;
        }
      }
    }
    return $title;
  }

}
