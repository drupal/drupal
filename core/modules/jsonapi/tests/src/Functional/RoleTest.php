<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\user\Entity\Role;

/**
 * JSON:API integration test for the "Role" config entity type.
 *
 * @group jsonapi
 */
class RoleTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'user_role';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'user_role--user_role';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer permissions']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $role = Role::create([
      'id' => 'llama',
      'label' => 'Llama',
    ]);
    $role->save();

    return $role;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/user_role/user_role/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'user_role--user_role',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'weight' => 2,
          'langcode' => 'en',
          'status' => TRUE,
          'dependencies' => [],
          'label' => 'Llama',
          'is_admin' => NULL,
          'permissions' => [],
          'drupal_internal__id' => 'llama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

}
