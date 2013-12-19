<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\style\MappingTest;
 */

namespace Drupal\views_test_data\Plugin\views\style;

use Drupal\views\Plugin\views\style\Mapping;
use Drupal\views\Plugin\views\field\Numeric;

/**
 * Provides a test plugin for the mapping style.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "mapping_test",
 *   title = @Translation("Field mapping"),
 *   help = @Translation("Maps specific fields to specific purposes."),
 *   theme = "views_view_mapping_test",
 *   display_types = {"normal", "test"}
 * )
 */
class MappingTest extends Mapping {

  /**
   * Overrides Drupal\views\Plugin\views\style\Mapping::defineMapping().
   */
  protected function defineMapping() {
    return array(
      'title_field' => array(
        '#title' => t('Title field'),
        '#description' => t('Choose the field with the custom title.'),
        '#toggle' => TRUE,
        '#required' => TRUE,
      ),
      'name_field' => array(
        '#title' => t('Name field'),
        '#description' => t('Choose the field with the custom name.'),
      ),
      'numeric_field' => array(
        '#title' => t('Numeric field'),
        '#description' => t('Select one or more numeric fields.'),
        '#multiple' => TRUE,
        '#toggle' => TRUE,
        '#filter' => 'filterNumericFields',
        '#required' => TRUE,
      ),
    );
  }

  /**
   * Restricts the allowed fields to only numeric fields.
   *
   * @param array $fields
   *   An array of field labels, keyed by the field ID.
   */
  protected function filterNumericFields(&$fields) {
    foreach ($this->view->field as $id => $field) {
      if (!($field instanceof Numeric)) {
        unset($fields[$id]);
      }
    }
  }

}
