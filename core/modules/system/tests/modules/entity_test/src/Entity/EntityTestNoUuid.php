<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestAccessControlHandler;

/**
 * Test entity class with revisions but without UUIDs.
 */
#[ContentEntityType(
  id: 'entity_test_no_uuid',
  label: new TranslatableMarkup('Test entity without UUID'),
  persistent_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'revision' => 'vid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'access' => EntityTestAccessControlHandler::class,
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_no_uuid',
  revision_table: 'entity_test_no_uuid_revision',
)]
class EntityTestNoUuid extends EntityTest {

}
