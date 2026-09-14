<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme;

use Drupal\default_admin\Hook\PreprocessHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Confirms that core/themes is autoloaded for tests.
 */
#[Group('Theme')]
class CoreThemesAutoloadedForTestsTest extends UnitTestCase {

  /**
   * Confirms that core/themes is autoloaded for tests.
   */
  public function testCoreThemesAutoloadedForTests(): void {
    $this->assertTrue(class_exists(PreprocessHooks::class), 'core/themes (Default Admin) is registered with the tests autoloader');
  }

}
