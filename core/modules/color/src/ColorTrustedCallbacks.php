<?php

namespace Drupal\color;

use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements ColorTrustedCallbacks for color module.
 *
 * @package Drupal\color
 */
class ColorTrustedCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #value_callback for color_scheme_form().
   */
  public static function paletteColorValue($element, $input, FormStateInterface $form_state) {
    // If we suspect a possible cross-site request forgery attack, only accept
    // hexadecimal CSS color strings from user input, to avoid problems when
    // this value is used in the JavaScript preview.
    if ($input !== FALSE) {
      // Start with the provided value for this textfield, and validate that if
      // necessary, falling back on the default value.
      $value = Textfield::valueCallback($element, $input, $form_state);
      $complete_form = $form_state->getCompleteForm();
      if (!$value || !isset($complete_form['#token']) || color_valid_hexadecimal_string($value) || \Drupal::csrfToken()->validate($form_state->getValue('form_token'), $complete_form['#token'])) {
        return $value;
      }
      else {
        return $element['#default_value'];
      }
    }
  }

  /**
   * Implements schemeFormValidate for color.module.
   */
  public static function schemeFormValidate(array $form, FormStateInterface $form_state) {
    // Only accept hexadecimal CSS color strings to avoid XSS upon use.
    foreach ($form_state->getValue('palette') as $key => $color) {
      if (!color_valid_hexadecimal_string($color)) {
        $form_state->setErrorByName('palette][' . $key, t('You must enter a valid hexadecimal color value for %name.', ['%name' => $form['color']['palette'][$key]['#title']]));
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return ['paletteColorValue', 'schemeFormValidate'];
  }

}
