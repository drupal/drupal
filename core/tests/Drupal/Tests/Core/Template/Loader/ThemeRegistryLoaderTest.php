<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Template\Loader;

use Drupal\Core\Template\Loader\ThemeRegistryLoader;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Template\Loader\ThemeRegistryLoader.
 */
#[CoversClass(ThemeRegistryLoader::class)]
#[Group('Template')]
class ThemeRegistryLoaderTest extends UnitTestCase {

  /**
   * Tests loader returns false for exists on nonexistent.
   *
   * @legacy-covers ::findTemplate
   */
  public function testLoaderReturnsFalseForExistsOnNonexistent(): void {
    $registry = $this->prophesize('Drupal\Core\Theme\Registry');
    $runtime = $this->prophesize('Drupal\Core\Utility\ThemeRegistry');
    $runtime->has('foo')
      ->shouldBeCalled()
      ->willReturn(FALSE);
    $registry->getRuntime()->willReturn($runtime);

    $loader = new ThemeRegistryLoader($registry->reveal());
    $this->assertFalse($loader->exists('foo'));
  }

}
