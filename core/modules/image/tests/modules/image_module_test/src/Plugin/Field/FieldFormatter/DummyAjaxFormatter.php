<?php

namespace Drupal\image_module_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Empty renderer for a dummy field with an AJAX handler.
 */
#[FieldFormatter(
  id: 'image_module_test_dummy_ajax_formatter',
  label: new TranslatableMarkup('Dummy AJAX'),
  field_types: [
    'image_module_test_dummy_ajax',
  ],
)]
class DummyAjaxFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Renders nothing');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    return $element;
  }

}
