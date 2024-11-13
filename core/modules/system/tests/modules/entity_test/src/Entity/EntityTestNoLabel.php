<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestAccessControlHandler;

/**
 * Test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_no_label',
  label: new TranslatableMarkup('Entity Test without label'),
  persistent_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
  ],
  handlers: [
    'access' => EntityTestAccessControlHandler::class,
  ],
  base_table: 'entity_test_no_label',
  internal: TRUE,
)]
class EntityTestNoLabel extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

}
