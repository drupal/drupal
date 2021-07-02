<?php

namespace Drupal\language\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 i18n node settings from database.
 *
 * For available configuration keys, refer to the parent classes:
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBas
 *
 * @MigrateSource(
 *   id = "d6_language_content_settings",
 *   source_module = "locale"
 * )
 */
class LanguageContentSettings extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('node_type', 't')
      ->fields('t', [
        'type',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'type' => $this->t('Type'),
      'language_content_type' => $this->t('Multilingual support.'),
      'i18n_lock_node' => $this->t('Lock language.'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $type = $row->getSourceProperty('type');
    $row->setSourceProperty('language_content_type', $this->variableGet('language_content_type_' . $type, NULL));
    $row->setSourceProperty('i18n_lock_node', $this->variableGet('i18n_lock_node_' . $type, 0));
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    return $ids;
  }

}
