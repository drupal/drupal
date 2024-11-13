<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestForm;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_cache',
  label: new TranslatableMarkup('Test entity with field cache'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
  ],
  handlers: [
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
    ],
  ],
  base_table: 'entity_test_cache',
)]
class EntityTestCache extends EntityTest {

}
