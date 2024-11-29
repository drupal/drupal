<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestForm;

/**
 * Test entity class routes.
 */
#[ContentEntityType(
  id: 'entity_test_add_page',
  label: new TranslatableMarkup('Entity test route add page'),
  render_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
  ],
  handlers: [
    'form' => [
      'default' => EntityTestForm::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-page' => '/entity_test_add_page/{user}/add',
    'add-form' => '/entity_test_add_page/add/{type}/{user}/form',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_add_page',
)]
class EntityTestAddPage extends EntityTest {
}
