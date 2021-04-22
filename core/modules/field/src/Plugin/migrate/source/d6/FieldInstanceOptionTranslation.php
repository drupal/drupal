<?php

namespace Drupal\field\Plugin\migrate\source\d6;

/**
 * Drupal 6 i18n field instance option labels source from database.
 *
 * For available configuration keys, refer to the parent classes:
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
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
    $query->join('content_node_field_instance', 'cnfi', '[cnfi].[field_name] = [cnf].[field_name]');
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
