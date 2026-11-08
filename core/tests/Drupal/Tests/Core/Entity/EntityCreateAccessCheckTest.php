<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityCreateAccessCheck;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Tests Drupal\Core\Entity\EntityCreateAccessCheck.
 */
#[CoversClass(EntityCreateAccessCheck::class)]
#[Group('Access')]
#[Group('Entity')]
class EntityCreateAccessCheckTest extends UnitTestCase {

  /**
   * The entity type manager.
   */
  public EntityTypeManagerInterface&Stub $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createStub(EntityTypeManagerInterface::class);
  }

  /**
   * Provides test data for testAccess.
   *
   * @return array
   *   An array of test data for testAccess.
   */
  public static function providerTestAccess(): array {
    $no_access = FALSE;
    $access = TRUE;

    return [
      ['', 'entity_test', $no_access, $no_access],
      ['', 'entity_test', $access, $access],
      ['test_entity', 'entity_test:test_entity', $access, $access],
      ['test_entity', 'entity_test:test_entity', $no_access, $no_access],
      ['test_entity', 'entity_test:{bundle_argument}', $access, $access],
      ['test_entity', 'entity_test:{bundle_argument}', $no_access, $no_access],
      ['', 'entity_test:{bundle_argument}', $no_access, $no_access, FALSE],
      // When the bundle is not provided, access should be denied even if the
      // access control handler would allow access.
      ['', 'entity_test:{bundle_argument}', $access, $no_access, FALSE],
    ];
  }

  /**
   * Tests the method for checking access to routes.
   */
  #[DataProvider('providerTestAccess')]
  public function testAccess(string $entity_bundle, string $requirement, bool $access, bool $expected, bool $expect_permission_context = TRUE): void {

    // Set up the access result objects for allowing or denying access.
    $access_result = $access ? AccessResult::allowed()->cachePerPermissions() : AccessResult::neutral()->cachePerPermissions();
    $expected_access_result = $expected ? AccessResult::allowed() : AccessResult::neutral();
    if ($expect_permission_context) {
      $expected_access_result->cachePerPermissions();
    }
    if (!$entity_bundle && !$expect_permission_context) {
      $expected_access_result->setReason("Could not find '{bundle_argument}' request argument, therefore cannot check create access.");
    }

    // Don't expect a call to the access control handler when we have a bundle
    // argument requirement but no bundle is provided.
    if ($entity_bundle || !str_contains($requirement, '{')) {
      $access_control_handler = $this->createMock('Drupal\Core\Entity\EntityAccessControlHandlerInterface');
      $access_control_handler->expects($this->once())
        ->method('createAccess')
        ->with($entity_bundle)
        ->willReturn($access_result);

      $this->entityTypeManager
        ->method('getAccessControlHandler')
        ->willReturn($access_control_handler);
    }

    $applies_check = new EntityCreateAccessCheck($this->entityTypeManager);

    $route = $this->createStub('Symfony\Component\Routing\Route');
    $route
      ->method('getRequirement')
      ->willReturnMap([
        ['_entity_create_access', $requirement],
      ]);

    $raw_variables = new InputBag();
    if ($entity_bundle) {
      $raw_variables->set('bundle_argument', $entity_bundle);
    }

    $route_match = $this->createStub(RouteMatchInterface::class);
    $route_match
      ->method('getRawParameters')
      ->willReturn($raw_variables);

    $account = $this->createStub(AccountInterface::class);
    $this->assertEquals($expected_access_result, $applies_check->access($route, $route_match, $account));
  }

}
