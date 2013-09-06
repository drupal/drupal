<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\GetFilenameUnitTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests drupal_get_filename()'s availability.
 */
class GetFilenameUnitTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Get filename test',
      'description' => 'Test that drupal_get_filename() works correctly.',
      'group' => 'Bootstrap',
    );
  }

  /**
   * Tests that drupal_get_filename() works when the file is not in database.
   */
  function testDrupalGetFilename() {
    // drupal_get_profile() is using obtaining the profile from state if the
    // install_state global is not set.
    global $install_state;
    $install_state['parameters']['profile'] = 'testing';

    // Assert that the test is meaningful by making sure the keyvalue service
    // does not exist.
    $this->assertFalse(drupal_container()->has('keyvalue'), 'The container has no keyvalue service.');
    // Retrieving the location of a module.
    $this->assertIdentical(drupal_get_filename('module', 'php'), 'core/modules/php/php.module', 'Retrieve module location.');

    // Retrieving the location of a theme.
    $this->assertIdentical(drupal_get_filename('theme', 'stark'), 'core/themes/stark/stark.info.yml', 'Retrieve theme location.');

    // Retrieving the location of a theme engine.
    $this->assertIdentical(drupal_get_filename('theme_engine', 'phptemplate'), 'core/themes/engines/phptemplate/phptemplate.engine', 'Retrieve theme engine location.');

    // Retrieving the location of a profile. Profiles are a special case with
    // a fixed location and naming.
    $this->assertIdentical(drupal_get_filename('profile', 'standard'), 'core/profiles/standard/standard.profile', 'Retrieve installation profile location.');

    // When a file is not found in the database cache, drupal_get_filename()
    // searches several locations on the filesystem, including the core/
    // directory. We use the '.script' extension below because this is a
    // non-existent filetype that will definitely not exist in the database.
    // Since there is already a core/scripts directory, drupal_get_filename()
    // will automatically check there for 'script' files, just as it does
    // for (e.g.) 'module' files in core/modules.
    $this->assertIdentical(drupal_get_filename('script', 'test'), 'core/scripts/test/test.script');

    // Searching for an item that does not exist returns NULL.
    $this->assertNull(drupal_get_filename('module', uniqid("", TRUE)), 'Searching for an item that does not exist returns NULL.');
  }
}
