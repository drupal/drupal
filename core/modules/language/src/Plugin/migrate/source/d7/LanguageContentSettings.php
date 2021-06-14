<?php

namespace Drupal\language\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 i18n node settings from database.
 *
 * For available configuration keys, refer to the parent classes:
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBas
 *
 * @MigrateSource(
 *   id = "d7_language_content_settings",
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
    $i18n_node_options = $this->variableGet('i18n_node_options_' . $type, NULL);
    if ($i18n_node_options && in_array('lock', $i18n_node_options)) {
      $row->setSourceProperty('i18n_lock_node', 1);
    }
    else {
      $row->setSourceProperty('i18n_lock_node', 0);
    }

    // Get the entity translation entity settings.
    $entity_translation_entity_types = $this->variableGet('entity_translation_entity_types', NULL);
    $row->setSourceProperty('entity_translation_entity_types', $entity_translation_entity_types);
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
