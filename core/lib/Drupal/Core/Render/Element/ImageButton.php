<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for a submit button with an image.
 *
 * @FormElement("image_button")
 */
class ImageButton extends Submit {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    unset($info['name']);

    return [
      '#return_value' => TRUE,
      '#has_garbage_value' => TRUE,
      '#src' => NULL,
      '#theme_wrappers' => ['input__image_button'],
    ] + $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      if (!empty($input)) {
        // If we're dealing with Mozilla or Opera, we're lucky. It will
        // return a proper value, and we can get on with things.
        return $element['#return_value'];
      }
      else {
        // Unfortunately, in IE we never get back a proper value for THIS
        // form element. Instead, we get back two split values: one for the
        // X and one for the Y coordinates on which the user clicked the
        // button. We'll find this element in the #post data, and search
        // in the same spot for its name, with '_x'.
        $input = $form_state->getUserInput();
        foreach (explode('[', $element['#name']) as $element_name) {
          // chop off the ] that may exist.
          if (substr($element_name, -1) == ']') {
            $element_name = substr($element_name, 0, -1);
          }

          if (!isset($input[$element_name])) {
            if (isset($input[$element_name . '_x'])) {
              return $element['#return_value'];
            }
            return NULL;
          }
          $input = $input[$element_name];
        }
        return $element['#return_value'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderButton($element) {
    $element['#attributes']['type'] = 'image';
    Element::setAttributes($element, ['id', 'name', 'value']);

    $element['#attributes']['src'] = \Drupal::service('file_url_generator')->generateString($element['#src']);
    if (!empty($element['#title'])) {
      $element['#attributes']['alt'] = $element['#title'];
      $element['#attributes']['title'] = $element['#title'];
    }

    $element['#attributes']['class'][] = 'image-button';
    if (!empty($element['#button_type'])) {
      $element['#attributes']['class'][] = 'image-button--' . $element['#button_type'];
    }
    $element['#attributes']['class'][] = 'js-form-submit';
    $element['#attributes']['class'][] = 'form-submit';

    if (!empty($element['#attributes']['disabled'])) {
      $element['#attributes']['class'][] = 'is-disabled';
    }

    return $element;
  }

}
