<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestDeleteForm;
use Drupal\entity_test\EntityTestForm;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_mul',
  label: new TranslatableMarkup('Test entity - data table'),
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
    'views_data' => EntityViewsData::class,
    'route_provider' => ['html' => DefaultHtmlRouteProvider::class],
  ],
  links: [
    'add-page' => '/entity_test_mul/add',
    'add-form' => '/entity_test_mul/add/{type}',
    'canonical' => '/entity_test_mul/manage/{entity_test_mul}',
    'edit-form' => '/entity_test_mul/manage/{entity_test_mul}/edit',
    'delete-form' => '/entity_test/delete/entity_test_mul/{entity_test_mul}',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_mul',
  data_table: 'entity_test_mul_property_data',
  translatable: TRUE,
  field_ui_base_route: 'entity.entity_test_mul.admin_form',
)]
class EntityTestMul extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    return parent::baseFieldDefinitions($entity_type);
  }

}
