<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Hook;

use Drupal\Core\Hook\ImplementationList;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests ImplementationList.
 */
#[Group('Hook')]
#[CoversClass(ImplementationList::class)]
class ImplementationListTest extends UnitTestCase {

  /**
   * Log for include files.
   *
   * @var list<string>
   */
  public static array $log = [];

  /**
   * Tests public methods on a common instance.
   *
   * This is easier than separate test methods.
   */
  public function testPublicMethods(): void {
    $listeners = [
      fn () => 'a0',
      fn () => 'b',
      fn () => 'a1',
    ];
    $modules = [
      'module_a',
      'module_b',
      // Repeat the same module.
      'module_a',
    ];
    $list = new ImplementationList($listeners, $modules);

    // Test public properties.
    $this->assertSame($listeners, $list->listeners);
    $this->assertSame($modules, $list->modules);

    // Test iterateByModule().
    $i = 0;
    foreach ($list->iterateByModule() as $module => $listener) {
      $this->assertSame($modules[$i], $module);
      $this->assertSame($listeners[$i], $listener);
      ++$i;
    }

    // Test getForModule().
    $this->assertSame([], $list->getForModule('other_module'));
    $this->assertSame([$listeners[0], $listeners[2]], $list->getForModule('module_a'));
    $this->assertSame([$listeners[1]], $list->getForModule('module_b'));

    // Test hasImplementations().
    $this->assertTrue($list->hasImplementations());

    // Test hasImplementationsForModules().
    $this->assertFalse($list->hasImplementationsForModules(['other_module']));
    $this->assertFalse($list->hasImplementationsForModules([]));
    $this->assertTrue($list->hasImplementationsForModules(['other_module', 'module_a']));
    $this->assertTrue($list->hasImplementationsForModules(['module_b']));
  }

  /**
   * Tests public methods on an empty list.
   */
  public function testEmptyList(): void {
    $list = new ImplementationList([], []);

    // Test public properties.
    $this->assertSame([], $list->listeners);
    $this->assertSame([], $list->modules);

    // Test iterateByModule().
    $iterator = $list->iterateByModule();
    $this->assertFalse($iterator->valid());

    // Test hasImplementations().
    $this->assertFalse($list->hasImplementations());

    // Test getForModule().
    $this->assertSame([], $list->getForModule('any_module'));

    // Test hasImplementationsForModules().
    $this->assertFalse($list->hasImplementationsForModules(['any_module']));
    $this->assertFalse($list->hasImplementationsForModules([]));
  }

}
