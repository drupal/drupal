<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\jsonapi\JsonApiSpec;
use Drupal\Core\Url;
use Drupal\system\Entity\Action;
use Drupal\user\RoleInterface;

/**
 * JSON:API integration test for the "Action" config entity type.
 *
 * @group Action
 */
class ActionTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'action';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'action--action';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\system\ActionConfigEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    $this->grantPermissionsToTestedRole(['administer actions']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $action = Action::create([
      'id' => 'user_add_role_action.' . RoleInterface::ANONYMOUS_ID,
      'type' => 'user',
      'label' => 'Add the anonymous role to the selected users',
      'configuration' => [
        'rid' => RoleInterface::ANONYMOUS_ID,
      ],
      'plugin' => 'user_add_role_action',
    ]);
    $action->save();

    return $action;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $self_url = Url::fromUri('base:/jsonapi/action/action/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'action--action',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'configuration' => [
            'rid' => 'anonymous',
          ],
          'dependencies' => [
            'config' => ['user.role.anonymous'],
            'module' => ['user'],
          ],
          'label' => 'Add the anonymous role to the selected users',
          'langcode' => 'en',
          'plugin' => 'user_add_role_action',
          'status' => TRUE,
          'action_type' => 'user',
          'drupal_internal__id' => 'user_add_role_action.anonymous',
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
