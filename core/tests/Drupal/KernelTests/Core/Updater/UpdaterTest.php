<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Updater;

use Drupal\Core\Updater\Updater;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests InfoParser class and exception.
 *
 * Files for this test are stored in core/modules/system/tests/fixtures and end
 * with .info.txt instead of info.yml in order not to be considered as real
 * extensions.
 *
 * @group Extension
 * @group legacy
 */
class UpdaterTest extends KernelTestBase {

  /**
   * Tests project and child project showing correct title.
   *
   * @see https://drupal.org/node/2409515
   */
  public function testGetProjectTitleWithChild(): void {
    // Get the project title from its directory. If it can't find the title
    // it will choose the first project title in the directory.
    $directory = $this->root . '/core/modules/system/tests/modules/module_handler_test_multiple';
    $title = Updater::getProjectTitle($directory);
    $this->assertEquals('module handler test multiple', $title);
  }

}
