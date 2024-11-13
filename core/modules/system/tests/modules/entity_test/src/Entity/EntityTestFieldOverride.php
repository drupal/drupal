<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a test entity class for testing default values.
 */
#[ContentEntityType(
  id: 'entity_test_field_override',
  label: new TranslatableMarkup('Test entity field overrides'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
  ],
  base_table: 'entity_test_field_override',
)]
class EntityTestFieldOverride extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['name']->setDescription('The default description.');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    if ($bundle == 'some_test_bundle') {
      $fields['name'] = clone $base_field_definitions['name'];
      $fields['name']->setDescription('Custom description.');
    }
    return $fields;
  }

}
