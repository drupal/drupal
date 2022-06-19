<?php

namespace Drupal\Tests\system\Functional\Rest;

use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;
use Drupal\system\Entity\Action;
use Drupal\user\RoleInterface;

abstract class ActionResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['action', 'user'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'action';

  /**
   * @var \Drupal\system\ActionConfigEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer actions']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $action = Action::create([
      'id' => 'user_add_role_action.' . RoleInterface::ANONYMOUS_ID,
      'type' => 'user',
      'label' => t('Add the anonymous role to the selected users'),
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
  protected function getExpectedNormalizedEntity() {
    return [
      'configuration' => [
        'rid' => 'anonymous',
      ],
      'dependencies' => [
        'config' => ['user.role.anonymous'],
        'module' => ['user'],
      ],
      'id' => 'user_add_role_action.anonymous',
      'label' => 'Add the anonymous role to the selected users',
      'langcode' => 'en',
      'plugin' => 'user_add_role_action',
      'status' => TRUE,
      'type' => 'user',
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

}
