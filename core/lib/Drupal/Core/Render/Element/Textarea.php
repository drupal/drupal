<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Textarea.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for input of multiple-line text.
 *
 * Properties:
 * - #rows: Number of rows in the text box.
 * - #cols: Number of columns in the text box.
 * - #resizable: Controls whether the text area is resizable.  Allowed values
 *   are "none", "vertical", "horizontal", or "both" (defaults to "vertical").
 *
 * Usage example:
 * @code
 * $form['text'] = array(
 *   '#type' => 'textarea',
 *   '#title' => t('Text'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 * @see \Drupal\filter\Element\TextFormat
 *
 * @FormElement("textarea")
 */
class Textarea extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#cols' => 60,
      '#rows' => 5,
      '#resizable' => 'vertical',
      '#process' => array(
        array($class, 'processAjaxForm'),
        array($class, 'processGroup'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderGroup'),
      ),
      '#theme' => 'textarea',
      '#theme_wrappers' => array('form_element'),
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
