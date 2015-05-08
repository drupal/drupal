<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\GetFilenameUnitTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests that drupal_get_filename() works correctly.
 *
 * @group Bootstrap
 */
class GetFilenameUnitTest extends KernelTestBase {

  /**
   * Tests that drupal_get_filename() works when the file is not in database.
   */
  function testDrupalGetFilename() {
    // drupal_get_profile() is using obtaining the profile from state if the
    // install_state global is not set.
    global $install_state;
    $install_state['parameters']['profile'] = 'testing';

    // Rebuild system.module.files state data.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    drupal_static_reset('system_rebuild_module_data');
    system_rebuild_module_data();

    // Retrieving the location of a module.
    $this->assertIdentical(drupal_get_filename('module', 'system'), 'core/modules/system/system.info.yml');

    // Retrieving the location of a theme.
    \Drupal::service('theme_handler')->install(array('stark'));
    $this->assertIdentical(drupal_get_filename('theme', 'stark'), 'core/themes/stark/stark.info.yml');

    // Retrieving the location of a theme engine.
    $this->assertIdentical(drupal_get_filename('theme_engine', 'twig'), 'core/themes/engines/twig/twig.info.yml');

    // Retrieving the location of a profile. Profiles are a special case with
    // a fixed location and naming.
    $this->assertIdentical(drupal_get_filename('profile', 'testing'), 'core/profiles/testing/testing.info.yml');


    // Generate a non-existing module name.
    $non_existing_module = uniqid("", TRUE);

    // Set a custom error handler so we can ignore the file not found error.
    set_error_handler(function($severity, $message, $file, $line) {
      // Skip error handling if this is a "file not found" error.
      if (strstr($message, 'is missing from the file system:')) {
        \Drupal::state()->set('get_filename_test_triggered_error', TRUE);
        return;
      }
      throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    $this->assertNull(drupal_get_filename('module', $non_existing_module), 'Searching for an item that does not exist returns NULL.');
    $this->assertTrue(\Drupal::state()->get('get_filename_test_triggered_error'), 'Searching for an item that does not exist triggers an error.');
    // Restore the original error handler.
    restore_error_handler();
  }
}
