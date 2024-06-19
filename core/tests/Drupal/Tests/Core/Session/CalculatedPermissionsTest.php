<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\CalculatedPermissions;
use Drupal\Core\Session\CalculatedPermissionsInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CalculatedPermissions value object.
 *
 * @covers \Drupal\Core\Session\CalculatedPermissions
 * @covers \Drupal\Core\Session\CalculatedPermissionsTrait
 * @group Session
 */
class CalculatedPermissionsTest extends UnitTestCase {

  /**
   * Tests that the object values were set in the constructor.
   */
  public function testConstructor(): void {
    $item_a = new CalculatedPermissionsItem(['baz'], FALSE, 'scope_a', 'foo');
    $item_b = new CalculatedPermissionsItem(['bob', 'charlie'], FALSE, 'scope_b', 1);

    $calculated_permissions = $this->prophesize(CalculatedPermissionsInterface::class);
    $calculated_permissions->getItems()->willReturn([$item_a, $item_b]);
    $calculated_permissions->getCacheTags()->willReturn(['24']);
    $calculated_permissions->getCacheContexts()->willReturn(['Oct']);
    $calculated_permissions->getCacheMaxAge()->willReturn(1986);
    $calculated_permissions = new CalculatedPermissions($calculated_permissions->reveal());

    $this->assertSame($item_a, $calculated_permissions->getItem('scope_a', 'foo'), 'Managed to retrieve the calculated permissions item by scope and identifier.');
    $this->assertFalse($calculated_permissions->getItem('scope_a', '404-id-not-found'), 'Requesting a non-existent identifier fails correctly.');
    $this->assertSame([$item_a, $item_b], $calculated_permissions->getItems(), 'Successfully retrieved all items regardless of scope.');
    $this->assertSame(['scope_a', 'scope_b'], $calculated_permissions->getScopes(), 'Successfully retrieved all scopes.');
    $this->assertSame([$item_a], $calculated_permissions->getItemsByScope('scope_a'), 'Successfully retrieved all items by scope.');

    $this->assertSame(['24'], $calculated_permissions->getCacheTags(), 'Successfully inherited all cache tags.');
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'All cache contexts were cleared so they do not bubble up.');
    $this->assertSame(1986, $calculated_permissions->getCacheMaxAge(), 'Successfully inherited cache max-age.');
  }

}
