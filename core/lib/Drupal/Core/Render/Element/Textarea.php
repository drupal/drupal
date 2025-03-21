<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;

/**
 * Provides a form element for input of multiple-line text.
 *
 * Properties:
 * - #rows: Number of rows in the text box.
 * - #cols: Number of columns in the text box.
 * - #resizable: Controls whether the text area is resizable.  Allowed values
 *   are "none", "vertical", "horizontal", or "both" (defaults to "vertical").
 * - #maxlength: The maximum amount of characters to accept as input.
 *
 * Usage example:
 * @code
 * $form['text'] = [
 *   '#type' => 'textarea',
 *   '#title' => $this->t('Text'),
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 * @see \Drupal\filter\Element\TextFormat
 */
#[FormElement('textarea')]
class Textarea extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#cols' => 60,
      '#rows' => 5,
      '#resizable' => 'vertical',
      '#process' => [
        [static::class, 'processAjaxForm'],
        [static::class, 'processGroup'],
      ],
      '#pre_render' => [
        [static::class, 'preRenderGroup'],
        [static::class, 'preRenderAttachments'],
      ],
      '#theme' => 'textarea',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Adds the textarea resize library.
   */
  public static function preRenderAttachments($element): array {
    $element['#attached']['library'][] = 'core/drupal.textarea-resize';
    return $element;
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
