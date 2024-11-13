<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\EntityViewsData;

/**
 * Test entity class with no bundle.
 */
#[ContentEntityType(
  id: 'entity_test_no_bundle',
  label: new TranslatableMarkup('Entity Test without bundle'),
  entity_keys: [
    'id' => 'id',
    'revision' => 'revision_id',
  ],
  handlers: [
    'views_data' => EntityViewsData::class,
  ],
  links: [
    'add-form' => '/entity_test_no_bundle/add',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_no_bundle',
)]
class EntityTestNoBundle extends EntityTest {

}
