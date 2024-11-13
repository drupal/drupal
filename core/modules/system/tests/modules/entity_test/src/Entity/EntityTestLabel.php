<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;

/**
 * Test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_label',
  label: new TranslatableMarkup('Entity Test label'),
  render_cache: FALSE,
  entity_keys: [
    'uuid' => 'uuid',
    'id' => 'id',
    'label' => 'name',
    'bundle' => 'type',
    'langcode' => 'langcode',
  ],
  handlers: [
    'access' => EntityTestAccessControlHandler::class,
    'view_builder' => TestViewBuilder::class,
  ],
  base_table: 'entity_test_label',
)]
class EntityTestLabel extends EntityTest {

}
