<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;

/**
 * Provides a render element for a group of form elements.
 *
 * Usage example:
 * @code
 * $form['author'] = [
 *   '#type' => 'fieldset',
 *   '#title' => $this->t('Author'),
 * ];
 *
 * $form['author']['name'] = [
 *   '#type' => 'textfield',
 *   '#title' => $this->t('Name'),
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Fieldgroup
 * @see \Drupal\Core\Render\Element\Details
 */
#[RenderElement('fieldset')]
class Fieldset extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#value' => NULL,
      '#theme_wrappers' => ['fieldset'],
    ];
  }

}
