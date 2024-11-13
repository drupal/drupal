<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestDeleteForm;
use Drupal\entity_test\EntityTestForm;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines a test entity class using a custom langcode entity key.
 */
#[ContentEntityType(
  id: 'entity_test_mul_langcode_key',
  label: new TranslatableMarkup('Test entity - data table - langcode key'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'custom_langcode_key',
    'default_langcode' => 'custom_default_langcode_key',
  ],
  handlers: [
    'view_builder' => TestViewBuilder::class,
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
      'delete' => EntityTestDeleteForm::class,
    ],
    'views_data' => EntityViewsData::class,
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-form' => '/entity_test_mul_langcode_key/add',
    'canonical' => '/entity_test_mul_langcode_key/manage/{entity_test_mul_langcode_key}',
    'edit-form' => '/entity_test_mul_langcode_key/manage/{entity_test_mul_langcode_key}/edit',
    'delete-form' => '/entity_test/delete/entity_test_mul_langcode_key/{entity_test_mul_langcode_key}',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_mul_langcode_key',
  data_table: 'entity_test_mul_langcode_key_field_data', translatable: TRUE,
  field_ui_base_route: 'entity.entity_test_mul_langcode_key.admin_form',
)]
class EntityTestMulLangcodeKey extends EntityTest {

}
