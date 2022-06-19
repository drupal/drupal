<?php

namespace Drupal\Tests\user\Functional\Rest;

use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;
use Drupal\user\Entity\Role;

abstract class RoleResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'user_role';

  /**
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
  protected function getExpectedNormalizedEntity() {
    return [
      'uuid' => $this->entity->uuid(),
      'weight' => 2,
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'llama',
      'label' => 'Llama',
      'is_admin' => NULL,
      'permissions' => [],
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
