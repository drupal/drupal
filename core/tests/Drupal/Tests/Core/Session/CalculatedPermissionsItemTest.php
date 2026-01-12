<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the CalculatedPermissionsItem value object.
 */
#[CoversClass(CalculatedPermissionsItem::class)]
#[Group('Session')]
class CalculatedPermissionsItemTest extends UnitTestCase {

  /**
   * Tests that the object values were set in the constructor.
   *
   * @legacy-covers ::__construct
   * @legacy-covers ::getIdentifier
   * @legacy-covers ::getScope
   * @legacy-covers ::getPermissions
   * @legacy-covers ::isAdmin
   */
  public function testConstructor(): void {
    $scope = 'some_scope';

    $item = new CalculatedPermissionsItem(['bar', 'baz', 'bar'], FALSE, $scope, 'foo');
    $this->assertEquals($scope, $item->getScope(), 'Scope name was set correctly.');
    $this->assertEquals('foo', $item->getIdentifier(), 'Scope identifier was set correctly.');
    $this->assertEquals(['bar', 'baz'], $item->getPermissions(), 'Permissions were made unique and set correctly.');
    $this->assertFalse($item->isAdmin(), 'Admin flag was set correctly');

    $item = new CalculatedPermissionsItem(['bar', 'baz', 'bar'], TRUE, $scope, 'foo');
    $this->assertEquals([], $item->getPermissions(), 'Permissions were emptied out for an admin item.');
    $this->assertTrue($item->isAdmin(), 'Admin flag was set correctly');
  }

  /**
   * Tests the permission check when the admin flag is not set.
   */
  #[Depends('testConstructor')]
  public function testHasPermission(): void {
    $item = new CalculatedPermissionsItem(['bar'], FALSE, 'some_scope', 'foo');
    $this->assertFalse($item->hasPermission('baz'), 'Missing permission was not found.');
    $this->assertTrue($item->hasPermission('bar'), 'Existing permission was found.');
  }

  /**
   * Tests the permission check when the admin flag is set.
   */
  #[Depends('testConstructor')]
  public function testHasPermissionWithAdminFlag(): void {
    $item = new CalculatedPermissionsItem(['bar'], TRUE, 'some_scope', 'foo');
    $this->assertTrue($item->hasPermission('baz'), 'Missing permission was found.');
    $this->assertTrue($item->hasPermission('bar'), 'Existing permission was found.');
  }

}
