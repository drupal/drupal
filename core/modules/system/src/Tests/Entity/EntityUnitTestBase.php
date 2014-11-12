<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityUnitTestBase.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an abstract test base for entity unit tests.
 */
abstract class EntityUnitTestBase extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity', 'user', 'system', 'field', 'text', 'filter', 'entity_test', 'entity_reference');

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->container->get('entity.manager');
    $this->state = $this->container->get('state');

    $this->installSchema('system', 'sequences');

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    // If the concrete test sub-class installs node.module, ensure that the node
    // entity schema is created before the field configurations are installed,
    // because the node entity tables need to be created before the body field
    // storage tables. This prevents trying to create the body field tables
    // twice.
    $class = get_class($this);
    while ($class) {
      if (property_exists($class, 'modules')) {
        // Only check the modules, if the $modules property was not inherited.
        $rp = new \ReflectionProperty($class, 'modules');
        if ($rp->class == $class) {
          if (in_array('node', $class::$modules, TRUE)) {
            $this->installEntitySchema('node');
            break;
          }
        }
      }
      $class = get_parent_class($class);
    }

    $this->installConfig(array('field'));
  }

  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   * @param array $permissions
   *   (optional) Array of permission names to assign to user. The
   *   users_roles tables must be installed before this can be used.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser($values = array(), $permissions = array()) {
    if ($permissions) {
      // Create a new role and apply permissions to it.
      $role = entity_create('user_role', array(
        'id' => strtolower($this->randomMachineName(8)),
        'label' => $this->randomMachineName(8),
      ));
      $role->save();
      user_role_grant_permissions($role->id(), $permissions);
      $values['roles'][] = $role->id();
    }

    $account = entity_create('user', $values + array(
      'name' => $this->randomMachineName(),
      'status' => 1,
    ));
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

  /**
   * Reloads the given entity from the storage and returns it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be reloaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity) {
    $controller = $this->entityManager->getStorage($entity->getEntityTypeId());
    $controller->resetCache(array($entity->id()));
    return $controller->load($entity->id());
  }

  /**
   * Returns the entity_test hook invocation info.
   *
   * @return array
   *   An associative array of arbitrary hook data keyed by hook name.
   */
  protected function getHooksInfo() {
    $key = 'entity_test.hooks';
    $hooks = $this->state->get($key);
    $this->state->set($key, array());
    return $hooks;
  }

  /**
   * Installs a module and refreshes services.
   *
   * @param string $module
   *   The module to install.
   */
  protected function installModule($module) {
    $this->enableModules(array($module));
    $this->refreshServices();
  }

  /**
   * Uninstalls a module and refreshes services.
   *
   * @param string $module
   *   The module to uninstall.
   */
  protected function uninstallModule($module) {
    $this->disableModules(array($module));
    $this->refreshServices();
  }

  /**
   * Refresh services.
   */
  protected function refreshServices() {
    $this->container = \Drupal::getContainer();
    $this->entityManager = $this->container->get('entity.manager');
    $this->state = $this->container->get('state');
  }

}
