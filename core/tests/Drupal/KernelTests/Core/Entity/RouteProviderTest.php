<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestAdminRoutes;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests route providers for entity types.
 *
 * @group Entity
 */
class RouteProviderTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser(['uid' => 1]);

    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_admin_routes');

    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create([
      'id' => RoleInterface::ANONYMOUS_ID,
    ]);
    $role
      ->grantPermission('administer entity_test content')
      ->grantPermission('view test entity');
    $role->save();
  }

  protected function httpKernelHandle($url) {
    $request = Request::create($url);
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');
    return $http_kernel->handle($request, HttpKernelInterface::SUB_REQUEST)->getContent();
  }

  /**
   * @covers \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider::getRoutes
   */
  public function testHtmlRoutes() {
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');

    $route = $route_provider->getRouteByName('entity.entity_test_mul.canonical');
    $this->assertEquals('entity_test_mul.full', $route->getDefault('_entity_view'));
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityController::title', $route->getDefault('_title_callback'));
    $this->assertEquals('entity_test_mul.view', $route->getRequirement('_entity_access'));
    $this->assertFalse($route->hasOption('_admin_route'));

    $route = $route_provider->getRouteByName('entity.entity_test_mul.edit_form');
    $this->assertEquals('entity_test_mul.default', $route->getDefault('_entity_form'));
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityController::editTitle', $route->getDefault('_title_callback'));
    $this->assertEquals('entity_test_mul.update', $route->getRequirement('_entity_access'));
    $this->assertFalse($route->hasOption('_admin_route'));

    $route = $route_provider->getRouteByName('entity.entity_test_mul.delete_form');
    $this->assertEquals('entity_test_mul.delete', $route->getDefault('_entity_form'));
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityController::deleteTitle', $route->getDefault('_title_callback'));
    $this->assertEquals('entity_test_mul.delete', $route->getRequirement('_entity_access'));
    $this->assertFalse($route->hasOption('_admin_route'));

    $entity = EntityTestMul::create([
      'name' => 'Test title',
    ]);
    $entity->save();

    $this->setRawContent($this->httpKernelHandle($entity->toUrl()->toString()));
    $this->assertTitle('Test title | ');

    $this->setRawContent($this->httpKernelHandle($entity->toUrl('edit-form')->toString()));
    $this->assertTitle('Edit Test title | ');

    $this->setRawContent($this->httpKernelHandle($entity->toUrl('delete-form')->toString()));
    $this->assertTitle('Are you sure you want to delete the test entity - data table Test title? | ');
  }

  /**
   * @covers \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider::getEditFormRoute
   * @covers \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider::getDeleteFormRoute
   */
  public function testAdminHtmlRoutes() {
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');

    $route = $route_provider->getRouteByName('entity.entity_test_admin_routes.canonical');
    $this->assertEquals('entity_test_admin_routes.full', $route->getDefault('_entity_view'));
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityController::title', $route->getDefault('_title_callback'));
    $this->assertEquals('entity_test_admin_routes.view', $route->getRequirement('_entity_access'));
    $this->assertFalse($route->hasOption('_admin_route'));

    $route = $route_provider->getRouteByName('entity.entity_test_admin_routes.edit_form');
    $this->assertEquals('entity_test_admin_routes.default', $route->getDefault('_entity_form'));
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityController::editTitle', $route->getDefault('_title_callback'));
    $this->assertEquals('entity_test_admin_routes.update', $route->getRequirement('_entity_access'));
    $this->assertTrue($route->hasOption('_admin_route'));
    $this->assertTrue($route->getOption('_admin_route'));

    $route = $route_provider->getRouteByName('entity.entity_test_admin_routes.delete_form');
    $this->assertEquals('entity_test_admin_routes.delete', $route->getDefault('_entity_form'));
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityController::deleteTitle', $route->getDefault('_title_callback'));
    $this->assertEquals('entity_test_admin_routes.delete', $route->getRequirement('_entity_access'));
    $this->assertTrue($route->hasOption('_admin_route'));
    $this->assertTrue($route->getOption('_admin_route'));

    $entity = EntityTestAdminRoutes::create([
      'name' => 'Test title',
    ]);
    $entity->save();

    $this->setRawContent($this->httpKernelHandle($entity->toUrl()->toString()));
    $this->assertTitle('Test title | ');

    $this->setRawContent($this->httpKernelHandle($entity->toUrl('edit-form')->toString()));
    $this->assertTitle('Edit Test title | ');

    $this->setRawContent($this->httpKernelHandle($entity->toUrl('delete-form')->toString()));
    $this->assertTitle('Are you sure you want to delete the test entity - admin routes Test title? | ');
  }

}
