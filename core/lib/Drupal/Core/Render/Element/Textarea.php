<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for input of multiple-line text.

 * Usage example:
 * @code
 * use Drupal\Core\Render\Element\Textarea;
 *
 * $form['text'] = Textarea::getBuilder()
 *   ->setTitle($this->t('Text'))
 *   ->toRenderable();
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 * @see \Drupal\filter\Element\TextFormat
 *
 * @FormElement("textarea")
 */
class Textarea extends TextElement {

  use ElementAjaxTrait;
  use ElementAttributesTrait;

  /**
   * {@inheritdoc}
   */
  protected $renderable = ['#type' => 'textarea'];

  /**
   * Sets the number of columns in the text box.
   *
   * @param int $cols
   *   The number of columns in the text box.
   *
   * @return $this
   */
  public function setCols(int $cols) {
    return $this->set('cols', $cols);
  }

  /**
   * Sets the number of rows in the text box.
   *
   * @param int $rows
   *   The number of rows in the text box.
   *
   * @return $this
   */
  public function setRows(int $rows) {
    return $this->set('rows', $rows);
  }

  /**
   * Sets whether the text area is resizable.
   *
   * @param string $resizable
   *   Whether the text area is resizable. Allowed values are "none",
   *   "vertical", "horizontal", or "both" (defaults to "vertical").
   *
   * @return $this
   */
  public function setResizable(string $resizable) {
    return $this->set('resizable', $resizable);
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#cols' => 60,
      '#rows' => 5,
      '#resizable' => 'vertical',
      '#process' => [
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme' => 'textarea',
      '#theme_wrappers' => ['form_element'],
    ];
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
