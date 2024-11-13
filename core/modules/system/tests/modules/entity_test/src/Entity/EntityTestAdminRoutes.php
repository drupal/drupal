<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestDeleteForm;
use Drupal\entity_test\EntityTestForm;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines a test entity type with administrative routes.
 */
#[ContentEntityType(
  id: 'entity_test_admin_routes',
  label: new TranslatableMarkup('Test entity - admin routes'),
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
    'route_provider' => ['html' => AdminHtmlRouteProvider::class],
  ],
  links: [
    'canonical' => '/entity_test_admin_routes/manage/{entity_test_admin_routes}',
    'edit-form' => '/entity_test_admin_routes/manage/{entity_test_admin_routes}/edit',
    'delete-form' => '/entity_test/delete/entity_test_admin_routes/{entity_test_admin_routes}',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_admin_routes',
  data_table: 'entity_test_admin_routes_property_data',
  translatable: TRUE,
)]
class EntityTestAdminRoutes extends EntityTest {

}
