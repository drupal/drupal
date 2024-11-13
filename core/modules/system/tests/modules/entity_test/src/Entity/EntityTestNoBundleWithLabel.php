<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\EntityViewsData;

/**
 * Test entity class with no bundle but with label.
 */
#[ContentEntityType(
  id: 'entity_test_no_bundle_with_label',
  label: new TranslatableMarkup('Entity Test without bundle but with label'),
  entity_keys: [
    'id' => 'id',
    'label' => 'name',
    'revision' => 'revision_id',
  ],
  handlers: [
    'views_data' => EntityViewsData::class,
  ],
  links: [
    'add-form' => '/entity_test_no_bundle_with_label/add',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_no_bundle_with_label',
)]
class EntityTestNoBundleWithLabel extends EntityTest {

}
