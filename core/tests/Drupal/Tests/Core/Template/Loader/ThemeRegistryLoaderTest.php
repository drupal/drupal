<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Template\Loader;

use Drupal\Core\Template\Loader\ThemeRegistryLoader;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Template\Loader\ThemeRegistryLoader
 * @group Template
 */
class ThemeRegistryLoaderTest extends UnitTestCase {

  /**
   * @covers ::findTemplate
   */
  public function testLoaderReturnsFalseForExistsOnNonexistent() {
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
