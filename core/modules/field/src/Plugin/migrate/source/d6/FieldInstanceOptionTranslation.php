<?php

namespace Drupal\field\Plugin\migrate\source\d6;

/**
 * Gets field instance option label translations.
 *
 * @MigrateSource(
 *   id = "d6_field_instance_option_translation",
 *   source_module = "i18ncck"
 * )
 */
class FieldInstanceOptionTranslation extends FieldOptionTranslation {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->join('content_node_field_instance', 'cnfi', 'cnf.field_name = cnfi.field_name');
    $query->addField('cnfi', 'type_name');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'type_name' => $this->t('Type (article, page, ....)'),
    ];
    return parent::fields() + $fields;
  }

}
