<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test for htaccess deprecations.
 *
 * @group File
 * @group legacy
 */
class HtaccessDeprecationTest extends KernelTestBase {

  /**
   * Tests messages for deprecated functions.
   *
   * @expectedDeprecation file_save_htaccess() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\HtaccessWriter::save() instead. See https://www.drupal.org/node/2940126
   * @expectedDeprecation file_ensure_htaccess() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\HtaccessWriter::ensure() instead. See https://www.drupal.org/node/2940126
   */
  public function testDeprecatedFunctions() {
    $public = Settings::get('file_public_path') . '/test/public';
    file_save_htaccess($public);
    file_ensure_htaccess();
  }

}
