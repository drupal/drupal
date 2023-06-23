<?php

namespace Drupal\field;

/**
 * Provides a trait for the valid field label options.
 */
trait FieldLabelOptionsTrait {

  /**
   * Returns an array of visibility options for field labels.
   *
   * @return array
   *   An array of visibility options.
   */
  protected function getFieldLabelOptions(): array {
    return [
      'above' => $this->t('Above'),
      'inline' => $this->t('Inline'),
      'hidden' => '- ' . $this->t('Hidden') . ' -',
      'visually_hidden' => '- ' . $this->t('Visually Hidden') . ' -',
    ];
  }

}
