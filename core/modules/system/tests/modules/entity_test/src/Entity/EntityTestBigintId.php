<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_bigint_id",
 *   label = @Translation("Entity Test with bigint id"),
 *   internal = TRUE,
 *   persistent_cache = FALSE,
 *   base_table = "entity_test_bigint_id",
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *   },
 * )
 */
class EntityTestBigintId extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id']->setSettings([
      'size' => 'big',
    ]);

    return $fields;
  }

}
