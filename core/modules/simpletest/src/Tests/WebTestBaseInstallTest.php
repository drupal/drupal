<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the test-specifics customisations done in the installation.
 *
 * @group simpletest
 * @group WebTestBase
 */
class WebTestBaseInstallTest extends WebTestBase {

  /**
   * Tests the Drupal install done in \Drupal\simpletest\WebTestBase::setUp().
   */
  public function testInstall() {
    $htaccess_filename = $this->getTempFilesDirectory() . '/.htaccess';
    $this->assertTrue(file_exists($htaccess_filename), "$htaccess_filename exists");
  }

}
