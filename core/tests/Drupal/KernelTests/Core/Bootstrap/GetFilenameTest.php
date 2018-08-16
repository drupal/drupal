<?php

namespace Drupal\KernelTests\Core\Bootstrap;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that drupal_get_filename() works correctly.
 *
 * @group Bootstrap
 */
class GetFilenameTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Use the testing install profile.
    $container->setParameter('install_profile', 'testing');
  }

  /**
   * Tests that drupal_get_filename() works when the file is not in database.
   */
  public function testDrupalGetFilename() {
    // Rebuild system.module.files state data.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    drupal_static_reset('system_rebuild_module_data');
    system_rebuild_module_data();

    // Retrieving the location of a module.
    $this->assertIdentical(drupal_get_filename('module', 'system'), 'core/modules/system/system.info.yml');

    // Retrieving the location of a theme.
    \Drupal::service('theme_handler')->install(['stark']);
    $this->assertIdentical(drupal_get_filename('theme', 'stark'), 'core/themes/stark/stark.info.yml');

    // Retrieving the location of a theme engine.
    $this->assertIdentical(drupal_get_filename('theme_engine', 'twig'), 'core/themes/engines/twig/twig.info.yml');

    // Retrieving the location of a profile. Profiles are a special case with
    // a fixed location and naming.
    $this->assertIdentical(drupal_get_filename('profile', 'testing'), 'core/profiles/testing/testing.info.yml');

    // Set a custom error handler so we can ignore the file not found error.
    set_error_handler(function ($severity, $message, $file, $line) {
      // Skip error handling if this is a "file not found" error.
      if (strstr($message, 'is missing from the file system:')) {
        \Drupal::state()->set('get_filename_test_triggered_error', $message);
        return;
      }
      throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    $this->assertNull(drupal_get_filename('module', 'there_is_a_module_for_that'), 'Searching for an item that does not exist returns NULL.');
    $this->assertEquals('The following module is missing from the file system: there_is_a_module_for_that', \Drupal::state()->get('get_filename_test_triggered_error'));

    $this->assertNull(drupal_get_filename('theme', 'there_is_a_theme_for_you'), 'Searching for an item that does not exist returns NULL.');
    $this->assertEquals('The following theme is missing from the file system: there_is_a_theme_for_you', \Drupal::state()->get('get_filename_test_triggered_error'));

    $this->assertNull(drupal_get_filename('profile', 'there_is_an_install_profile_for_you'), 'Searching for an item that does not exist returns NULL.');
    $this->assertEquals('The following profile is missing from the file system: there_is_an_install_profile_for_you', \Drupal::state()->get('get_filename_test_triggered_error'));

    // Restore the original error handler.
    restore_error_handler();
  }

}
