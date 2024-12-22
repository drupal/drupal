<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\jsonapi\JsonApiSpec;
use Drupal\Core\Url;
use Drupal\system\Entity\Menu;

/**
 * JSON:API integration test for the "Menu" config entity type.
 *
 * @group jsonapi
 */
class MenuTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'menu';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'menu--menu';

  /**
   * {@inheritdoc}
   */
  protected static $anonymousUsersCanViewLabels = TRUE;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\system\MenuInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    $this->grantPermissionsToTestedRole(['administer menu']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $menu = Menu::create([
      'id' => 'menu',
      'label' => 'Menu',
      'description' => 'Menu',
    ]);
    $menu->save();

    return $menu;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $self_url = Url::fromUri('base:/jsonapi/menu/menu/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => JsonApiSpec::SUPPORTED_SPECIFICATION_PERMALINK],
          ],
        ],
        'version' => JsonApiSpec::SUPPORTED_SPECIFICATION_VERSION,
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'menu--menu',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [],
          'description' => 'Menu',
          'label' => 'Menu',
          'langcode' => 'en',
          'locked' => FALSE,
          'status' => TRUE,
          'drupal_internal__id' => 'menu',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument(): array {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

}
