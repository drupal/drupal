<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\views\ResultRow;

/**
 * Render a numeric value as a size.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_size")
 */
class FileSize extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['file_size_display'] = ['default' => 'formatted'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['file_size_display'] = [
      '#title' => $this->t('File size display'),
      '#type' => 'select',
      '#options' => [
        'formatted' => $this->t('Formatted (in KB or MB)'),
        'bytes' => $this->t('Raw bytes'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if ($value) {
      switch ($this->options['file_size_display']) {
        case 'bytes':
          return $value;

        case 'formatted':
        default:
          return ByteSizeMarkup::create((int) $value);
      }
    }
    else {
      return '';
    }
  }

}
