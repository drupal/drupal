<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\SystemListingTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests scanning system directories in drupal_system_listing().
 */
class SystemListingTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Drupal system listing',
      'description' => 'Tests scanning system directories in drupal_system_listing().',
      'group' => 'Common',
    );
  }

  /**
   * Tests that files in different directories take precedence as expected.
   */
  function testDirectoryPrecedence() {
    // Define the module files we will search for, and the directory precedence
    // we expect.
    $expected_directories = array(
      // When the copy of the module in the profile directory is incompatible
      // with Drupal core, the copy in the core modules directory takes
      // precedence.
      'drupal_system_listing_incompatible_test' => array(
        'core/modules/system/tests/modules',
        'core/profiles/testing/modules',
      ),
      // When both copies of the module are compatible with Drupal core, the
      // copy in the profile directory takes precedence.
      'drupal_system_listing_compatible_test' => array(
        'core/profiles/testing/modules',
        'core/modules/system/tests/modules',
      ),
    );

    // This test relies on two versions of the same module existing in
    // different places in the filesystem. Without that, the test has no
    // meaning, so assert their presence first.
    foreach ($expected_directories as $module => $directories) {
      foreach ($directories as $directory) {
        $filename = "$directory/$module/$module.module";
        $this->assertTrue(file_exists(DRUPAL_ROOT . '/' . $filename), format_string('@filename exists.', array('@filename' => $filename)));
      }
    }

    // Now scan the directories and check that the files take precedence as
    // expected.
    $files = drupal_system_listing('/\.module$/', 'modules');
    foreach ($expected_directories as $module => $directories) {
      $expected_directory = array_shift($directories);
      $expected_filename = "$expected_directory/$module/$module.module";
      $this->assertEqual($files[$module]->uri, $expected_filename, format_string('Module @module was found at @filename.', array('@module' => $module, '@filename' => $expected_filename)));
    }
  }
}
