<?php

namespace Drupal\Tests\user\Unit\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityDependency;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Form\EntityPermissionsForm;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\Routing\Route;

/**
 * Tests the permissions administration form for a bundle.
 *
 * @coversDefaultClass \Drupal\user\Form\EntityPermissionsForm
 * @group user
 */
class EntityPermissionsFormTest extends UnitTestCase {

  /**
   * Tests generating the permissions list.
   *
   * This is really a test of the protected method permissionsByProvider(). Call
   * the public method access() with an object $bundle, so that it skips most of
   * the logic in that function.
   *
   * @param string $dependency_name
   *   The test permission has a direct dependency on this.
   * @param bool $found
   *   TRUE if there is a permission to be managed by the form.
   *
   * @dataProvider providerTestPermissionsByProvider
   * @covers ::access()
   * @covers ::permissionsByProvider()
   */
  public function testPermissionsByProvider(string $dependency_name, bool $found) {

    // Mock the constructor parameters.
    $prophecy = $this->prophesize(PermissionHandlerInterface::class);
    $prophecy->getPermissions()
      ->willReturn([
        'permission name' => [
          'title' => 'permission display name',
          'provider' => 'some module',
          'dependencies' => ['config' => [$dependency_name]],
        ],
      ]);
    $permission_handler = $prophecy->reveal();
    $role_storage = $this->prophesize(RoleStorageInterface::class)->reveal();
    $module_handler = $this->prophesize(ModuleHandlerInterface::class)->reveal();
    $prophecy = $this->prophesize(ConfigManagerInterface::class);
    $prophecy->getConfigEntitiesToChangeOnDependencyRemoval('config', ['node.type.article'])
      ->willReturn([
        'delete' => [
          new ConfigEntityDependency('core.entity_view_display.node.article.full'),
          new ConfigEntityDependency('field.field.node.article.body'),
        ],
      ]);
    $config_manager = $prophecy->reveal();
    $prophecy = $this->prophesize(EntityTypeInterface::class);
    $prophecy->getPermissionGranularity()
      ->willReturn('entity_type');
    $entity_type = $prophecy->reveal();
    $prophecy = $this->prophesize(EntityTypeManagerInterface::class);
    $prophecy->getDefinition('entity_type')
      ->willReturn($entity_type);
    $entity_type_manager = $prophecy->reveal();

    $bundle_form = new EntityPermissionsForm($permission_handler, $role_storage, $module_handler, $config_manager, $entity_type_manager);

    // Mock the method parameters.
    $route = new Route('some.path');
    $route_match = $this->prophesize(RouteMatchInterface::class)->reveal();
    $prophecy = $this->prophesize(EntityTypeInterface::class);
    $prophecy->getBundleOf()
      ->willReturn('entity_type');
    $bundle_type = $prophecy->reveal();
    $prophecy = $this->prophesize(EntityInterface::class);
    $prophecy->getEntityType()
      ->willReturn($bundle_type);
    $prophecy->getConfigDependencyName()
      ->willReturn('node.type.article');
    $bundle = $prophecy->reveal();

    $access_actual = $bundle_form->access($route, $route_match, $bundle);
    $this->assertEquals($found ? AccessResult::allowed() : AccessResult::neutral(), $access_actual);
  }

  /**
   * Provides data for the testPermissionsByProvider method.
   *
   * @return array
   */
  public function providerTestPermissionsByProvider() {
    return [
      'direct dependency' => ['node.type.article', TRUE],
      'indirect dependency' => ['core.entity_view_display.node.article.full', TRUE],
      'not a dependency' => ['node.type.page', FALSE],
    ];
  }

}
