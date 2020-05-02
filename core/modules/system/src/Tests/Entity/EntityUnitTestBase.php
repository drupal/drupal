<?php

namespace Drupal\system\Tests\Entity;

@trigger_error(__FILE__ . ' is deprecated in Drupal 8.1.0 and will be removed before Drupal 9.0.0. Use \Drupal\KernelTests\Core\Entity\EntityKernelTestBase instead.', E_USER_DEPRECATED);

use Drupal\simpletest\KernelTestBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Defines an abstract test base for entity unit tests.
 *
 * @deprecated in drupal:8.1.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\KernelTests\Core\Entity\EntityKernelTestBase instead.
 */
abstract class EntityUnitTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
  ];

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * A list of generated identifiers.
   *
   * @var array
   */
  protected $generatedIds = [];

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

    // If the concrete test sub-class installs the Node or Comment modules,
    // ensure that the node and comment entity schema are created before the
    // field configurations are installed. This is because the entity tables
    // need to be created before the body field storage tables. This prevents
    // trying to create the body field tables twice.
    $class = get_class($this);
    while ($class) {
      if (property_exists($class, 'modules')) {
        // Only check the modules, if the $modules property was not inherited.
        $rp = new \ReflectionProperty($class, 'modules');
        if ($rp->class == $class) {
          foreach (array_intersect(['node', 'comment'], $class::$modules) as $module) {
            $this->installEntitySchema($module);
          }
          if (in_array('forum', $class::$modules, TRUE)) {
            // Forum module is particular about the order that dependencies are
            // enabled in. The comment, node and taxonomy config and the
            // taxonomy_term schema need to be installed before the forum config
            // which in turn needs to be installed before field config.
            $this->installConfig(['comment', 'node', 'taxonomy']);
            $this->installEntitySchema('taxonomy_term');
            $this->installConfig(['forum']);
          }
        }
      }
      $class = get_parent_class($class);
    }

    $this->installConfig(['field']);
  }

  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   * @param array $permissions
   *   (optional) Array of permission names to assign to user.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser($values = [], $permissions = []) {
    if ($permissions) {
      // Create a new role and apply permissions to it.
      $role = Role::create([
        'id' => strtolower($this->randomMachineName(8)),
        'label' => $this->randomMachineName(8),
      ]);
      $role->save();
      user_role_grant_permissions($role->id(), $permissions);
      $values['roles'][] = $role->id();
    }

    $account = User::create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
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
    $controller->resetCache([$entity->id()]);
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
    $this->state->set($key, []);
    return $hooks;
  }

  /**
   * Installs a module and refreshes services.
   *
   * @param string $module
   *   The module to install.
   */
  protected function installModule($module) {
    $this->enableModules([$module]);
    $this->refreshServices();
  }

  /**
   * Uninstalls a module and refreshes services.
   *
   * @param string $module
   *   The module to uninstall.
   */
  protected function uninstallModule($module) {
    $this->disableModules([$module]);
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

  /**
   * Generates a random ID avoiding collisions.
   *
   * @param bool $string
   *   (optional) Whether the id should have string type. Defaults to FALSE.
   *
   * @return int|string
   *   The entity identifier.
   */
  protected function generateRandomEntityId($string = FALSE) {
    srand(time());
    do {
      // 0x7FFFFFFF is the maximum allowed value for integers that works for all
      // Drupal supported databases and is known to work for other databases
      // like SQL Server 2014 and Oracle 10 too.
      $id = $string ? $this->randomMachineName() : mt_rand(1, 0x7FFFFFFF);
    } while (isset($this->generatedIds[$id]));
    $this->generatedIds[$id] = $id;
    return $id;
  }

}
