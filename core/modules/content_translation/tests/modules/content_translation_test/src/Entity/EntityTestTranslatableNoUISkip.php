<?php

declare(strict_types=1);

namespace Drupal\content_translation_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\EntityTestForm;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_translatable_no_skip',
  label: new TranslatableMarkup('Test entity - Translatable check UI'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'form' => ['default' => EntityTestForm::class],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'edit-form' => '/entity_test_translatable_no_skip/{entity_test_translatable_no_skip}/edit',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_mul',
  data_table: 'entity_test_mul_property_data',
  translatable: TRUE,
)]
class EntityTestTranslatableNoUISkip extends EntityTest {

}
