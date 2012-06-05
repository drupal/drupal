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
      'description' => 'Test that drupal_get_filename() works correctly when the file is not found in the database.',
      'group' => 'Bootstrap',
    );
  }

  /**
   * Test that drupal_get_filename() works correctly when the file is not found in the database.
   */
  function testDrupalGetFilename() {
    // Reset the static cache so we can test the "db is not active" code of
    // drupal_get_filename().
    drupal_static_reset('drupal_get_filename');

    // Retrieving the location of a module.
    $this->assertIdentical(drupal_get_filename('module', 'php'), 'core/modules/php/php.module', t('Retrieve module location.'));

    // Retrieving the location of a theme.
    $this->assertIdentical(drupal_get_filename('theme', 'stark'), 'core/themes/stark/stark.info', t('Retrieve theme location.'));

    // Retrieving the location of a theme engine.
    $this->assertIdentical(drupal_get_filename('theme_engine', 'phptemplate'), 'core/themes/engines/phptemplate/phptemplate.engine', t('Retrieve theme engine location.'));

    // Retrieving the location of a profile. Profiles are a special case with
    // a fixed location and naming.
    $this->assertIdentical(drupal_get_filename('profile', 'standard'), 'profiles/standard/standard.profile', t('Retrieve install profile location.'));

    // When a file is not found in the database cache, drupal_get_filename()
    // searches several locations on the filesystem, including the core/
    // directory. We use the '.script' extension below because this is a
    // non-existent filetype that will definitely not exist in the database.
    // Since there is already a core/scripts directory, drupal_get_filename()
    // will automatically check there for 'script' files, just as it does
    // for (e.g.) 'module' files in core/modules.
    $this->assertIdentical(drupal_get_filename('script', 'test'), 'core/scripts/test.script', t('Retrieve test script location.'));
  }
}
