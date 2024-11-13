<?php

declare(strict_types=1);

namespace Drupal\content_translation_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_translatable_UI_skip',
  label: new TranslatableMarkup('Test entity - Translatable skip UI check'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  base_table: 'entity_test_mul',
  data_table: 'entity_test_mul_property_data',
  translatable: TRUE,
  additional: [
    'content_translation_ui_skip' => TRUE,
  ],
)]
class EntityTestTranslatableUISkip extends EntityTest {

}
