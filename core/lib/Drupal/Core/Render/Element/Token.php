<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Token.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Stores token data in a hidden form field.
 *
 * This is generally used to protect against cross-site forgeries. A token
 * element is automatically added to each Drupal form by an implementation of
 * \Drupal\Core\Form\FormBuilderInterface::prepareForm() so you don't generally
 * have to add one yourself.
 *
 * @FormElement("token")
 */
class Token extends Hidden {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#pre_render' => array(
        array($class, 'preRenderHidden'),
      ),
      '#theme' => 'input__hidden',
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      // This should be a string, but allow other scalars since they might be
      // valid input in programmatic form submissions.
      return is_scalar($input) ? (string) $input : '';
    }
    return NULL;
  }

}
