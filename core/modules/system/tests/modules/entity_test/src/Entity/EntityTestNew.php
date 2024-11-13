<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the test entity class for testing definition addition.
 *
 * This entity type is initially not defined. It is enabled when needed to test
 * the related updates.
 */
#[ContentEntityType(
  id: 'entity_test_new',
  label: new TranslatableMarkup('New test entity'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  base_table: 'entity_test_new',
)]
class EntityTestNew extends EntityTest {
}
