<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissions;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the RefinableCalculatedPermissions class.
 *
 * @covers \Drupal\Core\Session\CalculatedPermissionsTrait
 * @covers \Drupal\Core\Session\RefinableCalculatedPermissions
 * @group Session
 */
class RefinableCalculatedPermissionsTest extends UnitTestCase {

  /**
   * Tests the addition of a calculated permissions item.
   */
  public function testAddItem(): void {
    $calculated_permissions = new RefinableCalculatedPermissions();
    $scope = 'some_scope';

    $item = new CalculatedPermissionsItem(['bar'], FALSE, $scope, 'foo');
    $calculated_permissions->addItem($item);
    $this->assertSame($item, $calculated_permissions->getItem($scope, 'foo'), 'Managed to retrieve the calculated permissions item.');

    $item = new CalculatedPermissionsItem(['baz'], FALSE, $scope, 'foo');
    $calculated_permissions->addItem($item);
    $this->assertEquals(['bar', 'baz'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Adding a calculated permissions item that was already in the list merges them.');

    $item = new CalculatedPermissionsItem(['cat'], TRUE, $scope, 'foo');
    $calculated_permissions->addItem($item);
    $this->assertEquals([], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Merging in a calculated permissions item with admin rights empties the permissions.');
    $this->assertTrue($calculated_permissions->getItem($scope, 'foo')->isAdmin(), 'Merging in a calculated permissions item with admin rights flags the result as having admin rights.');
  }

  /**
   * Tests the overwriting of a calculated permissions item.
   *
   * @depends testAddItem
   */
  public function testAddItemOverwrite(): void {
    $calculated_permissions = new RefinableCalculatedPermissions();
    $scope = 'some_scope';

    $item = new CalculatedPermissionsItem(['bar'], FALSE, $scope, 'foo');
    $calculated_permissions->addItem($item);

    $item = new CalculatedPermissionsItem(['baz'], FALSE, $scope, 'foo');
    $calculated_permissions->addItem($item, TRUE);
    $this->assertEquals(['baz'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Successfully overwrote an item that was already in the list.');
  }

  /**
   * Tests the removal of a calculated permissions item.
   *
   * @depends testAddItem
   */
  public function testRemoveItem(): void {
    $scope = 'some_scope';
    $item = new CalculatedPermissionsItem(['bar'], FALSE, $scope, 'foo');

    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions->addItem($item);
    $calculated_permissions->removeItem($scope, 'foo');
    $this->assertFalse($calculated_permissions->getItem($scope, 'foo'), 'Could not retrieve a removed item.');
  }

  /**
   * Tests the removal of all calculated permissions items.
   *
   * @depends testAddItem
   */
  public function testRemoveItems(): void {
    $scope = 'some_scope';
    $item = new CalculatedPermissionsItem(['bar'], FALSE, $scope, 'foo');

    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions->addItem($item);

    $calculated_permissions->removeItems();
    $this->assertFalse($calculated_permissions->getItem($scope, 'foo'), 'Could not retrieve a removed item.');
  }

  /**
   * Tests the removal of calculated permissions items by scope.
   *
   * @depends testAddItem
   */
  public function testRemoveItemsByScope(): void {
    $scope_a = 'cat';
    $scope_b = 'dog';

    $item_a = new CalculatedPermissionsItem(['bar'], FALSE, $scope_a, 'foo');
    $item_b = new CalculatedPermissionsItem(['baz'], FALSE, $scope_b, 1);

    $calculated_permissions = (new RefinableCalculatedPermissions())
      ->addItem($item_a)
      ->addItem($item_b);

    $calculated_permissions->removeItemsByScope($scope_a);
    $this->assertFalse($calculated_permissions->getItem($scope_a, 'foo'), 'Could not retrieve a removed item.');
    $this->assertNotFalse($calculated_permissions->getItem($scope_b, 1), 'Untouched scope item was found.');
  }

  /**
   * Tests merging in another CalculatedPermissions object.
   *
   * @depends testAddItem
   */
  public function testMerge(): void {
    $scope = 'some_scope';

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());

    $item_a = new CalculatedPermissionsItem(['baz'], FALSE, $scope, 'foo');
    $item_b = new CalculatedPermissionsItem(['bob', 'charlie'], FALSE, $scope, 'foo');
    $item_c = new CalculatedPermissionsItem([], FALSE, $scope, 'bar');
    $item_d = new CalculatedPermissionsItem([], FALSE, $scope, 'baz');

    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions
      ->addItem($item_a)
      ->addItem($item_c)
      ->addCacheContexts(['foo'])
      ->addCacheTags(['foo']);

    $other = new RefinableCalculatedPermissions();
    $other
      ->addItem($item_b)
      ->addItem($item_d)
      ->addCacheContexts(['bar'])
      ->addCacheTags(['bar']);

    $calculated_permissions->merge($other);
    $this->assertNotFalse($calculated_permissions->getItem($scope, 'bar'), 'Original item that did not conflict was kept.');
    $this->assertNotFalse($calculated_permissions->getItem($scope, 'baz'), 'Incoming item that did not conflict was added.');
    $this->assertSame(['baz', 'bob', 'charlie'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Permissions were merged properly.');
    $this->assertEqualsCanonicalizing(['bar', 'foo'], $calculated_permissions->getCacheContexts(), 'Cache contexts were merged properly');
    $this->assertEqualsCanonicalizing(['bar', 'foo'], $calculated_permissions->getCacheTags(), 'Cache tags were merged properly');
  }

}
