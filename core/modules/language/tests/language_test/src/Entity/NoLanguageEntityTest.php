<?php

namespace Drupal\language_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "no_language_entity_test",
 *   label = @Translation("Test entity without language support"),
 *   handlers = {
 *     "views_data" = "Drupal\entity_test\EntityTestViewsData"
 *   },
 *   base_table = "no_language_entity_test",
 *   persistent_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class NoLanguageEntityTest extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the test entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the test entity.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
