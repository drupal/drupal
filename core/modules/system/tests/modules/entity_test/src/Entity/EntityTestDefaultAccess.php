<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a test entity class with no access control handler.
 */
#[ContentEntityType(
  id: 'entity_test_default_access',
  label: new TranslatableMarkup('Test entity with default access'),
  entity_keys: [
    'id' => 'id',
    'bundle' => 'type',
  ],
  base_table: 'entity_test_default_access',
)]
class EntityTestDefaultAccess extends EntityTest {

}
