<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestDeleteForm;
use Drupal\entity_test\EntityTestForm;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines the multilingual test entity class with bundles.
 */
#[ContentEntityType(
  id: 'entity_test_mul_with_bundle',
  label: new TranslatableMarkup('Test entity multilingual with bundle - data table'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'view_builder' => TestViewBuilder::class,
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
      'delete' => EntityTestDeleteForm::class,
    ],
    'translation' => ContentTranslationHandler::class,
    'views_data' => EntityViewsData::class,
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-page' => '/entity_test_mul_with_bundle/add',
    'add-form' => '/entity_test_mul_with_bundle/add/{type}',
    'canonical' => '/entity_test_mul_with_bundle/manage/{entity_test_mul_with_bundle}',
    'edit-form' => '/entity_test_mul_with_bundle/manage/{entity_test_mul_with_bundle}/edit',
    'delete-form' => '/entity_test/delete/entity_test_mul_with_bundle/{entity_test_mul_with_bundle}',
  ],
  admin_permission: 'administer entity_test content',
  permission_granularity: 'bundle',
  bundle_entity_type: 'entity_test_mul_bundle',
  base_table: 'entity_test_mul_with_bundle',
  data_table: 'entity_test_mul_with_bundle_property_data',
  translatable: TRUE,
  field_ui_base_route: 'entity.entity_test_mul_with_bundle.admin_form'
)]
class EntityTestMulWithBundle extends EntityTest {

}
