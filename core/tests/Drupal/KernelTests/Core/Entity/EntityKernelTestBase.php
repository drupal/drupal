<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines an abstract test base for entity kernel tests.
 */
abstract class EntityKernelTestBase extends KernelTestBase {
  use UserCreationTrait {
    checkPermissions as drupalCheckPermissions;
    createAdminRole as drupalCreateAdminRole;
    createRole as drupalCreateRole;
    createUser as drupalCreateUser;
    grantPermissions as drupalGrantPermissions;
    setCurrentUser as drupalSetCurrentUser;
    setUpCurrentUser as drupalSetUpCurrentUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->state = $this->container->get('state');

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    // If the concrete test sub-class installs the Node or Comment modules,
    // ensure that the node and comment entity schema are created before the
    // field configurations are installed. This is because the entity tables
    // need to be created before the body field storage tables. This prevents
    // trying to create the body field tables twice.
    $class = static::class;
    while ($class) {
      if (property_exists($class, 'modules')) {
        // Only check the modules, if the $modules property was not inherited.
        $rp = new \ReflectionProperty($class, 'modules');
        if ($rp->class == $class) {
          foreach (array_intersect(['node', 'comment'], $class::$modules) as $module) {
            $this->installEntitySchema($module);
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
   * @param array $permissions
   *   Array of permission names to assign to user. Note that the user always
   *   has the default permissions derived from the "authenticated users" role.
   * @param string $name
   *   The user name.
   * @param bool $admin
   *   (optional) Whether the user should be an administrator
   *   with all the available permissions.
   * @param array $values
   *   (optional) An array of initial user field values.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser(array $permissions = [], $name = NULL, bool $admin = FALSE, array $values = []) {
    // Allow for the old signature of this method:
    // createUser($values = [], $permissions = [])
    if (!array_is_list($permissions)) {
      // An array with keys is assumed to be entity values rather than
      // permissions, since there is no point in an array of permissions having
      // keys.
      @trigger_error('Calling createUser() with $values as the first parameter is deprecated in drupal:10.1.0 and will be removed from drupal:11.0.0. Use createUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) instead. See https://www.drupal.org/node/3330762', E_USER_DEPRECATED);

      $values = $permissions;
      $permissions = [];
    }

    if (is_array($name)) {
      // If $name is an array rather than a string, then the caller is intending
      // to pass in $permissions.
      @trigger_error('Calling createUser() with $permissions as the second parameter is deprecated in drupal:10.1.0 and will be removed from drupal:11.0.0. Use createUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) instead. See https://www.drupal.org/node/3330762', E_USER_DEPRECATED);

      $permissions = $name;
      $name = NULL;
    }

    return $this->drupalCreateUser($permissions, $name, $admin, $values);
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
    $controller = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
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

    $this->entityTypeManager = $this->container->get('entity_type.manager');
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
