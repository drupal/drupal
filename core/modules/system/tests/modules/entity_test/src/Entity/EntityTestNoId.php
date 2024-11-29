<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_no_id',
  label: new TranslatableMarkup('Entity Test without id'),
  entity_keys: [
    'bundle' => 'type',
  ],
  handlers: [
    'storage' => ContentEntityNullStorage::class,
  ],
  links: [
    'add-form' => '/entity_test_no_id/add/{type}',
    'add-page' => '/entity_test_no_id/add',
  ],
  admin_permission: 'administer entity_test content',
  field_ui_base_route: 'entity.entity_test_no_id.admin_form',
)]
class EntityTestNoId extends EntityTest {

}
