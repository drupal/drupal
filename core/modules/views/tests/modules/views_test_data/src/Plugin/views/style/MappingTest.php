<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\style;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\style\Mapping;
use Drupal\views\Plugin\views\field\NumericField;

/**
 * Provides a test plugin for the mapping style.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "mapping_test",
  title: new TranslatableMarkup("Field mapping"),
  help: new TranslatableMarkup("Maps specific fields to specific purposes."),
  theme: "views_view_mapping_test",
  display_types: ["normal", "test"],
)]
class MappingTest extends Mapping {

  /**
   * {@inheritdoc}
   */
  protected function defineMapping() {
    return [
      'title_field' => [
        '#title' => $this->t('Title field'),
        '#description' => $this->t('Choose the field with the custom title.'),
        '#toggle' => TRUE,
        '#required' => TRUE,
      ],
      'name_field' => [
        '#title' => $this->t('Name field'),
        '#description' => $this->t('Choose the field with the custom name.'),
      ],
      'numeric_field' => [
        '#title' => $this->t('Numeric field'),
        '#description' => $this->t('Select one or more numeric fields.'),
        '#multiple' => TRUE,
        '#toggle' => TRUE,
        '#filter' => 'filterNumericFields',
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * Restricts the allowed fields to only numeric fields.
   *
   * @param array $fields
   *   An array of field labels, keyed by the field ID.
   */
  protected function filterNumericFields(&$fields) {
    foreach ($this->view->field as $id => $field) {
      if (!($field instanceof NumericField)) {
        unset($fields[$id]);
      }
    }
  }

}
