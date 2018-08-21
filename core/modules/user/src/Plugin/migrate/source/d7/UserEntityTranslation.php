<?php

namespace Drupal\user\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Provides Drupal 7 user entity translations source plugin.
 *
 * @MigrateSource(
 *   id = "d7_user_entity_translation",
 *   source_module = "entity_translation"
 * )
 */
class UserEntityTranslation extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('entity_translation', 'et')
      ->fields('et')
      ->condition('et.entity_type', 'user')
      ->condition('et.source', '', '<>');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $uid = $row->getSourceProperty('entity_id');
    $language = $row->getSourceProperty('language');

    // Get Field API field values.
    foreach ($this->getFields('user') as $field_name => $field) {
      // Ensure we're using the right language if the entity is translatable.
      $field_language = $field['translatable'] ? $language : NULL;
      $row->setSourceProperty($field_name, $this->getFieldValues('user', $field_name, $uid, NULL, $field_language));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_type' => $this->t('The entity type this translation relates to'),
      'entity_id' => $this->t('The entity id this translation relates to'),
      'revision_id' => $this->t('The entity revision id this translation relates to'),
      'language' => $this->t('The target language for this translation.'),
      'source' => $this->t('The source language from which this translation was created.'),
      'uid' => $this->t('The author of this translation.'),
      'status' => $this->t('Boolean indicating whether the translation is published (visible to non-administrators).'),
      'translate' => $this->t('A boolean indicating whether this translation needs to be updated.'),
      'created' => $this->t('The Unix timestamp when the translation was created.'),
      'changed' => $this->t('The Unix timestamp when the translation was most recently saved.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_id' => [
        'type' => 'integer',
      ],
      'language' => [
        'type' => 'string',
      ],
    ];
  }

}
