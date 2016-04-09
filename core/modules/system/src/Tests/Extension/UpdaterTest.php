<?php

namespace Drupal\system\Tests\Extension;

use Drupal\simpletest\KernelTestBase;
use Drupal\Core\Updater\Updater;

/**
 * Tests InfoParser class and exception.
 *
 * Files for this test are stored in core/modules/system/tests/fixtures and end
 * with .info.txt instead of info.yml in order not not be considered as real
 * extensions.
 *
 * @group Extension
 */
class UpdaterTest extends KernelTestBase {

  /**
   * Tests project and child project showing correct title.
   *
   * @see https://drupal.org/node/2409515
   */
  public function testGetProjectTitleWithChild() {
    // Get the project title from it's directory. If it can't find the title
    // it will choose the first project title in the directory.
    $directory = \Drupal::root() . '/core/modules/system/tests/modules/module_handler_test_multiple';
    $title = Updater::getProjectTitle($directory);
    $this->assertEqual('module handler test multiple', $title);
  }

}
